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

package          StatsClient;
require          Exporter;
require          ReadConfig;
require			  SockClient;

use strict;
our @ISA        = "SockClient";


sub new {
  my $class = shift;
  
  my %msgs = ();
  
  my $spec_this = {
     %msgs => (),
     currentid => 0,
     socketpath => '',
     timeout => 5,
     set_timeout => 5,
     get_timeout => 120,
  };
  
  my $conf = ReadConfig::getInstance();
  $spec_this->{socketpath} = $conf->getOption('VARDIR')."/run/statsdaemon.sock";
  
  my $this = $class->SUPER::new($spec_this);
  
  bless $this, $class;
  return $this;
}

sub getValue {
	my $this = shift;
	my $element = shift;
	
	$this->{timeout} = $this->{get_timeout};
	my $ret = $this->query('GET '.$element);
	return $ret;
}

sub addValue {
	my $this = shift;
	my $element = shift;
	my $value = shift;
	
    $this->{timeout} = $this->{set_timeout};
	my $ret = $this->query('ADD '.$element.' '.$value);
	return $ret;
}

sub addMessageStats {
    my $this = shift;
    my $element = shift;
    my $valuesh = shift;
    my %values = %{$valuesh};	
	
    my $final_ret = 'ADDED';
    my $nbmessages = 0;
    $values{'msg'} = 1; 
    $values{'clean'} = 1;  

    my @dirtykeys = ('spam', 'highspam', 'virus', 'name', 'other', 'content');
    foreach my $ckey (@dirtykeys) {
      if (defined($values{$ckey}) && $values{$ckey} > 0) {
         $values{'clean'} = 0;
         last;
      }
    }

    foreach my $key (%values) {
      if ($values{$key}) {
        my $ret = $this->addValue($element.":".$key, $values{$key});
        if ($key eq 'msg' && $ret =~ /^ADDED\s+(\d+)/) {
          $nbmessages = $1;
        }
        if ($ret !~ /^ADDED/) {
          $final_ret = $ret;
    	}
      }
     }
	
     return $final_ret." ".$nbmessages;
}

sub setTimeout {
   my $this = shift;
   my $timeout = shift;
   
   return 0 if ($timeout !~ m/^\d+$/);
   $this->{timeout} = $timeout;
   return 1;
}

sub logStats {
   my $this = shift;
   
   my $query = 'STATS';
   return $this->query($query);
}

1;
