#!/usr/bin/perl -w
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
#   This script will dump the ssh key files
#
#   Usage:
#           dump_ssh_keys.pl


use strict;
use DBI();

my $DEBUG = 1;

my %config = readConfig("/etc/mailcleaner.conf");
my $known_hosts_file = $config{'VARDIR'}."/.ssh/known_hosts";
my $authorized_file = $config{'VARDIR'}."/.ssh/authorized_keys";

unlink($known_hosts_file);
unlink($authorized_file);

do_known_hosts();
my $uid = getpwnam('mailcleaner');
my $gid = getgrnam('mailcleaner');
chown($uid, $gid, $known_hosts_file);

do_authorized_keys();
chown($uid, $gid, $authorized_file);



############################
sub do_known_hosts {

	my $dbh;
	$dbh = DBI->connect("DBI:mysql:database=mc_config;host=localhost;mysql_socket=$config{VARDIR}/run/mysql_master/mysqld.sock",
			"mailcleaner", "$config{MYMAILCLEANERPWD}", {RaiseError => 0, PrintError => 0})
        	        or return;

	my $sth = $dbh->prepare("SELECT hostname, ssh_pub_key FROM slave");
	$sth->execute() or return;
	
	while (my $ref = $sth->fetchrow_hashref() ) {
		open KNOWNHOST, ">> $known_hosts_file";
		print KNOWNHOST $ref->{'hostname'}." ".$ref->{'ssh_pub_key'}."\n";
		close KNOWNHOST;	
	}
	$sth->finish();
	return;
}

sub do_authorized_keys {
	my $dbh;
        $dbh = DBI->connect("DBI:mysql:database=mc_config;host=localhost;mysql_socket=$config{VARDIR}/run/mysql_slave/mysqld.sock",
                        "mailcleaner", "$config{MYMAILCLEANERPWD}", {RaiseError => 0, PrintError => 0})
                        or return;

	my $sth = $dbh->prepare("SELECT ssh_pub_key FROM master");
	$sth->execute() or return;

        while (my $ref = $sth->fetchrow_hashref() ) {
		open KNOWNHOST, ">> $authorized_file";
                print KNOWNHOST $ref->{'ssh_pub_key'}."\n";
                close KNOWNHOST;
	}
	$sth->finish();
        return;
}


#############################
sub readConfig
{
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
