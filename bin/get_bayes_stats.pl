#!/usr/bin/env perl
#
# Mailcleaner - SMTP Antivirus/Antispam Gateway
# Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
# Copyright (C) 2023 John Mertz <git@john.me.tz>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
#

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
    unshift(@INC, $SRCDIR."/lib");
}

my $givenmode = shift;
my $mode = "";
if (defined($givenmode) && $givenmode =~ /^(-v|-h)$/) {
    $mode = 'h';
    if ($givenmode eq "-v") {
        $mode = "v";
    }
}

my $MSLOGFILE="${VARDIR}/log/mailscanner/infolog";

open(my $LOGFILE, '<', $MSLOGFILE) || confess("Cannot open log file: $MSLOGFILE\n");

my $msgs = 0;
my $spam_sure = 0;
my $ham_sure = 0;
my $weird = 0;
my $unsure = 0;
my $hour = 0;
my %hourly_counts;
my $samsgs = 0;
my $saspams = 0;
my $sahams = 0;
my $saunsure = 0;
while (<$LOGFILE>) {
    if (/(\d\d):\d\d:\d\d .* NiceBayes result (is not spam|is spam) \(([^)]+)\)/) {
        $msgs++;
        my $hour = $1;
        #my %hcount = ( 'msgs' => 0, 'spam' => 0, 'ham' => 0, 'weird' => 0, 'unsure' => 0);
        $hourly_counts{$hour}{'msgs'}++;

        my $percent = $3;
        if ($percent =~ m/^(100|99)(\.\d+)?%$/) {
        #if ($percent =~ m/^(100|9\d)(\.\d+)?%$/) {
            $spam_sure++;
            $hourly_counts{$hour}{'spam'}++;
            #print "found SPAM sure: $percent\n";
        } elsif ($percent =~ m/^0%$/) {
            $ham_sure++;
            $hourly_counts{$hour}{'ham'}++;
            #print "found HAM sure: $percent\n";
        } elsif ($percent eq "") {
            $hourly_counts{$hour}{'weird'}++;
            $weird++;
        } else {
            $hourly_counts{$hour}{'unsure'}++;
            $unsure++;
        }
    }

    if (/(\d\d):\d\d:\d\d .*(:?SpamAssassin|Spamc) \(.*\)/)        {
        $samsgs++;
        my $hour = $1;
        $hourly_counts{$hour}{'samsgs'}++;
        if (/BAYES_99/) {
            $saspams++;
            $hourly_counts{$hour}{'saspams'}++;
        } elsif (/BAYES_0/) {
            $sahams++;
            $hourly_counts{$hour}{'sahams'}++;
        } else {
            $saunsure++;
            $hourly_counts{$hour}{'saunsure'}++;
        }
    }
}
close $LOGFILE;

my $certainty = 0;
my $sacertainty = 0;
if ($msgs > 0) {
 $certainty = int( (100 / $msgs) * ($spam_sure + $ham_sure) * 100) / 100;
 $sacertainty = int( (100 / $samsgs) * ($saspams + $sahams) * 100) / 100;
}

if ($mode eq "v") {
    print "messages: $msgs\n";
    print "spam sure: $spam_sure\n";
    print "ham sure: $ham_sure\n";
    print "weird: $weird\n";
    print "unsure: $unsure\n";
    print "certainty: $certainty%\n";
    print "### SpamAssassin\n";
    print "SA messages: $samsgs\n";
    print "SA spam sure: $saspams\n";
    print "SA ham sure: $sahams\n";
    print "SA unsure: $saunsure\n";
    print "SA certainty: $sacertainty%\n";
    exit 0;
}

if ($mode eq "h") {
    foreach my $hour (sort keys %hourly_counts) {
        my $p = int( (100 / $hourly_counts{$hour}{'msgs'}) * ($hourly_counts{$hour}{'spam'} + $hourly_counts{$hour}{'ham'}) * 100) / 100;
        print "hour: $hour    => ".$hourly_counts{$hour}{'msgs'}."/".$hourly_counts{$hour}{'spam'}."/".$hourly_counts{$hour}{'ham'}." => $p%\n";
    }
    exit 0;
}

print "$certainty|$sacertainty\n";
exit 0;
