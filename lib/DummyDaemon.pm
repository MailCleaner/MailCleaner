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
##  DummyDaemon:
##    Provides a barebone and useless implementation of a socket base multithreaded daemon,
##    relying on SockTDaemon. Can be used as a started for more useful daemons.

package DummyDaemon;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;
require ReadConfig;
require DB;
require SockTDaemon2;

use threads;
use threads::shared;
use Time::HiRes qw(gettimeofday tv_interval);
use Digest::MD5 qw(md5_hex);
use Data::Dumper;
use Date::Calc qw(Add_Delta_Days Today);

our @ISA = "SockTDaemon2";

sub new($class,$myspec_thish=0)
{
    my %myspec_this;
    if ($myspec_thish) {
        %myspec_this = %$myspec_thish;
    }

    ## specific configuration options we want to override by default
    ## all options (expect name) can be overriden by config file though.
    ## the option name is mandatory.
    my $spec_this = {
        name        => 'DummySocketDaemon',
        profile     => 0,
        daemonize   => 1
    };

    # add specific options of child object
    foreach my $sk ( keys %myspec_this ) {
        $spec_this->{$sk} = $myspec_this{$sk};
    }

    ## call parent class creation
    my $self = $class->SUPER::new($spec_this->{'name'}, undef, $spec_this );
    bless $self, 'DummyDaemon';
    return $self;
}

### define specific hooks
sub initThreadHook($self)
{
    $self->doLog('DummyDaemon thread initialization hook...', 'dummy');
}

sub exitThreadHook($self)
{
    $self->doLog('DummyDaemon thread exiting hook...', 'dummy');
}

####### Main processing
sub dataRead($self,$data)
{
    $self->doLog("Got a query: $data", 'dummy');
    return 'OK';
}

1;
