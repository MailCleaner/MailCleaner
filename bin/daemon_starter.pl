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
##  daemon_starter:
##      starter/stopper executable for threaded daemon (based on PreForkTDaemon)
##
##  usage: daemon_starter.pl daemonclass [parameters] (start|stop|restart|status)
##
##     parameters are configuration file's element that can be forced here.
##        syntax is: -optioname value
#

use v5.36;
use strict;
use warnings;
use utf8;

if ($0 =~ m/(\S*)\/\S+.pl$/) {
    my $path = $1."/../lib";
    unshift (@INC, $path);
}

if ( scalar(@ARGV) < 2 ) {
    show_usage('not enough parameters');
}
my $daemon = shift @ARGV;
my $action = pop @ARGV;

if ( $action !~ /^(start|stop|restart|status)$/ ) {
    show_usage( 'bad action (' . $action . ')' );
}

my %options;
while (my $k = shift(@ARGV)) {
    if ( $k !~ /^\-(\S+)/ ) {
        print
"Warning: parameter ($k) is a value without a key. It will be discarded.\n";
        next;
    }
    my $key = $1;
    my $v   = shift @ARGV || '';
    if ( $v =~ /^-/ ) {
        print
"Warning: parameter key ($v) comes before a value. It will be discarded.\n";
        next;
    }
    $options{$key} = $v;
}

## include lib paths
my $path;
if ( $0 =~ m/(\S*)\/\S+.pl$/ ) {
    $path = $1 . "/../lib";
    unshift( @INC, $path );
} else {
    show_usage('no daemon available');
}
if ( !-f $path . "/" . $daemon . ".pm" ) {
    show_usage('not such daemon available');
}
require $daemon . ".pm";
my $service = $daemon->new( \%options );

if ( $action eq 'start' ) {
    $service->initDaemon();
} elsif ( $action eq 'stop' ) {
    $service->exitDaemon();
} elsif ( $action eq 'restart' ) {
    print "  Stopping... ";
    $service->exitDaemon();
    print "stopped\n  Starting...";
    $service->initDaemon();
} elsif ( $action eq 'status' ) {
    my $res = $service->status();
    if ( $res =~ /^_/ ) {
        print "No status available (" . $res . ")\n";
    } elsif ( $res =~ /NOSERVER/ ) {
        print "not running.\n";
    } else {
        $res =~ s/^\-/running.\n-/;
        print $res . "\n";
    }
}

exit 0;

sub show_usage($reason)
{
    print "daemon_starter: Bad usage ($reason).\n";
    print
"\t daemonstarter daemon [parameters] (start|stop|restart|status)\n";

    exit 1;
}
