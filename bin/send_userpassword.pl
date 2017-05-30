#!/usr/bin/perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
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
#   This script will send the spam summaries to the users
#
#   Usage:
#           send_userpassword.pl address username password reset [language]
#

use strict;
if ($0 =~ m/(\S*)\/\S+.pl$/) {
  my $path = $1."/../lib";
  unshift (@INC, $path);
}
require ReadConfig;
require DB;
require Email;
require MailTemplate;
require SystemPref;
use Digest::MD5 qw(md5_hex);

my $max_pending_age = 0; # in days

my $conf = ReadConfig::getInstance();
if ($conf->getOption('ISMASTER') !~ /^[y|Y]$/) {
  print "NOTAMASTER";
  exit 0;
}

## get params
my $address = shift;
my $username = shift;
my $password = shift;

if ($address !~ /^\S+\@(\S+)$/) {
   print "BADADDRESS";
   exit 0;
}
my $domain = $1;
my $email = Email::create($address);
if (!$email) {
  print "INVALIDADDRESS";
  exit 0;
}
my $domain = Domain::create($domain);
if (!$domain) {
   print "INVALIDDOMAIN";
   exit 0;
}

if (!$username || $username =~ m/^[^0-9a-zA-Z!?-_.,$@]{500}$/) {
    print "INVALIDUSERNAME";
    exit 0;
}
if (!$password || $password =~ m/^[^0-9a-zA-Z!?-_.,\$]{200}$/) {
   print "INVALIDPASSWORD";
   exit 0;
}
my $reset = shift;
my $mailtemp = 'sendpassword';
if ($reset && $reset eq '1') {
  $mailtemp = 'resetpassword';
}

my $language = shift;

if (!$language || !defined($language)) {
  $language = $email->getPref('language');
  print STDERR "language: ".$language;
}

my $sys = SystemPref::getInstance();
my $http = "http://";
if ( $sys->getPref('use_ssl') =~ /true/i ) {
  $http = "https://";
}
my $baseurl = $http.$sys->getPref('servername');
  
my %replace = (
  '__ADDRESS__' => $address,
  '__USER__' => $username,
  '__PASSWORD__' => $password,
);


my $template = MailTemplate::create('userpassword', $mailtemp, $domain->getPref('summary_template'), \$email, $language, 'html');

$template->setReplacements(\%replace);
if ($template->send()) {
  print "REQUESTSENT $address\n";
  exit 0;
}
print "_ERRORSENDING";
exit 1;

