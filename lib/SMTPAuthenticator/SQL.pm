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

package SMTPAuthenticator::SQL;

use v5.36;
use strict;
use warnings;
use utf8;

use DBI();
require Exporter;

our @ISA = qw(Exporter);
our @EXPORT = qw(create authenticate);
our $VERSION = 1.0;


sub create($server,$port,$params)
{
    my $usessl = 0;
    my $database_type = 'MariaDB';
    my $database = '';
    my $dbtable = '',
    my $dbuser = '',
    my $dbpass = '',
    my $loginfield = '',
    my $passfield  = '',
    my $crypt_type = 'crypt';
    my $dsn = '';

    my @fields = split /:/, $params;
    if ($fields[0] && $fields[0] =~ /^[01]$/) { $usessl = $fields[0]; }
    if ($fields[1]) { $database_type = $fields[1]; }
    if ($fields[2]) { $database = $fields[2]; }
    if ($fields[3]) { $dbtable = $fields[3]; }
    if ($fields[4]) { $dbuser = $fields[4]; }
    if ($fields[5]) { $dbpass = $fields[5]; }
    if ($fields[6]) { $loginfield = $fields[6]; }
    if ($fields[7]) { $passfield = $fields[7]; }
    if ($fields[10]) { $crypt_type = $fields[10]; }

    if ($port < 1 ) {
        $port = 3306;
    }

    $dsn = "DBI:$database_type:database=$database;host=$server:$port";

    if ($server eq 'local') {
        require ReadConfig;
        my $conf = ReadConfig::getInstance();
        my $socket = $conf->getOption('VARDIR')."/run/mysql_slave/mysqld.sock";

        $dsn = "DBI:MariaDB:database=mc_config;host=localhost;mariadb_socket=$socket";
        $dbuser = 'mailcleaner';
        $dbpass = $conf->getOption('MYMAILCLEANERPWD');
        $dbtable = 'mysql_auth';
        $loginfield = 'username';
        $passfield = 'password';
    }

    my $self = {
        error_code => -1,
        error_text => "",
        server => $server,
        port => $port,
        usessl => $usessl,
        database_type => $database_type,
        database => $database,
        dbtable => $dbtable,
        dbuser => $dbuser,
        dbpass => $dbpass,
        loginfield => $loginfield,
        passfield => $passfield,
        crypt_type => $crypt_type,
        dsn => $dsn
    };

    bless $self, "SMTPAuthenticator::SQL";
    return $self;
}

sub authenticate($self,$username,$password)
{
    my $dbh = DBI->connect($self->{dsn}, $self->{dbuser}, $self->{dbpass}, {RaiseError => 0, PrintError => 0});
    if (! $dbh ) {
        $self->{'error_code'} = 1;
        $self->{'error_text'} = 'Could not connect to SQL server';
        return 0;
    }

    my $system = SystemPref::create();
    my $domain = $system->getPref('default_domain');
    $username =~ s/'//g;
    if ($username =~ m/(\S+)\@(\S+)/) {
        #  $username = $1;
        $domain = $2;
    }
    my $query = "SELECT ".$self->{passfield}." AS pass FROM ".$self->{dbtable}." WHERE ".$self->{loginfield}."='".$username."' AND domain='".$domain."'";

    my $sth = $dbh->prepare($query);
    if (!$sth) {
        $self->{'error_code'} = 2;
        $self->{'error_text'} = 'Could not prepare query';
        $dbh->disconnect() if ($dbh);
        return 0;
    }
    my $res = $sth->execute();
    my $ret = $sth->fetchrow_hashref();
    if (! $ret) {
        $self->{'error_code'} = 2;
        $self->{'error_text'} = "Bad username or password ($username)";
        $sth->finish();
        $dbh->disconnect() if ($dbh);
        return 0;
    }
    if ($ret->{pass}) {
        if (crypt ($password, $ret->{pass}) eq $ret->{pass}) {
            $sth->finish();
            $dbh->disconnect() if ($dbh);
            return 1;
        }
    }

    $self->{'error_code'} = 3;
    $self->{'error_text'} = "Bad username or password ($username)";

    $sth->finish();
    $dbh->disconnect() if ($dbh);
    return 0;
}

1;
