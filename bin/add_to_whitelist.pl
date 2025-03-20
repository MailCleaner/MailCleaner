#!/usr/bin/env perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2018 MailCleaner <support@mailcleaner.net>
#   Copyright (C) 2020 John Mertz <git@john.me.tz>
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
#   This script will add a whitelist to the database.
#   This script is intended to be used by the MailCleaner SOAP API (Soaper).
#
#   Usage:
#           add_to_whitelist.pl msg_dest msg_sender

use v5.36;
use strict;
use warnings;
use utf8;
use Carp qw( confess );

my ($SRCDIR);
BEGIN {
    if ($0 =~ m/(\S*)\/\S+.pl$/) {
        my $path = $1."/../lib";
        unshift (@INC, $path);
    }
    require ReadConfig;
    my $conf = ReadConfig::getInstance();
    $SRCDIR = $conf->getOption('SRCDIR') || '/usr/mailcleaner';
    unshift(@INC, $SRCDIR."/lib");
}

require DB;

my $dest = shift;
my $sender = shift;
if (not isValidEmail($dest)){
    err("DESTNOTVALID");
}
if (not isValidEmail($sender)){
    err("SENDERNOTVALID");
}

my $dbh = DB::connect('master', 'mc_config') || err("CANNOTCONNECTDB");

# Remove content after plus in address so that rule applies to base address
$dest =~ s/([^\+]+)\+([^\@]+)\@(.*)/$1\@$3/;

# WWLists don't have unique indexes, check for duplicate first
my $sth = $dbh->prepare("SELECT * FROM wwlists WHERE sender = ? AND recipient = ? AND type = 'white'") || err("CANNOTSELECTDB");
$sth->execute($sender, $dest);
if ($sth->fetchrow_arrayref()) {
    err("DUPLICATEENTRY");
}

$sth = $dbh->prepare("INSERT INTO wwlists (sender, recipient, type, expiracy, status, comments)
    values (?, ?, 'white', '0000-00-00', 1, '[Whitelist]')");
$sth->execute($sender, $dest);
unless ($sth->rows() > 0) {
    err("CANNOTINSERTDB");
}

print("OK");
exit 0;

##########################################
sub isValidEmail($email_str)
{
    return 1 if ($email_str =~ /^\S*\@\S+\.\S+$/);
    return 0;
}

sub err($err="UNKNOWNERROR")
{
    die("$err\n");
}
