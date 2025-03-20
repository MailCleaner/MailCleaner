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

package SockClient;

use v5.36;
use strict;
use warnings;
use utf8;

require  Exporter;
use IO::Socket;
use IO::Select;
use Time::HiRes qw(setitimer time);

sub new($class,$spec_thish)
{
    my %spec_this = %$spec_thish;

    my $self = {
        timeout => 5,
        socketpath => '/tmp/'.$class,
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
    ## untaint some values
    if ($self->{socketpath} =~ m/^(\S+)/) {
        $self->{socketpath} = $1;
    }
    if ($self->{timeout} =~ m/^(\d+)$/) {
        $self->{timeout} = $1;
    }

    $self->{socket} = IO::Socket::UNIX->new(
        Peer    => $self->{socketpath},
        Type    => SOCK_STREAM,
        Timeout => $self->{timeout}
    ) or return 0;

    return 1;
}

sub query($self,$query)
{
    my $sent = 0;
    my $tries = 1;

    $self->connect() or return '_NOSERVER';
    my $sock = $self->{socket};

    $sock->send($query) or return '_NOSERVER';
    $sock->flush();

    my $data = '';
    my $rv;

    my $read_set = IO::Select->new();
    $read_set->add($sock);
    my ($r_ready, $w_ready, $error) =  IO::Select->select($read_set, undef, undef, $self->{timeout});

    foreach my $s (@$r_ready) {
        my $buf;
        my $buft;
        while(  my $ret = $s->recv($buft, 1024, 0) ) {
            if (defined($buft)) {
                $buf .= $buft;
            } else {
                $read_set->remove($sock);
                close($sock);
                return '_CLOSED';
            }
        }
        close($sock);
        return $buf;
    }
    return '_TIMEOUT';


    if (defined($rv) && length $data) {
        chomp($data);
        return $data;
    }
    close($sock);
}

1;
