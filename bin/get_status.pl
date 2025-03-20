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

use v5.36;
use strict;
use warnings;
use utf8;
use Carp qw( confess );
use Proc::ProcessTable;

my ($SRCDIR, $MYMAILCLEANERPWD);
our ($VARDIR);
BEGIN {
    if ($0 =~ m/(\S*)\/\S+.pl$/) {
        my $path = $1."/../lib";
        unshift (@INC, $path);
    }
    require ReadConfig;
    my $conf = ReadConfig::getInstance();
    $SRCDIR = $conf->getOption('SRCDIR') || '/usr/mailcleaner';
    $VARDIR = $conf->getOption('VARDIR') || '/var/mailcleaner';
    confess "Could not get DB password" unless ($MYMAILCLEANERPWD = $conf->getOption('MYMAILCLEANERPWD'));
    unshift(@INC, $SRCDIR."/lib");
}

# Process codes:
my %codes = (
    '0' => 'critical (not running and required)',
    '1' => 'running',
    '2' => 'stopped (not running but not required)',
    '3' => 'needs restart',
    '4' => 'currently stopping',
    '5' => 'currently starting',
    '6' => 'currently restarting (currently procesing stop/start script)',
    '255' => 'invalid service',
);

if ($0 =~ m/(\S*)\/\S+.pl$/) {
    my $path = $1."/../lib";
    unshift (@INC, $path);
}

my $ps = Proc::ProcessTable->new();
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
    { 'id' => 'exim_stage1', 'service' => 'exim4@1', 'human' => 'Incoming' },
    { 'id' => 'exim_stage2', 'service' => 'exim4@2', 'human' => 'Filtering' },
    { 'id' => 'exim_stage4', 'service' => 'exim4@4', 'human' => 'Outgoing' },
    { 'id' => 'apache', 'service' => 'apache2', 'human' => 'Web Server' },
    { 'id' => 'mailscanner', 'proc' => 'MailScanner', 'human' => 'Filtering Engine' },
    { 'id' => 'mysql_master', 'service' => 'mariadb@master', 'human' => 'Master Database' },
    { 'id' => 'mysql_slave', 'service' => 'mariadb@slave', 'human' => 'Slave Database' },
    { 'id' => 'snmpd', 'service' => 'snmpd', 'human' => 'SNMP Daemon' },
    { 'id' => 'greylistd', 'service' => 'greylistd', 'human' => 'Greylist Daemon' },
    { 'id' => 'cron', 'service' => 'cron', 'human' => 'Scheduler' },
    { 'id' => 'preftdaemon', 'service' => 'preftdaemon', 'human' => 'Preferences Daemon' },
    { 'id' => 'spamd', 'service' => 'spamd@spamd', 'human' => 'SpamAssassin Daemon' },
    { 'id' => 'clamd', 'proc' => 'clamd', 'human' => 'ClamAV Daemon' },
    { 'id' => 'clamspamd', 'proc' => 'clamspamd', 'human' => 'ClamSpam Daemon' },
    { 'id' => 'newsld', 'service' => 'spamd@newsld', 'human' => 'Newsletter Daemon' },
    { 'id' => 'spamhandler', 'service' => 'spamhandler', 'human' => 'SpamHandler Daemon' },
    { 'id' => 'firewall', 'human' => 'Firewall' }
);

my $res;
if (! $mode_given) {
    usage();
}
if ($mode_given =~ /s/) {
    my $restartdir = $VARDIR."/run/";
    my @output;
    my $i = 0;
    foreach my $service (@order) {
        my $key = $service->{'id'};
        my $st = 0;
        if (defined($service->{'service'})) {
            $st = system("systemctl is-active --quiet $service->{'service'}");
            # Old error codes are inverted
            if ($st == 0) {
                $st = 1;
            } else {
                $st = 0;
            }
	} elsif (defined($service->{'proc'})) {
	    if (findProcess($service->{'proc'})) {
	        $order[$i++]{'status'} = 1 if findProcess($service->{'proc'});
	        next;
	    } else {
		$order[$i]{'status'} = 0;
	    }
        } else {
            if ($service->{'id'} eq 'firewall') {
		my $res = `cat /tmp/fw.lock 2> /dev/null`;
		chomp($res);
		if ($res eq '1') {
		    $st = 1;
                } else {
		    $st = 2;
		}
	    } else {
                $order[$i++]{'status'} = 255;
                next;
            }
        }
        if ($st == 0 && -f $restartdir.$key.".stopped") {
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
            my $subcmd = "grep -e '^MTA\\s*=\\s*eximms' ".${SRCDIR}."/etc/mailscanner/MailScanner.conf";
            my $type = `$subcmd`;
            if ($type eq '') {
                $cmd = "runuser -u mailcleaner -- /opt/exim4/bin/exim -C ${VARDIR}/spool/tmp/exim/${key}.conf -bpc 2>/dev/null";
            } else {
                $cmd = "ls ${VARDIR}/spool/exim_stage2/input/*.env 2>&1 | grep -v 'No such' | wc -l";
            }
        } else {
            $cmd = "runuser -u mailcleaner -- /opt/exim4/bin/exim -C ${VARDIR}/spool/tmp/exim/${key}.conf -bpc 2>/dev/null";
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
    $cmd = "/opt/exim4/bin/exim -C ${VARDIR}/spool/tmp/exim/exim/exim_stage2.conf -bp | head -1 | cut -d' ' -f2";
    $res = `$cmd`;
    chomp($res);
    if ($verbose) {
        print("Longest time in filtering queue: ".( $res ? $res : 'immediate')."\n");
    } else {
        print($res."\n");
    }
} elsif ($mode_given =~ /u/) {
    $cmd = "echo \"use mc_config; select id, date from update_patch order by id desc limit 1;\" | /opt/mysql5/bin/mysql --skip-column-names -S ${VARDIR}/run/mysql_slave/mysqld.sock -umailcleaner -p${MYMAILCLEANERPWD}";
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
    my $cmd = "wc -l ".$VARDIR."/spool/mailcleaner/domains_to_greylist.list  | cut -d' ' -f1";
    my $res = `$cmd`;
    if ($res =~ m/(\d+)\s+/) {
        return $1;
    }
    return 0;
}

sub findProcess ($cmndline)
{
	foreach my $p ( @{$ps->table()} ) {
		if ($p->{'pid'} == $$) {
			next;
		}
		if ($p->{'cmndline'} =~ m#$cmndline#) {
			if ($p->{'state'} eq 'defunct') {
				next;
			}
			return $p->{'pid'};
		}
	}
	return 0;
}
