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

package UDPClient;

use v5.36;
use strict;
use warnings;
use utf8;

use IO::Socket;
use IO::Select;
require Exporter;

sub new($class,$spec_thish)
{
    my %spec_this = %$spec_thish;

    my $self = {
        port => -1,
        timeout => 5,
        socket => '',
    };

    # add specific options of child object
    foreach my $sk (keys %spec_this) {
        $self->{$sk} = $spec_this{$sk};
    }

    bless $self, $class;
    return $self;
}

sub connect($self)
{
    $self->{socket} = IO::Socket::INET->new(
        PeerAddr => '127.0.0.1',
        PeerPort => $self->{port},
        Proto     => 'udp',
        Timeout => $self->{timeout}
    ) or die "Couldn't be an udp server on port ".$self->{port}." : $@\n";

    return 0;
}

sub query($self,$query)
{
    my $sent = 0;
    my $tries = 1;
    while ($tries < 2 && ! $sent) {
        $tries++;
        my $write_set = IO::Select->new();
        $write_set->add($self->{socket});
        my ($r_ready, $w_ready, $error) =  IO::Select->select(undef, $write_set, undef, $self->{timeout});
        foreach my $sock (@$w_ready) {
            $sock->send($query."\n");
            $write_set->remove($sock);
            $sent = 1;
        }
        if (! $sent) {
            if ($tries < 2) {
                $self->connect();
                next;
            }
            return '_NOSOCKET';
        }
    }

    my $msg;

    my $read_set = IO::Select->new();
    $read_set->add($self->{socket});

    my ($r_ready, $w_ready, $error) =  IO::Select->select($read_set, undef, undef, $self->{timeout});
    foreach my $sock (@$r_ready) {
        my $buf = <$sock>;
        if ($buf) {
            chomp($buf);
            return $buf;
        } else {
            $read_set->remove($sock);
            return "_NOSERVER";
        }
    }
    return '_TIMEOUT';
}

sub ping($self)
{
    return 1 if ($self->{socket});
    return 0;
}

sub close($self)
{
    close($self->{socket});
    return 1;
}

1;
