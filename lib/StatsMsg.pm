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

package          StatsMsg;
require          Exporter;

use strict;

my @statdata_ = ('spam', 'highspam', 'virus', 'name', 'other', 'clean', 'bytes');

sub new {
  my $class = shift;
  
  my $this = {};
  
  foreach my $st (@statdata_) {
    $this->{$st} = 0;
  }
  
  $this->{'msgs'} = 1;
  
  bless $this, $class;
  return $this;
}

sub setStatus {
  my ($this, $isspam, $ishigh, $virusinfected, $nameinfected, $otherinfected, $size) = @_;
 
  if ($isspam) { $this->setAsSpam(); }
  if ($ishigh) { $this->setAsHighSpam(); }
  if ($virusinfected) { $this->setAsVirus(); }
  if ($nameinfected) { $this->setAsName(); }
  if ($otherinfected) { $this->setAsOther(); }
  $this->setBytes($size); 
}

sub setAsSpam {
  my $this = shift;
  
  $this->{'spam'} = 1;
}
sub setAsHighSpam {
  my $this = shift;
  
  $this->{'highspam'} = 1;
}
sub setAsVirus {
  my $this = shift;
  
  $this->{'virus'} = 1;
}
sub setAsName {
  my $this = shift;
  
  $this->{'name'} = 1;
}
sub setAsOther {
  my $this = shift;
  
  $this->{'other'} = 1;
}
sub setBytes {
  my $this = shift;
  my $bytes = shift;
  
  $this->{'bytes'} = $bytes;
}

sub getString {
  my $this = shift;
  
  $this->{'clean'} = 1;
  if ( $this->{'spam'} + $this->{'highspam'} + $this->{'virus'} + $this->{'name'} + $this->{'ohter'} > 0) {
    $this->{'clean'} = 0;
  }
  my $str = $this->{'msgs'}."|";
  foreach my $st (@statdata_) {
    $str .= $this->{$st}."|";
  }
  $str =~ s/\|$//;
  return $str; 
}

sub doUpdate {
  my $this = shift;
  my $client = shift;
  my $to = shift;
  my $update_domain = shift;
  my $update_global = shift;
  
  print STDERR "\ncalled: ".'ADD '.$to.' '.$this->getString().' '.$update_domain.' '.$update_global."\n";
  return $client->query('ADD '.$to.' '.$this->getString().' '.$update_domain.' '.$update_global);
}
1;
