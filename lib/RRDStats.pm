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
#   This module will just read the configuration file
#

package          RRDStats;
require          Exporter;
use ReadConfig;
use DBI();
use Net::SNMP;
use strict;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(createRRD collect plot);
our $VERSION    = 1.0;

sub New {
  my $hostname = shift;
  
  my $conf = ReadConfig::getInstance();
  my $spooldir = $conf->getOption('VARDIR')."/spool/rrdtools/".$hostname;
  my $pictdir = $conf->getOption('VARDIR')."/www/mrtg/".$hostname;
  my %stats = ();
 
  my $slave_db = DB::connect('slave', 'mc_config');
  my %row = $slave_db->getHashRow("SELECT community FROM snmpd_config WHERE set_id=1");
  $slave_db->disconnect();
  my $community = $row{'community'};
 
  my $this = {
         hostname => $hostname,
         spooldir => $spooldir,
         pictdir => $pictdir,
         snmp_session => undef,
         stats => \%stats,
         community => $community
         };
 bless $this, "RRDStats";

 if (!$this->create_stats_dir()) {
   print "WARNING, CANNOT CREATE STAT DIR\n";
 }
 if (!$this->create_graph_dir()) {
   print "WARNING, CANNOT CREATE STAT DIR\n";
 }
 if (!$this->connectSNMP()) {
   print "WARNING, CANNOT CONNECT TO SNMP\n";
 }

 return $this;
}

sub createRRD {
  my $this = shift;
  my $type = shift;
  
  if ($type eq 'cpu') {
	my $res = `uname -r`;
	if ($res =~ m/^2.4/) {
	  require RRD::Cpu24;
	  $this->{stats}{$type} = &RRD::Cpu24::New($this->{spooldir}, 0);
	} else { 
	  require RRD::Cpu;
  	  $this->{stats}{$type} = &RRD::Cpu::New($this->{spooldir}, 0);
	}
  } elsif ($type eq 'load') {
  	require RRD::Load;
  	$this->{stats}{$type} = &RRD::Load::New($this->{spooldir}, 0);
  } elsif ($type eq 'network') {
  	require RRD::Network;
  	$this->{stats}{$type} = &RRD::Network::New($this->{spooldir}, 0);
  } elsif ($type eq 'memory') {
  	require RRD::Memory;
  	$this->{stats}{$type} = &RRD::Memory::New($this->{spooldir}, 0);
  } elsif ($type eq 'disks') {
  	require RRD::Disk;
  	$this->{stats}{$type} = &RRD::Disk::New($this->{spooldir}, 0);
  } elsif ($type eq 'messages') {
  	require RRD::Messages;
  	$this->{stats}{$type} = &RRD::Messages::New($this->{spooldir}, 0);
  } elsif ($type eq 'spools') {
  	require RRD::Spools;
  	$this->{stats}{$type} = &RRD::Spools::New($this->{spooldir}, 0);
  } else {}
}

sub collect {
  my $this = shift;
  my $type = shift;
  
  if (defined($this->{stats}->{$type})) {
    $this->{stats}->{$type}->collect($this->{snmp_session});
  }
}

sub plot {
  my $this = shift;
  my $type = shift;
  my $mode = shift;
  
  my @ranges = ('day', 'week');
  if ($mode eq 'daily') {
    @ranges = ('month', 'year');
  }
  if (defined($this->{stats}->{$type})) {
  	for my $time (@ranges) {
     #$this->{stats}->{$type}->plot($this->{pictdir}, $time, 0);
     $this->{stats}->{$type}->plot($this->{pictdir}, $time, 1);
  	}
  }
}


sub create_stats_dir {
  my $this = shift;
  
  my $conf = ReadConfig::getInstance();
  my $dir = $this->{spooldir};
  if ( ! -d $dir) {
     return mkdir $dir;
  }
  return 1;
}

sub create_graph_dir {
  my $this = shift;
  
  my $conf = ReadConfig::getInstance();
  my $dir = $this->{pictdir};
  if ( ! -d $dir) {
     return mkdir $dir;
  }
  return 1;
}

sub connectSNMP {
  my $this = shift;
  
  if (defined($this->{snmp_session})) {
    return 1;
  }

  my ($session, $error) = Net::SNMP->session( 
                            -hostname => $this->{hostname},
                            -community => $this->{'community'},
                            -port => 161,
                            -timeout => 5,
                            -version => 2,
                            -retries => 1
                           );
  if ( !defined($session)) {
  	 print "WARNING, CANNOT CONTACT SNMP HOST\n";
  	 return 0;
  }
  $this->{snmp_session} = $session;
  return 1;
}

1;
