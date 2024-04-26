#!/usr/bin/perl -w
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
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
#   This script will output the count of messages/spams/viruses for a domain/user or globaly for a given period
#
#   Usage:
#       get_stats.pl domain|user|_global begindate enddate [-v]

use strict;
if ($0 =~ m/(\S*)\/get_stats\.pl$/) {
     my $path = $1."/../lib";
     unshift (@INC, $path);
}
require ReadConfig;
require Stats;

my $what = shift;
my $begin = shift;
my $end = shift;
my $verbose_o = shift;
my $fulldays_o = shift;
my $verbose = 0;
my $batchmode = 0;
my $fulldays = 0;
my $debug = 0;
my $msgs = 0;
my $spams = 0;
my $highspams = 0;
my $viruses = 0;
my $names = 0;
my $others = 0;
my $cleans = 0;
my $bytes = 0;
my $users = 0;
my $domains = 0;
my %global = ('msgs' => 0, 'spams' => 0, 'highspams' => 0, 'names' => 0, 'others' => 0, 'cleans' => 0, 'bytes' => 0, 'users' => 0, 'domains' => 0);

# check parameters
if (!defined($what) || $what !~ m/^[a-zA-Z0-9@._\-,*]+$/ ) {
    badUsage('what', $what);
}
if (!defined($begin) || $begin !~ m/^\-?\d{1,8}$/ ) {
    badUsage('begin', $begin);
}
if (!defined($end) || $end !~ /^\+?\d{1,8}$/ ) {
    badUsage('end', $end);
}
#if ($begin !~ /^\d{8}/ && $end !~ /^\d{8}/) {
#    badUsage('begin and end');
#}

if (defined($verbose_o) && $verbose_o eq '-v') {
    $verbose = 1;
}
if (defined($verbose_o) && $verbose_o eq '-b') {
    $batchmode = 1;
}
if ( ( defined($verbose_o) && $verbose_o eq '-f') ||  ( defined($fulldays_o) && $fulldays_o eq '-f') ) {
    $fulldays = 1;
}

print "PID ".$$."\n" if $batchmode;
print "STARTTIME ".time()."\n" if $batchmode;

my $conf = ReadConfig::getInstance();
my $basedir = $conf->getOption('VARDIR')."/spool/mailcleaner/counts";
my @dirs;
my $dir = '';
my %whats;

my %domains;
my $domainsfile = $conf->getOption('VARDIR')."/spool/tmp/mailcleaner/domains.list";
if (open(DOMAINFILE, '<'.$domainsfile)) {
    while (<DOMAINFILE>) {
        if (/^(\S+)\:/) {
            $domains{$1} = 1;
        }
    }
    close(DOMAINFILE);
}

my @tmpwhats = split /,/, $what;
foreach my $what ( @tmpwhats ) {

    $what = lc($what);
    if ($what !~ /\*/) {
        if ($what =~ /^(\S+)@(\S+)/) {
            $dir = $basedir."/$2/$1";
        } elsif ($what eq "_global") {
            $dir = $basedir."/_global";
        } else {
            $dir = $basedir."/$what/_global";
        }
        if (-d $dir) {
            push @dirs, $dir;
            $whats{$dir} = $what;
        }

    } else {
        my $isdomain = 0;
        my $domain = '';
        if ($what =~ /\*@(\S+)/) {
            $dir = $basedir."/$1";
            $domain = $1;
        } else {
            $dir = $basedir;
            $isdomain = 1;
        }
        if (-d $dir) {
            opendir(DIR, $dir);
            while(my $entry = readdir(DIR)) {
                next if $entry =~ /^\./;
                next unless (-d $dir."/".$entry);
                my $skip = 0;
                foreach (split(//,$entry)) {
                    if (unpack("H*",$_) =~ /([01][0-9a-f]|7f)/) {
                        $skip = 1;
                        last;
                    }
                }
                next if $skip;
                if ($what eq '*') {
                    next if $entry eq '_global';
                    next if (!defined($domains{$entry}));
                }
                my $fullentry = $dir."/".$entry;
                $fullentry .= "/_global" if $isdomain && $entry ne '_global';
                if (-d $fullentry) {
                    push @dirs, $fullentry;
                    $whats{$fullentry} = $entry;
                    if ($domain ne '') {
                        $whats{$fullentry} .= '@'.$domain;
                    }
                }
            }
            closedir(DIR);
        }
    }
}

## compute start and end dates
my $start = `date +%Y%m%d`;
my $stop = `date +%Y%m%d`;
if ($begin =~ /^\d{8}/ && $end =~ /^\d{8}/) {
    if (int($end) lt int($begin)) {
        badUsage("end date should come after begin date ($begin, $end)", $begin, $end);
    }
    $start = $begin;
    $stop = $end;
}
if ($begin =~ /^(\d{4})(\d{2})(\d{2})$/ && $end =~ /^\+/) {
    $start = $begin;
    $stop = addDate($start, $end);
}
if ($end =~ /^(\d{4})(\d{2})(\d{2})$/ && $begin =~ /^\-/) {
    $stop = $end;
    $start = addDate($stop, $begin);
}
if ($begin =~ /^\-/ && $end =~ /^\+/) {
    $start = addDate($start, $begin);
    $stop = addDate($start, $end);
}

if ($debug) {
    print "start date: $start\n";
    print "stop date: $stop\n";
}

my $day = $start;
foreach my $dir (@dirs) {
    clearStats();

    my $tday = $day;
    while ($tday <= $stop) {
        my $file = $dir."/".$tday."_counts";
        processFile($file);
        print $whats{$dir}.":" if $batchmode && $fulldays;
        print $tday.":" if $fulldays;
        returnStats($dir) if $fulldays;
        $tday = addDate($tday, '+1');
        clearStats() if $fulldays;
    }
    print $whats{$dir}.":" if $batchmode && !$fulldays;
    addGlobalStats();
    returnStats($dir) if !$fulldays;
}

if ($what eq '*') {
    print "_global:".$global{'msgs'}.'|'.$global{'spams'}.'|'.$global{'highspams'}.'|'.$global{'viruses'}.'|'.$global{'names'}.'|'.$global{'others'}.'|'.$global{'cleans'}.'|'.$global{'bytes'}.'|'.$global{'users'}.'|'.$global{'domains'}."\n";
}
print "STOPTIME ".time()."\n" if $batchmode;
print "done.\n" if $batchmode;

exit 0;

#######################
sub clearStats {
    $msgs = 0;
    $spams = 0;
    $highspams = 0;
    $viruses = 0;
    $names = 0;
    $others = 0;
    $cleans = 0;
    $bytes = 0;
    $users = 0;
    $domains = 0;
}

#######################
sub processFile {
    my $file = shift;

    my ($cmsgs, $cspams, $chighspams, $cviruses, $cnames, $cothers, $ccleans, $cbytes, $cusers, $cdomains) = Stats::readFile($file);

    $msgs = $msgs + $cmsgs;
    $spams = $spams + $cspams;
    $highspams = $highspams + $chighspams;
    $viruses = $viruses + $cviruses;
    $names = $names + $cnames;
    $others = $others + $cothers;
    $cleans = $cleans + $ccleans;
    $bytes = $bytes + $cbytes;
    if ($cusers > $users) {
        $users = $cusers;
    }
    if ($cdomains > $domains) {
        $domains = $cdomains;
    }
}

#######################
sub addDate {
    my $in = shift;
    my $add = shift;

    if ($in !~ m/^(\d{4})(\d{2})(\d{2})$/ ) {
        return $in;
    }
    my $sy = $1;
    my $sm = $2;
    my $sd = $3;

    if ($add !~ m/^([\-\+])(\d+)$/) {
        return $in;
    }
    my $op = $1;
    my $delta = $2;

    my @nbdays = (31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    # Compensate for leap year:
    if ( $sy % 4 == 0 ) {
        $nbdays[1] += 1;
    }

    my $adelta = $delta;
    while ($adelta > 0) {
        if ($op eq '+') {
            $sd = $sd + 1;
            if ($sd > $nbdays[$sm-1]) {
                $sd = 1;
                $sm = $sm + 1;
            }
            if ($sm > 12) {
                $sm = 1;
                $sy = $sy + 1;
            }
        }
        if ($op eq '-') {
            $sd = $sd - 1;
            if ($sd == 0) {
                if ($sm == 1) {
                    $sm = 12;
                    $sy = $sy - 1;
                } else {
                    $sm = $sm -1;
                }
                $sd = $nbdays[$sm-1];
            }
        }
        $adelta = $adelta -1;
    }
    my $enddate = sprintf '%.4u%.2u%.2u', $sy, $sm, $sd;
    return $enddate;
}

#######################
sub returnStats {
    my $dir = shift;
    if (! $verbose) {
        print "$msgs|$spams|$highspams|$viruses|$names|$others|$cleans|$bytes|$users|$domains\n";
    } else {
        print "total bytes:   $bytes\n";
        print "total msgs:    $msgs\n";
        print "total spams:   ". ($spams + $highspams)."\n";
        print "total viruses: $viruses\n";
        print "total content: ". ($names + $others) ."\n";
        print "total clean:   $cleans\n";
        print "total users:   $users\n";
        print "total domains: $domains\n";
    }
}

#######################
sub addGlobalStats {
    $global{'msgs'} += $msgs;
    $global{'spams'} += $spams;
    $global{'highspams'} += $highspams;
    $global{'viruses'} += $viruses;
    $global{'names'} += $names;
    $global{'others'} += $others;
    $global{'cleans'} += $cleans;
    $global{'bytes'} += $bytes;
    $global{'users'} += $users;
    if ($users > 0) {
        $global{'domains'}++;
    }
}

#######################
sub badUsage {
    my $bad = shift;
    print "Bad Usage: wrong paremeter: $bad (".join(', ',@_).")\n";
    print "    Usage: get_stats.pl what begindate enddate\n";
    print "       'what' :  either user, domain name or '_global'\n";
    print "       'begindate' and 'enddate' : dates\n";
    exit 1;
}
