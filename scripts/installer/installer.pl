#!/usr/bin/env perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
#   Copyright (C) 2023 John Mertz <git@john.me.tz>
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

use v5.36;
use strict;
use warnings;
use utf8;

if ($0 =~ m/(\S*)\/\S+.pl$/) {
  my $path = $1;
  unshift (@INC, $path);
}

require module::Network;
require module::Hostname;
require module::Keyboard;
require module::Timezone;
require module::MCSetup;
require module::Cluster;
require module::RootPassword;
require DialogFactory;

my $d = DialogFactory::get('InLine');
my $dlg = $d->getListDialog();

my @basemenu = (
    'Keyboard configuration', 
    'Set root shell password', 
    'Set hostname', 
    'Network configuration', 
    'Timezone configuration', 
    'MailCleaner configuration', 
    'Join existing cluster (optional)', 
    'Exit'
);
my $currentstep = 1;

while (doMenu()) {
}

$dlg->clear();

exit 0;

sub doMenu
{

    $dlg->build('MailCleaner: base system configuration', \@basemenu, $currentstep, 1);

    my $res = $dlg->display();
    return 0 if $res eq 'Exit';

    if ($res eq 'Keyboard configuration') {
        my $keyb = module::Keyboard::get();
        $keyb->do();
        $currentstep = 2;
        return 1;
    }

    if ($res eq 'Set root shell password') {
        my $pass = module::RootPassword::get();
        $pass->do();
        $currentstep = 3;
        return 1;
    }

    if ($res eq 'Set hostname') {
        my $hostn = module::Hostname::get();
        $hostn->do();
        $currentstep = 4;
        return 1;
    }

    if ($res eq 'Network configuration') {
        my $net = module::Network::get();
        $net->do();
        $currentstep = 5;
        return 1;
    }

    if ($res eq 'Timezone configuration') {
        my $tz = module::Timezone::get();
        $tz->do();
        $currentstep = 6;
        return 1;
    }

    if ($res eq 'MailCleaner configuration') {
        my $mcinstall = module::MCSetup::get();
	my $ret = $mcinstall->do();
        if ($ret == 0) {
            $currentstep = 7;
        } elsif ($ret == 1) {
	    $currentstep = 4;
        }
        return 1;
    }

    if ($res eq 'Join existing cluster (optional)') {
        my $cluster = module::Cluster::get();
        if ($cluster->do()) {
            $currentstep = 8;
        } else {
            $currentstep = 6;
        }
        return 1;
    }
    $dlg->clear();

    die "Invalid selection: $res\n";
}
