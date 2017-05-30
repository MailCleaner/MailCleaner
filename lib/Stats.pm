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

package          Stats;
require          Exporter;
use strict;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(readFile);
our $VERSION    = 1.0;


sub readFile {
  my $file = shift;

  if (! -f $file) {
    return (0,0,0,0,0,0,0,0,0,0);
  }
  
  my $c_msgs = 0;
  my $c_spams = 0;
  my $c_highspams = 0;
  my $c_viruses = 0;
  my $c_names = 0;
  my $c_others = 0;
  my $c_bytes = 0;
  my $c_cleans = 0;
  my $c_users = 0;
  my $c_domains = 0;
  
  if (open COUNTFILE, $file) {
    
   while (<COUNTFILE>) {
        if (/^MSGS\ (\d+)$/) {
           $c_msgs = $1;
        }
        if (/^SPAMS\ (\d+)$/) {
           $c_spams = $1;
        }
        if (/^HIGHSPAMS\ (\d+)$/) {
           $c_highspams = $1;
        }
        if (/^VIRUSES\ (\d+)$/) {
           $c_viruses = $1;
        }
        if (/^NAMES\ (\d+)$/) {
           $c_names = $1;
        }
        if (/^OTHERS\ (\d+)$/) {
           $c_others = $1;
        }
        if (/^CLEANS\ (\d+)$/) {
           $c_cleans = $1;
        }
        if (/^BYTES\ (\d+)$/) {
           $c_bytes = $1;
        }
        if (/^USERS\ (\d+)$/) {
           $c_users = $1;
        }
        if (/^DOMAINS\ (\d+)$/) {
           $c_domains = $1;
        }
   }
  close COUNTFILE;
  return ($c_msgs, $c_spams, $c_highspams, $c_viruses, $c_names, $c_others, $c_cleans, $c_bytes, $c_users, $c_domains);
  }
}

1;
