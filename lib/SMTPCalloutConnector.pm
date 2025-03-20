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

package SMTPCalloutConnector;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;
require SystemPref;
require Domain;

our @ISA = qw(Exporter);
our @EXPORT = qw(create verify lastMessage);
our $VERSION = 1.0;


sub create($domainname)
{
    if (!defined($domainname) || $domainname eq "") {
        my $system = SystemPref::create();
        $domainname = $system->getPref('default_domain');
    }
    my $domain = Domain::create($domainname);
    if (!$domain) {
        return 0;
    }

    my $useable = 1;
    my $last_message = '';

    my $domain_pref =  $domain->getPref('extcallout');
    if ($domain_pref ne 'true') {
        $useable = 0;
        $last_message = 'not using external callout';
        return;
    }

    my $self = {
        'domain' => $domain,
        'last_message' => $last_message,
        'useable' => $useable,
        'default_on_error' => 1 ## we accept in case of any failure, to avoid false positives
    };

    bless $self, "SMTPCalloutConnector";
    return $self;
}

sub verify($self,$address)
{
    if (! $self->{useable}) {
        return $self->{default_on_error};
    }
    if (!defined($address) || $address !~ m/@/) {
        $self->{last_message} = 'the address to check is invalid';
        return $self->{default_on_error};
    }
    my $type = $self->{domain}->getPref('extcallout_type');
    if (!defined($type) || $type eq '' || $type eq 'NOTFOUND') {
        $self->{last_message} = 'no external callout type defined';
        return $self->{default_on_error};
    }
    my $class = "SMTPCalloutConnector::".ucfirst($type);
    if (! eval "require $class") {
        $self->{last_message} = 'define external callout type does not exists';
        return $self->{default_on_error};
    }

    my @callout_params = ();
    my $params = $self->{domain}->getPref('extcallout_param');
    foreach my $p (split /:/, $params) {
        if ($p eq 'NOTFOUND') {
            next;
        }
        $p =~ s/__C__/:/;
        push @callout_params, $p;
    }

    my $connector = $class->new(\@callout_params);

    if ($connector->isUseable()) {
        my $res = $connector->verify($address);
        $self->{last_message} = $connector->lastMessage();
        return $res;
    }
    $self->{last_message} = $connector->lastMessage();
    return $self->{default_on_error};
}

sub lastMessage($self)
{
    my $msg = $self->{last_message};
    chomp($msg);
    return $msg;
}

1;
