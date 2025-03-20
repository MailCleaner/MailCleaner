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
##  SockTDaemon:
##    Provides implementation of a socket base multithreaded daemon, relying on PreForkTDaemon
##
##    Hooks to be implemented by children are:
##         initThreadHook: thread initialization implementation
##         exitThreadHook: thread exiting implementation. Cleanup on TERM signal.
##         dataRead: action on data received by socket. Expect answer to send back to client.

package SockTDaemon;

use v5.36;
use strict;
use warnings;
use utf8;

use threads;
use threads::shared;
use IO::Socket;
use IO::Select;
use Time::HiRes qw(gettimeofday tv_interval);
use SockClient;
require Exporter;
require PreForkTDaemon;

our @ISA = "PreForkTDaemon";

my %global_shared : shared;
my %daemoncounts_ : shared = ( 'starttime' => 0, 'stoptime' => 0, 'queries' => 0 );
my $server : shared;

sub new($class,$daemonname,$conffilepath,$spec_thish)
{
    my %spec_this;
    if ($spec_thish) {
        %spec_this = %$spec_thish;
    }
    if ( !$daemonname ) {
        $daemonname = 'defautSocketThreadedDaemon';
    }
    my @log_sets = ('socket');

    my $sockspec_this = {
        server               => '',
        tid                  => 0,
        socketpath           => '/tmp/' . $daemonname,
        timeout              => 5,
        clean_thread_exit    => 0,
        log_sets             => 'all',
        time_before_hardkill => 8,
        long_response_time   => 2
    };

    # add specific options of child object
    foreach my $sk ( keys %spec_this ) {
        $sockspec_this->{$sk} = $spec_this{$sk};
    }

    my $self = $class->SUPER::create( $daemonname, $conffilepath, $sockspec_this );

    $daemoncounts_{'realstarttime'} = time;

    $self->{socks_status} = ();
    $self->{sock_timer} = ();

    bless $self, 'SockTDaemon';
    return $self;
}

sub preForkHook($self)
{
    ## first remove socket if already present
    if ( -e $self->{socketpath} ) {
        unlink( $self->{socketpath} );
    }

    ## bind to socket
    $self->{server} = IO::Socket::UNIX->new(
        Local  => $self->{socketpath},
        Type   => SOCK_STREAM,
        Listen => 50,
    ) or die( "Cannot listen on socket: $self->{socketpath}: " . $@ . "\n" );

    $self->{server}->autoflush(1);
    chmod 0777, $self->{socketpath};
    $self->doLog( "Listening on socket " . $self->{socketpath}, 'socket' );

    return 1;
}

sub exitHook($self)
{
    close( $self->{server} );
    $self->doLog( "Listener socket closed", 'socket' );

    return 1;
}

sub postKillHook($self)
{
    $self->doLog( 'No postKillHook redefined...', 'socket' );
    close( $self->{server} );
    return 1;
}

sub mainLoopHook($self)
{
    my $t = threads->self;
    $self->{tid} = $t->tid;

    $SIG{'INT'} = $SIG{'KILL'} = $SIG{'TERM'} = sub {
        $self->doLog(
            "Thread " . $t->tid . " got TERM! Proceeding to shutdown thread...",
            'daemon'
        );
        ## give our child a chance to exit cleanly
        $self->exitThreadHook();

        threads->detach();
        $self->doLog( "Thread " . $t->tid . " detached.", 'daemon' );
        threads->exit();
        $self->doLog( "Huho... Thread " . $t->tid . " still working though...",
            'daemon', 'error' );
    };

    $self->initThreadHook();

    $SIG{ALRM} = sub {
        local $| = 1;
        print time, ": Caught SIGALRM in thread\n";
    };

    $self->doLog( "In SockTDaemon main loop", 'socket' );

    my $data;

    while ( my $client = $self->{server}->accept() ) {
        $self->{socks_status}{$client} = 'connected';

        if (\$client =~ m/REF(0x[a-f0-9]+)/  ) {
            print STDERR "Got conenction from: ".$1."\n";
        }
        my $client_on = 1;
        $SIG{'PIPE'} = sub {
            $self->doLog( "closing client socket, got PIPE signal", 'socket' );
            $self->{socks_status}{$client} .= ',PIPE received';
            if (defined($self->{sock_timer}{$client})) {
                my $interval = tv_interval($self->{sock_timer}{$client});
                my $time     = ( int( $interval * 10000 ) / 10000 );
                $self->{socks_status}{$client} .= " ($time s.)";
            }
            $self->doLog( 'connection closed by PIPE: '.$self->{socks_status}{$client}, 'socket' );
            delete($self->{socks_status}{$client});
            undef($self->{socks_status}{$client});
            delete($self->{sock_timer}{$client});
            undef($self->{sock_timer}{$client});
            $client->close();
            $client_on = 0;
        };
        my $rv = $client->recv( $data, 1024, 0 );
        $self->{socks_status}{$client} .= ",received data($data)";
        $self->{sock_timer}{$client} = [gettimeofday];
        if ( defined($rv) && length $data ) {

            $self->doLog( 'GOT some data: ' . $data, 'socket', 'debug' );
            $daemoncounts_{'queries'}++;
            my $result = '';
            if ( $data eq 'STATUS' ) {
                $result = $self->getStatus();
            }
            if ( $result eq '' ) {
                $result = $self->dataRead( $data, $self->{server} );
            }
            if ($self->{long_response_time} > 0) {
               my $interval = tv_interval($self->{sock_timer}{$client});
               my $time     = ( int( $interval * 10000 ) / 10000 );
               if ($time >= $self->{long_response_time}) {
                   $self->doLog( 'Long response detected: '.$self->{socks_status}{$client}." took: ".$time." s.");
               }
            }

            $self->{socks_status}{$client} .= ',data processed';
            ## only answer if client still here
            if ($client_on) {
                $client->send($result);
                $client->flush();
                $self->{socks_status}{$client} .= ',response sent';
                if (defined($self->{sock_timer}{$client})) {
                    my $interval = tv_interval($self->{sock_timer}{$client});
                    my $time     = ( int( $interval * 10000 ) / 10000 );
                    $self->{socks_status}{$client} .= " ($time s.)";
                }
                $self->doLog( 'connection closed by response sent: '.$self->{socks_status}{$client}, 'socket', 'debug' );
                delete($self->{socks_status}{$client});
                undef($self->{socks_status}{$client});
                delete($self->{sock_timer}{$client});
                undef($self->{sock_timer}{$client});
                close($client);
                $self->doLog( 'response sent, client socket closed ',
                    'socket', 'debug' );
            }
        } else {
            $self->{socks_status}{$client} .= ',end of data';
            if (defined($self->{sock_timer}{$client})) {
               my $interval = tv_interval($self->{sock_timer}{$client});
               my $time     = ( int( $interval * 10000 ) / 10000 );
               $self->{socks_status}{$client} .= " ($time s.)";
            }
            $self->doLog( 'connection closed by end of data: '.$self->{socks_status}{$client}, 'socket' );
            delete($self->{socks_status}{$client});
            delete($self->{sock_timer}{$client});
            close($client);
            $self->doLog( "closed client connection", 'socket' );
        }
    }
}

sub statusHook($self)
{
    my $client = SockClient->new( { 'socketpath' => $self->{socketpath} } );
    return $client->query('STATUS');
}

sub getStatus($self)
{
    my $counts = $self->getDaemonCounts();

    my $res      = "Status of daemon: " . $self->{name} . "\n";
    my $run_time = ( time() - $counts->{starttime} );
    $res .= "  Running time: " . $self->format_time($run_time) . "\n";
    $res .= "  Threads running: " . threads->list(threads::running) . "\n";
    $res .= "  Number of queries: " . $daemoncounts_{'queries'} . "\n";
    $self->doLog($res);
    return $res;
}

### Available hooks
sub initThreadHook($self)
{
    $self->doLog(
        'No initThreadHook redefined, using default one...',
        'socket'
    );
}

sub exitThreadHook($self)
{
    $self->doLog(
        'No exitThreadHook redefined, using default one...',
        'socket'
    );
}

1;
