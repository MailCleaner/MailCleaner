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

package RRD::Disk;

use v5.36;
use strict;
use warnings;
use utf8;

use lib qw(/usr/rrdtools/lib/perl/);
require RRD::Generic;
require Exporter;

our @ISA = qw(Exporter);
our @EXPORT = qw(new collect plot);
our $VERSION = 1.0;

sub new($statfile,$reset)
{
    $statfile = $statfile."/disk.rrd";
    my %things = (
        rootused => ['GAUGE', 'LAST'],
        varused => ['GAUGE', 'LAST'],
    );
    my $rrd = RRD::Generic::create($statfile, \%things, $reset);


    my $self = {
        statfile => $statfile,
        rrd => $rrd
    };

    return bless $self, "RRD::Disk";
}


sub collect($self,$snmp)
{
    my %things = (
        rootused => '1.3.6.1.4.1.2021.9.1.9.1',
        varused => '1.3.6.1.4.1.2021.9.1.9.2',
    );

    return RRD::Generic::collect($self->{rrd}, $snmp, \%things);
}

sub plot($self,$dir,$period,$leg)
{
    my %things = (
        rootused => ['line', '7648EB', '', 'System [/]   ', 'AVERAGE', '%10.2lf %%', ''],
        varused => ['area', 'EB9C48', '', 'Datas  [/var]', 'AVERAGE', '%10.2lf %%', ''],
    );
    my @order = ('varused', 'rootused');

    my $legend = "\t\t              Last\t  Average\t\t    Max\\n";
    return RRD::Generic::plot('disk', $dir, $period, $leg, 'Disks usage [%]', 0, 100, $self->{rrd}, \%things, \@order, $legend);
}

1;
