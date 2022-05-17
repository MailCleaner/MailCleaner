#!/usr/bin/perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2021 John Mertz <git@john.me.tz>
#
#   This program is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; either version 2 of the License, or
#   (at your option) any later version.
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
#
#   Send alert email to system administrator (support address)

use strict;
if ($0 =~ m/(\S*)\/\S+.pl$/) {
  my $path = $1."/../lib";
  unshift (@INC, $path);
}
require ReadConfig;
require DB;
require Email;
require MailTemplate;
require 'lib_utils.pl';
use LWP::UserAgent;

my $conf = ReadConfig::getInstance();
if ($conf->getOption('ISMASTER') !~ /^[y|Y]$/) {
  print "NOTAMASTER";
  exit 0;
}

my $vardir = $conf->getOption('VARDIR');
if (-e "$vardir/spool/mailcleaner/disable-watchdog-emails") {
	print "Email reporting disabled with '$vardir/spool/mailcleaner/disable-watchdog-emails'\n";
	exit 0;
}

my $enterprise = $conf->getOption('REGISTERED');
my $baseurl;
## select language
my $sysconf = SystemPref::getInstance();
my $lang = $sysconf->getPref('default_language') || 'en';

## report templates (ie. SRCDIR/templates/reports/*) are not yet exposed
my $temp_id = 'default';

my $recipient;
my $custom_recipient = "$vardir/spool/mailcleaner/watchdog-recipient";
if (-e $custom_recipient && open(my $fh, '<', $custom_recipient)) {
	while (<$fh>) {
		$recipient .= $_;
	}
	chomp($recipient);
} else {
	$recipient = $sysconf->getPref('sysadmin');
}
unless (valid_rfc822_email($recipient)) {
	die "Invalid recipient address: $recipient\n";
}
print "Recipient: $recipient\n";
exit();
my $email = Email::create($recipient);

my $template = MailTemplate::create('reports', 'watchdog', $temp_id, \$email, $lang, 'html');

## get slaves
my %slaves;
my $conf_db = DB::connect('master', 'mc_config', 0);
my $sth = $conf_db->prepare("SELECT id, hostname FROM slave");
$sth->execute() or fatal_error("CANNOTEXECUTEQUERY", $conf_db->errstr);
while (my $ref = $sth->fetchrow_hashref()) {
	$slaves{$ref->{'id'}}->{'hostname'} = $ref->{'hostname'};
}
$sth->finish();

## uses ssl?
my $baseurl;
my $https = 'http';
my $port = '';
$sth = $conf_db->prepare("SELECT servername, use_ssl, http_port, https_port FROM httpd_config");
$sth->execute() or fatal_error("CANNOTEXECUTEQUERY", $conf_db->errstr);
while (my $ref = $sth->fetchrow_hashref()) {
	$baseurl = $ref->{'servername'};
	if ($ref->{'use_ssl'} eq 'true') {
		$https .= 's';
		if ($ref->{'https_port'} != 443) {
			$port = ":$ref->{'https_port'}";
		}
	} elsif ($ref->{'http_port'} != 80) {
		$port = ":$ref->{'https_port'}";
	}
}
$sth->finish();

## fetch the watchdog report from each slave
my $ua = LWP::UserAgent->new('verify_hostname' => 0);
foreach my $host (keys(%slaves)) {
	my $response = $ua->get("$https://".$slaves{$host}->{'hostname'}.$port."/admin/downloads/watchdogs.html");
	unless ($response->is_success) {
		die "Unable to fetch watchdog report from host $host\n";
	}
	$slaves{$host}->{'warnings'} = $response->decoded_content();
	chomp($slaves{$host}->{'warnings'});
	if ($slaves{$host}->{'warnings'} eq '<br/>') {
		delete($slaves{$host});
	}
}

unless (scalar(keys(%slaves))) {
	die("No watchdogs to report\n");
}

## assemble report body
my $report = '';
my @err_hosts = sort {$a cmp $b} (keys %slaves);
foreach my $host (@err_hosts) {
	$report .= "<h3>Host $host:</h3>";
	$report .= $slaves{$host}->{'warnings'};
}

## set subject
my %langs = (
	'en' => 'Watchdog Warnings'
);
my $subject = $langs{$lang} || $langs{'en'};
my $hosts = scalar(@err_hosts);

## fill template body
my $version;
if ($enterprise) {
	my %langs = (
		'en' => 'MailCleaner staff will receive reports for watchdog errors on your machines. We will periodically investigate and resolve these issues on your behalf, if possible. For further assistance you can <a href="https://support.mailcleaner.net/boards/3/topics/82-watchdogs">read our Knowledge Base article on the topic</a> or <a href="https://support.mailcleaner.net">open a support ticket</a>.'
	);
	$version = $langs{$lang} || $langs{'en'};
} else {
	my %langs = (
		'en' => 'For further assistance you can <a href="https://support.mailcleaner.net/boards/3/topics/82-watchdogs">read our Knowledge Base article on the topic</a> or seek out help on <a href="https://forum.mailcleaner.org">the Community Edition forum</a>.'
	);
	$version = $langs{$lang} || $langs{'en'};
}

my %replace = (
	'__SUBJECT__' => $subject,
	'__NUM_HOSTS__' => $hosts,
	'__WARNINGS__' => $report,
	'__BASEURL__' => "$https://$baseurl$port",
	'__VERSION_MESSAGE__' => $version
);
	
$template->setReplacements(\%replace);
my $result = $template->send($recipient, 10);
if ($result) {
	print "Sent Watchdog alert to $recipient\n";
	exit 1;
} else {
	print "Failed to send Watchdog alert to $recipient\n";
	exit 0;
}
