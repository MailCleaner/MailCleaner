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
    $SRCDIR = $conf->getOption('SRCDIR');
    $VARDIR = $conf->getOption('VARDIR');
    unshift(@INC, $SRCDIR."/lib");
}

my $logfile="${VARDIR}/log/exim_stage4/mainlog";

open(my $LOGFILE, '<', $logfile) or die "cannot open log file: $logfile\n";

our %counts = ();
our %sums = ();
our %max = ();
our %min = ();
while (<$LOGFILE>) {
    if (/\d+\.\d+s/) {
        my @fields = split / /,$_;
        foreach my $field (@fields) {
         if ($field =~ m/\((\d+\.\d+)s\/(\d+\.\d+)s\)/) {
             $sums{'global'} += $2;
             $counts{'global'}++;
             if ($2 > $max{'global'}) {
                 $max{'global'} = $2;
             }
             if (!defined($min{'global'}) || $min{'global'} eq "" || $2 < $min{'global'}) {
                 $min{'global'} = $2;
             }
         }

         if ($field =~ m/\((\w+):(\d+\.\d+)s\)/) {
             $sums{$1} += $2;
             $counts{$1}++;
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
printStat('global');
my $av = 0;
if (defined($counts{'global'}) && $counts{'global'} > 0) {
    $av = $sums{'global'}/$counts{'global'};
}
my $msgpersec = 'nan';
if ($av > 0 ) {
    $msgpersec = 1/$av;
}
print "     rate: ".(int($msgpersec*10000)/10000)." msgs/s\n";
print "-----------------------------------------------------------------------------------------------\n";
foreach my $var (keys %counts) {
    next if ($var eq 'global');
    printStat($var);
}

sub printStat($var)
{
    if (defined($counts{$var}) && $counts{$var} > 0) {
        $av = (int(($sums{$var}/$counts{$var})*10000)/10000);
    }
    print $var.": ".$counts{$var}." => ".$av."s (max:".$max{$var}."s, min:".$min{$var}."s)\n";
}
