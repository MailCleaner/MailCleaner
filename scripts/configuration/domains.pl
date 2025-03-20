#!/usr/bin/env perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
#   Copyright (C) 2023 John Mertz <git@john.me.tz>
#
#   This program is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; either version 2 of the License, or
#   (at your option) any later version.
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

use v5.36;
use strict;
use warnings;
use utf8;

use DBI();
use Term::ReadKey;

my %config = readConfig("/etc/mailcleaner.conf");

my $master_dbh = DBI->connect(
    "DBI:MariaDB:database=mc_config;mariadb_socket=$config{'VARDIR'}/run/mysql_master/mysqld.sock",
    "mailcleaner","$config{'MYMAILCLEANERPWD'}", {RaiseError => 0, PrintError => 0}
);

if (!$master_dbh) {
    printf ("ERROR: no master database found on this system. This script will only run on a Mailcleaner master host.\n");
    exit 1;
}

my $quit=0;
while (! $quit) {
    system("clear");
    printf "\n################################\n";
    printf "## Mailcleaner domain manager ##\n";
    printf "################################\n\n";
    printf "1) view domains\n";
    printf "2) delete domain\n";
    printf "3) add domain\n";
    printf "q) quit\n";
    printf "\nEnter your choice: ";

    ReadMode 'cbreak';
    my $key = ReadKey(0);
    ReadMode 'normal';

    if ($key =~ /q/) {
        $quit=1;
    } elsif ($key =~ /1/) {
        view_domains();
    } elsif ($key =~ /2/) {
        delete_domain();
    } elsif ($key =~ /3/) {
        add_domain();
    } elsif ($key =~ /4/) {
        synchronize_slaves();
    }
}
printf "\n\n";

if (defined $master_dbh) {
    $master_dbh->disconnect();
}

exit 0;

sub view_domains
{
    system("clear");
    my $sth =  $master_dbh->prepare(
        "SELECT id, name, active, destination, prefs FROM domain ORDER BY name"
    ) or die ("error in SELECT");
    $sth->execute() or die ("error in SELECT");
    my $el=$sth->rows;
    printf "Domain list: ($el element(s))\n";
    while (my $ref=$sth->fetchrow_hashref()) {
        printf $ref->{'id'}."-\t".$ref->{'name'}."\t\t".$ref->{'destination'}."\n";
    }
    $sth->finish();
    printf "\n******\ntype any key to return to main menu";
    ReadMode 'cbreak';
    my $key = ReadKey(0);
    ReadMode 'normal';
}

sub delete_domain
{
    system("clear");
    printf "Please enter domain id to delete: ";
    my $d_id = ReadLine(0);
    $d_id =~ s/^\s+//;
    $d_id =~ s/\s+$//;

    my $sth =  $master_dbh->prepare("DELETE FROM domain WHERE id='$d_id'");
    if (! $sth->execute()) {
               printf "no domain deleted..\n";
    } else {
        printf "domain $d_id deleted.\n";
        $sth->finish();
    }
    printf "\n******\ntype any key to return to main menu";
    ReadMode 'cbreak';
    my $key = ReadKey(0);
    ReadMode 'normal';    
}

sub add_domain
{
    system("clear");
    printf "Enter domain name: ";
    my $name = ReadLine(0);
    $name =~ s/^\s+//;
    $name =~ s/\s+$//;
    printf "Enter destination server: ";
    my $destination = ReadLine(0);
    $destination =~ s/^\s+//;
    $destination =~ s/\s+$//;

    if ( $name =~ /^[A-Z,a-z,0-9,\.,\_,\-,\*]{1,200}$/) {

        my $sth =  $master_dbh->prepare(
            "INSERT INTO domain_pref SET auth_server='$destination', auth_modif='att_add'"
        );
        if (!$sth->execute()) {
            printf "Domain prefs NOT added !\n";
            return;
        }
        $sth =  $master_dbh->prepare("SELECT LAST_INSERT_ID() id");
        if (!$sth->execute()) {
            printf "Domain prefs could NOT be found !\n";
            return;
        }
        my $ref=$sth->fetchrow_hashref();
        if (!$ref) {
            printf "Domain prefs array could NOT be found !\n";
            return;
        }
                    
        $sth =  $master_dbh->prepare(
            "INSERT INTO domain (name, destination, prefs) VALUES('$name', '$destination', '".$ref->{'id'}."')"
        );
        if (!$sth->execute()) {
            printf "Domain NOT added !\n";
        } else {    
            printf "Domain $name added.\n";
            $sth->finish();
        }
    } else {
        printf "please enter a domain name !\n";
    }
    printf "\n******\ntype any key to return to main menu";
    ReadMode 'cbreak';
    my $key = ReadKey(0);
    ReadMode 'normal';
}

sub readConfig
{
    my $configfile = shift;
    my %config;
    my ($var, $value);

    open(my $CONFIG, '<', $configfile) or die "Cannot open $configfile: $!\n";
    while (<$CONFIG>) {
            chomp;              # no newline
            s/#.*$//;           # no comments
            s/^\*.*$//;         # no comments
            s/;.*$//;           # no comments
            s/^\s+//;           # no leading white
            s/\s+$//;           # no trailing white
            next unless length; # anything left?
            my ($var, $value) = split(/\s*=\s*/, $_, 2);
            $config{$var} = $value;
    }
    close $CONFIG;
    return %config;
}
