#!/usr/bin/perl -w
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
#   This script will add a blacklist to the database.
#   This script is intended to be used by the MailCleaner SOAP API (Soaper).
#
#   Usage:
#           add_to_blacklist.pl msg_dest msg_sender

use strict;
if ($0 =~ m/(\S*)\/\S+.pl$/) {
  my $path = $1."/../lib";
  unshift (@INC, $path);
}
require DB;

my %config = readConfig("/etc/mailcleaner.conf");

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
my $sth = $dbh->prepare("SELECT * FROM wwlists WHERE sender = ? AND recipient = ? AND type = 'black'") || err("CANNOTSELECTDB");
$sth->execute($sender, $dest);
if ($sth->fetchrow_arrayref()) {
    err("DUPLICATEENTRY");
}

$sth = $dbh->prepare("INSERT INTO wwlists (sender, recipient, type, expiracy, status, comments)
    values (?, ?, 'black', '0000-00-00', 1, '[Blacklist]')");
$sth->execute($sender, $dest);
unless ($sth->rows() > 0) {
    err("CANNOTINSERTDB");
}

print("OK");
exit 0;

##########################################
sub isValidEmail
{
    my $email_str = shift;
    return 1 if $email_str =~ /^\S+\@\S+\.\S+$/;
    return 0;
}

##########################################
sub readConfig
{       # Reads configuration file given as argument.
        my $configfile = shift;
        my %config;
        my ($var, $value);

        open CONFIG, $configfile || err("CONFIGREADFAIL");
        while (<CONFIG>) {
                chomp;                  # no newline
                s/#.*$//;                # no comments
                s/^\*.*$//;             # no comments
                s/;.*$//;                # no comments
                s/^\s+//;               # no leading white
                s/\s+$//;               # no trailing white
                next unless length;     # anything left?
                my ($var, $value) = split(/\s*=\s*/, $_, 2);
                $config{$var} = $value;
        }
        close CONFIG;
        return %config;
}

sub err
{
	my $err = shift || "UNKNOWNERROR";
	print $err . "\n";
        exit(1);
}
