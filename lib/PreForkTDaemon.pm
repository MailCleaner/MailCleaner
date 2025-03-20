#!/usr/bin/env perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
#   Copyright (C) 2025 John Mertz <git@john.me.tz>
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

package PreForkTDaemon;

use v5.36;
use strict;
use warnings;
use utf8;

use threads ();
use threads::shared;
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
our $LOGGERLOG;

sub create($class,$daemonname,$conffilepath,$spec_thish)
{
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

    my $self = {
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
    bless $self, 'PreForkTDaemon';

    # add specific options of child object
    foreach my $sk ( keys %spec_this ) {
        $self->{$sk} = $spec_this{$sk};
    }

    ## add log_sets
    foreach my $set ( split( /,/, $self->{log_sets} ) ) {
        push @{ $self->{'logged_sets'} }, $set;
    }

    # do we need to dump configuration
    my $file  = $self->{configfile};
    my $tfile = $file . "_template";
    if ( -f $tfile ) {
        my $template = ConfigTemplate::create( $tfile, $file );
        my $ret = $template->dump();
    }

    # replace with configuration file values
    if ( open(my $CONFFILE, '<', $self->{configfile}) ) {
        while (<$CONFFILE>) {
            chomp;
            next if /^\#/;
            if (/^(\S+)\s*\=\s*(.*)$/) {
                $self->{$1} = $2;
            }
        }
        close $CONFFILE;
    }

    ## make sure we have the correct owners for critical files:
    foreach my $file (('pidfile', 'logfile', 'socketpath')) {
        if (defined($self->{$file}) && -f $self->{$file}) {
            my $uid = getpwnam( $self->{'runasuser'}) || getpwnam('mailcleaner');
            my $gid = getgrnam( $self->{'runasgroup'}) || getgrnam('mailcleaner');
            chown $uid, $gid, $self->{$file};
        }
    }

    $self->{log_prio_level} = $log_prio_levels{ $self->{log_priority} };

    $self->{'uid'} = $self->{'runasuser'} ? getpwnam( $self->{'runasuser'} ) : 0;
    $self->{'gid'} = $self->{'runasgroup'} ? getgrnam( $self->{'runasgroup'} ) : 0;

    ## set our process name
    $0 = $self->{name};
    if ( $self->{'syslog_progname'} eq '' ) {
        $self->{'syslog_progname'} = $self->{name};
    }

    ## init syslog if required
    if ( $self->{'syslog_facility'} ne '' ) {
        openlog( $self->{'syslog_progname'},
            'ndelay,pid,nofatal', $self->{'syslog_facility'} );
        $self->{'do_syslog'} = 1;
    }
    return $self;
}

sub initDaemon($self)
{
    my $result = 'not started';

    ## change user and group if needed
    if ( $self->{'gid'} ) {
        ( $(, $) ) = ( $self->{'gid'}, $self->{'gid'} );
        $( == $self->{'gid'} && $) == $self->{'gid'}
          or die "Can't set GID " . $self->{'gid'};
        $) = $self->{'gid'};
    }
    if ( $self->{'uid'} ) {
        ( $<, $> ) = ( $self->{'uid'}, $self->{'uid'} );
        $< == $self->{'uid'} && $> == $self->{'uid'}
          or die "Can't set UID " . $self->{'uid'};
    }
    $self->doLog(
        'Set UID to ' . $self->{'runasgroup'} . "(" . $< . ")",
        'daemon'
    );
    $self->doLog(
        'Set GID to ' . $self->{'runasgroup'} . "(" . $( . ")",
        'daemon'
    );

    my @pids = $self->readPidFile();

    require Proc::ProcessTable;
    my $t = Proc::ProcessTable->new();
    my $match = 0;
    my @errors;
    foreach my $p ( @{ $t->table } ) {
        my $cmndline = $p->{'cmndline'};
        $cmndline =~ s/\s*$//g;
        if ($cmndline eq $self->{'name'}) {
            if ($p->{'pid'} == $$) {
                next;
            } elsif (scalar(grep(/$p->{'pid'}/,@pids))) {
                $match = $p->{'pid'};
            } else {
                $p->kill(9);
            }
        }
    }

    if ($match) {
        push @errors, "Found $match matching expected PID (already running).";
        return output("not started", @errors);
    }

    $self->doLog( 'Initializing Daemon', 'daemon' );

# first install some critical signal handler so that main script wont get killed
    $SIG{ALRM} =
      sub { $self->doLog( "Got alarm signal.. nothing to do", 'daemon' ); };
    $SIG{PIPE} = sub {
        $self->doLog( "Got PIPE signal.. nothing to do", 'daemon' );
    };

    if ( $self->{daemonize} ) {

        # first daemonize
        my $pid = fork;
        if ($pid) {
            # parent
            my $fh;
            if (open(my $fh, '>', $self->{pidfile})) {
                print $fh $pid;
                close $fh;
            } else {
                print STDERR "Unable to log PID to $self->{pidfile}: $!\n";
            }
            $self->doLog( 'Deamonized with PID ' . $pid, 'daemon' );
            sleep(1);
            foreach my $p ( @{ $t->table } ) {
                my $cmndline = $p->{'cmndline'};
                $cmndline =~ s/\s*$//g;
                if ($cmndline eq $self->{'name'}) {
                    if ($p->{'pid'} == $$) {
                        next;
                    } elsif ($p->{'pid'} == $pid) {
                        return output("started.",@errors);
                    }
                }
            }
            return output('Failed to start.',@errors);
            exit();
        } elsif ($pid == -1) {
            # failed
            $result = "not started.";
            push @errors, "Couldn't fork: $!";
            return output($result,@errors);
        } else {
            # Child now logs to JournalD
            #open STDIN, '<', '/dev/null';
            #open STDOUT, '>>', '/dev/null';
            #open STDERR, '>>', '/dev/null';
            setsid();
            umask 0;
        }
    } else {
        my $pid = $$;
        open(my $fh, '>>', $self->{pidfile});
        print $fh $pid;
        close $fh;
    }

    $self->preForkHook();
    $self->forkChildren();
}

sub exitDaemon($self)
{
    my $result = 'not stopped';
    my $time_before_hardkill = $self->{time_before_hardkill};

    my @errors;
    my @pids = $self->readPidFile();

    require Proc::ProcessTable;
    my $t = Proc::ProcessTable->new();

    my @running = ();
    my $match = 0;
    foreach my $p ( @{ $t->table } ) {
        my $cmndline = $p->{'cmndline'};
        $cmndline =~ s/\s*$//g;
        if ($cmndline eq $self->{'name'}) {
            if ($p->{'pid'} == $$) {
                next;
            }
            push @running, $p->{'pid'};
            if (scalar(grep(/$p->{'pid'}/,@pids))) {
                push @errors, "Active process detected ($p->{'pid'}). Killing... ";
                $match = $p->{'pid'};
            } else {
                push @errors, "Orphaned process detected ($p->{'pid'}). Killing... ";
            }

            my $pidstillhere = 1;
            my $start_ps = [gettimeofday];
            while ($pidstillhere) {
                if ( tv_interval($start_ps) > $time_before_hardkill ) {
                    $p->kill(9);
                    last;
                } else {
                    $p->kill(15);
                }
                sleep 1;
                $pidstillhere = 0;
                my $n = Proc::ProcessTable->new();
                foreach ( @{$t->table} ) {
                    if ($_->{'pid'} == $p->{'pid'}) {
                        $pidstillhere = 1;
                        last;
                    }
                }
            }
            if ($pidstillhere) {
                $errors[scalar(@errors)-1] .= "Failed.";
            } else {
                pop @running;
                $errors[scalar(@errors)-1] .= "Done.";
            }
        }
    }

    if (scalar @running) {
        push @errors, "Failed to stop all processes (" . join(', ',@running) . ") not stopped.";
    } elsif ($match) {
        $result = "stopped.";
        @errors = ();
    } else {
        push @errors, "No existing active process found.";
    }

    return output($result,@errors);
}

sub status($self)
{
    $self->statusHook();
}

sub getDaemonCounts($self)
{
    return $daemoncounts_;
}

sub readPidFile($self)
{
    my @pids;
    if ( open(my $PIDFILE, '<', $self->{pidfile}) ) {
        while (<$PIDFILE>) {
            if (/(\d+)/) {
                push @pids, $1;
            }
        }
        close $PIDFILE;
    }
    return @pids;
}

sub forkChildren($self)
{
    $SIG{'TERM'} = sub {
        $self->doLog(
            'Main thread got a TERM signal. Proceeding to shutdown...',
            'daemon' );

        foreach my $t ( threads->list(threads::running) ) {
            if ( $self->{clean_thread_exit} ) {
                $self->doLog( "Sending TERM signal to thread " . $t->tid,
                    'daemon' );
                $t->kill('TERM')
                  ; ## does not always work, TERM signal cannot interrupt accept() call in thread
            } else {
                $t->detach();
            }
        }

        $self->postKillHook();

        while ( threads->list() > 0 ) {
            my $thread_count = threads->list();
            $self->doLog( "Still $thread_count threads running...", 'daemon' );
            sleep 1;
        }

        while ( threads->list(threads::running) > 0 ) {
            my $thread_count = threads->list(threads::running);
            $self->doLog( "Still $thread_count threads running...", 'daemon' );
            sleep 1;
        }
        $self->doLog( "All threads finished!", 'daemon' );
        foreach my $t ( threads->list(threads::joinable) ) {
            $self->doLog( "Joining thread " . $t->tid, 'daemon' );
            my $res = $t->join();
        }

        ## let our child class a chance do clean up stuff
        $self->exitHook();

        $self->doLog( "All threads finished and joined. ", 'daemon' );
        $self->doLog( 'Bye !',                             'daemon' );
        $self->closeLog();
        exit();
    };

    my $leaving = 0;
    while (1) {
        if ($leaving) {
            last;
        }
        my $thread_count = threads->list(threads::running);
        for ( $thread_count .. $self->{prefork} - 1 ) {
            $self->makeNewChild();
            sleep $self->{interval};
        }

        $self->doLog(
            "Population check done ("
              . $thread_count
              . "), waiting "
              . $self->{'allthreadsrestartin'}
              . " seconds for next check..",
            'daemon', 'debug'
        );

        sleep $self->{'allthreadsrestartin'};
    }
    $self->doLog( "Error, in main thread neverland !", 'daemon', 'error' );
}

sub makeNewChild($self)
{
    my $pid;
    my $sigset;

    my $threadnumber = $self->getNbThreads() + 1;
    $self->doLog(
        "Lauching new thread ($threadnumber/" . $self->{prefork} . ") ...",
        'daemon' );
    ## mainLoopHook
    my $t = threads->create( { 'void' => 1 }, sub { $self->mainLoopHook(); } );
}

#### Available Hooks
sub mainLoopHook($self)
{
    while (1) {
        $self->doLog( 'In dummy main loop...', 'daemon' );
        sleep 5;
    }
}

sub preForkHook($self)
{
    $self->doLog( 'No preForkHook redefined, using default one...', 'daemon' );
    return 1;
}

sub exitHook($self)
{
    $self->doLog( 'No exitHook redefined, using default one...', 'daemon' );
    return 1;
}

sub postKillHook($self)
{
    $self->doLog( 'No postKillHook redefined, using default one...', 'daemon' );
    return 1;
}

sub statusHook($self)
{
    my @errors;

    my @pids = $self->readPidFile();
    my $time_before_hardkill = $self->{time_before_hardkill};

    require Proc::ProcessTable;
    my $t = Proc::ProcessTable->new();
    my @match;
    foreach my $p ( @{ $t->table } ) {
        my $cmndline = $p->{'cmndline'};
        $cmndline =~ s/\s*$//g;
        if ($cmndline eq $self->{'name'}) {
            if ($p->{'pid'} == $$) {
                next;
            } elsif (scalar(grep(/$p->{'pid'}/,@pids))) {
                push @match, $p->{'pid'};
            } else {
                push @errors, "Orphaned process detected ($p->{'pid'})";
            }
        }
    }

    if (scalar(@pids)) {
        if (scalar(@match) == scalar(@pids)) {
            if (scalar(@errors)) {
                return "running with errors.\n  " . join('\n  ',@errors);
            } else {
                return "running.";
            }
        } else {
            my @missing;
            foreach (@pids) {
                unless (scalar(grep(/$_/,@{ $t->table }))) {
                    push @missing, $_;
                }
            }
            if (scalar @missing) {
                push @errors, "Expected PIDs from PID file not found: " . join(', ',@missing);
            }
        }
    }

    if (scalar(@errors)) {
        return "not running, but errors found.\n  " . join('\n  ',@errors);
    } else {
        return "not running.";
    }
}

#### Threads tools
sub getNbThreads($self)
{
    my @tlist = threads->list;
    if (@tlist) {
        $self->{nbthreads} = @tlist;
        return $self->{nbthreads};
    }
    return 0;
}

sub getThreadID($self)
{
    my $t = threads->self;
    return $t->tid;
}

##### Log management
sub doLog($self,$message,$given_set=undef,$priority='info')
{
    foreach my $set ( @{ $self->{logged_sets} } ) {
        if ( $set eq 'all' || !defined($given_set) || $set eq $given_set ) {
            if ( $log_prio_levels{$priority} <= $self->{log_prio_level} ) {
                $self->doEffectiveLog($message);
            }
            last;
        }
    }
}

sub doEffectiveLog($self,$message)
{
    foreach my $line ( split( /\n/, $message ) ) {
        if ( $self->{logfile} ne '' ) {
            $self->writeLogToFile($line);
        }
        if ( $self->{'syslog_facility'} ne '' && $self->{'syslog_progname'} ne '' ) {
            syslog( 'info', '(' . $self->getThreadID() . ') ' . $line );
        }
    }
}

sub writeLogToFile($self,$message)
{
    chomp($message);

    if ( $self->{logfile} eq '' ) {
        return;
    }

    my $LOCK_SH = 1;
    my $LOCK_EX = 2;
    my $LOCK_NB = 4;
    my $LOCK_UN = 8;
    $| = 1;

    if ( !defined($LOGGERLOG) || !fileno($LOGGERLOG) ) {
        $self->openLog();
    }
    my ( $sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst ) = localtime(time);
    $mon++;
    $year += 1900;
    my $date = sprintf( "%d-%.2d-%.2d %.2d:%.2d:%.2d", $year, $mon, $mday, $hour, $min, $sec );
    flock( $LOGGERLOG, $LOCK_EX );
    print $LOGGERLOG "$date (" . $self->getThreadID() . ") " . $message . "\n";
    flock( $LOGGERLOG, $LOCK_UN );
}

sub openLog($self)
{
    if ( !defined( $self->{'logfile'}) || $self->{'logfile'} eq '' ) {
	      print STDERR "Module does not have a log file\n";
	      $LOGGERLOG = &STDERR();
    } else {
        unless (open($LOGGERLOG, '>>', $self->{'logfile'})) {
            print STDERR "Unable to open expected log $self->{logfile}\n";
		        # Use temporary log
		        my @path = split(/\//, $self->{'logfile'});
		        shift(@path);
		        my $file = pop(@path);
		        my $d = '/tmp';
		        foreach my $dir (@path) {
		            $d .= "/$dir";
		            unless ( -d $d ) {
		                unless (mkdir($d)) {
                        print STDERR "Cannot create $d: $!\n";
                        $d = '/tmp';
                        last;
                    }
                }
	          }
            open $LOGGERLOG, '>>', $d.'/'.$file;
            print STDERR "Logging to $d/$file\n";
        }
        $| = 1;
    }
    print $LOGGERLOG "Log file has been opened, hello !\n", 'daemon';
}

sub closeLog($self)
{
    $self->doLog( 'Closing log file now.', 'daemon' );
    close $LOGGERLOG;
    exit;
}

sub format_time($self,$time)
{
    my $res     = '';
    my $hours   = int( $time / ( 60 * 60 ) );
    my $rest    = $time % ( 60 * 60 );
    my $minutes = int( $rest / 60 );
    my $seconds = $rest % 60;

    $res = $hours . "h " . $minutes . "m " . $seconds . "s";
    return $res;
}

##### profiling
sub profile_start($self,$var)
{
    return unless $PROFILE;
    $prof_start{$var} = [gettimeofday];
}

sub profile_stop($self,$var)
{
    return unless $PROFILE;
    return unless defined( $prof_start{$var} );
    my $interval = tv_interval( $prof_start{$var} );
    my $time     = ( int( $interval * 10000 ) / 10000 );
    $prof_res{$var} = $time;
    return $time;
}

sub profile_output($self)
{
    return unless $PROFILE;
    my $out = "";
    foreach my $var ( keys %prof_res ) {
        $out .= " ($var:" . $prof_res{$var} . "s)";
    }
    $self->doLog($out);
}

sub output($result,@errors)
{
    if (scalar @errors) {
        print STDOUT "$result\n  " . join("\n  ",@errors) . "\n";
        return 1;
    } else {
        print STDOUT $result . "\n";
        return 0;
    }
}

1;
