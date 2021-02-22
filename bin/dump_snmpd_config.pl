#!/usr/bin/perl -w
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
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
#   This script will dump the snmp configuration file from the configuration
#   setting found in the database.
#
#   Usage:
#           dump_snmp_config.pl


use strict;
use DBI();
use File::Path qw(mkpath);
if ($0 =~ m/(\S*)\/\S+.pl$/) {
  my $path = $1."/../lib";
  unshift (@INC, $path);
}
require GetDNS;

my $DEBUG = 1;

my %config = readConfig("/etc/mailcleaner.conf");
my $system_mibs_file = '/usr/share/snmp/mibs/MAILCLEANER-MIB.txt';
if ( ! -d '/usr/share/snmp/mibs') {
 mkpath('/usr/share/snmp/mibs');
}
my $mc_mib_file = $config{'SRCDIR'}.'/www/guis/admin/public/downloads/MAILCLEANER-MIB.txt';

my $lasterror = "";

my $dbh;
$dbh = DBI->connect("DBI:mysql:database=mc_config;host=localhost;mysql_socket=$config{VARDIR}/run/mysql_slave/mysqld.sock",
			"mailcleaner", "$config{MYMAILCLEANERPWD}", {RaiseError => 0, PrintError => 0})
		or fatal_error("CANNOTCONNECTDB", $dbh->errstr);

my %snmpd_conf;
%snmpd_conf = get_snmpd_config() or fatal_error("NOSNMPDCONFIGURATIONFOUND", "no snmpd configuration found");

my %master_hosts;
%master_hosts = get_master_config();

dump_snmpd_file() or fatal_error("CANNOTDUMPSNMPDFILE", $lasterror);

$dbh->disconnect();

if (-f $system_mibs_file) {
	unlink($system_mibs_file);
}
symlink($mc_mib_file,$system_mibs_file);
print "DUMPSUCCESSFUL";

#############################
sub dump_snmpd_file
{
	my $stage = shift;

	my $template_file = "$config{'SRCDIR'}/etc/snmp/snmpd.conf_template";
	my $target_file = "$config{'SRCDIR'}/etc/snmp/snmpd.conf";

	if ( !open(TEMPLATE, $template_file) ) {
		$lasterror = "Cannot open template file: $template_file";
		return 0;
	}
	if ( !open(TARGET, ">$target_file") ) {
                $lasterror = "Cannot open target file: $target_file";
		close $template_file;
                return 0;
        }

 	my @ips = expand_host_string($snmpd_conf{'__ALLOWEDIP__'}.' 127.0.0.1');
	my $ip;
	foreach $ip ( keys %master_hosts) {
		print TARGET "com2sec local     $ip     $snmpd_conf{'__COMMUNITY__'}\n";
		print TARGET "com2sec6 local     $ip     $snmpd_conf{'__COMMUNITY__'}\n";
	}
	foreach $ip (@ips) {
		print TARGET "com2sec local     $ip	$snmpd_conf{'__COMMUNITY__'}\n";
		print TARGET "com2sec6 local     $ip     $snmpd_conf{'__COMMUNITY__'}\n";
	}

	while(<TEMPLATE>) {
		my $line = $_;

		$line =~ s/__VARDIR__/$config{'VARDIR'}/g;
		$line =~ s/__SRCDIR__/$config{'SRCDIR'}/g;

		print TARGET $line;
	}

	my @disks = split(/\:/, $snmpd_conf{'__DISKS__'});
        my $disk;
        foreach $disk (@disks) {
                print TARGET "disk      $disk   100000\n";
        }

	close TEMPLATE;
	close TARGET;
	
	return 1;
}

#############################
sub get_snmpd_config{
	my %config;
	
	my $sth = $dbh->prepare("SELECT allowed_ip, community, disks FROM snmpd_config");
	$sth->execute() or fatal_error("CANNOTEXECUTEQUERY", $dbh->errstr);

	if ($sth->rows < 1) {
                return;
        }
        my $ref = $sth->fetchrow_hashref() or return;

	$config{'__ALLOWEDIP__'} = join(' ',expand_host_string($ref->{'allowed_ip'}));
	$config{'__COMMUNITY__'} = $ref->{'community'};
	$config{'__DISKS__'} = $ref->{'disks'};

	$sth->finish();
        return %config;
}

#############################
sub get_master_config {

	my %masters;

	my $sth = $dbh->prepare("SELECT hostname FROM master");
	$sth->execute() or fatal_error("CANNOTEXECUTEQUERY", $dbh->errstr);

        if ($sth->rows < 1) {
                return;
        }
	while (my $ref = $sth->fetchrow_hashref()) {
		$masters{$ref->{'hostname'}} = 1;
	}

	$sth->finish();
        return %masters;
}

#############################
sub fatal_error
{
	my $msg = shift;
	my $full = shift;

	print $msg;
	if ($DEBUG) {
		print "\n Full information: $full \n";
	}
	exit(0);
}

#############################
sub print_usage
{
	print "Bad usage: dump_exim_config.pl [stage-id]\n\twhere stage-id is an integer between 0 and 4 (0 or null for all).\n";
	exit(0);
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

sub expand_host_string
{
    my $string = shift;
    my $dns = GetDNS->new();
    return $dns->dumper($string);
}
