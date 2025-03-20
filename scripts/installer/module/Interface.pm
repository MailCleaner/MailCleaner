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

package module::Interface;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;
require DialogFactory;

our @ISA = qw(Exporter);
our @EXPORT = qw(get ask do);
our $VERSION = 1.0;

sub get($interface)
{
    my %dns;

    my $this = {
        interface => $interface,
        ip => '192.168.1.101',
        mask => '',
        gateway => '',
        broadcast => '',
        dns => '',
    };

    bless $this, 'module::Interface';
    return $this;
}

sub dhcp($this)
{
    my $str;

    $str = "allow-hotplug ".$this->{interface}."\n";
    $str .= "auto ".$this->{interface}."\n";
    $str .= "iface ".$this->{interface}." inet dhcp\n";

    return $str;
}

sub ask($this)
{
    my $dfact = DialogFactory::get('InLine');
    my $dlg = $dfact->getSimpleDialog();
    $dlg->clear();
    my $title = "Configuring network interface (".$this->{interface}.")";
    print $title."\n";
    for (my $i=0; $i<length($title); $i++) {
        print "-";
    }
    print "\n\n";

    ##########
    ## get IP
    $dlg->build('Please enter the IP address', $this->{ip});
    my $ip = '';
    while( !module::Interface::isIP($ip) ) {
        print "Bad address format, please type again.\n" if ! $ip eq "";
        $ip = $dlg->display();
    }
    $this->{ip} = $ip;

    my $mask = '255.255.255.0';
    if ( $this->{ip} =~ m/^(\d+)/ ) {
        my $init = $1;
        if ($init < 127 ) {
            $mask = '255.0.0.0';
        } elsif ($init < 192) {
            $mask = '255.255.0.0';
        }
    }

    ##########
    ## get mask
    $dlg->build('Please enter the network mask', $mask);
    my $lmask = '';
    while( !module::Interface::isIP($lmask) ) {
        print "Bad address format, please type again.\n" if ! $lmask eq "";
        $lmask = $dlg->display();
    }
    $this->{mask} = $lmask;

    $this->computeData();

    ##############
    ## get gateway
    $dlg->build('Please enter the default gateway', $this->{gateway});
    my $lgate = '';
    while( !module::Interface::isIP($lgate) ) {
        print "Bad address format, please type again.\n" if ! $lgate eq "";
        $lgate = $dlg->display();
    }
    $this->{gateway} = $lgate;
}

sub getGateway($this)
{
    return $this->{gateway};
}

sub do($this)
{
    print "Interface: ".$this->{interface}."\n";
    print "got ip: ".$this->{ip}."\n";
    print "got mask: ".$this->{mask}."\n";
    print "got gateway: ".$this->{gateway}."\n";
    print "got broadcast: ".$this->{broadcast}."\n";
}

sub getConfig($this)
{
    my $str;

    $str = "allow-hotplug ".$this->{interface}."\n";
    $str .= "auto ".$this->{interface}."\n";
    $str .= "iface ".$this->{interface}." inet static\n";
    $str .= "        address ".$this->{ip}."\n";
    $str .= "        netmask ".$this->{mask}."\n";
    $str .= "        gateway ".$this->{gateway}."\n";
    $str .= "        broadcast ".$this->{broadcast}."\n";

    return $str;
}


sub computeData($this)
{
    my @ipoct = split(/\./, $this->{ip});
    my @nmoct = split(/\./, $this->{mask});
    my @broct = ();
    my @gwoct = ();

    for (my $i = 0; $i < 4; $i++) {
        if ($nmoct[$i] == 255) {
            push(@gwoct, $ipoct[$i]);
            push(@broct, $ipoct[$i]);
        } else {
            if ($i == 3) {
                push(@gwoct, 1);
                push(@broct, 255);
            } else {
                push(@gwoct, 0);
                push(@broct, 0);
            }
        }
    }
    $this->{gateway} = join('.', @gwoct);
    $this->{broadcast} = join('.', @broct);
}


########
## static functions
sub isIP($ip)
{
    if ( $ip =~ m/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/ ) {
        return 0 if $1 < 0 || $1 > 255;
        return 0 if $2 < 0 || $2 > 255;
        return 0 if $3 < 0 || $3 > 255;
        return 0 if $4 < 0 || $4 > 255;
        return 1;
    }
    return 0;
}

1;
