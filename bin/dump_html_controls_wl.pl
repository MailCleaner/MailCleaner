#!/usr/bin/env perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
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
#           dump_html_controls_wl.pl

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
    $SRCDIR = $conf->getOption('SRCDIR') || '/usr/mailcleaner';
    $VARDIR = $conf->getOption('VARDIR') || '/var/mailcleaner';
}

use lib_utils qw(open_as);
require DB;

my $file = "${VARDIR}/spool/tmp/mailscanner/whitelist_HTML";
unlink($file);

my $dbh =  DB::connect('slave', 'mc_config');

my $sth = $dbh->prepare("SELECT sender FROM wwlists WHERE type='htmlcontrols'");
$sth->execute() or return;

my $count=0;

my $HTML_WL;
confess "Cannot open $file: $!\n" unless ($HTML_WL = ${open_as($file)});
while (my $ref = $sth->fetchrow_hashref() ) {
    print $HTML_WL $ref->{'sender'}."\n";
    $count++;
}
$sth->finish();
close $HTML_WL;

unlink $file unless $count;
