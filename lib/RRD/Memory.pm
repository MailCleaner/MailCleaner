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

package          RRD::Memory;
require          Exporter;

use strict;
use lib qw(/usr/rrdtools/lib/perl/);
require RRD::Generic;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(New collect plot);
our $VERSION    = 1.0;


sub New {
  my $statfile = shift;
  $statfile = $statfile."/memory.rrd";
  my $reset = shift;
  
  my %things = (
           used => ['GAUGE', 'LAST'],
           buffered => ['GAUGE', 'LAST'],
           cached => ['GAUGE', 'LAST'],
           free => ['GAUGE', 'LAST'],
           swapused => ['GAUGE', 'LAST']
         );
  my $rrd = RRD::Generic::create($statfile, \%things, $reset);
  

  my $this = {
  	 statfile => $statfile,
  	 rrd => $rrd
  };
  
  return bless $this, "RRD::Memory";
}


sub collect {
  my $this = shift;
  my $snmp = shift;
  
  require Net::SNMP;
  require RRDTool::OO;
  my %things = (
        totalreal => '1.3.6.1.4.1.2021.4.5.0',
        freereal => '1.3.6.1.4.1.2021.4.6.0',
        buffered => '1.3.6.1.4.1.2021.4.14.0',
        cached => '1.3.6.1.4.1.2021.4.15.0',
        totalswap => '1.3.6.1.4.1.2021.4.3.0',
        freeswap => '1.3.6.1.4.1.2021.4.4.0'
        );
  
  my %values;
  foreach my $thing (keys %things) {
    my $oid = $things{$thing};
    my $result = $snmp->get_request(-varbindlist => [$oid]);
    my $value = 0;
    if ($result) {
      $value = $result->{$oid};
    }
    $values{$thing} = $value;
  }
    
  return $this->{rrd}->update(
        values => {
            used => $values{totalreal} - $values{freereal},
            buffered => $values{buffered},
            cached => $values{cached},
            free => $values{freereal},
            swapused => $values{totalswap} - $values{freeswap}
        }
  );
}

sub plot {
  my $this = shift;
  my $dir = shift;
  my $period = shift;
  my $leg = shift;
  
  my %things = (
        mused => ['area', 'EB9C48', '', 'Used', 'AVERAGE', '%10.2lf Mb', 'used,1024,/'],
        mbuffered => ['stack', '7648EB', '', 'Buffered', 'AVERAGE', '%10.2lf Mb', 'buffered,1024,/'],
        mcached => ['area', '48C3EB', '', 'Cached', 'AVERAGE', '%10.2lf Mb', 'cached,1024,/'],
        mfree => ['stack', '54EB48', '', 'Free', 'AVERAGE', '%10.2lf Mb', 'free,1024,/'],
        mswapused => ['line', 'FF0000', '', 'Swap used', 'AVERAGE', '%10.2lf Mb', 'swapused,1024,/']
   );
  my @order = ('mused', 'mfree','mcached', 'mbuffered', 'mswapused');
  
  my $legend = "\t\t           Last\t      Average\t\t  Max\\n";
  return RRD::Generic::plot('memory', $dir, $period, $leg, 'Memory usage [Mb]', 0, 100, $this->{rrd}, \%things, \@order, $legend);
}
1;