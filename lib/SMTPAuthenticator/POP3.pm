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

package          SMTPAuthenticator::POP3;
require          Exporter;
use strict;
use Mail::POP3Client;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(create authenticate);
our $VERSION    = 1.0;


sub create {
   my $server = shift;
   my $port = shift;
   my $params = shift;
   
   my $use_ssl = 0;
   if ($params =~ /^[01]$/) {
     $use_ssl = $params;
   }
   
   if ($port < 1 ) {
     $port = 110;
   }
   my $this = {
           error_text => "",
           error_code => -1,
           server => $server,
           port => $port,
           use_ssl => $use_ssl
         };
         
  bless $this, "SMTPAuthenticator::POP3";
  return $this;
}

sub authenticate {
  my $this = shift;
  my $username = shift;
  my $password = shift;

  my $pop = new Mail::POP3Client(
                               HOST     => $this->{server},
                               PORT     => $this->{port},
                               USESSL   => $this->{use_ssl},
                             );
  
  $pop->User( $username );
  $pop->Pass( $password );                
  my $code = $pop->Connect();
  
  if ($code > 0) {
    $this->{'error_code'} = 0;
    $this->{'error_text'} = "";
    #print "code: $code => ".$pop->Message()."\n";
    return 1;
  }
  $pop->Message();

  $this->{'error_code'} = $code;
  $this->{'error_text'} = $pop->Message();
  return 0;
}


1;
