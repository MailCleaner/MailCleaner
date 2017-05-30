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

package          RRD::Load;
require          Exporter;

use strict;
use lib qw(/usr/rrdtools/lib/perl/);
require RRD::Generic;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(New collect plot);
our $VERSION    = 1.0;


sub New {
  my $statfile = shift;
  $statfile = $statfile."/load.rrd";
  my $reset = shift;
  
  my %things = (
           load => ['GAUGE', 'AVERAGE']
         );
  my $rrd = RRD::Generic::create($statfile, \%things, $reset);
  

  my $this = {
  	 statfile => $statfile,
  	 rrd => $rrd
  };
  
  return bless $this, "RRD::Load";
}


sub collect {
  my $this = shift;
  my $snmp = shift;
  
  my %things = (
        load => '1.3.6.1.4.1.2021.10.1.3.2'
        );
        
  return RRD::Generic::collect($this->{rrd}, $snmp, \%things);
}

sub plot {
  my $this = shift;
  my $dir = shift;
  my $period = shift;
  my $leg = shift;
  
  my %things = (
        load => ['area', 'EB9C48', 'BA3614', 'Load', 'AVERAGE', '%10.2lf', ''],
   );
  my @order = ('load');
  
  my $legend = "\t\t     Last\t   Average\t\t  Max\\n";
  return RRD::Generic::plot('load', $dir, $period, $leg, 'System Load', 0, 0, $this->{rrd}, \%things, \@order, $legend);
}
1;