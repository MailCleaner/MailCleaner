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
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
#
#   This script will output some informations on the status of the system
#

use strict;
use warnings;
use utf8;

# Process codes:
my %codes = (
    '0' => 'critical (not running and required)',
    '1' => 'running',
    '2' => 'stopped (not running but not required)',
    '3' => 'needs restart',
    '4' => 'currently stopping',
    '5' => 'currently starting',
    '6' => 'currently restarting (currently procesing stop/start script)'
);

if ($0 =~ m/(\S*)\/\S+.pl$/) {
    my $path = $1."/../lib";
    unshift (@INC, $path);
}

my %config = readConfig("/etc/mailcleaner.conf");

my $mode_given;
my $verbose = 0;
while (scalar(@ARGV)) {
    my $arg = shift;
    if ($arg eq '-h') {
        usage();
    } elsif ($arg eq '-v' && !$verbose) {
        $verbose = 1;
    } elsif (!defined($mode_given)) {
        $mode_given = $arg;
    } else {
        unshift(@ARGV, $arg);
        print("Excess argument".(scalar(@ARGV) > 1 ? 's' : '').": ".join(', ', @ARGV)."\n");
        usage();
    }
}
my $mode = "";
my $cmd;

my @order = (
    { 'id' => 'exim_stage1', 'proc' => 'exim/exim_stage1.conf', 'human' => 'Incoming' },
    { 'id' => 'exim_stage2', 'proc' => 'exim/exim_stage2.conf', 'human' => 'Filtering' },
    { 'id' => 'exim_stage4', 'proc' => 'exim/exim_stage4.conf', 'human' => 'Outgoing' },
    { 'id' => 'apache', 'proc' => 'apache/httpd.conf', 'human' => 'Web Server' },
    { 'id' => 'mailscanner', 'proc' => 'MailScanner', 'human' => 'Filtering Engine' },
    { 'id' => 'mysql_master', 'proc' => 'mysql/my_master.cnf', 'human' => 'Master Database' },
    { 'id' => 'mysql_slave', 'proc' => 'mysql/my_slave.cnf', 'human' => 'Slave Database' },
    { 'id' => 'snmpd', 'proc' => 'snmpd.conf', 'human' => 'SNMP Daemon' },
    { 'id' => 'greylistd', 'proc' => 'greylistd/greylistd.conf', 'human' => 'Greylist Daemon' },
    { 'id' => 'cron', 'proc' => '/usr/sbin/cron', 'human' => 'Scheduler' },
    { 'id' => 'preftdaemon', 'proc' => 'PrefTDaemon', 'human' => 'Preferences Daemon' },
    { 'id' => 'spamd', 'proc' => 'spamd.sock', 'human' => 'SpamAssassin Daemon' },
    { 'id' => 'clamd', 'proc' => 'clamd.conf', 'human' => 'ClamAV Daemon' },
    { 'id' => 'clamspamd', 'proc' => 'clamspamd.conf', 'human' => 'ClamSpam Daemon' },
    { 'id' => 'newsld', 'proc' => 'newsld.sock', 'human' => 'Newsletter Daemon' },
    { 'id' => 'spamhandler', 'proc' => 'SpamHandler', 'human' => 'SpamHandler Daemon' },
    { 'id' => 'firewall', 'proc' => 'SpamHandler', 'human' => 'Firewall' }
);

my $res;
if (! $mode_given) {
    usage();
}
if ($mode_given =~ /s/) {
    my $restartdir = $config{VARDIR}."/run/";
    my @output;
    my $i = 0;
    $cmd = "ps -efww";
    $res = `$cmd`;
    foreach my $service (@order) {
    last if ($service->{'id'} eq 'firewall');
    my $key = $service->{'id'};
        my $st = 0;
        if ($res =~ /$service->{'proc'}/ ) {
            $st = 1;
        }
        if ($st == 0 && -f $restartdir."/".$key.".stopped") {
            $st = 2;
        }
        if ( -f $restartdir.$key.".rn") {
            $st = 3;
        }
        if ( -f $restartdir.$key.".start.rs") {
            $st = 4;
        }
        if ( -f $restartdir.$key.".stop.rs") {
            $st = 5;
        }
        if ( -f $restartdir.$key.".restart.rs") {
            $st = 6;
        }
        $order[$i++]{'status'} = $st;
    }

    $res = `cat /tmp/fw.lock 2> /dev/null`;
    my $st = 2;
    if ($res =~ /^1$/) {
        $st = 1;
    }
    if (-f $restartdir."firewall.restart.rs") {
        $st = 6
    } elsif (-f $restartdir."firewall.stop.rs") {
        $st = 5;
    } elsif (-f $restartdir."firewall.start.rs") {
        $st = 4;
    } elsif (-f $restartdir."firewall.rn") {
        $st = 3;
    }
    $order[$i]->{'status'} = $st;
    if ($verbose) {
        foreach my $service (@order) {
            printf("%-20s %s\n", $service->{'human'}.':', $codes{$service->{'status'}});
        }
    } else {
        print(
            '|'.
            join('|', map { $_->{'status'} } @order)
            ."\n"
        );
    }
} elsif  ($mode_given =~ /p/) {
    foreach ( 0 .. 2 ) {
    my $key = $order[$_]->{'id'};
        if ($key eq 'exim_stage2') {
            my $subcmd = "grep -e '^MTA\\s*=\\s*eximms' ".$config{SRCDIR}."/etc/mailscanner/MailScanner.conf";
            my $type = `$subcmd`;
            if ($type eq '') {
                $cmd = "/opt/exim4/bin/exim -C $config{SRCDIR}/etc/exim/$key.conf -bpc";
            } else {
                $cmd = "ls $config{VARDIR}/spool/exim_stage2/input/*.env 2>&1 | grep -v 'No such' | wc -l";
            }
        } else {
            $cmd = "/opt/exim4/bin/exim -C $config{SRCDIR}/etc/exim/$key.conf -bpc";
        }
        $res = `$cmd`;
        chomp($res);
        $order[$_]->{'queue'} = "$res";
    }
    if ($verbose) {
        foreach ( 0 .. 2 ) {
            printf("%-16s %d\n", $order[$_]->{'human'}.":", $order[$_]->{'queue'});
        }
    } else {
        print('|'.$order[$_]->{'queue'}) foreach ( 0 .. 2 );
        print "\n";
    }
} elsif  ($mode_given =~ /l/) {
    $cmd = "cat /proc/loadavg | cut -d' ' -f-3";
    $res = `$cmd`;
    chomp($res);
    my @loads = split(/ /, $res);
    if ($verbose) {
        printf("Last %02s minutes: %.2f\n", $_*5, $loads[$_-1]) foreach (1 .. 3);
    } else {
        print "|$loads[0]|$loads[1]|$loads[2]\n";
    }
} elsif ($mode_given =~ /d/) {
    $cmd = "df";
    $res = `$cmd`;
    my @lines = split(/\n/, $res);
    foreach my $line (@lines) {
        if ($line =~ /\S+\s+\d+\s+\d+\s+\d+\s+(\d+\%)\s+(\S+)/) {
            if ($verbose) {
                printf("%-28s %s\n", $2.':', $1);
            } else {
                print "|$2|$1";
            }
        }
    }
    print "\n" unless ($verbose);
} elsif ($mode_given =~ /m/) {
    $cmd = "cat /proc/meminfo";
    $res = `$cmd`;
    my @fields = ('MemTotal', 'MemFree', 'SwapTotal', 'SwapFree');
    foreach my $field (@fields) {
        if ($res =~ /$field:\s+(\d+)/) {
            if ($verbose) {
                printf("%-10s %dMB\n", $field.':', $1 >> 10);
            } else {
                print "|$1";
            }
        }
    }
    print "\n" unless ($verbose);
} elsif ($mode_given =~ /t/) {
    $cmd = "/opt/exim4/bin/exim -C $config{SRCDIR}/etc/exim/exim_stage2.conf -bp | head -1 | cut -d' ' -f2";
    $res = `$cmd`;
    chomp($res);
    if ($verbose) {
        print("Longest time in filtering queue: ".( $res ? $res : 'immediate')."\n");
    } else {
        print($res."\n");
    }
} elsif ($mode_given =~ /u/) {
    $cmd = "echo \"use mc_config; select id, date from update_patch order by id desc limit 1;\" | /opt/mysql5/bin/mysql --skip-column-names -S $config{VARDIR}/run/mysql_slave/mysqld.sock -umailcleaner -p$config{MYMAILCLEANERPWD}";
    $res = `$cmd`;
    my $patch = "";
    if ($res =~ /^(\d+)\s+(\S+)$/) {
        $patch = $1;
    }
    if ($verbose) {
        print "Patch level: $patch\n";
    } else {
        print $patch."\n";
    }
} else {
    usage();
}

sub usage
{
    print(
"Usage:
    get_status.pl [-s, -p, -l, -d, -m, -t, -u] <-v>

    -s: output status of vital Mailcleaner processes:
        Incoming MTA
        Filtering MTA
        Outgoing MTA
        Web GUI
        Filtering Engine (MailScanner)
        Master Database
        Slave Database
        Snmp Daemon
        Greylist Daemon
        Cron Daemon
        Preference Daemon
        Firewall
    -p: output number of messages in spools
        Incoming MTA
        Filtering MTA
        Outgoing MTA
    -l: output system load
    -d: output disks usage
    -m: output memory counters
    -t: output the maximum waiting time for a message in each spool
    -u: output the last system patch
    -v: verbose print for humans
    -h: this menu
");
    exit(1);
}

sub getNumberOfGreylistDomains
{
    my $cmd = "wc -l ".$config{VARDIR}."/spool/mailcleaner/domains_to_greylist.list  | cut -d' ' -f1";
    my $res = `$cmd`;
    if ($res =~ m/(\d+)\s+/) {
        return $1;
    }
    return 0;
}

#############################
sub readConfig
{
    my $configfile = shift;
    my %config;
    my ($var, $value);

    open (my $CONFIG, '<', $configfile) or die "Cannot open $configfile: $!\n";
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
