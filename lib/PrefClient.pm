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

package PrefClient;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;
require ReadConfig;
require SockClient;

our @ISA = "SockClient";

sub new($class)
{
    my %msgs = ();

    my $spec_this = {
       %msgs => (),
       currentid => 0,
       socketpath => '',
       timeout => 5,
    };

    my $conf = ReadConfig::getInstance();
    $spec_this->{socketpath} = $conf->getOption('VARDIR')."/run/prefdaemon.sock";

    my $self = $class->SUPER::new($spec_this);

    bless $self, $class;
    return $self;
}

sub setTimeout($self,$timeout)
{
   return 0 if ($timeout !~ m/^\d+$/);
   $self->{timeout} = $timeout;
   return 1;
}

## fetch a pref by calling de pref daemon
sub getPref($self,$object,$pref)
{
    if ($object !~ m/^[-_.!\$#=*&\@a-z0-9]+$/i) {
        return '_BADOBJECT';
    }
    if ($pref !~ m/^[-_a-z0-9]+$/) {
        return '_BADPREF';
    }

    my $query = "PREF $object $pref";

    my $result = $self->query($query);
    return $result;
}

## fetch a pref, just like getPref but force pref daemon to fetch domain pref if user pref is not found or not set
sub getRecursivePref($self,$object,$pref)
{
    if ($object !~ m/^[-_.!\$#=*&\@a-z0-9]+$/i) {
        return '_BADOBJECT';
    }
    if ($pref !~ m/^[-_a-z0-9]+$/) {
        return '_BADPREF';
    }

    my $query = "PREF $object $pref R";

    my $result = $self->query($query);
    return $result;
}

sub extractSRSAddress($self,$sender)
{
    my $sep = '[=+-]';
    my @segments;
    if ($sender =~ m/^srs0.*/i) {
        @segments = split(/$sep/, $sender);
        my $tag = shift(@segments);
        my $hash = shift(@segments);
        my $time = shift(@segments);
        my $domain = shift(@segments);
        my $remove = "$tag$sep$hash$sep$time$sep$domain$sep";
        $remove =~ s/\//\\\//;
        $sender =~ s/^$remove(.*)\@[^\@]*$/$1/;
        $sender .= '@' . $domain;
    } elsif ($sender =~ m/^srs1.*/i) {
        my @blocks = split(/=$sep/, $sender);
        @segments = split(/$sep/, $blocks[0]);
        my $domain = $segments[scalar(@segments)-1];
        @segments = split(/$sep/, $blocks[scalar(@blocks)-1]);
        my $hash = shift(@segments);
        my $time = shift(@segments);
        my $relay = shift(@segments);
        my $remove = "$hash$sep$time$sep$relay$sep";
        $remove =~ s/\//\\\//;
        $sender = $blocks[scalar(@blocks)-1];
        $sender =~ s/^$remove(.*)\@[^\@]*$/$1/;
        $sender .= '@' . $domain;
    }
    return $sender;
}

sub extractVERP($self,$sender)
{
    if ($sender =~ /^[^\+]+\+.+=[a-z0-9\-\.]+\.[a-z]+/i) {
        $sender =~ s/([^\+]+)\+.+=[a-z0-9\-]{2,}\.[a-z]{2,}\@([a-z0-9\-]{2,}\.[a-z]{2,})/$1\@$2/i;
    }
    return $sender;
}

sub extractSubAddress($self,$sender)
{
    if ($sender =~ /^[^\+]+\+.+=[a-z0-9\-\.]+\.[a-z]+/i) {
        $sender =~ s/([^\+]+)\+.+\@([a-z0-9\-]{2,}\.[a-z]{2,})/$1\@$2/i;
    }
    return $sender;
}

sub extractSender($self,$sender)
{
    my $orig = $sender;
    $sender = $self->extractSRSAddress($sender);
    $sender = $self->extractVERP($sender);
    $sender = $self->extractSubAddress($sender);
    if ($orig eq $sender) {
        return 0;
    }
    return $sender;
}

sub isWhitelisted($self,$object,$sender)
{
    if ($object !~ m/^[-_.!\$+#=*&\@a-z0-9]+$/i) {
        return '_BADOBJECT';
    }

    my $query = "WHITE $object $sender";
    my $result;
    if (my $result = $self->query("WHITE $object $sender")) {
        return $result;
    }
    $sender = $self->extractSender($sender);
    if ($sender) {
        if ($result = $self->query("WHITE $object $sender")) {
            return $result;
        }
    } else {
        return 0;
    }
}

sub isWarnlisted($self,$object,$sender)
{
    if ($object !~ m/^[-_.!\$+#=*&\@a-z0-9]+$/i) {
        return '_BADOBJECT';
    }

    my $result;
    if (my $result = $self->query("WARN $object $sender")) {
        return $result;
    }
    $sender = $self->extractSender($sender);
    if ($sender) {
        if ($result = $self->query("WARN $object $sender")) {
            return $result;
        }
    } else {
        return 0;
    }
}

sub isBlacklisted($self,$object,$sender)
{
    if ($object !~ m/^[-_.!\$+#=*&\@a-z0-9]+$/i) {
        return '_BADOBJECT';
    }

    my $query = "BLACK $object $sender";
    my $result;
    if (my $result = $self->query("BLACK $object $sender")) {
        return $result;
    }
    $sender = $self->extractSender($sender);
    if ($sender) {
        if ($result = $self->query("BLACK $object $sender")) {
            return $result;
        }
    } else {
        return 0;
    }
}

sub logStats($self)
{
    my $query = 'STATS';
    return $self->query($query);
}

1;
