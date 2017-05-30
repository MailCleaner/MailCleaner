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

package          RRD::Cpu24;
require          Exporter;

use strict;
use lib qw(/usr/rrdtools/lib/perl/);
require RRD::Generic;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(New collect plot);
our $VERSION    = 1.0;


sub New {
  my $statfile = shift;
  $statfile = $statfile."/cpu.rrd";
  my $reset = shift;
  
  my %things = (
           idle => ['COUNTER', 'AVERAGE'],
           nice => ['COUNTER', 'AVERAGE'],
           system => ['COUNTER', 'AVERAGE'],
           user => ['COUNTER', 'AVERAGE'],
 #          wait => ['COUNTER', 'AVERAGE'],
         );
  my $rrd = RRD::Generic::create($statfile, \%things, $reset);
  

  my $this = {
  	 statfile => $statfile,
  	 rrd => $rrd
  };
  
  return bless $this, "RRD::Cpu24";
}


sub collect {
  my $this = shift;
  my $snmp = shift;
  
  my %things = (
        idle => '1.3.6.1.4.1.2021.11.53.0',
        nice => '1.3.6.1.4.1.2021.11.51.0',
        system => '1.3.6.1.4.1.2021.11.52.0',
        user => '1.3.6.1.4.1.2021.11.50.0',
 #       wait => '1.3.6.1.4.1.2021.11.54.0',
        );
        
  return RRD::Generic::collect($this->{rrd}, $snmp, \%things);
}

sub plot {
  my $this = shift;
  my $dir = shift;
  my $period = shift;
  my $leg = shift;
  
  my %things = (
        idle => ['stack', 'EEEEEE', 'EEEEEE', 'Idle', 'AVERAGE', '%10.2lf %%', ''],
        nice => ['stack', '48C3EB', '2B82C5', 'Nice', 'AVERAGE', '%10.2lf %%', ''],
        system => ['area', 'FF0000' , 'FF0000', 'System', 'AVERAGE', '%10.2lf %%', ''],
        user => ['stack', 'EB9C48', 'BA3614', 'User', 'AVERAGE', '%10.2lf %%', ''],
 #       wait => ['stack', 'E9644A', 'C2791F', 'Wait', 'AVERAGE', '%10.2lf %%', ''],
   );
  #my @order = ('system', 'wait', 'user', 'nice', 'idle');
  my @order = ('system', 'user', 'nice', 'idle');
  
  my $legend = "\t\t        Last\t  Average\t\t   Max\\n";
  return RRD::Generic::plot('cpu', $dir, $period, $leg, 'CPU usage [%]', 0, 100, $this->{rrd}, \%things, \@order, $legend);
}
1;
