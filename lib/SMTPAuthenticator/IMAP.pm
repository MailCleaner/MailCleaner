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

package          SMTPAuthenticator::IMAP;
require          Exporter;
use strict;

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
     $port = 143;
   }
   my $this = {
           error_text => "",
           error_code => -1,
           server => $server,
           port => $port,
           use_ssl => $use_ssl
         };
         
  bless $this, "SMTPAuthenticator::IMAP";
  return $this;
}

sub authenticate {
  my $this = shift;
  my $username = shift;
  my $password = shift;

  my $imap;
  if ($this->{use_ssl}) {
    require Net::IMAP::Simple::SSL;
    $imap = new Net::IMAP::Simple::SSL($this->{server}.":".$this->{port});
  } else {
    require Net::IMAP::Simple;
    $imap = new Net::IMAP::Simple($this->{server}.":".$this->{port});
  }

  if ($imap && $imap->login( $username, $password )) {
     $imap->quit;
     $this->{'error_code'} = 0;
     $this->{'error_text'} = $imap->errstr;
     return 1;
  }
  
  if (!$imap) {
     $this->{'error_text'} = "Could not connect to ".$this->{server}.":".$this->{port}." ssl:".$this->{use_ssl};  
  } else {
     $this->{'error_text'} = $imap->errstr;
  }
  $this->{'error_code'} = 1;
  return 0;
}
1;