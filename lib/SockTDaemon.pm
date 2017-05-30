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
##  SockTDaemon:
##    Provides implementation of a socket base multithreaded daemon, relying on PreForkTDaemon
##
##    Hooks to be implemented by children are:
##         initThreadHook: thread initialization implementation
##         exitThreadHook: thread exiting implementation. Cleanup on TERM signal.
##         dataRead: action on data received by socket. Expect answer to send back to client.
##
#


package          SockTDaemon;
use threads;
use threads::shared;
use strict;
use IO::Socket;
use IO::Select;
use Time::HiRes qw(gettimeofday tv_interval);
use SockClient;
require Exporter;
require PreForkTDaemon;

our @ISA = "PreForkTDaemon";

my %global_shared : shared;
my %daemoncounts_ : shared =
  ( 'starttime' => 0, 'stoptime' => 0, 'queries' => 0 );
my $server : shared;

sub new {
    my $class        = shift;
    my $daemonname   = shift;
    my $conffilepath = shift;
    my $spec_thish   = shift;
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

    my $this =
      $class->SUPER::create( $daemonname, $conffilepath, $sockspec_this );

    $daemoncounts_{'realstarttime'} = time;

    $this->{socks_status} = ();
    $this->{sock_timer} = ();

    bless $this, 'SockTDaemon';
    return $this;
}

sub preForkHook() {
    my $this = shift;

    ## first remove socket if already present
    if ( -e $this->{socketpath} ) {
        unlink( $this->{socketpath} );
    }
    
    ## bind to socket
    $this->{server} = IO::Socket::UNIX->new(
        Local  => $this->{socketpath},
        Type   => SOCK_STREAM,
        Listen => 50,
      )
      or die(
        "Cannot listen on socket: " . $this->{socketpath} . ": " . $@ . "\n" );

    $this->{server}->autoflush(1);
    chmod 0777, $this->{socketpath};
    $this->doLog( "Listening on socket " . $this->{socketpath}, 'socket' );

    return 1;
}

sub exitHook() {
    my $this = shift;

    close( $this->{server} );
    $this->doLog( "Listener socket closed", 'socket' );

    return 1;
}

sub postKillHook {
    my $this = shift;

    $this->doLog( 'No postKillHook redefined...', 'socket' );
    close( $this->{server} );
    return 1;
}

sub mainLoopHook {
    my $this = shift;

    my $t = threads->self;
    $this->{tid} = $t->tid;

    $SIG{'INT'} = $SIG{'KILL'} = $SIG{'TERM'} = sub {
        $this->doLog(
            "Thread " . $t->tid . " got TERM! Proceeding to shutdown thread...",
            'daemon'
        );
        ## give our child a chance to exit cleanly
        $this->exitThreadHook();

        threads->detach();
        $this->doLog( "Thread " . $t->tid . " detached.", 'daemon' );
        threads->exit();
        $this->doLog( "Huho... Thread " . $t->tid . " still working though...",
            'daemon', 'error' );
    };

    $this->initThreadHook();

    $SIG{ALRM} = sub {
        local $| = 1;
        print time, ": Caught SIGALRM in thread\n";
    };

    $this->doLog( "In SockTDaemon main loop", 'socket' );

    my $data;

    while ( my $client = $this->{server}->accept() ) {
        $this->{socks_status}{$client} = 'connected';
        
    	if (\$client =~ m/REF(0x[a-f0-9]+)/  ) {
        	print STDERR "Got conenction from: ".$1."\n";
        }
        my $client_on = 1;
        $SIG{'PIPE'} = sub {
            $this->doLog( "closing client socket, got PIPE signal", 'socket' );
            $this->{socks_status}{$client} .= ',PIPE received';
            if (defined($this->{sock_timer}{$client})) {
                my $interval = tv_interval($this->{sock_timer}{$client});
                my $time     = ( int( $interval * 10000 ) / 10000 ); 
                $this->{socks_status}{$client} .= " ($time s.)";
            }
            $this->doLog( 'connection closed by PIPE: '.$this->{socks_status}{$client}, 'socket' );
            delete($this->{socks_status}{$client});
            undef($this->{socks_status}{$client});
            delete($this->{sock_timer}{$client});
            undef($this->{sock_timer}{$client});
            $client->close();
            $client_on = 0;
        };
        my $rv = $client->recv( $data, 1024, 0 );
        $this->{socks_status}{$client} .= ",received data($data)";
        $this->{sock_timer}{$client} = [gettimeofday];
        if ( defined($rv) && length $data ) {

            $this->doLog( 'GOT some data: ' . $data, 'socket', 'debug' );
            $daemoncounts_{'queries'}++;
            my $result = '';
            if ( $data eq 'STATUS' ) {
                $result = $this->getStatus();
            }
            if ( $result eq '' ) {
                $result = $this->dataRead( $data, $this->{server} );
            }
            if ($this->{long_response_time} > 0) {
               my $interval = tv_interval($this->{sock_timer}{$client});
               my $time     = ( int( $interval * 10000 ) / 10000 );
               if ($time >= $this->{long_response_time}) {
                   $this->doLog( 'Long response detected: '.$this->{socks_status}{$client}." took: ".$time." s.");
               }
            }

            $this->{socks_status}{$client} .= ',data processed';
            ## only answer if client still here
            if ($client_on) {
                $client->send($result);
                $client->flush();
                $this->{socks_status}{$client} .= ',response sent';
                if (defined($this->{sock_timer}{$client})) {
                    my $interval = tv_interval($this->{sock_timer}{$client});
                    my $time     = ( int( $interval * 10000 ) / 10000 ); 
                    $this->{socks_status}{$client} .= " ($time s.)";
                }
                $this->doLog( 'connection closed by response sent: '.$this->{socks_status}{$client}, 'socket', 'debug' );
                delete($this->{socks_status}{$client}); 
                undef($this->{socks_status}{$client});
                delete($this->{sock_timer}{$client});       
                undef($this->{sock_timer}{$client});
                close($client);
                $this->doLog( 'response sent, client socket closed ',
                    'socket', 'debug' );
            }
        }
        else {
            $this->{socks_status}{$client} .= ',end of data';
            if (defined($this->{sock_timer}{$client})) {
               my $interval = tv_interval($this->{sock_timer}{$client});
               my $time     = ( int( $interval * 10000 ) / 10000 );
               $this->{socks_status}{$client} .= " ($time s.)";
            }
            $this->doLog( 'connection closed by end of data: '.$this->{socks_status}{$client}, 'socket' );
            delete($this->{socks_status}{$client});
            undef($this->{socks_status}{$client});
            delete($this->{sock_timer}{$client});
            undef(sock_timer{$client});
            close($client);
            $this->doLog( "closed client connection", 'socket' );
        }
    }
}

sub statusHook {
    my $this = shift;

    my $client = new SockClient( { 'socketpath' => $this->{socketpath} } );
    return $client->query('STATUS');
}

sub getStatus {
    my $this = shift;

    my $counts = $this->getDaemonCounts();

    my $res      = "Status of daemon: " . $this->{name} . "\n";
    my $run_time = ( time() - $counts->{starttime} );
    $res .= "  Running time: " . $this->format_time($run_time) . "\n";
    $res .= "  Threads running: " . threads->list(threads::running) . "\n";
    $res .= "  Number of queries: " . $daemoncounts_{'queries'} . "\n";
    $this->doLog($res);
    return $res;
}

### Available hooks
sub initThreadHook {
    my $this = shift;

    $this->doLog( 'No initThreadHook redefined, using default one...',
        'socket' );
    return;
}

sub exitThreadHook {
    my $this = shift;

    $this->doLog( 'No exitThreadHook redefined, using default one...',
        'socket' );
    return;
}
1;
