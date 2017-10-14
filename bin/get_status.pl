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
#   This script will output some informations on the status of the system
#
#   Usage:
#           get_status.pl [-s, -p, -l, -d, -m, -t]
#   -s: output status of vital Mailcleaner processes
#       processes are:
#       	incoming MTA
#       	queuing MTA
#       	outgoing MTA
#       	Web GUI
#       	antispam/antivirus process
#       	master database
#       	slave database
#          snmp daemon
#          greylist daemon
#          cron daemon
#          Preference daemon
#          firewall
#   -p: output number of messages in spools
#   -l: output system load
#   -d: output disks usage
#   -m: output memory counters
#   -t: output the maximum waiting time for a message in each spool
#   -u: output the last system patch
#
# processes code are:
#     0: critical (not running and required)
#     1: running
#     2: stopped (not running but not required)
#     3: needs restart
#     4: currently stopping
#     5: currently starting
#     6: currently restarting (currently procesing stop/start script)

use strict;

my %config = readConfig("/etc/mailcleaner.conf");

my $mode_given = shift;
my $mode = "";
my $cmd;

my %proc_strings = ( 'exim_stage1' => 'exim/exim_stage1.conf',
		     'exim_stage2' => 'exim/exim_stage2.conf',
		     'exim_stage4' => 'exim/exim_stage4.conf',
		     'apache' => 'apache/httpd.conf',
		     'mailscanner' => 'MailScanner',
		     'mysql_master' => 'mysql/my_master.cnf',
		     'mysql_slave' => 'mysql/my_slave.cnf',
		     'snmpd' => 'snmp/snmpd.conf',
		     'greylistd' => 'greylistd/greylistd.conf',
		     'cron' => '/usr/sbin/cron',
		     'preftdaemon' => 'PrefTDaemon',
		     'spamd' => 'spamd.sock',
		     'clamd' => 'clamd.conf',
		     'clamspamd' => 'clamspamd.conf',
                     'newsld' => 'newsld.sock',
		     'spamhandler' => 'SpamHandler');

my @order = ('exim_stage1', 'exim_stage2', 'exim_stage4', 'apache', 'mailscanner', 'mysql_master', 'mysql_slave', 'snmpd', 'greylistd', 'cron', 'preftdaemon', 'spamd', 'clamd', 'clamspamd', 'spamhandler', 'newsld');
		     

my $res;
if (! $mode_given) {
	bad_usage();
}
if  ($mode_given =~ /s/) { 
    my $restartdir = $config{VARDIR}."/run/";
	$cmd = "ps -efww";
	$res = `$cmd`;
	foreach my $key (@order) {
		my $st = 0;
		if ($res =~ /$proc_strings{$key}/ ) {
			$st = 1;
		} else {
			#if ($key eq 'mysql_master' && $config{ISMASTER} =~ m/n|N/ ) {
	        #  $st = 2;
		    #} elsif ($key eq 'greylistd' && getNumberOfGreylistDomains() < 1) {
		    #  $st = 2;
		    #} else {
			#  $st = 0;
			#}
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
		print '|'.$st;
		
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
    print '|'.$st;
	print "\n";
}
elsif  ($mode_given =~ /p/) {
 	my @spools = ('exim_stage1', 'exim_stage2', 'exim_stage4');
	foreach my $key (@spools) {	
                if ($key !~ m/^exim_stage2$/) {
                  $cmd = "/opt/exim4/bin/exim -C $config{SRCDIR}/etc/exim/$key.conf -bpc";
                } else {
                  my $subcmd = "grep -e '^MTA\\s*=\\s*eximms' ".$config{SRCDIR}."/etc/mailscanner/MailScanner.conf";
                  my $type = `$subcmd`;
                  if ($type eq '') {
                    $cmd = "/opt/exim4/bin/exim -C $config{SRCDIR}/etc/exim/$key.conf -bpc";
                  } else {
                    $cmd = "ls $config{VARDIR}/spool/exim_stage2/input/*.env 2>&1 | grep -v 'No such' | wc -l";
                  }
                }
		#$cmd = "ls $config{VARDIR}/spool/$key/input | wc -l ";
		$res = `$cmd`;
		chomp($res);
		#my $val = int(($res)/2);
		#print "|$val";
                print "|$res";
	}
	print "\n";
}
elsif  ($mode_given =~ /l/) { 
	$cmd = "cat /proc/loadavg | cut -d' ' -f-3";
	$res = `$cmd`;
	chomp($res);
	my @loads = split(/ /, $res);
	print "|$loads[0]|$loads[1]|$loads[2]\n";
}
elsif ($mode_given =~ /d/) {
	$cmd = "df";
	$res = `$cmd`;
	my @lines = split(/\n/, $res);
	foreach my $line (@lines) {
		if ($line =~ /\S+\s+\d+\s+\d+\s+\d+\s+(\d+\%)\s+(\S+)/) {
			print "|$2|$1";
		}
	}
	print "\n";
}
elsif ($mode_given =~ /m/) {
	$cmd = "cat /proc/meminfo";
	$res = `$cmd`;
	my @fields = ('MemTotal', 'MemFree', 'SwapTotal', 'SwapFree');
	foreach my $field (@fields) {
		if ($res =~ /$field:\s+(\d+)/) {
			print "|$1";
		} 
	}
	print "\n";
}
elsif ($mode_given =~ /t/) {
	$cmd = "/opt/exim4/bin/exim -C $config{SRCDIR}/etc/exim/exim_stage2.conf -bp | head -1 | cut -d' ' -f2";
	$res = `$cmd`;
	chomp($res);
	print $res."\n";
}
elsif ($mode_given =~ /u/) {
        $cmd = "echo \"use mc_config; select id, date from update_patch order by id desc limit 1;\" | /opt/mysql5/bin/mysql --skip-column-names -S $config{VARDIR}/run/mysql_slave/mysqld.sock -umailcleaner -p$config{MYMAILCLEANERPWD}";
	$res = `$cmd`;
	my $patch = "";
	if ($res =~ /^(\d+)\s+(\S+)$/) {
	  $patch = $1;
	}
        print $patch."\n";
}
else {
	bad_usage();
}

sub bad_usage 
{
        printf("Usage: get_status.pl [-s, -p, -l, -d, -m, -t, -u]\n");
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

