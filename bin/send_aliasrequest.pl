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
#           send_aliasrequest.pl username alias uniqid [language]
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
my $username = shift;

my $alias = shift;
if ($alias !~ /^\S+\@(\S+)$/) {
   print "BADALIAS";
   exit 0;
}
my $domain = $1;
my $email = Email::create($alias);
if (!$email) {
  print "INVALIDALIAS";
  exit 0;
}
my $domain = Domain::create($domain);
if (!$domain) {
   print "INVALIDDOMAIN";
   exit 0;
}

my $uniqid = shift;
if (!$uniqid || $uniqid !~ m/^[0-9a-zA-Z]{32}$/) {
   print "INVALIDUNIQID";
   exit 0;
}
my $language = shift;

if (!$language || !defined($language)) {
  $language = $email->getPref('language');
}

my $sys = SystemPref::getInstance();
my $http = "http://";
if ( $sys->getPref('use_ssl') =~ /true/i ) {
  $http = "https://";
}
my $baseurl = $http.$sys->getPref('servername');
  
my %replace = (
  '__ALIAS__' => $alias,
  '__USER__' => $username,
  '__VALIDATEURL__' => $baseurl.'/aa.php?id=3D'.$uniqid.'&add=3D'.$alias.'&lang=3D'.$language,
  '__REFUSEURL__' => $baseurl.'/aa.php?id=3D'.$uniqid.'&add=3D'.$alias.'&lang=3D'.$language.'&m=3Dd',
  '__VALIDATEQUERYNE__' => '/aa.php?id='.$uniqid.'&add='.$alias.'&lang='.$language,
  '__REFUSEQUERYNE__' => '/aa.php?id='.$uniqid.'&add='.$alias.'&lang='.$language.'&m=d',
  '__VALIDATEQUERY__' => '/aa.php?id=3D'.$uniqid.'&add=3D'.$alias.'&lang=3D'.$language,
  '__REFUSEQUERY__' => '/aa.php?id=3D'.$uniqid.'&add=3D'.$alias.'&lang=3D'.$language.'&m=3Dd',
);


my $template = MailTemplate::create('aliasquery', 'aliasquery', $domain->getPref('summary_template'), \$email, $language, 'html');

$template->setReplacements(\%replace);
if ($template->send()) {
  print "REQUESTSENT $alias\n";
  exit 0;
}
print "_ERRORSENDING";
exit 1;

