#!/usr/bin/perl -w
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2018 MailCleaner <support@mailcleaner.net>
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
#   This script will add a newsletter whitelist to the database.
#   This script is intended to be used by the MailCleaner SOAP API (Soaper).
#
#   Usage:
#           add_newsletter_to_whitelist.pl msg_id msg_dest msg_sender

use strict;
use DBI();

my %config = readConfig("/etc/mailcleaner.conf");

my $dest = shift;
my $sender = shift;
if (not isValidEmail($dest)){
    die("DESTNOTVALID");
}
if (not isValidEmail($sender)){
    die("SENDERNOTVALID");
}

my $dbh;
my $sth;
$dbh = DBI->connect("DBI:mysql:database=mc_config;host=localhost;mysql_socket=$config{VARDIR}/run/mysql_master/mysqld.sock",
                    "mailcleaner", "$config{MYMAILCLEANERPWD}", {RaiseError => 0, PrintError => 0})
            or fatal_error("CANNOTCONNECTDB\n", $dbh->errstr);

$sth = $dbh->prepare("INSERT INTO wwlists (sender, recipient, type, expiracy, status, comments)
                     values (?, ?, 'wnews', '0000-00-00', 1, '[Newsletter]')");
$sth->execute($sender, $dest); # or die("CANNOTINSERTDB\n", $dbh->errstr);

# Manage the possible duplicate entries, in case someone tries to insert the same multiple times
if ($dbh->errstr() =~ /^Duplicate entry/) {
    print("DUPLICATEENTRY\n");
} elsif ($dbh->err()) {
    die("CANNOTINSERTDB\n", $dbh->errstr);
} else {
    print("OK\n");
}
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

        open CONFIG, $configfile or die "Cannot open $configfile: $!\n";
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
