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

package ElementMapper;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;
require SystemPref;

our @ISA = qw(Exporter);
our @EXPORT = qw(getElementMapper setNewDefault addNewElement);
our $VERSION = 1.0;

sub getElementMapper($what)
{
    my $el;

    if ($what eq "domain") {
        require ElementMappers::DomainMapper;
        $el = ElementMappers::DomainMapper::create();
    }
    if ($what eq "email") {
        require ElementMappers::EmailMapper;
        $el = ElementMappers::EmailMapper::create();
    }

    $el->{db} = DB::connect('master', 'mc_config', 0);
    if (! $el->{db}->ping()) {
        print "Cannot connect to configuration database";
        return;
    }

    return $el;
}

1;
