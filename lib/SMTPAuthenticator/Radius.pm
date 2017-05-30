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

package          SMTPAuthenticator::Radius;
require          Exporter;
use strict;
use Authen::Radius;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(create authenticate);
our $VERSION    = 1.0;


sub create {
   my $server = shift;
   my $port = shift;
   my $params = shift;
   
   my $secret = '';
   my @fields = split /:/, $params;
   if ($fields[0]) {
     $secret = $fields[0];
   }
   
  
   if ($port < 1 ) {
     $port = 1645;
   }
   my $this = {
           error_text => "",
           error_code => -1,
           server => $server,
           port => $port,
           secret => $secret,
         };
         
  bless $this, "SMTPAuthenticator::Radius";
  return $this;
}

sub authenticate {
  my $this = shift;
  my $username = shift;
  my $password = shift;
 
  my $r = new Authen::Radius(Host => $this->{server}.":".$this->{port}, Secret => $this->{secret});
  
  if ($r) {
    if ( $r->check_pwd($username, $password) ) {
      $this->{'error_code'} = 0;
      $this->{'error_text'} = Authen::Radius::strerror;
      return 1;
    }
  }
  
  $this->{'error_code'} =  Authen::Radius::get_error;
  $this->{'error_text'} = Authen::Radius::strerror;
  return 0;
}


1;
