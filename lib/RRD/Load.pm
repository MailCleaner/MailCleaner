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

package RRD::Load;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;

use strict;
use lib qw(/usr/rrdtools/lib/perl/);
require RRD::Generic;

our @ISA = qw(Exporter);
our @EXPORT = qw(new collect plot);
our $VERSION = 1.0;

sub new($statfile,$reset)
{
    $statfile = $statfile."/load.rrd";

    my %things = (
        load => ['GAUGE', 'AVERAGE']
    );
    my $rrd = RRD::Generic::create($statfile, \%things, $reset);

    my $self = {
        statfile => $statfile,
        rrd => $rrd
    };

    return bless $self, "RRD::Load";
}

sub collect($self,$snmp)
{
    my %things = (
        load => '1.3.6.1.4.1.2021.10.1.3.2'
    );

    return RRD::Generic::collect($self->{rrd}, $snmp, \%things);
}

sub plot($self,$dir,$period,$leg)
{
    my %things = (
        load => ['area', 'EB9C48', 'BA3614', 'Load', 'AVERAGE', '%10.2lf', ''],
    );
    my @order = ('load');

    my $legend = "\t\t     Last\t   Average\t\t  Max\\n";
    return RRD::Generic::plot('load', $dir, $period, $leg, 'System Load', 0, 0, $self->{rrd}, \%things, \@order, $legend);
}

1;
