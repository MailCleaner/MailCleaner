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
##  PreForkTDaemon:
##    Provides a base class of a multithreaded daemon
##
##    Hooks to be implemented by children are:
##         mainLoopHook: main thread loop, should be infinite. All thread share the same code
##         preForkHook: code before threads forking, this will be global for all thread
##         postKillHook: code after threads are gone, typically used to clean up global stuff
##         statusHook: code expected to log or output some status data.
##    Threads termination is thrown by a TERM signal. 
##    Threads signal handling should be implemented in the mainLoopHook() of the children class.
#

package          PreForkTDaemon;

use threads ();
use threads::shared;
use strict;
use POSIX;
use Sys::Syslog;
require ReadConfig;
require ConfigTemplate;
use Proc::ProcessTable;
use Time::HiRes qw(gettimeofday tv_interval);

my $PROFILE = 1;
my ( %prof_start, %prof_res ) = ();
my $daemoncounts_ = &share( {} );
my %log_prio_levels = ( 'error' => 0, 'info' => 1, 'debug' => 2 );

sub create {
    my $class        = shift;
    my $daemonname   = shift;
    my $conffilepath = shift;
    my $spec_thish   = shift;
    my %spec_this;
    if ($spec_thish) {
        %spec_this = %$spec_thish;
    }

    $daemoncounts_->{'starttime'} = time();

    my $conf = ReadConfig::getInstance();
    if ( !$daemonname ) {
        $daemonname = 'defautThreadedDaemon';
    }
    if ( !$conffilepath ) {
        $conffilepath = 'etc/mailcleaner/' . $daemonname . ".cf";
    }
    my $configfile = $conf->getOption('SRCDIR') . "/" . $conffilepath;

    ## default values
    my $pidfile = $conf->getOption('VARDIR') . "/run/$daemonname.pid";
    my $logfile =
      $conf->getOption('VARDIR') . "/log/mailcleaner/$daemonname.log";
    my $prefork = 5;
    my $debug   = 0;
    my $leaving = 0;

    my $this = {
        name                 => $daemonname,
        prefork              => $prefork,
        allthreadsrestartin  => 10,
        interval             => 3,
        daemonize            => 1,
        pidfile              => $pidfile,
        configfile           => $configfile,
        uid                  => 0,
        runasuser            => '',
        gid                  => 0,
        runasgroup           => '',
        syslog_progname      => '',
        syslog_facility      => '',
        debug                => $debug,
        clean_thread_exit    => 1,
        log_sets             => 'all',
        log_priority         => 'info',
        logfile              => $logfile,
        time_before_hardkill => 5,
        pidfile              => $pidfile
    };
    bless $this, 'PreForkTDaemon';

    # add specific options of child object
    foreach my $sk ( keys %spec_this ) {
        $this->{$sk} = $spec_this{$sk};
    }

    ## add log_sets
    foreach my $set ( split( /,/, $this->{log_sets} ) ) {
        push @{ $this->{'logged_sets'} }, $set;
    }

    # do we need to dump configuration
    my $file  = $this->{configfile};
    my $tfile = $file . "_template";
    if ( -f $tfile ) {
        my $template = ConfigTemplate::create( $tfile, $file );
        my $ret = $template->dump();
    }
    
    # replace with configuration file values
    if ( open CONFFILE, $this->{configfile} ) {
        while (<CONFFILE>) {
            chomp;
            next if /^\#/;
            if (/^(\S+)\s*\=\s*(.*)$/) {
                $this->{$1} = $2;
            }
        }
        close CONFFILE;
    }

    ## make sure we have the correct owners for critical files:
    foreach my $file (('pidfile', 'logfile', 'socketpath')) {
    	if (defined($this->{$file}) && -f $this->{$file}) {
    		my $uid = getpwnam( $this->{'runasuser'});
    		my $gid = getgrnam( $this->{'runasgroup'});
    		chown $uid, $gid, $this->{$file};
    	}
    }
    
    $this->{log_prio_level} = $log_prio_levels{ $this->{log_priority} };
      
    $this->{'uid'} =
      $this->{'runasuser'} ? getpwnam( $this->{'runasuser'} ) : 0;
    $this->{'gid'} =
      $this->{'runasgroup'} ? getgrnam( $this->{'runasgroup'} ) : 0;

    ## set our process name
    $0 = $this->{name};
    if ( $this->{'syslog_progname'} eq '' ) {
        $this->{'syslog_progname'} = $this->{name};
    }

    ## init syslog if required
    if ( $this->{'syslog_facility'} ne '' ) {
        openlog( $this->{'syslog_progname'},
            'ndelay,pid,nofatal', $this->{'syslog_facility'} );
        $this->{'do_syslog'} = 1;
    }
    return $this;
}

sub initDaemon {
    my $this = shift;

    ## change user and group if needed
    if ( $this->{'gid'} ) {
        ( $(, $) ) = ( $this->{'gid'}, $this->{'gid'} );
        $( == $this->{'gid'} && $) == $this->{'gid'}
          or die "Can't set GID " . $this->{'gid'};
        $) = $this->{'gid'};
    }
    if ( $this->{'uid'} ) {
        ( $<, $> ) = ( $this->{'uid'}, $this->{'uid'} );
        $< == $this->{'uid'} && $> == $this->{'uid'}
          or die "Can't set UID " . $this->{'uid'};
    }
    $this->doLog(
        'Set UID to ' . $this->{'runasgroup'} . "(" . $< . ")",
        'daemon'
    );
    $this->doLog(
        'Set GID to ' . $this->{'runasgroup'} . "(" . $( . ")",
        'daemon'
    );

    my @pids = $this->readPidFile();

    if (@pids) {
        require Proc::ProcessTable;
        my $t = new Proc::ProcessTable;
        foreach my $p ( @{ $t->table } ) {
            foreach my $pid (@pids) {
                if ( $p->pid == $pid ) {
                    print STDOUT
                      "(already running) ";
                    exit 1;
                }
            }
        }
    }

    $this->doLog( 'Initializing Daemon', 'daemon' );

# first install some critical signal handler so that main script wont get killed
    $SIG{ALRM} =
      sub { $this->doLog( "Got alarm signal.. nothing to do", 'daemon' ); };
    $SIG{PIPE} = sub {
        $this->doLog( "Got PIPE signal.. nothing to do", 'daemon' );
    };

    if ( $this->{daemonize} ) {

        # first daemonize
        open STDIN,  '/dev/null';
        open STDOUT, '>>/dev/null';
        open STDERR, '>>/dev/null';
        my $pid = fork;
        if ($pid) {
            my $cmd = "echo $pid > " . $this->{pidfile};
            `$cmd`;
        }
        if ($pid) {
            $this->doLog( 'Deamonized with PID ' . $pid, 'daemon' );
        }
        exit if $pid;
        die "Couldn't fork: $!" unless defined($pid);
        setsid();
        umask 0;
    }
    else {
        my $pid = $$;
        my $cmd = "echo $pid > " . $this->{pidfile};
        `$cmd`;
    }

    $this->preForkHook();

    $this->forkChildren();
}

sub exitDaemon {
    my $this = shift;

    my $time_before_hardkill = $this->{time_before_hardkill};
    my @pids                 = $this->readPidFile();
    my $hardkilled           = 0;
    if (@pids) {
        while (<PIDFILE>) {
            if (/(\d+)/) {
                push @pids, $1;
            }
        }

        ## first send a TERM signal
        foreach my $pid (@pids) {
            kill 15, $pid;
        }
        sleep 1;
        my $start_ps = [gettimeofday];
        ## then wait for processes to die, or kill'em if wait is too long
        my $pidstillhere = 1;
        while ( $pidstillhere && @pids ) {
            my $t = new Proc::ProcessTable;
            $pidstillhere = 0;
            foreach my $p ( @{ $t->table } ) {
                foreach my $pid (@pids) {
                    if ( $p->pid == $pid ) {
                        if ( tv_interval($start_ps) > $time_before_hardkill ) {
                            print STDOUT "Hard killing process: "
                              . $p->pid . " ("
                              . $p->cmndline . ")\n";
                            $p->kill(9);
                            $hardkilled++;
                            next;
                        }
                        else {
                            $p->kill(15);
                            $pidstillhere = 1;
                        }
                    }
                }
            }
            sleep 1;
        }

        #if (@pids) {
        #   print "Terminated all process\n";
        #}
    }
    if ( -f $this->{pidfile} && !$hardkilled ) {
        unlink( $this->{pidfile} );
    }
}

sub exitAllDaemon {
    my $this = shift;

    my $pname = $this->{name};
    require Proc::ProcessTable;
    my $t   = new Proc::ProcessTable;
    my $pid = $$;
    foreach my $p ( @{ $t->table } ) {
        if ( $p->cmndline eq $pname && $p->pid != $pid ) {
            print "Killing process: "
              . $p->cmndline . " ("
              . $p->pid . ", "
              . $p->state . ")\n";
            $p->kill(9);
        }
    }
    return 1;
}

sub status {
    my $this = shift;
    $this->statusHook();
}

sub getDaemonCounts {
    my $this = shift;

    return $daemoncounts_;
}

sub readPidFile {
    my $this = shift;

    my @pids;
    if ( open( PIDFILE, $this->{pidfile} ) ) {
        while (<PIDFILE>) {
            if (/(\d+)/) {
                push @pids, $1;
            }
        }
    }
    return @pids;
}

sub forkChildren {
    my $this = shift;

    $SIG{'TERM'} = sub {
        $this->doLog(
            'Main thread got a TERM signal. Proceeding to shutdown...',
            'daemon' );

        foreach my $t ( threads->list(threads::running) ) {
            if ( $this->{clean_thread_exit} ) {
                $this->doLog( "Sending TERM signal to thread " . $t->tid,
                    'daemon' );
                $t->kill('TERM')
                  ; ## does not always work, TERM signal cannot interrupt accept() call in thread
            }
            else {
                $t->detach();
            }
        }

        $this->postKillHook();

        while ( threads->list() > 0 ) {
            my $thread_count = threads->list();
            $this->doLog( "Still $thread_count threads running...", 'daemon' );
            sleep 1;
        }

        while ( threads->list(threads::running) > 0 ) {
            my $thread_count = threads->list(threads::running);
            $this->doLog( "Still $thread_count threads running...", 'daemon' );
            sleep 1;
        }
        $this->doLog( "All threads finished!", 'daemon' );
        foreach my $t ( threads->list(threads::joinable) ) {
            $this->doLog( "Joining thread " . $t->tid, 'daemon' );
            my $res = $t->join();
        }

        ## let our child class a chance do clean up stuff
        $this->exitHook();

        $this->doLog( "All threads finished and joined. ", 'daemon' );
        $this->doLog( 'Bye !',                             'daemon' );
        $this->closeLog();
        exit();
    };

    my $leaving = 0;
    while (1) {
        if ($leaving) {
            last;
        }
        my $thread_count = threads->list(threads::running);
        for ( $thread_count .. $this->{prefork} - 1 ) {
            $this->makeNewChild();
            sleep $this->{interval};
        }

        $this->doLog(
            "Population check done ("
              . $thread_count
              . "), waiting "
              . $this->{'allthreadsrestartin'}
              . " seconds for next check..",
            'daemon', 'debug'
        );

        sleep $this->{'allthreadsrestartin'};
    }
    $this->doLog( "Error, in main thread neverland !", 'daemon', 'error' );
}

sub makeNewChild {
    my $this = shift;
    my $pid;
    my $sigset;

    my $threadnumber = $this->getNbThreads() + 1;
    $this->doLog(
        "Lauching new thread ($threadnumber/" . $this->{prefork} . ") ...",
        'daemon' );
    ## mainLoopHook
    my $t = threads->create( { 'void' => 1 }, sub { $this->mainLoopHook(); } );
}

#### Available Hooks
sub mainLoopHook {
    my $this = shift;

    while (1) {
        $this->doLog( 'In dummy main loop...', 'daemon' );
        sleep 5;
    }
}

sub preForkHook {
    my $this = shift;

    $this->doLog( 'No preForkHook redefined, using default one...', 'daemon' );
    return 1;
}

sub exitHook {
    my $this = shift;

    $this->doLog( 'No exitHook redefined, using default one...', 'daemon' );
    return 1;
}

sub postKillHook {
    my $this = shift;

    $this->doLog( 'No postKillHook redefined, using default one...', 'daemon' );
    return 1;
}

sub statusHook {
    my $this = shift;

    print "No status available for this daemon.\n";
    exit;
}

#### Threads tools
sub getNbThreads {
    my $this = shift;

    my @tlist = threads->list;
    if (@tlist) {
        $this->{nbthreads} = @tlist;
        return $this->{nbthreads};
    }
    return 0;
}

sub getThreadID {
    my $this = shift;

    my $t = threads->self;
    return $t->tid;
}

##### Log management
sub doLog {
    my $this      = shift;
    my $message   = shift;
    my $given_set = shift;
    my $priority  = shift;

    if ( !defined($priority) ) {
        $priority = 'info';
    }

    foreach my $set ( @{ $this->{logged_sets} } ) {
        if ( $set eq 'all' || !defined($given_set) || $set eq $given_set ) {
            if ( $log_prio_levels{$priority} <= $this->{log_prio_level} ) {
                $this->doEffectiveLog($message);
            }
            last;
        }
    }
}

sub doEffectiveLog {
    my $this    = shift;
    my $message = shift;

    foreach my $line ( split( /\n/, $message ) ) {
        if ( $this->{logfile} ne '' ) {
            $this->writeLogToFile($line);
        }
        if ( $this->{'syslog_facility'} ne '' && $this->{'syslog_progname'} ne '' ) {
            syslog( 'info', '(' . $this->getThreadID() . ') ' . $line );
        }
    }
}

sub writeLogToFile {
    my $this    = shift;
    my $message = shift;
    chomp($message);

    if ( $this->{logfile} eq '' ) {
        return;
    }

    my $LOCK_SH = 1;
    my $LOCK_EX = 2;
    my $LOCK_NB = 4;
    my $LOCK_UN = 8;
    $| = 1;

    if ( !defined( fileno(LOGGERLOG) ) || !-f $this->{logfile} ) {
        open LOGGERLOG, ">>" . $this->{logfile};
        if ( !defined( fileno(LOGGERLOG) ) ) {
            open LOGGERLOG, ">>/tmp/" . $this->{logfile};
            $| = 1;
        }
        $this->doLog( 'Log file has been opened, hello !', 'daemon' );
    }
    my ( $sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst ) =
      localtime(time);
    $mon++;
    $year += 1900;
    my $date = sprintf( "%d-%.2d-%.2d %.2d:%.2d:%.2d",
        $year, $mon, $mday, $hour, $min, $sec );
    flock( LOGGERLOG, $LOCK_EX );
    print LOGGERLOG "$date (" . $this->getThreadID() . ") " . $message . "\n";
    flock( LOGGERLOG, $LOCK_UN );
}

sub closeLog {
    my $this = shift;

    $this->doLog( 'Closing log file now.', 'daemon' );
    close LOGGERLOG;
    exit;
}

sub format_time {
    my $this = shift;
    my $time = shift;

    my $res     = '';
    my $hours   = int( $time / ( 60 * 60 ) );
    my $rest    = $time % ( 60 * 60 );
    my $minutes = int( $rest / 60 );
    my $seconds = $rest % 60;

    $res = $hours . "h " . $minutes . "m " . $seconds . "s";
    return $res;
}

##### profiling
sub profile_start {
    my $this = shift;

    return unless $PROFILE;
    my $var = shift;
    $prof_start{$var} = [gettimeofday];

}

sub profile_stop {
    my $this = shift;

    return unless $PROFILE;
    my $var = shift;
    return unless defined( $prof_start{$var} );
    my $interval = tv_interval( $prof_start{$var} );
    my $time     = ( int( $interval * 10000 ) / 10000 );
    $prof_res{$var} = $time;
    return $time;
}

sub profile_output {
    my $this = shift;

    return unless $PROFILE;
    my $out = "";
    foreach my $var ( keys %prof_res ) {
        $out .= " ($var:" . $prof_res{$var} . "s)";
    }
    $this->doLog($out);
}
1;
