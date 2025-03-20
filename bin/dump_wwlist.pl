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
#   This script will dump the whitelist and warnlists for the system/domain/user
#
#   Usage:
#           dump_wwlists.pl [domain|user]

use v5.36;
use strict;
use warnings;
use utf8;
use Carp qw( confess );

my ($SRCDIR, $VARDIR);
BEGIN {
    if ($0 =~ m/(\S*)\/\S+.pl$/) {
        my $path = $1."/../lib";
        unshift (@INC, $path);
    }
    require ReadConfig;
    my $conf = ReadConfig::getInstance();
    $SRCDIR = $conf->getOption('SRCDIR');
    $VARDIR = $conf->getOption('VARDIR');
    unshift(@INC, $SRCDIR."/lib");
}

use lib_utils qw( create_and_open );
use File::Path qw( make_path );

require DB;

my $uid = getpwnam( 'mailcleaner' );
my $gid = getgrnam( 'mailcleaner' );

my $what = shift;
if (!defined($what)) {
    $what = "";
}
my $to = "";
my $filepath = "${VARDIR}/spool/mailcleaner/prefs/";
if ($what =~ /^\@([a-zA-Z0-9\.\_\-]+)$/) {
    $to = $what;
    $filepath .= $1."/_global/";
} elsif ($what =~ /^([a-zA-Z0-9\.\_\-]+)\@([a-zA-Z0-9\.\_\-]+)/) {
    $to = $what;
    $filepath .= $2."/".$1."@".$2."/";
} else {
    $filepath .= "_global/";
}

my $slave_db = DB::connect('slave', 'mc_config');

dumpWWFiles($to, $filepath);

$slave_db->disconnect();

sub dumpWWFiles($to,$filepath)
{
    my @types = ('warn', 'white');

    foreach my $type (@types) {
        my @list = $slave_db->getList("SELECT sender FROM wwlists WHERE
            status=1 AND type='".$type."' AND recipient='".$to."'"
        );

        my $file = $filepath."/".$type.".list";
        if ( -f $file) {
            unlink $file;
        }

        next unless (scalar(@list));

        make_path($filepath, {'mode'=>0755,'user'=>'mailcleaner','group'=>'mailcleaner'});

        my $WWFILE;
        confess "Failed to open $file\n" unless ($WWFILE = ${create_and_open($file)});

        foreach my $entry (@list) {
            print $WWFILE "$entry\n";
        }

        close $WWFILE;
    }
    return 1;
}
