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

package          RRD::Messages;
require          Exporter;

use strict;
use lib qw(/usr/rrdtools/lib/perl/);
require RRD::Generic;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(New collect plot);
our $VERSION    = 1.0;


sub New {
  my $statfile = shift;
  $statfile = $statfile."/messages.rrd";
  my $reset = shift;    
#    use Log::Log4perl qw(:easy);
#    Log::Log4perl->easy_init({
#        level    => $INFO, 
#       category => 'rrdtool',
#        layout   => '%m%n',
#    });
    
  my %things = (
           bytes => ['GAUGE', 'LAST'],
           msgs => ['GAUGE', 'LAST'],
           spams => ['GAUGE', 'LAST'],
           pspams => ['GAUGE', 'AVERAGE'],
           viruses => ['GAUGE', 'LAST'],
           pviruses => ['GAUGE', 'AVERAGE'],
           contents => ['GAUGE', 'LAST'],
           pcontents => ['GAUGE', 'AVERAGE'],
           cleans => ['GAUGE', 'LAST'],
           pcleans => ['GAUGE', 'AVERAGE']
         );

  my $rrd = RRD::Generic::create($statfile, \%things, $reset);
  

  my $this = {
  	 statfile => $statfile,
  	 rrd => $rrd
  };
  
  return bless $this, "RRD::Messages";
}


sub collect {
  my $this = shift;
  my $snmp = shift;
  
  require Net::SNMP;
  require RRDTool::OO;
  
  my $oid = '1.3.6.1.4.1.2021.8.1.101.10';
  
  my $result = $snmp->get_request(-varbindlist => [$oid]);
  my $value = '0|0|0|0|0|0|0|0|0|0';
  if ($result) {
    $value = $result->{$oid};
  }
  
  my @values = split('\|', $value);
  return $this->{rrd}->update(
        values => {
            bytes => $values[0],
            msgs => $values[1],
            spams => $values[2],
            pspams => $values[3],
            pviruses => $values[5],
            viruses => $values[4],
            contents => $values[6],
            pcontents => $values[7],
            cleans => $values[8],
            pcleans => $values[9]
         }
  );
  
}

sub plot {
  my $this = shift;
  my $dir = shift;
  my $period = shift;
  my $leg = shift;
  my %things = (
        msgs => ['line', '000000', '', 'Messages', 'LAST', '%10.0lf', ''],
        viruses => ['area', 'FF0000', '', 'Viruses', 'LAST', '%10.0lf', ''],
        contents => ['stack', 'EB9C48', '', 'Contents', 'LAST', '%10.0lf', ''],
        spams => ['stack', '6633FF', '', 'Spams', 'LAST', '%10.0lf', ''],
        cleans => ['stack', '54EB48', '', 'Cleans', 'LAST', '%10.0lf', '']
   );
  my @order = ('msgs', 'viruses', 'contents', 'spams', 'cleans');
  
  my $legend = "\t\t         Last\t Average\t\tMax\\n";
  RRD::Generic::plot('messages', $dir, $period, $leg, 'Messages stats', 0, 5, $this->{rrd}, \%things, \@order, $legend);

  %things = (
       pviruses => ['area', 'FF0000', '', 'Viruses', 'AVERAGE', '%10.2lf %%', ''],
       pcontents => ['stack', 'EB9C48', '', 'Content', 'AVERAGE', '%10.2lf %%', ''],
       pspams => ['stack', '6633FF', '', 'Spams', 'AVERAGE', '%10.2lf %%', ''],
       pcleans => ['stack', 'EEEEEE', '', 'Cleans', 'AVERAGE', '%10.2lf %%', '']  
  );
  @order = ('pviruses', 'pcontents', 'pspams', 'pcleans');
  $legend = "\t\t           Last\t     Average\t\tMax\\n";
  RRD::Generic::plot('pmessages', $dir, $period, $leg, 'Message type [%]', 0, 100, $this->{rrd}, \%things, \@order, $legend);
  return 1;
}
1;