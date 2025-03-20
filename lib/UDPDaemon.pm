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

package UDPDaemon;

use v5.36;
use strict;
use warnings;
use utf8;

require ReadConfig;
use POSIX;
use Sys::Hostname;
use Socket;
use Symbol;
use IO::Socket::INET;
require Mail::SpamAssassin::Timeout;
use lib_utils qw( open_as );
use Carp qw ( confess );

our $LOGGERLOG;

sub create($class,$daemonname,$conffilepath)
{
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

    my $self = {
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
    if (open(my $CONFFILE, '<', $configfile)) {
        while (<$CONFFILE>) {
            chomp;
            next if /^\#/;
            if (/^(\S+)\ ?\=\ ?(.*)$/) {
                if (defined($self->{$1})) {
                    $self->{$1} = $2;
                }
            }
        }
        close $CONFFILE;
    }

    bless $self, $class;
    return $self;
}

sub logMessage($self,$message)
{
    if ($self->{debug}) {
        if ( !fileno($LOGGERLOG) ) {
            confess "Failed to open log file: /tmp".$self->{logfile}.": $!\n" unless ( $LOGGERLOG = ${open_as("/tmp".$self->{logfile}, '>>', 0664, 'mailscanner:mailcleaner')} );
            $| = 1;
        }
        my $date=`date "+%Y-%m-%d %H:%M:%S"`;
        chomp($date);
        print $LOGGERLOG "$date: $message\n";
    }
}

######
## startDaemon
######
sub startDaemon($self)
{
    confess "Failed to open log file: $self->{logfile}: $!\n" unless ( $LOGGERLOG = ${open_as($self->{logfile}, '>>', 0664, 'mailscanner:mailcleaner')} );

    my $pid = fork();
    if (!defined($pid)) {
        die "Couldn't fork: $!";
    }
    if ($pid) {
        exit;
    } else {
        # Dameonize
        POSIX::setsid();

        $self->logMessage("Starting Daemon");

        $SIG{INT} = $SIG{TERM} = $SIG{HUP} = $SIG{ALRM} = sub { $self->parentGotSignal(); };

        #alarm $self->{daemontimeout};
        $0 = $self->{'name'};
        $self->initDaemon();
        $self->launchChilds();
        until ($self->{time_to_die}) {};
    }
    exit;
}

sub parentGotSignal($self)
{
    $self->{time_to_die} = 1;
}


sub reaper($self)
{
    $self->logMessage("Got child death...");
    $SIG{CHLD} = sub { $self->reaper(); };
    my $pid = wait;
    $self->{children}--;
    delete $self->{childrens}{$pid};
    if ($self->{time_to_die} < 1 ) {
        $self->logMessage("Not yet dead.. relauching new child");
        $self->makeChild();
    }
}

sub huntsMan($self)
{
    local($SIG{CHLD}) = 'IGNORE';
    $self->{time_to_die} = 1;
    $self->logMessage("Shutting down childs");
    kill 'INT' => keys %{$self->{childrens}};
    $self->logMessage("Daemon shut down");
    exit;
}

sub initDaemon($self)
{
    $self->logMessage("Initializing Daemon");
    $self->{server} = IO::Socket::INET->new(
        LocalAddr => '127.0.0.1',
        LocalPort => $self->{port},
        Proto     => 'udp'
    ) or die "Couldn't be an udp server on port ".$self->{port}." : $@\n";

    $self->logMessage("Listening on port ".$self->{port});

    return 0;
}

sub launchChilds($self)
{
    for (1 .. $self->{prefork}) {
        $self->logMessage("Launching child ".$self->{children}." on ".$self->{prefork}."...");
        $self->makeChild();
    }
    # Install signal handlers
    $SIG{CHLD} = sub { $self->reaper(); };
    $SIG{INT} = sub { $self->huntsMan(); };

    while (1) {
        sleep;
        $self->logMessage("Child death... still: ".$self->{children});
        for (my $i = $self->{children}; $i < $self->{prefork}; $i++) {
            $self->makeChild();
        }
    }
}

sub makeChild($self)
{
    my $pid;
    my $sigset;

    if ($self->{time_to_die} > 0) {
        $self->logMessage("Not creating child because shutdown requested");
        exit;
    }
    # block signal for fork
    $sigset = POSIX::SigSet->new(SIGINT);
    sigprocmask(SIG_BLOCK, $sigset) or die "Can't block SIGINT for fork: $!\n";

    die "fork: $!" unless defined ($pid = fork);

    if ($pid) {
        # Parent records the child's birth and returns.
        sigprocmask(SIG_UNBLOCK, $sigset) or die "Can't unblock SIGINT for fork: $!\n";
        $self->{childrens}{$pid} = 1;
        $self->{children}++;
        $self->logMessage("Child created with pid: $pid");
        return;
    } else {
        # Child can *not* return from this subroutine.
        $SIG{INT} = sub { };

        # unblock signals
        sigprocmask(SIG_UNBLOCK, $sigset) or die "Can't unblock SIGINT for fork: $!\n";

        $self->logMessage("In child listening...");
        $self->listenForQuery();
        exit;
    }
}


sub listenForQuery($self)
{
    my $message;
    my $serv = $self->{server};
    my $MAXLEN = 1024;

    $self->{'lastdump'} = time();
    my $datas;
    while (my $cli = $serv->recv($datas, $MAXLEN)) {
        my($cli_add, $cli_port) =  sockaddr_in($serv->peername);
        $self->manageClient($cli, $cli_port, $datas);
        my $time = int(time());
    }
}

sub manageClient($self,$cli,$cli_add,$datas)
{
    alarm $self->{daemontimeout};

    if ($datas =~ /^EXIT/) {
        $self->logMessage("Received EXIT command");
        $self->huntsMan();
        exit;
    }
    my $query .= $datas;
    chomp($query);
    if ($query =~ /^HELO\ (\S+)/) {
        $self->{server}->send("NICE TO MEET YOU: $1\n");
        #$self->logMessage("Command HELO answered");
    } elsif ($query =~ /^NULL/) {
        $self->{server}->send("\n");
        #$self->logMessage("Command NULL answered");
    } else {
        my $result = $self->processDatas($datas);
        $self->{server}->send("$result\n");
    }
}

###########################
## client call

sub exec($self,$command)
{
    my $res = "NORESPONSE";
    my $t = Mail::SpamAssassin::Timeout->new({ secs => $self->{clienttimeout} });
    $t->run( sub { $res = $self->queryDaemon($command);  });

    return "TIMEDOUT" if ($t->timed_out());

    return $res;
}

sub queryDaemon($self,$query)
{
    my $socket;
    if ( $socket = IO::Socket::INET->new(
        PeerAddr => '127.0.0.1',
        PeerPort => $self->{port},
        Proto    => "udp")
    ) {
        $socket->send($query."\n");
        my $MAXLEN  = 1024;
        my $response;

        $! = 0;

        $socket->recv($response, $MAXLEN);
        if ($! !~ /^$/) {
            return "NODAEMON";
        }
        my $res = $response;
        chomp($res);
        return $res;
    }
    return "NODAEMON";
}

sub timedOut($self)
{
    exit();
}

1;
