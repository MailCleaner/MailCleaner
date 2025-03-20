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

package UDPTDaemon;

use v5.36;
use strict;
use warnings;
use utf8;

require PreForkTDaemon;
use threads;
use threads::shared;
use IO::Socket;
use IO::Select;
require Exporter;

our @ISA = "PreForkTDaemon";

my %global_shared : shared;

sub new($class,$init,$config,$spec_thish)
{
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

    my $self = $class->SUPER::create($class, $config, $udpspec_this);

    bless $self, $class;
    return $self;
}

sub preForkHook($self)
{
    ## bind to UDP port
    $self->{server} = IO::Socket::INET->new(
        LocalAddr => '127.0.0.1',
        LocalPort => $self->{port},
        Proto     => 'udp',
        Timeout => 10
    ) or die "Couldn't be an udp server on port ".$self->{port}." : $@\n";
    $self->{server}->autoflush ( 1 ) ;
    $self->logMessage("Listening on port ".$self->{port});

    return 1;
}

sub mainLoopHook($self)
{
    require Mail::SpamAssassin::Timeout;

    $self->logMessage("In UDPTDaemon main loop");

    my $read_set = IO::Select->new();
    $read_set->add($self->{server});

    my $t = threads->self;
    $self->{tid} = $t->tid;

    my $data;
    while ($self->{server}->recv($data, 1024)) {
        my($port, $ipaddr) = sockaddr_in($self->{server}->peername);
        my $hishost = gethostbyaddr($ipaddr, AF_INET);
        chomp($data);
        my $result =  $self->dataRead($data, $self->{server});
        $self->{server}->send($result."\n");
    }

    return 1;
}

sub exitHook($self)
{
    close ($self->{server});
    $self->logMessage("Listener socket closed");
    return 1;
}

1;
