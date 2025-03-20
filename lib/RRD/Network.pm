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

package RRD::Network;

use v5.36;
use strict;
use warnings;
use utf8;

use strict;
use lib qw(/usr/rrdtools/lib/perl/);
require RRD::Generic;
require Exporter;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(new collect plot);
our $VERSION    = 1.0;

sub new($statfile,$reset)
{
    $statfile = $statfile."/network.rrd";

    my %things = (
        in => ['COUNTER', 'AVERAGE'],
        out => ['COUNTER', 'AVERAGE']
        );
    my $rrd = RRD::Generic::create($statfile, \%things, $reset);

    my $self = {
        statfile => $statfile,
        rrd => $rrd
    };

    return bless $self, "RRD::Network";
}

sub collect($self,$snmp)
{
    my $if = $self->getInterfaceID($snmp, 'eth0');

    my %things = (
        in => '1.3.6.1.2.1.2.2.1.10.'.$if,
        out => '1.3.6.1.2.1.2.2.1.16.'.$if,
    );

    return RRD::Generic::collect($self->{rrd}, $snmp, \%things);
}

sub plot($self,$dir,$period,$leg)
{
    my %things = (
        kbin => ['area', '54EB48', 'BA3614', 'In', 'AVERAGE', '%3.2lf KBps', 'in,1024,/'],
        kbout => ['line', '7648EB', 'BA3614', 'Out', 'AVERAGE', '%3.2lf KBps', 'out,1024,/'],
    );
    my @order = ('kbin', 'kbout');

    my $legend = "\t\t  Last\tAverage\t   Max\\n";
    return RRD::Generic::plot('network', $dir, $period, $leg, 'Bandwidth [KBps]', 0, 0, $self->{rrd}, \%things, \@order, $legend);
}

sub getInterfaceID($self,$snmp,$if_name)
{
    my $if_nb = 1;

    my $base_oid = '1.3.6.1.2.1.2.2.1.2';
    for my $i (1..10) {
        my $oid = $base_oid.".$i";
        my $result = $snmp->get_request(
            -varbindlist => [$oid]
        );
        if (defined($result)) {
            if ($result->{$oid} eq $if_name) {
                return $i;
            }
        } else {
            return $if_nb;
        }
    }
    return $if_nb;
}

1;
