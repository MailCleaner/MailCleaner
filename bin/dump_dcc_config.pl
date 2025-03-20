#!/usr/bin/env perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004-2014 Olivier Diserens <olivier@diserens.ch>
#   Copyright (C) 2015-2017 Florian Billebault <florian.billebault@gmail.com>
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
#
#
#   This script will dump the dccifd config file with the configuration
#   settings found in the database.
#
#   Usage:
#           dump_dcc_config.pl


use v5.36;
use strict;
use warnings;
use utf8;
use Carp qw( confess );

our ($SRCDIR, $VARDIR, $HOSTID, $MYMAILCLEANERPWD);
BEGIN {
    if ($0 =~ m/(\S*)\/\S+.pl$/) {
        my $path = $1."/../lib";
        unshift (@INC, $path);
    }
    require ReadConfig;
    my $conf = ReadConfig::getInstance();
    $SRCDIR = $conf->getOption('SRCDIR');
    $VARDIR = $conf->getOption('VARDIR');
    $HOSTID = $conf->getOption('HOSTID');
    $MYMAILCLEANERPWD = $conf->getOption('MYMAILCLEANERPWD');
    unshift(@INC, $SRCDIR."/lib");
}

require DB;
use lib_utils qw( open_as rmrf );
use File::Touch qw( touch );

our $DEBUG = 1;
our $uid = getpwnam('dcc');
our $gid = getgrnam('mailcleaner');

my $lasterror = "";

# Fix symlinks if broken
my %links = (
    '/var/lib/dcc/dcc_conf' => '/etc/dcc/dcc_conf',
    '/var/lib/dcc/flod' => '/etc/dcc/flod',
    '/var/lib/dcc/grey_flod' => '/etc/dcc/grey_flod',
    '/var/lib/dcc/grey_whitelist' => '/etc/dcc/grey_whitelist',
    '/var/lib/dcc/ids' => '/etc/dcc/ids',
    '/var/lib/dcc/map.txt' => '/etc/dcc/map.txt',
    '/var/lib/dcc/whiteclnt' => '/etc/dcc/whiteclnt',
    '/var/lib/dcc/whitecommon' => '/etc/dcc/whitecommon',
    '/var/lib/dcc/whitelist' => '/etc/dcc/whitelist',
);
foreach my $link (keys(%links)) {
    if (-e $link) {
        if (-l $link) {
            chown($uid, $gid, $link, $links{$link});
            next if (readlink($link) eq $links{$link});
        unlink($link);
        } else {
            rmrf($link);
        }
    }
    symlink($links{$link}, $link);
    chown($uid, $gid, $link, $links{$link});
}

# Add to mailcleaner group if not already a member
`usermod -a -G mailcleaner dcc` unless (grep(/\bmailcleaner\b/, `groups dcc`));

# Set proper permissions
chown($uid, $gid,
    '/var/lib/dcc/',
    '/var/lib/dcc/log',
    ${VARDIR}.'/spool/dcc',
    ${VARDIR}.'/run/dcc',
    glob(${VARDIR}.'/run/dcc/*'),
    glob('/var/lib/dcc/log/*'),
);
