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

package RRD::Generic;

use v5.36;
use strict;
use warnings;
use utf8;

use strict;
use lib qw(/usr/rrdtools/lib/perl/);
require Net::SNMP;
require RRDTool::OO;
require Exporter;

our @ISA = qw(Exporter);
our @EXPORT = qw(create collect plot);
our $VERSION = 1.0;

sub create($statfile,$things_a,$reset)
{
    my %things = %$things_a;

    my $rrdstep = 300;
    my $interval = int($rrdstep / 60);
    my $rows = 4000 / $interval;

    if ($reset) {
        unlink($statfile);
    }
    my $rrd = RRDTool::OO->new( file => $statfile );
    if (! -f $statfile) {
        my @options;
        ## add data sources
        foreach my $thing (keys %things) {
            @options = (
                @options,
                data_source => {
                    name => $thing,
                    type => $things{$thing}[0]
                }
            );
        }
        # add day archives (average and max)
        @options = (@options,
            archive  => {
                rows      => $rows, # day
                cpoints   => 1,
                cfunc     => 'LAST',
            },
            archive  => {
                rows      => $rows, # day
                cpoints   => 1,
                cfunc     => 'AVERAGE',
            },
            archive  => {
                rows      => $rows, # day
                cpoints   => 1,
                cfunc     => 'MAX',
            },
        );
        # add week, month and year archives
        my @times = (int(30/$interval), int(120/$interval), int(1440/$interval));
        foreach my $time (@times) {
            @options = (@options,
                archive  => {
                    rows      => 800,
                    cpoints   => $time,
                    cfunc     => 'LAST',
                },
                archive  => {
                    rows      => 800,
                    cpoints   => $time,
                    cfunc     => 'AVERAGE',
                },
                archive  => {
                    rows      => 800,
                    cpoints   => $time,
                    cfunc     => 'MAX',
                }
            );
        }

        $rrd->create(
              step        => $rrdstep,
              @options
        )
    }
    return $rrd;
}

sub collect($rrd,$snmp,$things_a)
{
    my %things = %$things_a;

    my %values;
    foreach my $thing (keys %things) {
        my $oid = $things{$thing};
        my $result = $snmp->get_request(-varbindlist => [$oid]);
        my $error = $snmp->error();
        my $value = 0;
        if (defined($error) && ! $error eq "") {
            print "Error found: $error\n";
        } else {
            if ($result && defined($result->{$oid})) {
                $value = $result->{$oid};
            }
        }
        $values{$thing} = $value;
    }
    $rrd->update(values => {%values});
    return 1;
}

sub plot($type,$dir,$period,$leg,$ylegend,$lower,$upper,$rrd,$things_a,$order_a,$legend)
{
    my %things = %$things_a;
    my @order = @$order_a;

    my $time = 24*3600;
    if ($period eq 'week') {
        $time = $time*7;
    } elsif ($period eq 'month') {
        $time = $time*31;
    } elsif ($period eq 'year') {
        $time = $time *365;
    } else {
        $period = 'day';
    }

    my $image_name = $dir."/$type-$period.png";
    if ($leg) {
        $image_name = $dir."/$type-$period-full.png";
    }
    my @options = (
        image          => $image_name,
        vertical_label => $ylegend,
        start          => time() - $time,
        end            => time(),
        width       => 500,
        height      => 100,
        upper_limit => $upper,
        lower_limit => $lower,
        units_exponent => 0
    );
    if ($leg) {
        @options = (@options,
            comment     => $legend
        );
    }

    # alignement stuf
    my $max = 0;
    foreach my $n (keys %things)  {
        if ($max < length($n)) { $max = length($n) };
    }

    # add graphs
    foreach my $thing (@order) {
        my $legend = $things{$thing}[3] ;
        while (length($legend)<$max) { $legend = "${legend} "; }
        if ( $things{$thing}[6] eq '') {
            @options = (@options,
                draw => {
                    type   => $things{$thing}[0],
                    color  => $things{$thing}[1],
                    dsname => $thing,
                    name   => $thing,
                    cfunc  => $things{$thing}[4],
                    legend => $legend
                },
            );
        } else {
            if ($things{$thing}[6] =~ m/^(\S+)\,(\S+)\,(\S+)/) {
                @options = (@options,
                    draw => {
                        dsname => $1,
                        name => $1,
                        cfunc  => $things{$thing}[4],
                        type => 'none'
                    }
                );
            }
            @options = (@options,
                draw => {
                    type   => $things{$thing}[0],
                    color  => $things{$thing}[1],
                    cdef => $things{$thing}[6],
                    name   => $thing,
                    cfunc  => $things{$thing}[4],
                    legend => $legend,
                },
            );
        }

        if ($leg) {
            @options = (@options,
                gprint => {
                    draw   => $thing,
                    cfunc  => "LAST",
                    format => "  ".$things{$thing}[5]
                },
                gprint => {
                    draw   => $thing,
                    cfunc  => "AVERAGE",
                    format => $things{$thing}[5]
                },
                gprint => {
                    draw   => $thing,
                    cfunc  => "MAX",
                    format => $things{$thing}[5]."\\n"
                }
            );
        }
    }
    $rrd->graph(@options);
    return 1;
}

1;
