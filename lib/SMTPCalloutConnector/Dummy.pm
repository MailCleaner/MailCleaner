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

package SMTPCalloutConnector::Dummy;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(create authenticate);
our $VERSION    = 1.0;


sub new($class,$paramsh)
{
    my @params = @{$paramsh};

    my $self = {
        'last_message' => '',
        'useable' => 1,
        'default_on_error' => 1 ## we accept in case of any failure, to avoid false positives
    };

    bless $self, $class;
    return $self;
}

sub verify($self,$address)
{
    $self->{last_message} = 'Dummy callout will always answer yes';
    return 1;
}

sub isUseable($self)
{
    return $self->{useable};
}

sub lastMessage($self)
{
    return $self->{last_message};
}

1;
