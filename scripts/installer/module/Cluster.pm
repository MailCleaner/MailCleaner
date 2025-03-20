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
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

package module::Cluster;

use v5.36;
use strict;
use warnings;
use utf8;

BEGIN {
    if ($0 =~ m/(\S*)\/\S+\.pl$/) {
        my $path = $1."/../../lib";
        unshift(@INC, $path);
    }
    require ReadConfig;
}

require Exporter;
require DialogFactory;
use strict;

our @ISA = qw(Exporter);
our @EXPORT = qw(get ask do);
our $VERSION = 1.0;

sub get
{
    my $this = {};
    bless $this, 'module::Cluster';
    return $this;
}

sub do($this)
{
    my ($SRCDIR, $MYMAILCLENARPWD);
    my $conf = ReadConfig::getInstance();
    $SRCDIR = $conf->getOption('SRCDIR');
    $MYMAILCLENARPWD = $conf->getOption('MYMAILCLENARPWD') || undef;
    unless (defined($MYMAILCLENARPWD)) {
        print "Database password is not configured in `/etc/mailcleaner.conf`. Please run previous step. [Enter]\n";
        my $null = <STDIN>;
        return 0;
    }
    `$SRCDIR/scripts/configuration/slaves.pl --setmaster`
}

1;
