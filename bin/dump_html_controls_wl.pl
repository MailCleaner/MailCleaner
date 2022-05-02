#!/usr/bin/perl -w
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2020 MailCleaner
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
#   This script will dump the ssh key files
#
#   Usage:
#           dump_html_controls_wl.pl


use strict;
use DBI();

my %config = readConfig("/etc/mailcleaner.conf");

my $file = '/var/mailcleaner/spool/tmp/mailscanner/whitelist_HTML';
unlink($file);

do_htmls_wl();

############################
sub do_htmls_wl {
        my $dbh;
        $dbh = DBI->connect("DBI:mysql:database=mc_config;host=localhost;mysql_socket=$config{VARDIR}/run/mysql_slave/mysqld.sock",
                        "mailcleaner", "$config{MYMAILCLEANERPWD}", {RaiseError => 0, PrintError => 0})
                        or return;

        my $sth = $dbh->prepare("SELECT sender FROM wwlists WHERE type='htmlcontrols'");
        $sth->execute() or return;

        my $count=0;

        open HTML_WL, '>', $file;
        while (my $ref = $sth->fetchrow_hashref() ) {
                print HTML_WL $ref->{'sender'}."\n";
                $count++;
        }
        $sth->finish();
        close HTML_WL;

        # Unlink file if it is empty
        unlink $file unless $count;

        return;
}


#############################
sub readConfig {
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

