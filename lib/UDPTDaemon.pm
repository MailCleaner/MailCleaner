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

package          UDPTDaemon;
require          Exporter;
require			  PreForkTDaemon;
use threads;
use threads::shared;
use IO::Socket;
use IO::Select;

our @ISA        = "PreForkTDaemon";

use strict;

my %global_shared : shared;

sub new {
  my $class = shift;
  my $init = shift;
  my $config = shift;
  my $spec_thish = shift;
  my %spec_this = %$spec_thish;
   
  my $udpspec_this = {
     server => '',
     port => -1,
     tid => 0,
     
     read_set => '',
     write_set => '',
  };
  # add specific options of child object
  foreach my $sk (keys %spec_this) {
     $udpspec_this->{$sk} = $spec_this{$sk};
  } 
  
  my $this = $class->SUPER::create($class, $config, $udpspec_this);
     
  bless $this, $class;
  return $this;
}

sub preForkHook() {
   my $this = shift;
   
   ## bind to UDP port
   $this->{server} = IO::Socket::INET->new(
   								  LocalAddr => '127.0.0.1',
   								  LocalPort => $this->{port},
   								  Proto     => 'udp',
   								  Timeout => 10)
     or die "Couldn't be an udp server on port ".$this->{port}." : $@\n";
   $this->{server}->autoflush ( 1 ) ;
   $this->logMessage("Listening on port ".$this->{port});
   
   return 1;
}

sub mainLoopHook() {
  my $this = shift;
  require Mail::SpamAssassin::Timeout;
   
  $this->logMessage("In UDPTDaemon main loop");
  
  my $read_set = new IO::Select;
  $read_set->add($this->{server}); 
 
  my $t = threads->self;
  $this->{tid} = $t->tid;
  
  my $data;
  while ($this->{server}->recv($data, 1024)) {
      my($port, $ipaddr) = sockaddr_in($this->{server}->peername);
      my $hishost = gethostbyaddr($ipaddr, AF_INET);
      chop($data);
      my $result =  $this->dataRead($data, $this->{server});
      $this->{server}->send($result."\n");
  }
#  while(1) {
#    my ($r_ready, $w_ready, $error) =  IO::Select->select($read_set, undef, undef, 5);
#    
#    foreach my $sock (@$r_ready) {
#      if ($sock == $this->{server}) {
#        my $new_sock = $sock->accept();
#        $read_set->add($new_sock);
#        $this->logDebug(" (".$this->{tid}.") Accepted new connection...");
#        my $buf = <$sock>;
#        if ($buf) {
#          ## chop twice.. why not ?
#          chop $buf;
#          #chop $buf;
#          my($cli_add, $cli_port) =  sockaddr_in($sock->peername);
#          my $result = $this->dataRead($buf, $sock);
#          $sock->send($result."\n");
#        } else {
#          $read_set->remove($sock);
#          close($sock);
#          $this->logDebug(" (".$this->{tid}.") Closed socket");
#        }
#    }
#      }
#  }
   
  return 1;
}

sub exitHook() {
  my $this = shift;
 
  close ($this->{server});
  $this->logMessage("Listener socket closed");
  return 1;
}
1;
