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

package dumpers::TrustedSources;

use v5.36;
use strict;
use warnings;
use utf8;

sub get_specific_config
{
    require DB;
    my $db = DB::connect('slave', 'mc_config');

    my %config = ();
    my %row = $db->getHashRow("SELECT use_alltrusted, use_authservers, useSPFOnLocal, useSPFOnGlobal, authservers, authstring, domainsToSPF, whiterbls FROM trustedSources");
    $config{'__USE_ALLTRUSTED__'} = $row{'use_alltrusted'};
    $config{'__USE_AUTHSERVERS__'} = $row{'use_authservers'};
    $config{'__USE_SPFONLOCAL__'} = $row{'useSPFOnLocal'};
    $config{'__USE_SPFONGLOBAL__'} = $row{'useSPFOnGlobal'};
    $config{'__AUTHSERVERS__'} = $row{'authservers'} || '';
    $config{'__AUTHSTRING__'} = $row{'authstring'} || '';
    $config{'__DOMAINSTOSPF__'} = $row{'domainsToSPF'} || '';
    $config{'__DOMAINSTOSPF__'} =~ s/\n//g;
    $config{'__DOMAINSTOSPF__'} =~ s/\s+/ /g;
    $config{'__WHITERBLS__'} = $row{'whiterbls'} || '';

    return %config;
}

1;
