#!/usr/bin/perl
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

package  SockClient;
require  Exporter;

use strict;
use IO::Socket;
use IO::Select;
use Time::HiRes qw(setitimer time);

sub new {
  my $class = shift;
  my $spec_thish = shift;
  my %spec_this = %$spec_thish;

  my $this = {
         timeout => 5,
         
         socketpath => '/tmp/'.$class,
  };
  
  # add specific options of child object
  foreach my $sk (keys %spec_this) {
     $this->{$sk} = $spec_this{$sk};
  } 
  
  bless $this, $class;
  return $this;
}

sub connect {
  my $this = shift;

  ## untaint some values
  if ($this->{socketpath} =~ m/^(\S+)/) {
    $this->{socketpath} = $1;
  }
  if ($this->{timeout} =~ m/^(\d+)$/) {
    $this->{timeout} = $1;
  }
 
  $this->{socket} = IO::Socket::UNIX->new(
                                Peer    => $this->{socketpath},
                                Type    => SOCK_STREAM,
                                Timeout => $this->{timeout} )
    or return 0;
    
  return 1;
}

sub query {
  my $this = shift;
  my $query = shift;
  
  my $sent = 0;
  my $tries = 1;

  $this->connect() or return '_NOSERVER';
  my $sock = $this->{socket};
  
  $sock->send($query) or return '_NOSERVER';
  $sock->flush();

  my $data = '';
  my $rv;
 # $SIG{ALRM} = $SIG{PIPE} = sub { die("Fucked by signal\n"); };
 # my $alarm = 0;
 # eval {
 #     my $h = Sys::SigAction::set_sig_handler( 'ALRM' ,sub { print STDERR "Caught SIGALRM in thread\n"; $alarm = 1; return '_TIMEOUT'; } );
 #    $SIG{ALRM} = sub {
 #      local $| = 1;
 #      print STDERR "Caught SIGALRM in thread\n";
 #      $sock->close();
 #      return '_TIMEOUT';
 #    };
 #    alarm $this->{timeout};
 #    $rv = $sock->recv($data, 1024, 0);
 #    alarm 0;    
 #};
 
  my $read_set = new IO::Select;
  $read_set->add($sock);
  my ($r_ready, $w_ready, $error) =  IO::Select->select($read_set, undef, undef, $this->{timeout});
   
  foreach my $s (@$r_ready) {
    my $buf;
    my $buft;
    while(  my $ret = $s->recv($buft, 1024, 0) ) {
      if (defined($buft)) {
        ## chop twice.. why not ?
        #chop $buf;
        #chop $buf;
        #close($sock);
        #return $buf;
        $buf .= $buft;
       } else {
        $read_set->remove($sock);
        close($sock);
        return '_CLOSED';
       }
     }
     close($sock);
     return $buf;
   }
  return '_TIMEOUT';
 
   
  if (defined($rv) && length $data) {
    chop($data);
    return $data;
  }
  close($sock);
}

1;
