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

my $logfile="$VARDIR/log/mailscanner/infolog";

open(my $LOGFILE, '<', $logfile) or die "cannot open log file: $logfile\n";

our %counts = ();
our %sums = ();
our %max = ();
our %min = ();
our %hourly_counts = ();
our %hourly_sums = ();
while (<$LOGFILE>) {
    if (/\d+\.\d+s/) {
        my $hour = 0;
        if (/\w+\s+\d+\s+(\d+):\d+:\d+/) {
            $hour = $1;
        }
        my @fields = split / /,$_;
        foreach my $field (@fields) {
         if ($field =~ m/\((\w+):(\d+\.?\d*)s\)/) {
             $sums{$1} += $2;
             $hourly_sums{$hour}{$1} += $2;
             $counts{$1}++;
             $hourly_counts{$hour}{$1}++;
             if ($2 > $max{$1}) {
                 $max{$1} = $2;
             }
             if (!defined($min{$1}) || $min{$1} eq "" || $2 < $min{$1}) {
                 $min{$1} = $2;
             }
         }
        }
    }
}
close $LOGFILE;

print "-----------------------------------------------------------------------------------------------\n";
printStat('Prefilters');
my $av = 0;
if (defined($counts{'Prefilters'}) && $counts{'Prefilters'} > 0) {
    $av = $sums{'Prefilters'}/$counts{'Prefilters'};
}
my $msgpersec = 'nan';
if ($av > 0 ) {
    $msgpersec = 1/$av;
}
print "     rate: ".(int($msgpersec*10000)/10000)." msgs/s\n";
print "-----------------------------------------------------------------------------------------------\n";
foreach my $var (sort hashValueDescendingNum(keys %counts)) {
    next if ($var eq 'Prefilters');
    printStat($var);
}
print "-----------------------------------------------------------------------------------------------\n";
print "Hourly stats: \n";
my @h = sort keys %hourly_counts;
foreach my $hour (@h) {
    printHourly($hour, 'Prefilters');
}

sub printStat($var)
{
    my $av = 0;
    if (defined($counts{$var}) && $counts{$var} > 0) {
     $av = (int(($sums{$var}/$counts{$var})*10000)/10000);
    }
    my $percent = 0;
    if ($counts{'SpamCacheCheck'} > 0) {
        $percent = (int( (100/$counts{'SpamCacheCheck'} * $counts{$var}) * 100) / 100);
    }
    print $var.": ".$counts{$var}." ($percent%) => ".$av."s (max:".$max{$var}."s, min:".$min{$var}."s)\n";
}

sub printHourly($h,$var)
{
    if (defined($hourly_counts{$h}{$var}) && $hourly_counts{$h}{$var} > 0) {
        $av = (int(($hourly_sums{$h}{$var}/$hourly_counts{$h}{$var})*10000)/10000);
    }
    print $h.": ".$hourly_counts{$h}{$var}." => ".$av."s \n";
}

sub hashValueAscendingNum
{
    $counts{$a} <=> $counts{$b};
}

sub hashValueDescendingNum
{
    $counts{$b} <=> $counts{$a};
}
