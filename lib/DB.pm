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
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA    02111-1307    USA
#
#
#   This module will just read the configuration file

package DB;

use v5.36;
use strict;
use warnings;
use utf8;
use Carp qw( confess );

BEGIN {
    my ($SRCDIR);
    if ($0 =~ m/(\S*)\/\S+.pl$/) {
        my $path = $1."/../lib";
        unshift (@INC, $path);
    }
    require ReadConfig;
    my $conf = ReadConfig::getInstance();
    $SRCDIR = $conf->getOption('SRCDIR') || '/usr/mailcleaner';
    unshift(@INC, $SRCDIR."/lib");
}

require Exporter;
use DBI();

our @ISA = qw(Exporter);
our @EXPORT = qw(connect);
our $VERSION = 1.0;

sub connect($type,$db='mc_config',$critical=1)
{
    if (!$type || $type !~ /slave|master|realmaster|custom/) {
        print "BADCONNECTIONTYPE (".(defined($type) ? $type : 'null').")\n";
        return "";
    }

    # determine socket to use
    my $conf = ReadConfig::getInstance();
    my $socket = $conf->getOption('VARDIR')."/run/mysql_master/mysqld.sock";
    $socket = $conf->getOption('VARDIR')."/run/mysql_slave/mysqld.sock" if ($type =~ /slave/);

    my $params = {};
    my $realmaster = 0;
    if ( $type =~ m/realmaster/) {
        $realmaster = getRealMaster($conf->getOption('VARDIR')."/spool/mailcleaner/master.conf", $params);
        confess("Failed to fetch parameters from master.conf\n") unless ($realmaster);
    } elsif ($type =~ m/custom/) {
        confess("'custom' type used without hashref as second argument\n") unless (ref($db));
        confess("'custom' type requires value for 'host' in second argument hashref\n") unless (defined($db->{'host'}));
        confess("'custom' type requires value for 'port' in second argument hashref\n") unless (defined($db->{'port'}));
        confess("'custom' type requires value for 'password' in second argument hashref\n") unless (defined($db->{'password'}));
        $params = $db;
        $realmaster = 1;
    }

    my $dbh;
    if ($realmaster) {
        $dbh = DBI->connect(
            "DBI:MariaDB:database=$db;host=$params->{'host'}:$params->{'port'};",
            "mailcleaner", $params->{'password'}, {RaiseError => 0, PrintError => 0, AutoCommit => 1}
        ) or fatal_error("CANNOTCONNECTDB", $critical);
    } else {
        $dbh = DBI->connect(
            "DBI:MariaDB:database=$db;host=localhost;mariadb_socket=$socket",
            "mailcleaner", $conf->getOption('MYMAILCLEANERPWD'), {RaiseError => 0, PrintError => 0}
        ) or fatal_error("CANNOTCONNECTDB 2", $critical);
    }

    my $self = {
        dbh => $dbh,
        type => $type,
        critical => $critical,
    };

    return bless $self, "DB";
}

sub getRealMaster($file, $params) {
    return 0 unless (-f $file);
    if (open(my $MASTERFILE, '<', $file)) {
        while (<$MASTERFILE>) {
            if (/HOST (\S+)/) { $params->{'host'} = $1; }
            if (/PORT (\S+)/) { $params->{'port'} = $1; }
            if (/PASS (\S+)/) { $params->{'password'} = $1; }
        }
        close $MASTERFILE;
    } else {
        confess("Failed to open $file to locate master\n");
    }
    confess("$file does not contain 'HOST' value\n") unless (defined($params->{'host'}));
    confess("$file does not contain 'PORT' value\n") unless (defined($params->{'port'}));
    confess("$file does not contain 'PASS' value\n") unless (defined($params->{'password'}));
    return 1;
}

sub getType($self)
{
    return $self->{dbh};
}

sub ping($self)
{
    if (defined($self->{dbh})) {
        return $self->{dbh}->ping();
    }
}

sub disconnect($self)
{
    my $dbh = $self->{dbh};
    if ($dbh) {
        $dbh->disconnect();
    }
    $self->{dbh} = "";
    return 1;
}

sub fatal_error($msg,$critical=0)
{
    return 0 unless ($critical);
    die("$msg\n");
}

sub prepare($self,$query)
{
    my $dbh = $self->{dbh};

    my $prepared = $dbh->prepare($query);
    if (! $prepared) {
        print "WARNING, CANNOT EXECUTE ($query => ".$dbh->errstr.")\n";
        return 0;
    }
    return $prepared;
}


sub execute($self,$query,$nolock=0)
{
    my $dbh = $self->{dbh};

    if (!defined($dbh)) {
        print "WARNING, DB HANDLE IS NULL\n";
        return 0;
    }
    if (!$dbh->do($query)) {
        print "WARNING, CANNOT EXECUTE ($query => ".$dbh->errstr.")\n";
        return 0;
    }
    return 1;
}

sub commit($self,$query)
{
    my $dbh = $self->{dbh};

    if (! $dbh->commit()) {
        print "WARNING, CANNOT commit\n";
        return 0;
    }
    return 1;
}

sub getListOfHash($self,$query,$nowarnings=0)
{
    my $dbh = $self->{dbh};
    my @results;

    my $sth = $dbh->prepare($query);
    my $res = $sth->execute();
    if (!defined($res)) {
        if (! $nowarnings) {
            print "WARNING, CANNOT QUERY ($query => ".$dbh->errstr.")\n";
        }
        return @results;
    }
    while (my $ref = $sth->fetchrow_hashref()) {
        push @results, $ref;
    }

    $sth->finish();
    return @results;
}

sub getList($self,$query,$nowarnings=0)
{
    my $dbh = $self->{dbh} || confess("Not connected to a database\n");
    my @results;

    my $sth = $dbh->prepare($query) || confess("Failed to run prepare: $query\n");
    my $res = $sth->execute() || confess("Failed to execute prepare: $query\n");
    if (!defined($res)) {
        if (! $nowarnings) {
            print "WARNING, CANNOT QUERY ($query => ".$dbh->errstr.")\n";
        }
        return @results;
    }
    while (my @ref = $sth->fetchrow_array()) {
        push @results, $ref[0];
    }

    $sth->finish();
    return @results;
}

sub getCount($self,$query)
{
    my $dbh = $self->{dbh};
    my $sth = $dbh->prepare($query);
    my $res = $sth->execute();

    my ($count) = $sth->fetchrow_array;

    return($count);
}


sub getHashRow($self,$query,$nowarnings=0)
{
    my $dbh = $self->{dbh};
    my %results;

    my $sth = $dbh->prepare($query);
    my $res = $sth->execute();
    if (!defined($res)) {
        if (! $nowarnings) {
            print "WARNING, CANNOT QUERY ($query => ".$dbh->errstr.")\n";
        }
        return %results;
    }

    my $ret = $sth->fetchrow_hashref();
    foreach my $key (keys %$ret ) {
        $results{$key} = $ret->{$key};
    }
    $sth->finish();
    return %results;
}

sub getLastID($self)
{
    my $res = 0;
    my $query = "SELECT LAST_INSERT_ID() as lid;";

    my $sth = $self->{dbh}->prepare($query);
    my $ret = $sth->execute();
    if (!$ret) {
        return $res;
    }
    $ret = $sth->fetchrow_hashref();
    if (!defined($ret)) {
        return $res;
    }

    if (defined($ret->{'lid'})) {
        return $ret->{'lid'};
    }
    return $res;
}

sub getError($self)
{
    my $dbh = $self->{dbh};

    if (defined($dbh->errstr)) {
        return $dbh->errstr;
    }
    return "";
}

sub setAutoCommit($self,$v=0)
{
    if ($v) {
        $self->{dbh}->{AutoCommit} = 1;
        return 1;
    }
    $self->{dbh}->{AutoCommit} = 0;
    return 0
}

1;
