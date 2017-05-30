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

package          RRD::Spools;
require          Exporter;

use strict;
use lib qw(/usr/rrdtools/lib/perl/);
require RRD::Generic;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(New collect plot);
our $VERSION    = 1.0;


sub New {
  my $statfile = shift;
  $statfile = $statfile."/spools.rrd";
  my $reset = shift;    
    
  my %things = (
           incoming => ['GAUGE', 'AVERAGE'],
           filtering => ['GAUGE', 'AVERAGE'],
           outgoing => ['GAUGE', 'AVERAGE'],
         );

  my $rrd = RRD::Generic::create($statfile, \%things, $reset);
  

  my $this = {
  	 statfile => $statfile,
  	 rrd => $rrd
  };
  
  return bless $this, "RRD::Spools";
}


sub collect {
  my $this = shift;
  my $snmp = shift;
  
  require Net::SNMP;
  require RRDTool::OO;
  
  my $oid = '1.3.6.1.4.1.2021.8.1.101.6';
  
  my $result = $snmp->get_request(-varbindlist => [$oid]);
  my $value = '|0|0|0';
  if ($result) {
    $value = $result->{$oid};
  }
  
  my @values = split('\|', $value);
  return $this->{rrd}->update(
        values => {
            incoming => $values[1],
            filtering => $values[2],
            outgoing => $values[3],
         }
  );
  
}

sub plot {
  my $this = shift;
  my $dir = shift;
  my $period = shift;
  my $leg = shift;
  my %things = (
        incoming => ['line', '000000', '', 'Incoming', 'AVERAGE', '%10.0lf', ''],
        filtering => ['line', 'FF0000', '', 'Filtering', 'AVERAGE', '%10.0lf', ''],
        outgoing => ['line', 'EB9C48', '', 'Outgoing', 'AVERAGE', '%10.0lf', ''],
   );
  my @order = ('incoming', 'filtering', 'outgoing');
  
  my $legend = "\t\t          Last\t  Average\t\t Max\\n";
  RRD::Generic::plot('spools', $dir, $period, $leg, 'Spools count', 0, 5, $this->{rrd}, \%things, \@order, $legend);
  return 1;
}
1;