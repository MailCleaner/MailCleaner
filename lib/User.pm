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

package User;

use v5.36;
use strict;
use warnings;
use utf8;

require          ReadConfig;
require          SystemPref;
require          Domain;
require          PrefClient;
require          Exporter;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(create);
our $VERSION    = 1.0;

sub create($username,$domain=undef)
{
    my %prefs;
    my %addresses;

    my $self = {
        id          => 0,
        username    => '',
        domain      => '',
        prefs       => \%prefs,
        addresses   => \%addresses,
        d           => undef,
        db          => undef
    };

    bless $self, "User";

    if (defined($username) && defined($domain)) {
        $self->loadFromUsername($username, $domain);
    } elsif (defined($username) && $username =~ m/^\d+$/) {
        $self->loadFromId($username);
    } elsif (defined($username) && $username =~ m/^\S+\@\S+$/) {
        $self->loadFromLinkedAddress($username);
    }
    return $self;
}

sub loadFromId($self,$id)
{
    my $query = "SELECT u.username, u.domain, u.id FROM user u, WHERE u.id=".$id;
    $self->load($query);
}

sub loadFromUsername($self,$username,$domain)
{
    my $query = "SELECT u.username, u.domain, u.id FROM user u, WHERE u.username='".$username."' AND u.domain='".$domain."'";
    $self->load($query);
}

sub loadFromLinkedAddress($self,$email)
{
    if ($email =~ m/^(\S+)\@(\S+)$/) {
        $self->{domain} = $2;
    }

    $self->{addresses}->{$email} = 1;

    my $query = "SELECT u.username, u.domain, u.id FROM user u, email e WHERE e.address='".$email."' AND e.user=u.id";
    $self->load($query);
}

sub load($self,$query)
{
    if (!$self->{db}) {
        require DB;
        $self->{db} = DB::connect('slave', 'mc_config', 0);
    }
    my %userdata = $self->{db}->getHashRow($query);
    if (keys %userdata) {
        $self->{username} = $userdata{'username'};
        $self->{domain} = $userdata{'domain'};
        $self->{id} = $userdata{'id'};
    }
    return 0;
}

sub getAddresses($self)
{
    if ($self->{id}) {
        ## get registered addresses
        if (!$self->{db}) {
            require DB;
            $self->{db} = DB::connect('slave', 'mc_config', 0);
        }

        my $query = "SELECT e.address, e.is_main FROM email e WHERE e.user=".$self->{'id'};
        my @addresslist = $self->{db}->getListOfHash($query);
        foreach my $regadd (@addresslist) {
            $self->{addresses}->{$regadd->{'address'}} = 1;
            if ($regadd->{is_main}) {
                foreach my $add (keys %{$self->{addresses}}) {
                    $self->{addresses}->{$add} = 1;
                }
                $self->{addresses}->{$regadd->{'address'}} = 2;
            }
        }
    }

    ## adding connector addresses
    if (!$self->{d} && $self->{domain}) {
        $self->{d} = Domain::create($self->{domain});
    }
    if ($self->{d}) {
        if ($self->{d}->getPref('address_fetcher') eq 'ldap') {
            require SMTPAuthenticator::LDAP;
            my $serverstr =  $self->{d}->getPref('auth_server');
            my $server = $serverstr;
            my $port = 0;
            if ($serverstr =~ /^(\S+):(\d+)$/) {
                $server = $1;
                $port = $2;
            }
            my $auth = SMTPAuthenticator::LDAP::create($server, $port, $self->{d}->getPref('auth_param'));
            my @ldap_addesses;
            if ($self->{username} ne '') {
                @ldap_addesses = $auth->fetchLinkedAddressesFromUsername($self->{username});
            } elsif (scalar(keys %{$self->{addresses}})) {
                my @keys = keys %{$self->{addresses}};
                @ldap_addesses = $auth->fetchLinkedAddressesFromEmail(pop(@keys));
            }
            if (!@ldap_addesses ) {
                ## check for errors
                if ($auth->{'error_text'} ne '') {
                    #print STDERR "Got ldap error: ".$auth->{'error_text'}."\n";
                }
            } else {
                foreach my $add (@ldap_addesses) {
                    $self->{addresses}->{$add} = 1;
                }
            }
        }
    }

    return keys %{$self->{addresses}};
}

sub getMainAddress($self)
{
    if (!keys %{$self->{addresses}}) {
        $self->getAddresses();
    }
    my $first;
    foreach my $add (keys %{$self->{addresses}}) {
        if (!$first) {
            $first = $add;
        }
        if ($self->{addresses}->{$add} == 2) {
            return $add;
        }
    }
    if (!$self->{d} && $self->{domain}) {
        $self->{d} = Domain::create($self->{domain});
    }
    if ($self->{d}->getPref('address_fetcher') eq 'at_login') {
        if ($self->{username} =~ /\@/) {
            return $self->{username};
        }
        if ($self->{username} && $self->{domain}) {
            return $self->{username}.'@'.$self->{domain};
        }
    }
    if ($first) {
        return $first;
    }
    return undef;
}

sub getPref($self,$pref)
{
    if (keys %{$self->{prefs}} < 1) {
        $self->loadPrefs();
    }

    if (defined($self->{prefs}->{$pref}) && $self->{prefs}->{$pref} ne 'NOTSET') {
        return $self->{prefs}->{$pref};
    }
    ## find out if domain has pref
    if (!$self->{d} && $self->{domain}) {
        $self->{d} = Domain::create($self->{domain});
        if ($self->{d}) {
            return $self->{d}->getPref($pref);
        }
    }
    if ($self->{d}) {
        return $self->{d}->getPref($pref);
    }
    return undef;
}

sub loadPrefs($self)
{
    if (!$self->{id}) {
        return 0;
    }
    if (!$self->{db}) {
        require DB;
        $self->{db} = DB::connect('slave', 'mc_config', 0);
    }

    if ($self->{db} && $self->{db}->ping()) {
        my $query = "SELECT p.* FROM user u, user_pref p WHERE u.pref=p.id AND u.id=".$self->{id};
        my %res = $self->{db}->getHashRow($query);
        if ( !%res || !$res{id} ) {
            return 0;
        }
        foreach my $p (keys %res) {
            $self->{prefs}->{$p} = $res{$p};
        }
    }
}

1;
