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

package StatsMsg;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;

my @statdata_ = ('spam', 'highspam', 'virus', 'name', 'other', 'clean', 'bytes');

sub new($class)
{
    my $self = {};

    foreach my $st (@statdata_) {
        $self->{$st} = 0;
    }

    $self->{'msgs'} = 1;

    bless $self, $class;
    return $self;
}

sub setStatus
{
    my ($self, $isspam, $ishigh, $virusinfected, $nameinfected, $otherinfected, $size) = @_;

    if ($isspam) { $self->setAsSpam(); }
    if ($ishigh) { $self->setAsHighSpam(); }
    if ($virusinfected) { $self->setAsVirus(); }
    if ($nameinfected) { $self->setAsName(); }
    if ($otherinfected) { $self->setAsOther(); }
    $self->setBytes($size);
}

sub setAsSpam($self)
{
    $self->{'spam'} = 1;
}

sub setAsHighSpam($self)
{
    $self->{'highspam'} = 1;
}

sub setAsVirus($self)
{
    $self->{'virus'} = 1;
}

sub setAsName($self)
{
    $self->{'name'} = 1;
}

sub setAsOther($self)
{
    $self->{'other'} = 1;
}

sub setBytes($self,$bytes)
{
    $self->{'bytes'} = $bytes;
}

sub getString($self)
{
    $self->{'clean'} = 1;
    if ( $self->{'spam'} + $self->{'highspam'} + $self->{'virus'} + $self->{'name'} + $self->{'ohter'} > 0) {
        $self->{'clean'} = 0;
    }
    my $str = $self->{'msgs'}."|";
    foreach my $st (@statdata_) {
        $str .= $self->{$st}."|";
    }
    $str =~ s/\|$//;
    return $str;
}

sub doUpdate($self,$client,$to,$update_domain,$update_global)
{
    print STDERR "\ncalled: ".'ADD '.$to.' '.$self->getString().' '.$update_domain.' '.$update_global."\n";
    return $client->query('ADD '.$to.' '.$self->getString().' '.$update_domain.' '.$update_global);
}

1;
