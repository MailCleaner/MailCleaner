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

package module::Network;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;
require DialogFactory;
require module::Interface;
require module::Resolver;

our @ISA = qw(Exporter);
our @EXPORT = qw(get ask do);
our $VERSION = 1.0;

sub get
{
    my $this = {
        networkfile => "/etc/network/interfaces",
        resolvfile => "/etc/resolv.conf",
        dhcp => 0
    };

    bless $this, 'module::Network';
    return $this;
}

sub do($this)
{
    my $dfact = DialogFactory::get('InLine');
    $this->{dlg} = $dfact->getListDialog();

    my $config = "";
    $config .= "\nauto lo\n";
    $config .= "iface lo inet loopback\n";
    mkdir($this->{networkfile}.'.d/') unless (-d $this->{networkfile}.'.d/');
    if (open(my $fh, '>', $this->{networkfile}.'.d/lo')) {
        print $fh $config;
        close($fh);
    } else {
        die "Failed to open '$this->{networkfile}.d/' for writing\n"
    }

    my $i = `ip link | cut -d' ' -f2- | cut -d':' -f1 | grep -v lo | grep -v docker | grep -vP '^ '`;
    my %interfaces = map { $_ => 0 } split("\n", $i);
    my %configured;
    $this->doint(\%interfaces,\%configured);

    my $gw = (keys(%interfaces))[0];
    if (scalar(keys(%interfaces)) > 1) {
        my @gateways = map { "$_ ($interfaces{$_})" } keys(%interfaces);
        $this->{dlg}->build("Select primary gateway:", \@gateways, 1, 1);
        $gw = $this->{dlg}->display();
    }
    foreach my $if (keys %interfaces) {
	next if ($interfaces{$if} eq '');
        if (open(my $fh, '>>', $this->{networkfile}.'.d/'.$if)) {
            print $fh '    post-up /sbin/ip route add '.$interfaces{$if}.' dev '.$if.'\n';
            print $fh '    post-up /sbin/ip route add default via '.$interfaces{$if}.' dev '.$if.'\n' if ($gw eq $if);
            print $fh '    pre-down /sbin/ip route del default via '.$interfaces{$if}.' dev '.$if.'\n' if ($gw eq $if);
            print $fh '    pre-down /sbin/ip route del '.$interfaces{$if}.' dev '.$if.'\n';
            close($fh);
        }
    }
    my $resolv = module::Resolver::get($this->{dhcp});
    $resolv->setDNS(1, $interfaces{$gw});
    $resolv->ask();
    my $resconfig .= $resolv->getConfig();

    if (open(my $RESFILE, '>', $this->{resolvfile})) {
        print $RESFILE $resconfig;
        close $RESFILE;
    }
    if (open(my $NETFILE, '>', $this->{networkfile})) {
        print $NETFILE "source /etc/network/interfaces.d/*\n";
        close $NETFILE;
    }
    print("Restarting networking...");
    `/etc/init.d/networking restart 2>&1 > /dev/null`;
    `dhclient` if ($this->{dhcp});
}

sub doint($this, $listh, $configured)
{
    return 0 if (scalar(keys(%{$configured})) >= scalar(keys%{$listh}));
    my @lista = sort(keys%{$listh});
    my $current = $lista[0];
    my @list = @lista;
    my $i = 1;
    while ($current = shift(@list)) {
        last unless (defined($configured->{$current}));
        $i++;
    }

    my $dlg = $this->{dlg};
    my $if = $lista[0];
    if (scalar(@lista) gt 1) {
        push(@lista, 'finish');
        $dlg->build("Select interface to configure [".$current."]:", \@lista, $i, 1);
        $if = $dlg->display();
        return 0 if ($if eq 'finish');
    }

    my @am = ( 'auto (DHCP)', 'manual' );
    $dlg->build("Configuration mode [auto]:", \@am, 1, 1);
    my $auto = $dlg->display();
    my $int = module::Interface::get($if);
    my $config;
    if ($auto eq $am[0]) {
	    $config = $int->dhcp();
        $this->{dhcp} = 1;
    } else {
    	$int->ask();
    	$config = $int->getConfig();
    }
    $dlg->clear();

    if (open(my $fh, '>', $this->{networkfile}.'.d/'.$if)) {
        print $fh $config;
        close($if);
    }
    $listh->{$if} = $int->{gateway};
    $configured->{$if} = 1;
    $this->doint($listh, $configured);
}

1;
