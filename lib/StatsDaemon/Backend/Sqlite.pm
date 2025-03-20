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

package StatsDaemon::Backend::Sqlite;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;
use threads;
use threads::shared;
use File::Copy;
use DBD::SQLite;
require ReadConfig;
use File::Path qw(mkpath);
use Fcntl qw(:flock SEEK_END);

our @ISA = qw(Exporter);
our @EXPORT = qw(new threadInit accessFlatElement stabilizeFlatElement getStats announceMonthChange announceDayChange);
our $VERSION = 1.0;

my $shema = "
CREATE TABLE stat (
    date       int,
    key        varchar(100),
    value      int,
    UNIQUE (date, key)
);
";

sub new($class,$daemon)
{
    my $conf = ReadConfig::getInstance();

    my $self = {
        'class' => $class,
        'daemon' => $daemon,
        'data' => undef,
        'basepath' => $conf->getOption('VARDIR') . '/spool/mailcleaner/stats',
        'dbfilename' => 'stats.sqlite',
        'history_avoid_keys' => '',
        'history_avoid_keys_a' => [],
        'template_database' => $conf->getOption('SRCDIR').'/lib/StatsDaemon/Backend/data/stat_template.sqlite'
    };

    bless $self, $class;

    foreach my $option (keys %{ $self->{daemon} }) {
        if (defined($self->{$option})) {
            $self->{$option} = $self->{daemon}->{$option};
        }
    }
    foreach my $o (split(/\s*,\s*/, $self->{history_avoid_keys})) {
        push @{$self->{history_avoid_keys_a}}, $o;
    }
    if (! -d $self->{basepath}) {
        mkpath($self->{basepath});
        $self->doLog("base path created: ".$self->{basepath});
    }
    $self->doLog("backend loaded", 'statsdaemon');

    $self->{data} = $StatsDaemon::data_;
    return $self;
}

sub threadInit($self)
{
    $self->doLog("backend thread initialization", 'statsdaemon');
}

sub accessFlatElement($self,$element)
{
    my $value = 0;

    my ($path, $file, $base, $el_key) = $self->getPathFileBaseAndKeyFromElement($element);
    if (! -f $file) {
        return $value;
    }
    my $dbh = $self->connectToDB($file);
    if (defined($dbh)) {
        my $current_date = sprintf( '%.4d%.2d%.2d',
                               $self->{daemon}->getCurrentDate()->{'year'},
                               $self->{daemon}->getCurrentDate()->{'month'},
                               $self->{daemon}->getCurrentDate()->{'day'});
        my $query = 'SELECT value FROM stat WHERE date='.$current_date.' AND key=\''.$el_key.'\'';
        my $res = $dbh->selectrow_hashref($query);
        $self->{daemon}->addStat( 'backend_read', 1 );
        if (defined($res) && defined($res->{'value'})) {
            $value = $res->{'value'};
        }
        $dbh->disconnect;
    } else {
        $self->doLog( "Cannot connect to database: " . $file, 'statsdaemon',
            'error' );
    }
    return $value;
}

sub stabilizeFlatElement($self,$element)
{
    my ($path, $file, $base, $el_key) = $self->getPathFileBaseAndKeyFromElement($element);
    foreach my $unwantedkey ( @{ $self->{history_avoid_keys_a} } ) {
        if ($el_key eq $unwantedkey) {
            return 'UNWANTEDKEY';
        }
    }

    if (! -d $path) {
        mkpath($path);
    }

    my $dbh = $self->connectToDB($file);
    if (defined($dbh)) {
        my $current_date = sprintf( '%.4d%.2d%.2d',
                               $self->{daemon}->getCurrentDate()->{'year'},
                               $self->{daemon}->getCurrentDate()->{'month'},
                               $self->{daemon}->getCurrentDate()->{'day'});
        my $query = 'REPLACE INTO stat (date,key,value) VALUES(?,?,?)';
        my $nbrows =  $dbh->do($query, undef, $current_date, $el_key, $self->{daemon}->getElementValueByName($element, 'value'));
        if (!defined($nbrows)) {
            $self->doLog( "Could not update database: " . $query, 'statsdaemon', 'error' );
        }
        $dbh->disconnect;
        $self->{daemon}->addStat( 'backend_write', 1 );
    } else {
        $self->doLog( "Cannot connect to database: " . $file, 'statsdaemon', 'error' );
        return '_CANNOTCONNECTDB';
    }

    return 'STABILIZED';
}

sub getStats($self,$start,$stop,$what,$data)
{
    return 'OK';
}

sub announceMonthChange($self)
{
    return;
}

sub doLog($self,$message,$given_set,$priority='info')
{
    my $msg = $self->{class}." ".$message;
    if ($self->{daemon}) {
        $self->{daemon}->doLog($msg, $given_set, $priority);
    }
}

sub getPathFileBaseAndKeyFromElement($self,$element)
{
    my @els = split(/:/, $element);
    my $key = pop @els;

    my $path = $self->{basepath}.'/'.join('/',@els);
    my $file = $path.'/'.$self->{dbfilename};
    my $base = join(':', @els);
    return (lc($path), lc($file), lc($base), lc($key));
}

sub connectToDB($self,$file)
{
    if (! -f $file) {
        copy($self->{template_database}, $file);
    }

    my $dbh = DBI->connect("dbi:SQLite:dbname=".$file,"","");
    if (!$dbh) {
        $self->doLog( "Cannot create database: " . $file, 'statsdaemon',
            'error' );
        return undef;
    }

    return $dbh;
}
1;
