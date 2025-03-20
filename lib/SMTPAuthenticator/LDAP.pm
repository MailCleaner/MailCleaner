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

package SMTPAuthenticator::LDAP;

use v5.36;
use strict;
use warnings;
use utf8;

use Net::LDAP;
require Exporter;

our @ISA = qw(Exporter);
our @EXPORT = qw(create authenticate);
our $VERSION = 1.0;

my @mailattributes = ('mail', 'maildrop', 'mailAlternateAddress', 'mailalternateaddress', 'proxyaddresses', 'proxyAddresses', 'oldinternetaddress', 'oldInternetAddress', 'cn', 'userPrincipalName');

sub create($server,$port,$params)
{
    my $self = {
        error_text => "",
        error_code => -1,
        server => '',
        port => 389,
        use_ssl => 0,
        base => '',
        attribute => 'uid',
        binduser => '',
        bindpassword => '',
        version => 3
    };

    $self->{server} = $server;
    if ($port > 0 ) {
        $self->{port} = $port;
    }
    my @fields = split /:/, $params;
    if ($fields[4] && $fields[4] =~ /^[01]$/) {
        $self->{use_ssl} = $fields[4];
    }
    if ($fields[0]) {
        $self->{base} = $fields[0];
    }
    if ($fields[1]) {
        $self->{attribute} = $fields[1];
    }
    if ($fields[2]) {
        $self->{binduser} = $fields[2];
    }
    if ($fields[3]) {
        $self->{bindpassword} = $fields[3];
        $self->{bindpassword} =~ s/__C__/:/;
    }
    if ($fields[5] && $fields[5] == 2) {
        $self->{version} = 2;
    }

    bless $self, "SMTPAuthenticator::LDAP";
    return $self;
}

sub authenticate($self,$username,$password)
{
    my $scheme = 'ldap';
    if ($self->{use_ssl} > 0) {
        $scheme = 'ldaps';
    }
    #print "connecting to $scheme://".$self->{server}.":".$self->{port}."\n";
    my $ldap = Net::LDAP->new ( $self->{server}, port=>$self->{port}, scheme=>$scheme, timeout=>30, debug=>0 );

    if (!$ldap) {
        $self->{'error_text'} = "Cannot contact LDAP/AD server at $scheme://".$self->{server}.":".$self->{port};
        return 0;
    }

    my $userdn = $self->getDN($username);
    return 0 if ($userdn eq '');

    my $mesg = $ldap->bind ( $userdn,
        password => $password,
        version => $self->{version}
    );

    $self->{'error_code'} = $mesg->code;
    $self->{'error_text'} = $mesg->error_text;
    if ($mesg->code == 0) {
        return 1;
    }
    return 0;
}

sub getDN($self,$username)
{
    my $scheme = 'ldap';
    if ($self->{use_ssl} > 0) {
        $scheme = 'ldaps';
    }

    my $ldap = Net::LDAP->new ( $self->{server}, port=>$self->{port}, scheme=>$scheme, timeout=>30, debug=>0 );
    my $mesg;
    if (! $self->{binduser} eq '') {
        $mesg = $ldap->bind($self->{binduser}, password => $self->{bindpassword}, version => $self->{version});
    } else {
        $mesg = $ldap->bind ;
    }
    if ( $mesg->code ) {
        $self->{'error_text'} = "Could not search for user DN (bind error)";
        return '';
    }
    $mesg = $ldap->search (base => $self->{base}, scope => 'sub', filter => "(".$self->{attribute}."=$username)");
    if ( $mesg->code ) {
        $self->{'error_text'} = "Could not search for user DN (search error)";
        return '';
    }
    my $numfound = $mesg->count ;
    my $dn="" ;
    if ($numfound) {
        my $entry = $mesg->entry(0);
        $dn = $entry->dn ;
    } else {
        $self->{'error_text'} = "No such user ($username)";
    }
    $ldap->unbind;   # take down session
    return $dn ;
}

sub fetchLinkedAddressesFromEmail($self,$email)
{
    my $filter = '(|';
    foreach my $att (@mailattributes) {
        $filter .= '('.$att.'='.$email.')('.$att.'='.'smtp:'.$email.')';
    }
    $filter .= ')';
    return $self->fetchLinkedAddressFromFilter($filter);
}

sub fetchLinkedAddressesFromUsername($self,$username)
{
    my $filter = $self->{attribute}."=".$username;
    return $self->fetchLinkedAddressFromFilter($filter);
}

sub fetchLinkedAddressFromFilter($self,$filter)
{
    my @addresses;

    my $scheme = 'ldap';
    if ($self->{use_ssl} > 0) {
        $scheme = 'ldaps';
    }

    my $ldap = Net::LDAP->new ( $self->{server}, port=>$self->{port}, scheme=>$scheme, timeout=>30, debug=>0 );
    my $mesg;
    if (!$ldap) {
        $mesg = 'Cannot open LDAP session';
        return @addresses;
    }
    if (! $self->{binduser} eq '') {
        $mesg = $ldap->bind($self->{binduser}, password => $self->{bindpassword}, version => $self->{version});
    } else {
        $mesg = $ldap->bind ;
    }
    if ( $mesg->code ) {
        $self->{'error_text'} = "Could not bind";
        return @addresses;
    }
    $mesg = $ldap->search (base => $self->{base}, scope => 'sub', filter => $filter);
    if ( $mesg->code ) {
        $self->{'error_text'} = "Could not search";
        return @addresses;
    }
    my $numfound = $mesg->count ;
    my $dn="" ;
    if ($numfound) {
        my $entry = $mesg->entry(0);
        foreach my $att (@mailattributes) {
            foreach my $add ($entry->get_value($att)) {
                if ($add =~ m/\@/) {
                    $add =~ s/^smtp\://gi;
                    push @addresses, lc($add);
                }
            }
        }
    } else {
        #$self->{'error_text'} = "No data for filter ($filter)";
    }
    $ldap->unbind;   # take down session
    return @addresses;
}

1;
