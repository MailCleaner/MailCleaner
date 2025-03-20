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

package ElementMappers::EmailMapper;

use v5.36;
use strict;
use warnings;
use utf8;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(create setNewDefault processElement);
our $VERSION    = 1.0;
require          Exporter;

sub create
{

    my $self = {
        my %prefs => (),
        my %field_email => (),
    };

    bless $self, "ElementMappers::EmailMapper";
    $self->{prefs}{'address'} = '';
    $self->{field_email} = {'address' => 1, 'user' => 1, 'is_main' => 1};

    return $self;
}

sub setNewDefault($self,$defstr)
{
    foreach my $data (split('\s', $defstr)) {
        if ($data =~ m/(\S+):(\S+)/) {
        $self->{prefs}{$1} = $2;
        }
    }
}

sub checkElementExistence($self,$address)
{
    my $check_query = "SELECT address, pref FROM email WHERE address='$address'";
    my %check_res = $self->{db}->getHashRow($check_query);
    if (defined($check_res{'prefs'})) {
        return $check_res{'prefs'};
    }
    return 0;
}

sub processElement($self,$address,$flags='',$params='')
{
    my $update = 1;
    $update = 0 if ( $flags =~ m/noupdate/ );
    $self->{prefs}{'address'} = lc($address);

    my $pref = 0;
    $pref = $self->checkElementExistence($self->{prefs}{'address'});
    if ($pref > 0 && $update) {
        return 1 if (! $update );
        return $self->updateElement($self->{prefs}{'address'}, $pref);
    }
    return $self->addNewElement($self->{prefs}{'address'});
}

sub updateElement($self,$address,$pref)
{
    my $set_prefquery = $self->getPrefQuery();
    if (! $set_prefquery eq '') {
        my $prefquery = "UPDATE user_pref SET ".$set_prefquery." WHERE id=".$pref;
        $self->{db}->execute($prefquery);
        print $prefquery."\n";
    }

    my $set_emailquery = $self->getEmailQuery();
    if (! $set_emailquery eq '') {
        my $email_query = "UPDATE email SET ".$set_emailquery." WHERE address='$address'";
        $self->{db}->execute($email_query);
        print $email_query."\n";
    }
}

sub getPrefQuery($self)
{
    my $set_prefquery = '';
    foreach my $datak (keys %{$self->{prefs}}) {
        if (! defined($self->{field_email}{$datak})) {
            $set_prefquery .= "$datak='".$self->{prefs}{$datak}."', ";
        }
    }
    $set_prefquery =~ s/, $//;
    return $set_prefquery;
}

sub getEmailQuery($self)
{
    my $set_emailquery = '';
    foreach my $datak (keys %{$self->{prefs}}) {
        if (defined($self->{field_email}{$datak})) {
            $set_emailquery .= "$datak='".$self->{prefs}{$datak}."', ";
        }
    }
    $set_emailquery =~ s/, $//;
    return $set_emailquery;
}

sub addNewElement($self,$address)
{
    my $set_prefquery = $self->getPrefQuery();
    my $prefquery = "INSERT INTO user_pref SET id=NULL";
    if (! $set_prefquery eq '') {
        $prefquery .= " , ".$set_prefquery;
    }
    print $prefquery."\n";
    $self->{db}->execute($prefquery.";");

    my $getid = "SELECT LAST_INSERT_ID() as id;";
    my %res = $self->{db}->getHashRow($getid);
    if (!defined($res{'id'})) {
        print "WARNING ! could not get last inserted id!\n";
        return;
    }
    my $prefid = $res{'id'};

    my $set_emailquery = $self->getEmailQuery();
    my $query  = "INSERT INTO email SET pref=".$prefid;
    if (! $set_emailquery eq '') {
        $query .= ", ".$set_emailquery;
    }
    $self->{db}->execute($query);
    print $query."\n";
}

sub deleteElement($self,$address)
{
    my $query = "DELETE FROM email WHERE address=".$address;
    my @res = $self->{db}->getList($query);
    return @res;
}

sub getExistingElements($self)
{
    my $query = "SELECT address FROM email";
    my @res = $self->{db}->getList($query);
    return @res;
}

1;
