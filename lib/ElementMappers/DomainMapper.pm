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

package ElementMappers::DomainMapper;

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

    my @field_domain_o = ('name', 'destination', 'callout', 'altcallout', 'adcheck', 'forward_by_mx', 'greylist');

    my $self = {
        my %prefs => (),
        my %field_domain => (),
        'name' => '',
        'destination' => '',
        my @params => ()
    };

    bless $self, "ElementMappers::DomainMapper";
    $self->{prefs}{'name'} = '';
    $self->{prefs}{'destination'} = '';
    $self->{field_domain} = {'name' => 1, 'destination' => 1, 'callout' => 1, 'altcallout' => 1, 'adcheck' => 1, 'forward_by_mx' => 1, 'greylist' => 1};

    return $self;
}

sub setNewDefault($self,$defstr)
{
    foreach my $data (split('\s', $defstr)) {
        if ($data =~ m/(\S+):(\S+)/) {
            my $val = $2;
            my $key = $1;
            $val =~ s/__S__/ /g;
            $val =~ s/__P__/:/g;
            $self->{prefs}{$key} = $val;
        }
    }
}

sub checkElementExistence($self,$name)
{
    my $check_query = "SELECT name, prefs FROM domain WHERE name='$name'";
    my %check_res = $self->{db}->getHashRow($check_query);
    if (defined($check_res{'prefs'})) {
        return $check_res{'prefs'};
    }
}

sub processElement($self,$name,$flags='',$params='')
{
    my $update = 1;
    $update = 0 if ($flags =~ m/noupdate/ );

    $self->{params} = ();
    $self->{prefs}{'name'} = $name;
    if ($params) {
        foreach my $el (split(':', $params) ) {
            chomp($el);
            $el =~ s/^\s+//;
            #print "\nSetting param: $el from $params\n";
            push @{$self->{params}}, $el;
        }
    }

    my $pref = 0;
    $pref = $self->checkElementExistence($name);
    if ($pref > 0) {
        return 1 if (! $update );
        return $self->updateElement($name, $pref);
    }
    return $self->addNewElement($name);
}

sub updateElement($self,$name,$pref)
{
    my $set_prefquery = $self->getPrefQuery();
    if (! $set_prefquery eq '') {
        my $prefquery = "UPDATE domain_pref SET ".$set_prefquery." WHERE id=".$pref;
        $self->{db}->execute($prefquery);
        print $prefquery."\n";
    }

    my $set_domquery = $self->getDomQuery();
    if (! $set_domquery eq '') {
        my $dom_query = "UPDATE domain SET ".$set_domquery." WHERE name='$name'";
        $self->{db}->execute($dom_query);
        print $dom_query."\n";
    }
}

sub getPrefQuery($self)
{
    my $set_prefquery = '';
    foreach my $datak (keys %{$self->{prefs}}) {
        if (! defined($self->{field_domain}{$datak})) {
            my $val = $self->{prefs}{$datak};
            $val =~ s/PARAM(\d+)/$self->{params}[$1-1]/g;
            $set_prefquery .= "$datak='".$val."', ";
        }
    }
    $set_prefquery =~ s/, $//;
    return $set_prefquery;
}

sub getDomQuery($self)
{
    my $set_domquery = '';
    foreach my $datak (keys %{$self->{prefs}}) {
        if (defined($self->{field_domain}{$datak})) {
            my $val = $self->{prefs}{$datak};
            $val =~ s/PARAM(\d+)/$self->{params}[$1-1]/g;
            $set_domquery .= "$datak='".$val."', ";
        }
    }
    $set_domquery =~ s/, $//;
    return $set_domquery;
}

sub addNewElement($self,$name)
{
    my $set_prefquery = $self->getPrefQuery();
    my $prefquery = "INSERT INTO domain_pref SET id=NULL";
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

    my $set_domquery = $self->getDomQuery();
    my $query  = "INSERT INTO domain SET prefs=".$prefid;
    if (! $set_domquery eq '') {
        $query .= ", ".$set_domquery;
    }
    $self->{db}->execute($query);
    print $query."\n";
}

sub deleteElement($self,$name)
{
    my $getprefid = "SELECT prefs FROM domain WHERE name='$name'";
    my %res = $self->{db}->getHashRow($getprefid);
    if (!defined($res{'prefs'})) {
        print "WARNING ! could not get preferences id for: $name!\n";
        return;
    }
    my $prefid = $res{'prefs'};

    my $deletepref = "DELETE FROM domain_pref WHERE id=$prefid";
    $self->{db}->execute($deletepref);
    print $deletepref."\n";
    my $deletedomain = "DELETE FROM domain WHERE name='$name'";
    $self->{db}->execute($deletedomain);
    print $deletedomain."\n";
    return;
}

sub getExistingElements($self)
{
    my $query = "SELECT name FROM domain";
    my @res = $self->{db}->getList($query);

    return @res;
}

1;
