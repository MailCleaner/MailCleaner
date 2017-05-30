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

package          UDPDaemon;

require          ReadConfig;
use strict;
use POSIX;
use Sys::Hostname;
use Socket;
use Symbol;
use IO::Socket::INET;
require Mail::SpamAssassin::Timeout;

sub create {
  my $class = shift;
  my $daemonname = shift;
  my $conffilepath = shift; 
  my $conf = ReadConfig::getInstance();
  my $configfile = $conf->getOption('SRCDIR')."/".$conffilepath;
  
  ## default values
  my $pidfile = $conf->getOption('VARDIR')."/run/$daemonname.pid";
  my $port = 10000;
  my $logfile = $conf->getOption('VARDIR')."/log/mailcleaner/$daemonname.log";
  my $daemontimeout = 86400;
  my $clientTimeout = 5;
  my $sockettimeout = 120;
  my $listenmax = 100;
  my $prefork = 1;
  my $debug = 1;
  my %childrens;
  
  my $this = {
  	     name => $daemonname,
         port => $port,
         server => '',
         pidfile => $pidfile,
         logfile => $logfile,
         daemontimeout => $daemontimeout,
         clienttimeout => $clientTimeout,
         sockettimeout => $sockettimeout,
         listenmax => $listenmax,
         debug => $debug,
         prefork => $prefork,
         children => 0,
         basefork => 0,
         inexit => 0,
         %childrens => (),
         time_to_die => 0,
         };
  
  # replace with configuration file values
  if (open CONFFILE, $configfile) {
  	while (<CONFFILE>) {
  		chop;
  		next if /^\#/;
  		if (/^(\S+)\ ?\=\ ?(.*)$/) {
  		  if (defined($this->{$1})) {
  		  	$this->{$1} = $2;
  		  }	
  		}
  	}
  	close CONFFILE;
  }
  
  bless $this, $class;
  return $this;
}

sub logMessage {
  my $this = shift;
  my $message = shift;
  
  if ($this->{debug}) {
  	if ( !defined(fileno(LOGGERLOG))) {
  	   open LOGGERLOG, ">>/tmp/".$this->{logfile};
  	   $| = 1;
  	}
    my $date=`date "+%Y-%m-%d %H:%M:%S"`; 
    chop($date);
    print LOGGERLOG "$date: $message\n";
  }
}

######
## startDaemon
######
sub startDaemon {
  my $this = shift;
  
  open LOGGERLOG, ">>".$this->{logfile};

  my $pid = fork();
  if (!defined($pid)) {
  	die "Couldn't fork: $!";
  }
  if ($pid) {
    exit;
   } else {
     # Dameonize
     POSIX::setsid();
          
     $this->logMessage("Starting Daemon");

     $SIG{INT} = $SIG{TERM} = $SIG{HUP} = $SIG{ALRM} = sub { $this->parentGotSignal(); };
     
     
     #alarm $this->{daemontimeout};
     $0 = $this->{'name'};
     $this->initDaemon();
     $this->launchChilds();
     until ($this->{time_to_die}) {
     };
   }
   exit;
}

sub parentGotSignal {
  my $this = shift;
  
  $this->{time_to_die} = 1;
}


sub reaper {
   my $this = shift;
   
   $this->logMessage("Got child death...");
   $SIG{CHLD} = sub { $this->reaper(); };
   my $pid = wait;
   $this->{children}--;
   delete $this->{childrens}{$pid};
   if ($this->{time_to_die} < 1 ) {
   	  $this->logMessage("Not yet dead.. relauching new child");
   	  $this->makeChild();
   }
}

sub huntsMan {  
	my $this = shift;
	local($SIG{CHLD}) = 'IGNORE';
	$this->{time_to_die} = 1;
    $this->logMessage("Shutting down childs");
	kill 'INT' => keys %{$this->{childrens}};
	$this->logMessage("Daemon shut down");
	exit;
}

sub initDaemon {
   my $this = shift;
   $this->logMessage("Initializing Daemon");
   $this->{server} = IO::Socket::INET->new(
   								  LocalAddr => '127.0.0.1',
   								  LocalPort => $this->{port},
   								  Proto     => 'udp')
     or die "Couldn't be an udp server on port ".$this->{port}." : $@\n";

   $this->logMessage("Listening on port ".$this->{port});
   
   return 0;
}

sub launchChilds {
  my $this = shift;
	
  for (1 .. $this->{prefork}) {
  	$this->logMessage("Launching child ".$this->{children}." on ".$this->{prefork}."...");
  	$this->makeChild();
  }
  # Install signal handlers
  $SIG{CHLD} = sub { $this->reaper(); };
  $SIG{INT} = sub { $this->huntsMan(); };
       
  while (1) {
	sleep;
	$this->logMessage("Child death... still: ".$this->{children});
	for (my $i = $this->{children}; $i < $this->{prefork}; $i++) {
     $this->makeChild();
	}
  }
}

sub makeChild {
  my $this = shift;	
  my $pid;
  my $sigset;
	
  if ($this->{time_to_die} > 0) {
  	$this->logMessage("Not creating child because shutdown requested");
  	exit;
  }
  # block signal for fork
  $sigset = POSIX::SigSet->new(SIGINT);
  sigprocmask(SIG_BLOCK, $sigset)
        or die "Can't block SIGINT for fork: $!\n";
    
  die "fork: $!" unless defined ($pid = fork);
    
  if ($pid) {
     # Parent records the child's birth and returns.
     sigprocmask(SIG_UNBLOCK, $sigset)
            or die "Can't unblock SIGINT for fork: $!\n";
     $this->{childrens}{$pid} = 1;
     $this->{children}++;
     $this->logMessage("Child created with pid: $pid");
     return;
   } else {
     # Child can *not* return from this subroutine.
     $SIG{INT} = sub { };
    
     # unblock signals
     sigprocmask(SIG_UNBLOCK, $sigset)
            or die "Can't unblock SIGINT for fork: $!\n";
   
     $this->logMessage("In child listening...");
     $this->listenForQuery();
     exit;
    }
}


sub listenForQuery {
  my $this = shift;
  my $message;
  my $serv = $this->{server};
  my $MAXLEN = 1024;
  
  $this->{'lastdump'} = time();
  my $datas;
  while (my $cli = $serv->recv($datas, $MAXLEN)) {
  	my($cli_add, $cli_port) =  sockaddr_in($serv->peername);
    $this->manageClient($cli, $cli_port, $datas);
    my $time = int(time());
  }
}

sub manageClient {
  my $this = shift;	
  my $cli = shift;
  my $cli_add = shift;
  my $datas = shift;
     
  alarm $this->{daemontimeout};
     
  #if ($cli_add ne "127.0.0.1") {
  #  close($cli);
  #  return;
  #}
     
  #$this->logMessage("Accepting connection");   
  	
  if ($datas =~ /^EXIT/) {
    $this->logMessage("Received EXIT command");
    $this->huntsMan();
    exit;
  }
  my $query .= $datas;
  chomp($query);
  if ($query =~ /^HELO\ (\S+)/) {
    $this->{server}->send("NICE TO MEET YOU: $1\n");
    #$this->logMessage("Command HELO answered");
  } elsif ($query =~ /^NULL/) {
    $this->{server}->send("\n");
    #$this->logMessage("Command NULL answered");
  } else {	
    my $result = $this->processDatas($datas);
    $this->{server}->send("$result\n");
  }
}

###########################
## client call

sub exec {
  my $this = shift;
  my $command = shift;
  
  my $res = "NORESPONSE";
  my $t = Mail::SpamAssassin::Timeout->new({ secs => $this->{clienttimeout} });
  $t->run( sub { $res = $this->queryDaemon($command);  });
  
  if ($t->timed_out()) { return "TIMEDOUT"; };
  
  return $res;
}

sub queryDaemon {
  my $this = shift;
  my $query = shift;
  
  my $socket;
   if ( $socket = IO::Socket::INET->new(
                              PeerAddr => '127.0.0.1',
                              PeerPort => $this->{port},
                              Proto    => "udp")) {
                                	
    $socket->send($query."\n");
    my $MAXLEN  = 1024;
    my $response;

    $! = 0;

    $socket->recv($response, $MAXLEN);
    if ($! !~ /^$/) {
    	return "NODAEMON";
    }
    my $res = $response;   
    chop($res);
    return $res;
   } 
   return "NODAEMON";
 }

sub timedOut {
  my $this = shift;
  exit();
}

1;
