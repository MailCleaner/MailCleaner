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
#   This script will dump the domains configuration
#
#   Usage:
#           collect_rrd_stats.pl

use v5.36;
use strict;
use warnings;
use utf8;
use Carp qw( confess );

my ($SRCDIR, $VARDIR, $ISMASTER);
BEGIN {
    if ($0 =~ m/(\S*)\/\S+.pl$/) {
        my $path = $1."/../lib";
        unshift (@INC, $path);
    }
    require ReadConfig;
    my $conf = ReadConfig::getInstance();
    $SRCDIR = $conf->getOption('SRCDIR') || '/usr/mailcleaner';
    $VARDIR = $conf->getOption('VARDIR') || '/var/mailcleaner';
    $ISMASTER = $conf->getOption('ISMASTER') || 'Y';
    unshift(@INC, $SRCDIR."/lib");
}

require DB;
require RRDStats;
require RRDArchive;

my $mode = shift;
my $m = '';
if (defined($mode) && $mode eq 'daily') {
   $m = 'daily';
}

if ($ISMASTER !~ /^[Yy]$/) {
    # not a master, so no more processing
    exit 0;
}

mkdir "${VARDIR}/spool/rrdtools" unless (-d "${VARDIR}/spool/rrdtools");
mkdir "${VARDIR}/spool/newrrds" unless (-d "${VARDIR}/spool/newrrds");

# get stats to plot
my @stats = ('cpu', 'load', 'network', 'memory', 'disks', 'messages', 'spools');

# get hosts to query
my $slave_db = DB::connect('slave', 'mc_config');
my @hosts = $slave_db->getListOfHash("SELECT id, hostname FROM slave");

## main hosts loops
foreach my $host (@hosts) {
    my $hostname = $host->{'hostname'};
    my $host_stats = RRDStats::new($host->{'hostname'});
    #print "processing: $hostname\n";

    for my $stattype (@stats) {
        if ($m eq 'daily') {
            $host_stats->createRRD($stattype);
            $host_stats->plot($stattype, 'daily');
        } else {
            $host_stats->createRRD($stattype);
            $host_stats->collect($stattype);
            $host_stats->plot($stattype, '');
        }
    }
}

## new rrd collecting scheme
my %collections;
my @collections_list = $slave_db->getListOfHash("SELECT id, name, type FROM rrd_stats");
my %dynamic_oids;
foreach my $collection (@collections_list) {
    my $c = RRDArchive::new($collection->{'id'}, $collection->{'name'}, $collection->{'type'});
    if (keys %dynamic_oids < 1) {
        $c->getDynamicOids(\%dynamic_oids);

    }
    my @elements = $slave_db->getListOfHash("SELECT name, type, function, oid, min, max FROM rrd_stats_element WHERE stats_id=".$collection->{'id'}." order by draw_order");
    foreach my $element (@elements) {
        $c->addElement($element);
    }

    $c->collect(\%dynamic_oids);
}

$slave_db->disconnect();
print "SUCCESSFULL\n";
