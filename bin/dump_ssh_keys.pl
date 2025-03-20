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
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
#
#   This script will dump the ssh key files
#
#   Usage:
#           dump_ssh_keys.pl

use v5.36;
use strict;
use warnings;
use utf8;
use Carp qw( confess );

our ($SRCDIR, $VARDIR;
BEGIN {
    if ($0 =~ m/(\S*)\/\S+.pl$/) {
        my $path = $1."/../lib";
        unshift (@INC, $path);
    }
    require ReadConfig;
    my $conf = ReadConfig::getInstance();
    $SRCDIR = $conf->getOption('SRCDIR') || '/usr/mailcleaner';
    $VARDIR = $conf->getOption('VARDIR') || '/var/mailcleaner';
}

use lib_utils qw(open_as);
require DB;

my $known_hosts_file = ${VARDIR}."/.ssh/known_hosts";
my $authorized_file = ${VARDIR}."/.ssh/authorized_keys";

unlink($known_hosts_file);
unlink($authorized_file);

do_known_hosts();
my $uid = getpwnam('mailcleaner');
my $gid = getgrnam('mailcleaner');
chown($uid, $gid, $known_hosts_file);

do_authorized_keys();
chown($uid, $gid, $authorized_file);

sub do_known_hosts()
{
    my $dbh = DB::connect('slave', 'mc_config');

    my $sth = $dbh->prepare("SELECT hostname, ssh_pub_key FROM slave");
    $sth->execute() or return;

    my $KNOWNHOST;
    confess "Cannot open $KNOWNHOST: $!" unless ($KNOWNHOST = ${open_as($known_hosts_file, '>>')});
    while (my $ref = $sth->fetchrow_hashref() ) {
        print $KNOWNHOST $ref->{'hostname'}." ".$ref->{'ssh_pub_key'}."\n";
    }
    close $KNOWNHOST;
    $sth->finish();
    return;
}

sub do_authorized_keys()
{
    my $dbh = DB::connect('slave', 'mc_config');

    my $sth = $dbh->prepare("SELECT ssh_pub_key FROM master");
    $sth->execute() or return;

    my $AUTHORIZED;
    confess "Cannot open $AUTHORIZED $!" unless ($AUTHORIZED = ${open_as($authorized_file, '>>')});
    while (my $ref = $sth->fetchrow_hashref() ) {
        print $AUTHORIZED $ref->{'ssh_pub_key'}."\n";
    }
    close $AUTHORIZED;
    $sth->finish();
    return;
}
