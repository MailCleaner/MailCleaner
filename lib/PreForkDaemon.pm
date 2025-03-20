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
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
#
#   Init script library for PreForkDaemon

package PreForkDaemon;

use v5.36;
use strict;
use warnings;
use utf8;

use POSIX;
use Sys::Hostname;
use Socket;
use Symbol;
use IPC::Shareable;
use Data::Dumper;
require Mail::SpamAssassin::Timeout;
require ReadConfig;
use Time::HiRes qw(gettimeofday tv_interval);

our $PROFILE = 1;
my (%prof_start, %prof_res) = ();


sub create($class,$daemonname,$conffilepath,$spec_thish)
{
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

    my $self = {
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
    $self->{shared} = {};

    # add specific options of child object
    foreach my $sk (keys %spec_this) {
        $self->{$sk} = $spec_this{$sk};
    }

    # replace with configuration file values
    if (open(my $CONFFILE, '<', $configfile)) {
        while (<$CONFFILE>) {
            chomp;
            next if /^\#/;
            if (/^(\S+)\s*\=\s*(.*)$/) {
                $self->{$1} = $2;
            }
        }
        close $CONFFILE;
    }

    bless $self, $class;

    $0 = $self->{name};
    return $self;
}

sub createShared($self)
{
    if ($self->{needshared}) {

        ## first, clear shared
        $self->clearSystemShared();

        my %options = (
             create     => 'yes',
             exclusive  => 0,
             mode       => 0644,
             destroy    => 0
        );

        my $glue = $self->{glue};
        my %sharedhash;
        # set shared memory
        tie %sharedhash, 'IPC::Shareable', $glue, { %options } or die "server: tie failed\n";
        $self->{shared} = \%sharedhash;
        $self->initShared(\%sharedhash);
        $self->{sharedcreated} = 1;
    }
    return 1;
}

# global variables
my %children = ();  # keys are current child process IDs
my $children = 0;   # current number of children
my %shared;

sub REAPER($self)
{
    $SIG{CHLD} = \&REAPER;
    my $pid = wait;
    $children --;
    delete $children{$pid};
}

sub HUNTSMAN($self)
{
    local($SIG{CHLD}) = 'IGNORE';

    while (! $self->{finishedforked}) {
        $self->logMessage('Not yet finished forking...');
        sleep 2;
    }

    for my $pid (keys %children) {
        kill 'INT', $pid;
        $self->logMessage("Child $pid shut down");
    }

    if ($self->{clearshared} > 0) {
        IPC::Shareable->clean_up_all;
    }
    $self->logMessage('Daemon shut down');
    exit;
}

sub logMessage($self,$message)
{
    $self->doLog($message);
}

sub logDebug($self,$message)
{
    $self->doLog($message) if ($self->{debug});
}

sub doLog($self,$message)
{
    my $LOGGERLOG;
    open($LOGGERLOG, '>>', $self->{logfile});
    if ( !defined(fileno($LOGGERLOG))) {
        open($LOGGERLOG, '>>', "/tmp/".$self->{logfile});
        $| = 1;
    }
    my $date=`date "+%Y-%m-%d %H:%M:%S"`;
    chomp($date);
    print $LOGGERLOG "$date (".$$."): $message\n";
    close $LOGGERLOG;
}

sub initDaemon($self)
{
    $self->logMessage('Initializing Daemon');
    # first daemonize
    my $pid = fork;
    if ($pid) {
        my $cmd = "echo $pid > ".$self->{pidfile};
        `$cmd`;
    }
    exit if $pid;
    die "Couldn't fork: $!" unless defined($pid);
    $self->logMessage('Deamonized');

    ## preForkHook
    $self->preForkHook();

    # and then fork children
    $self->forkChildren();

    return 0;
}

sub forkChildren($self)
{
    # Fork off our children.
    for (1 .. $self->{prefork}) {
         $self->makeNewChild();
         sleep $self->{interval};
    }

    # Install signal handlers.
    $SIG{CHLD} = sub { $self->REAPER(); };
    $SIG{INT}  = $SIG{TERM} = sub { $self->HUNTSMAN(); };

    $self->{finishedforked} = 1;
    # And maintain the population.
    while (1) {
        sleep;    # wait for a signal (i.e., child's death)
        for (my $i = $children; $i < $self->{prefork}; $i++) {
            $self->makeNewChild(); # top up the child pool
        }
    }
}

sub makeNewChild($self)
{
    my $pid;
    my $sigset;

    # block signal for fork
    $sigset = POSIX::SigSet->new(SIGINT);
    sigprocmask(SIG_BLOCK, $sigset) or die "Can't block SIGINT for fork: $!\n";

    die "fork: $!" unless defined ($pid = fork);

    if ($pid) {
        # Parent records the child's birth and returns.
        sigprocmask(SIG_UNBLOCK, $sigset) or die "Can't unblock SIGINT for fork: $!\n";
        $children{$pid} = 1;
        $children++;
        return;
    } else {
        # Child can *not* return from this subroutine.
        $SIG{INT} = 'DEFAULT';            # make SIGINT kill us as it did before

        # unblock signals
        sigprocmask(SIG_UNBLOCK, $sigset) or die "Can't unblock SIGINT for fork: $!\n";

        # get shared memory
        if ($self->{needshared} && $self->{sharedcreated}) {
            my %options = (
                create      => 0,
                exclusive   => 0,
                mode        => 0644,
                destroy     => 0,
            );
            my $glue = $self->{glue};
            # set shared memory
            tie %shared, 'IPC::Shareable', $glue, { %options }; # or die "server: tie failed\n";
            $self->{shared} = \%shared;
        }

        $SIG{ALRM} = sub { $self->exitChild(); };
        alarm 10;
        ## mainLoopHook
        $self->mainLoopHook();

        # tidy up gracefully and finish

        # this exit is VERY important, otherwise the child will become
        # a producer of more and more children, forking yourself into
        # process death.
        exit;
    }
}

sub clearSystemShared($self)
{
    my $cmd = "ipcrm -M ".$self->{gluevalue};
    `$cmd 2>&1 > /dev/null`;
    $cmd = "ipcrm -S ".$self->{gluevalue};
    `$cmd 2>&1 > /dev/null`;

    sleep 2;
}

sub preForkHook($self)
{
    $self->logMessage('No preForkHook redefined, using default one...');
    return 1;
}


sub mainLoopHook($self)
{
    while(1) {
        sleep 5;
        $self->logMessage('No mainLoopHook redefined, waiting in default loop...');
    }
    return 1;
}

sub exit($self)
{
     $self->logMessage('Exit called');
     $self->logMessage('...');

     my $ppid = `cat $self->{pidfile}`;
     kill 'INT', $ppid;
     return 1;
}

sub exitChild($self)
{
    return;
}

sub profile_start($var)
{
    return unless $PROFILE;
    $prof_start{$var} = [gettimeofday];
}

sub profile_stop($var)
{
    return unless $PROFILE;
    return unless defined($prof_start{$var});
    my $interval = tv_interval ($prof_start{$var});
    my $time = (int($interval*10000)/10000);
    $prof_res{$var} = $time;
    return $time;
}

sub profile_output
{
    return unless $PROFILE;
    my $out = "";
    foreach my $var (keys %prof_res) {
        $out .= " ($var:".$prof_res{$var}."s)";
    }
    print $out;
}

1;
