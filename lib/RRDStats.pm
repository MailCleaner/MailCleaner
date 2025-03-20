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
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

package RRDStats;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;
use ReadConfig;
use DBI();
use Net::SNMP;

our @ISA          = qw(Exporter);
our @EXPORT       = qw(createRRD collect plot);
our $VERSION      = 1.0;

sub new($hostname)
{
    my $conf = ReadConfig::getInstance();
    my $spooldir = $conf->getOption('VARDIR')."/spool/rrdtools/".$hostname;
    mkdir ($conf->getOption('VARDIR')."/www") unless (-d $conf->getOption('VARDIR')."/www");
    mkdir ($conf->getOption('VARDIR')."/www/mrtg") unless (-d $conf->getOption('VARDIR')."/www/mrtg");
    mkdir ($conf->getOption('VARDIR')."/www/stats") unless (-d $conf->getOption('VARDIR')."/www/stats");
    my $pictdir = $conf->getOption('VARDIR')."/www/mrtg/".$hostname;
    my %stats = ();

    my $slave_db = DB::connect('slave', 'mc_config');
    my %row = $slave_db->getHashRow("SELECT community FROM snmpd_config WHERE set_id=1");
    $slave_db->disconnect();
    my $community = $row{'community'};

    my $self = {
        hostname => $hostname,
        spooldir => $spooldir,
        pictdir => $pictdir,
        snmp_session => undef,
        stats => \%stats,
        community => $community
        };
    bless $self, "RRDStats";

    if (!$self->create_stats_dir()) {
        print "WARNING, CANNOT CREATE STAT DIR\n";
    }
    if (!$self->create_graph_dir()) {
        print "WARNING, CANNOT CREATE STAT DIR\n";
    }
    if (!$self->connectSNMP()) {
        print "WARNING, CANNOT CONNECT TO SNMP\n";
    }

    return $self;
}

sub createRRD($self,$type)
{
    if ($type eq 'cpu') {
        my $res = `uname -r`;
        if ($res =~ m/^2.4/) {
            require RRD::Cpu24;
            $self->{stats}{$type} = &RRD::Cpu24::new($self->{spooldir}, 0);
        } else {
            require RRD::Cpu;
            $self->{stats}{$type} = &RRD::Cpu::new($self->{spooldir}, 0);
        }
    } elsif ($type eq 'load') {
        require RRD::Load;
        $self->{stats}{$type} = &RRD::Load::new($self->{spooldir}, 0);
    } elsif ($type eq 'network') {
        require RRD::Network;
        $self->{stats}{$type} = &RRD::Network::new($self->{spooldir}, 0);
    } elsif ($type eq 'memory') {
        require RRD::Memory;
        $self->{stats}{$type} = &RRD::Memory::new($self->{spooldir}, 0);
    } elsif ($type eq 'disks') {
        require RRD::Disk;
        $self->{stats}{$type} = &RRD::Disk::new($self->{spooldir}, 0);
    } elsif ($type eq 'messages') {
        require RRD::Messages;
        $self->{stats}{$type} = &RRD::Messages::new($self->{spooldir}, 0);
    } elsif ($type eq 'spools') {
        require RRD::Spools;
        $self->{stats}{$type} = &RRD::Spools::new($self->{spooldir}, 0);
    }
}

sub collect($self,$type)
{
    if (defined($self->{stats}->{$type})) {
        $self->{stats}->{$type}->collect($self->{snmp_session});
    }
}

sub plot($self,$type,$mode)
{
    my @ranges = ('day', 'week');
    if ($mode eq 'daily') {
        @ranges = ('month', 'year');
    }
    if (defined($self->{stats}->{$type})) {
        for my $time (@ranges) {
            #$self->{stats}->{$type}->plot($self->{pictdir}, $time, 0);
            $self->{stats}->{$type}->plot($self->{pictdir}, $time, 1);
        }
    }
}


sub create_stats_dir($self)
{
    my $conf = ReadConfig::getInstance();
    my $dir = $self->{spooldir};
    if ( ! -d $dir) {
       return mkdir $dir;
    }
    return 1;
}

sub create_graph_dir($self)
{
    my $conf = ReadConfig::getInstance();
    my $dir = $self->{pictdir};
    if ( ! -d $dir) {
       return mkdir $dir;
    }
    return 1;
}

sub connectSNMP($self)
{
    if (defined($self->{snmp_session})) {
        return 1;
    }

    my ($session, $error) = Net::SNMP->session(
        -hostname => $self->{hostname},
        -community => $self->{'community'},
        -port => 161,
        -timeout => 5,
        -version => 2,
        -retries => 1
    );
    if ( !defined($session)) {
       print "WARNING, CANNOT CONTACT SNMP HOST\n";
       return 0;
    }
    $self->{snmp_session} = $session;
    return 1;
}

1;
