#!/usr/bin/env perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
#   Copyright (C) 2025 John Mertz <git@john.me.tz>
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

use v5.36;
use strict;
use warnings;
use utf8;
use Carp qw( confess );

our ($SRCDIR, $VARDIR);
BEGIN {
    if ($0 =~ m/(\S*)\/\S+.pl$/) {
        my $path = $1."/../lib";
        unshift (@INC, $path);
    }
    require ReadConfig;
    my $conf = ReadConfig::getInstance();
    $SRCDIR = $conf->getOption('SRCDIR') || '/usr/mailcleaner';
    $VARDIR = $conf->getOption('VARDIR') || '/var/mailcleaner';
}

use lib_utils qw(open_as rmrf detect_ipv6);
use File::Path qw(mkpath);
require DB;
require GetDNS;

our $DEBUG = 1;
our $uid = getpwnam('Debian-snmp');
our $gid = getpwnam('Debian-snmp');

# Add to mailcleaner group if not already a member
`usermod -a -G mailcleaner Debian-snmp` unless (grep(/\bmailcleaner\b/, `groups Debian-snmp`));

my $system_mibs_file = '/usr/share/snmp/mibs/MAILCLEANER-MIB.txt';
if ( ! -d '/usr/share/snmp/mibs') {
    mkpath('/usr/share/snmp/mibs');
}
my $mc_mib_file = "${SRCDIR}/www/guis/admin/public/downloads/MAILCLEANER-MIB.txt";

my $lasterror = "";

our $dbh = DB::connect('slave', 'mc_config');

my %snmpd_conf;
confess "Error fetching snmp config: $!" unless %snmpd_conf = get_snmpd_config();

my %master_hosts;
%master_hosts = get_master_config();

chown($uid, $gid, "/etc/snmp/");
confess "Error dumping snmp config: $!" unless dump_snmpd_files();

if ( !-d "/var/mailcleaner/log/snmpd/") {
    mkdir("/var/mailcleaner/log/snmpd/") || confess("Failed to create '/var/mailcleaner/log/snmpd/'\n");
    chown($uid, $gid, "/var/mailcleaner/log/snmpd/");
}
if (-f $system_mibs_file) {
    unlink($system_mibs_file);
}
symlink($mc_mib_file,$system_mibs_file);
chown($uid, $gid, $system_mibs_file);
#chown($uid, $gid, '/usr/share/snmp/mibs');

# Remove default AppArmor rules and reload with ours
if (-e "/etc/apparmor.d/usr.sbin.snmpd") {
    `apparmor_parser -R /etc/apparmor.d/usr.sbin.snmpd`;
    unlink("/etc/apparmor.d/usr.sbin.snmpd");
}
`apparmor_parser -r ${SRCDIR}/etc/apparmor.d/usr.sbin.snmpd` if ( -d '/sys/kernel/security/apparmor' );

sub dump_snmpd_files()
{
    my $ipv6 = detect_ipv6();

    my ($TEMPLATE, $TARGET);
    my $template_file = "${SRCDIR}/etc/snmp/mailcleaner.conf_template";
    my $target_file = "/etc/snmp/snmpd.conf";
    unlink($target_file);
    confess "Cannot open $template_file: $!" unless ($TEMPLATE = ${open_as($template_file, '<')} );
    confess "Cannot open $target_file: $!" unless ($TARGET = ${open_as($target_file, '>', 0664, "$uid:$gid")});

    my @ips = expand_host_string($snmpd_conf{'__ALLOWEDIP__'}.' 127.0.0.1',('dumper'=>'snmp/allowedip'));
    foreach my $ip ( keys(%master_hosts) ) {
        print $TARGET "com2sec local     $ip     $snmpd_conf{'__COMMUNITY__'}\n";
        print $TARGET "com2sec6 local     $ip     $snmpd_conf{'__COMMUNITY__'}\n";
    }
    foreach my $ip (@ips) {
        print $TARGET "com2sec local     $ip    $snmpd_conf{'__COMMUNITY__'}\n";
        if ($ipv6) {
            print $TARGET "com2sec6 local     $ip     $snmpd_conf{'__COMMUNITY__'}\n";
        }
    }
    while(<$TEMPLATE>) {
        my $line = $_;

        $line =~ s/__VARDIR__/${VARDIR}/g;
        $line =~ s/__SRCDIR__/${SRCDIR}/g;

        print $TARGET $line;
    }
    my @disks = split(/\:/, $snmpd_conf{'__DISKS__'});
    foreach my $disk (@disks) {
        print $TARGET "disk      $disk   100000\n";
    }
    close $TEMPLATE;
    close $TARGET;
    chown($uid, $gid, $target_file);

    return 1;
}

#############################
sub get_snmpd_config
{
    my %config;

    my $sth = $dbh->prepare("SELECT allowed_ip, community, disks FROM snmpd_config");
    confess "CANNOTEXECUTEQUERY $dbh->errstr" unless $sth->execute();

    return unless ($sth->rows);
    my $ref;
    confess "CANNOTFETCHROWS $dbh->errstr" unless $ref = $sth->fetchrow_hashref();

    $config{'__ALLOWEDIP__'} = join(' ',expand_host_string($ref->{'allowed_ip'},('dumper'=>'snmp/allowedip')));
    $config{'__COMMUNITY__'} = $ref->{'community'};
    $config{'__DISKS__'} = $ref->{'disks'};

    $sth->finish();
    
    $sth = $dbh->prepare("SELECT sysadmin FROM system_conf");
    confess "CANNOTEXECUTEQUERY $dbh->errstr" unless $sth->execute();

    return unless ($sth->rows);
    confess "CANNOTFETCHROWS $dbh->errstr" unless $ref = $sth->fetchrow_hashref();
    $config{'__SYSADMIN__'} = $ref->{'sysadmin'};

    $sth->finish();
    
    return %config;
}

#############################
sub get_master_config
{
    my %masters;

    my $sth = $dbh->prepare("SELECT hostname FROM master");
    confess "CANNOTEXECUTEQUERY $dbh->errstr" unless $sth->execute();

    return unless ($sth->rows);
    my $ref;
    while ($ref = $sth->fetchrow_hashref()) {
        $masters{$ref->{'hostname'}} = 1;
    }

    $sth->finish();
    return %masters;
}

sub expand_host_string($string, %args)
{
    my $dns = GetDNS->new();
    return $dns->dumper($string,%args);
}
