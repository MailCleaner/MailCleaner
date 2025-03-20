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

package MailScanner::Profiler;

use v5.36;
use strict 'vars';
use strict 'refs';
no strict 'subs'; # Allow bare words for parameter %'s

use vars qw($VERSION);

use Time::HiRes qw(gettimeofday tv_interval);

# Constructor.
sub new($class='MailScanner::Profiler')
{
    my (%start_times, %res_times) = ();

    my $self = {
        %start_times => (),
        %res_times => (),
    };

    bless $self, $class;
    return $self;
}

sub start($self,$var)
{
    return unless MailScanner::Config::Value('profile');

    $self->{start_times}{$var} = [gettimeofday];
}

sub stop($self,$var)
{
    return unless MailScanner::Config::Value('profile');

    return unless defined($self->{start_times}{$var});
    my $interval = tv_interval ($self->{start_times}{$var});
    $self->{res_times}{$var} = (int($interval*10000)/10000);
}

sub getResult($self)
{
    return unless MailScanner::Config::Value('profile');

    my $out = "";

    my @keys = sort keys %{$self->{res_times}};
    foreach my $key (@keys) {
        $out .= " ($key:".$self->{res_times}{$key}."s)";
    }
    return $out;
}

sub log($self,$extra)
{
    return unless MailScanner::Config::Value('profile');

    MailScanner::Log::InfoLog($extra.$self->getResult());
    return 1;
}

1;

