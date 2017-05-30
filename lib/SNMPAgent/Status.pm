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

package          SNMPAgent::Status;
require          Exporter;

use strict;
use NetSNMP::agent;
use NetSNMP::OID (':all'); 
use NetSNMP::agent (':all'); 
use NetSNMP::ASN (':all');
use lib qw(/usr/rrdtools/lib/perl/);
use ReadConfig;
use DBI;
use Proc::ProcessTable;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(getMIB getFullVersion getEdition getVersion getPatchLevel getSpool);
our $VERSION    = 1.0;

my $mib_root_position = 1;

my %mib_version = (1 => \&getFullVersion, 2 => \&getEdition, 3 => \&getVersion, 4 => \&getPatchLevel);
my %mib_spools = (1 => \&getSpool, 2 => \&getSpool, 4 => \&getSpool);
my %mib_processes_index = ();
my %mib_processes_name = ();
my %mib_processes_count = ();
my %mib_processes_status = ();
my %mib_processes = (1 => \%mib_processes_index, 2 => \%mib_processes_name, 3 => \%mib_processes_count, 4 => \%mib_processes_status);
#my %mib_process = (1 => \&getProcessIndex, 2 => \&getProcessName, 3 => \&getProcessCount, 4 => \&getProcessStatus);

my %mib_status = ( 1 => \%mib_version, 2 => \%mib_spools, 3 => \%mib_processes);

my $conf;
my $EXIMBIN = "/opt/exim4/bin/exim";

my %processes_tmpl = (
          '1' => {'name'=>'Incoming MTA', 'pstring'=>'exim/exim_stage1.conf'},
          '2' => {'name'=>'Filtering MTA', 'pstring'=>'exim/exim_stage2.conf'},
          '3' => {'name'=>'Outgoing MTA', 'pstring'=>'exim/exim_stage4.conf'},
          '4' => {'name'=>'Filtering engine', 'pstring'=>'MailScanner'},
          '5' => {'name'=>'Web Interface', 'pstring'=>'apache/httpd.conf'},
          '6' => {'name'=>'Master database', 'pstring'=>'mysql/my_master.cnf'},
          '7' => {'name'=>'Slave database', 'pstring'=>'mysql/my_slave.cnf'},
          '8' => {'name'=>'SNMP daemon', 'pstring'=>'snmp/snmpd.conf'},
          '9' => {'name'=>'Greylisting daemon', 'pstring'=>'greylistd/greylistd.conf'},
          '10' => {'name'=>'Scheduler', 'pstring'=>'/usr/sbin/cron'},
          '11' => {'name'=>'Preferences daemon', 'pstring'=>'PrefTDaemon'},
          '12' => {'name'=>'SpamAssassin daemon', 'pstring'=>'spamd.sock'},
          '13' => {'name'=>'AntiVirus daemon', 'pstring'=>'clamd.conf'},
          '14' => {'name'=>'ClamSpam daemon', 'pstring'=>'clamspamd.conf'},
          '15' => {'name'=>'SpamHandler daemon', 'pstring'=>'SpamHandler'},
          '16' => {'name'=>'Statistics Daemon', 'pstring'=>'StatsDaemon'}
);

sub initAgent() {
   doLog('Agent Status initializing', 'status', 'debug');
   
   $conf = ReadConfig::getInstance();
   
   populateProcesses();
   
   return $mib_root_position;
}


sub getMIB() {
   return \%mib_status;	
}

sub doLog() {
	my $message = shift;
	my $cat = shift;
	my $level = shift;
	
	SNMPAgent::doLog($message, $cat, $level);
}


##### Handlers
sub getFullVersion {
	my ($editiontype, $edition) = getEdition();
	my ($versiontype, $version) = getVersion();
	my ($patchtype, $patchlevel) = getPatchLevel();
	
	my $fullstring = 'MailCleaner '.$edition." ".$version." (".$patchlevel.")";
    return (ASN_OCTET_STR, $fullstring);
}
sub getEdition {
	my $edition = 'Unknown';
	my $file = $conf->getOption('SRCDIR')."/etc/edition.def";
	my $f;
	
	if (open($f, $file)) {
		while (<$f>) {
			$edition = $_;
		}
		close($f)
	}
	chomp($edition);
    return (ASN_OCTET_STR, $edition);
}

sub getVersion {
	my $version = 'Unknown';
    my $file = $conf->getOption('SRCDIR')."/etc/mailcleaner/version.def";
    my $f;
    
    if (open($f, $file)) {
        while (<$f>) {
            $version = $_;
        }
        close($f)
    }
    chomp($version);
    return (ASN_OCTET_STR, $version);
}

sub getPatchLevel {
	
	my $patch = 'Unknown';

        my $patchfile = $conf->getOption('SRCDIR').'/etc/mailcleaner/patchlevel.def';
        if (-f $patchfile) {
           my $pfile;
           if (open($pfile, $patchfile)) {
             while(<$pfile>) {
               if (/^(\d+)$/) {
                 $patch = $1;
               }
             }
             close($pfile);
           }
        }

        if ($patch eq 'Unknown') {
          my $dbh = DBI->connect("DBI:mysql:database=mc_config;mysql_socket=".$conf->getOption('VARDIR')."/run/mysql_slave/mysqld.sock",
                                         "mailcleaner",$conf->getOption('MYMAILCLEANERPWD'), {RaiseError => 0, PrintError => 1} );
	  my $sth = $dbh->prepare("SELECT id FROM update_patch ORDER BY id DESC LIMIT 1");
	  if ($sth->execute()) {
	     if (my $row = $sth->fetchrow_hashref()) {
        	if (defined($row) && defined($row->{'id'})) {
	        	$patch = $row->{'id'};
                }
	     }
	  }
	  $sth->finish();
        }
	chomp($patch);
    return (ASN_OCTET_STR , $patch);
}

sub getSpool {
	my $oid = shift;
	my @oid = $oid->to_array();
    
    my $spool = pop(@oid);
    if ($spool !~ /^[124]$/) {
    	$spool = 1;
    }
    my $cmd = $EXIMBIN." -C ".$conf->getOption('SRCDIR')."/etc/exim/exim_stage".$spool.".conf -bpc";
    my $res = `$cmd`;
    chomp($res);
    return (ASN_INTEGER, int($res));
}


sub populateProcesses {
    foreach my $p (sort { $a <=> $b} keys %processes_tmpl ) {
        SNMPAgent::doLog("Added process: $p: ".$processes_tmpl{$p}->{'name'},'status','debug');
        
        $mib_processes_index{1}{$p} = \&getProcessIndex;
        $mib_processes_index{2}{$p} = \&getProcessName;
        $mib_processes_index{3}{$p} = \&getProcessCount;
        $mib_processes_index{4}{$p} = \&getProcessStatus;
    }        
}

sub getRealProcessCount {
	my $procIndex = shift;
	my $count = 0;
	
	if (!defined($processes_tmpl{$procIndex})) {
		return $count;
	}
	my $str = $processes_tmpl{$procIndex}->{'pstring'};

	my $t = new Proc::ProcessTable;
	foreach my $p ( @{ $t->table } ) {
		if ($p->cmndline =~ /$str/) {
            $count++;			
		}
	}
	return $count;
}

sub getProcessIndex {
	my $oid = shift;
    my @oid = $oid->to_array();
    
    #my $field = pop(@oid);
    my $proc = pop(@oid);
    return (ASN_INTEGER, int($proc));
}
sub getProcessName {
    my $oid = shift;
    my @oid = $oid->to_array();
    
    #my $field = pop(@oid);
    my $proc = pop(@oid);
    return (ASN_OCTET_STR, $processes_tmpl{$proc}->{'name'});
}
sub getProcessCount {
    my $oid = shift;
    my @oid = $oid->to_array();
    
    #my $field = pop(@oid);
    my $proc = pop(@oid);
    my $count = getRealProcessCount($proc);
    return (ASN_INTEGER, int($count));
}
sub getProcessStatus {
    my $oid = shift;
    my @oid = $oid->to_array();

    #my $field = pop(@oid);
    my $proc = pop(@oid);
    my $count = getRealProcessCount($proc);
    my $status = 'CRITICAL';
    if ($count > 0) {
    	$status = 'RUNNING';
    } else {
    	if ($proc == 6 && $conf->getOption('ISMASTER') eq 'Y') {
    		$status = 'STOPPED';
    	}
    	if ($proc == 9) {
    		$status = 'STOPPED';
    	}
    }
    return (ASN_OCTET_STR, $status);
}
1;
