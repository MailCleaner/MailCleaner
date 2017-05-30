#!/usr/bin/perl

package          PreForkDaemon;

use strict;
use POSIX;
use Sys::Hostname;
use Socket;
use Symbol;
use IPC::Shareable;
use Data::Dumper;
require Mail::SpamAssassin::Timeout;
require ReadConfig;
use Time::HiRes qw(gettimeofday tv_interval);

my $PROFILE = 1;
my (%prof_start, %prof_res) = ();


sub create {
  my $class = shift;
  my $daemonname = shift;
  my $conffilepath = shift;
  my $spec_thish = shift;
  my %spec_this = %$spec_thish;
  
  my $conf = ReadConfig::getInstance();
  my $configfile = $conf->getOption('SRCDIR')."/".$conffilepath;
  
  ## default values
  my $pidfile = $conf->getOption('VARDIR')."/run/$daemonname.pid";
  my $port = 10000;
  my $logfile = $conf->getOption('VARDIR')."/log/mailcleaner/$daemonname.log";
  my $daemontimeout = 86400;
  my $prefork = 5;
  my $debug = 0;
  
  my $this = {
  	     name => $daemonname,
         server => '',
         pidfile => $pidfile,
         logfile => $logfile,
         daemontimeout => $daemontimeout,
         debug => $debug,
         prefork => $prefork,
         basefork => 0,
         inexit => 0,
         needshared => 0,
         clearshared => 0,
         glue => 'ABCD',
         gluevalue => '0x44434241',
         sharedcreated => 0,
         finishedforked => 0,
         interval => 10
     };
  $this->{shared} = {};
  
  # add specific options of child object
  foreach my $sk (keys %spec_this) {
     $this->{$sk} = $spec_this{$sk};
  }    
  
  # replace with configuration file values
  if (open CONFFILE, $configfile) {
  	while (<CONFFILE>) {
  		chop;
  		next if /^\#/;
  		if (/^(\S+)\s*\=\s*(.*)$/) {
  		  $this->{$1} = $2;
  		}
  	}
  	close CONFFILE;
  }
 
  bless $this, $class;
  
  $0 = $this->{name};
  return $this;
}

sub createShared {
  my $this = shift;
 
  if ($this->{needshared}) {
   
    ## first, clear shared
    $this->clearSystemShared();
    
    my %options = (
       create    => 'yes',
       exclusive => 0,
       mode      => 0644,
       destroy   => 0
    );
  
    my $glue = $this->{glue};
    my %sharedhash;
    # set shared memory
    tie %sharedhash, 'IPC::Shareable', $glue, { %options } or die "server: tie failed\n";
    $this->{shared} = \%sharedhash;
    $this->initShared(\%sharedhash);
    $this->{sharedcreated} = 1;
  }
  return 1;
}

# global variables
my %children               = ();       # keys are current child process IDs
my $children               = 0;        # current number of children
my %shared;

sub REAPER {
    my $this = shift;         

    $SIG{CHLD} = \&REAPER;
    my $pid = wait;
    $children --;
    delete $children{$pid};
}

sub HUNTSMAN {
    my $this = shift;

    local($SIG{CHLD}) = 'IGNORE';
    
    while (! $this->{finishedforked}) {
      $this->logMessage('Not yet finished forking...');
      sleep 2;
    }
    
    for my $pid (keys %children) {
      kill 'INT', $pid;
      $this->logMessage("Child $pid shut down");
    }
#    kill 'INT' => keys %children;
    
    if ($this->{clearshared} > 0) {
      IPC::Shareable->clean_up_all;
    }
    $this->logMessage('Daemon shut down');
    exit;
}

sub logMessage {
  my $this = shift;
  my $message = shift;
  
  $this->doLog($message);
}

sub logDebug {
  my $this = shift;
  my $message = shift;
  
  if ($this->{debug}) {
    $this->doLog($message);
  }
}

sub doLog {
 my $this = shift;
 my $message = shift;
  
 open LOGGERLOG, ">>".$this->{logfile};
 if ( !defined(fileno(LOGGERLOG))) {
   open LOGGERLOG, ">>/tmp/".$this->{logfile};
   $| = 1;
  }
  my $date=`date "+%Y-%m-%d %H:%M:%S"`; 
  chop($date);
  print LOGGERLOG "$date (".$$."): $message\n";
  close LOGGERLOG;
}

sub initDaemon {
   my $this = shift;

   $this->logMessage('Initializing Daemon');
   # first daemonize 
   my $pid = fork;
   if ($pid) {
      my $cmd = "echo $pid > ".$this->{pidfile};
     `$cmd`;
   }
   exit if $pid;
   die "Couldn't fork: $!" unless defined($pid);
   $this->logMessage('Deamonized');
   
   ## preForkHook
   $this->preForkHook();
   
   # and then fork children
   $this->forkChildren();
   
   return 0;
}

sub forkChildren {
  my $this = shift;
  
  # Fork off our children.
  for (1 .. $this->{prefork}) {
     $this->makeNewChild();
     sleep $this->{interval};
  }

  # Install signal handlers.
  $SIG{CHLD} = sub { $this->REAPER(); };
  $SIG{INT}  = $SIG{TERM} = sub { $this->HUNTSMAN(); };

  $this->{finishedforked} = 1;
  # And maintain the population.
  while (1) {
    sleep;  # wait for a signal (i.e., child's death)
    for (my $i = $children; $i < $this->{prefork}; $i++) {
        $this->makeNewChild(); # top up the child pool
    }
  }
}

sub makeNewChild {
    my $this = shift;
    my $pid;
    my $sigset;
    
    # block signal for fork
    $sigset = POSIX::SigSet->new(SIGINT);
    sigprocmask(SIG_BLOCK, $sigset)
        or die "Can't block SIGINT for fork: $!\n";
    
    die "fork: $!" unless defined ($pid = fork);
    
    if ($pid) {
        # Parent records the child's birth and returns.
        sigprocmask(SIG_UNBLOCK, $sigset)
            or die "Can't unblock SIGINT for fork: $!\n";
        $children{$pid} = 1;
        $children++;
        return;
    } else {
        # Child can *not* return from this subroutine.
        $SIG{INT} = 'DEFAULT';      # make SIGINT kill us as it did before
    
        # unblock signals
        sigprocmask(SIG_UNBLOCK, $sigset)
            or die "Can't unblock SIGINT for fork: $!\n";
    
    
        # get shared memory    
        if ($this->{needshared} && $this->{sharedcreated}) {
          my %options = (
            create    => 0,
            exclusive => 0,
            mode      => 0644,
            destroy   => 0,
          );
          my $glue = $this->{glue};
          # set shared memory
          tie %shared, 'IPC::Shareable', $glue, { %options }; # or die "server: tie failed\n";
          $this->{shared} = \%shared;
        }
  
        ##
        $SIG{ALRM} = sub { $this->exitChild(); };
        alarm 10;
        ## mainLoopHook
        $this->mainLoopHook();
          
        # tidy up gracefully and finish
    
        # this exit is VERY important, otherwise the child will become
        # a producer of more and more children, forking yourself into
        # process death.
        exit;
    }
}

sub clearSystemShared() {
  my $this = shift;
 
  my $cmd = "ipcrm -M ".$this->{gluevalue};
  `$cmd 2>&1 > /dev/null`; 
  $cmd = "ipcrm -S ".$this->{gluevalue};
  `$cmd 2>&1 > /dev/null`; 
  
  sleep 2;
}

sub preForkHook() {
  my $this = shift;
  
  $this->logMessage('No preForkHook redefined, using default one...'); 
  return 1;
}


sub mainLoopHook() {
  my $this = shift;
  
  while(1) {
    sleep 5;
    $this->logMessage('No mainLoopHook redefined, waiting in default loop...');
  }
  return 1;
}

sub exit() {
   my $this = shift;
   
   $this->logMessage('Exit called');
   $this->logMessage('...');
   
   my $ppid = `cat $this->{pidfile}`;
   kill 'INT', $ppid;
   return 1;
}

sub exitChild {
  my $this = shift;
  
}
############################################

sub profile_start {
  return unless $PROFILE;
  my $var = shift;
  $prof_start{$var} = [gettimeofday];
  
}

sub profile_stop {
  return unless $PROFILE;
  my $var = shift;
  return unless defined($prof_start{$var});
  my $interval = tv_interval ($prof_start{$var});
  my $time = (int($interval*10000)/10000);
  $prof_res{$var} = $time;
  return $time;
}

sub profile_output {
  return unless $PROFILE;
  my $out = "";
  foreach my $var (keys %prof_res) {
  	$out .= " ($var:".$prof_res{$var}."s)";
  }
  print $out;
}
1;