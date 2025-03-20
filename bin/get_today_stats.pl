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
#   This script will output the count of messages/spams/viruses for today

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

require StatsClient;

my $mode_given = shift;
my $mode = "B";

if (!$mode_given || $mode_given =~ /^\-a$/) {
    $mode = 'a';
} elsif ($mode_given =~ /^\-h$/) {
    usage();
} elsif ($mode_given =~ /^\-A$/) {
    $mode = 'A';
} elsif ($mode_given =~ /^\-B$/) {
    $mode = 'B';
} elsif ($mode_given =~ /^\-m$/) {
    $mode = 'm';
} elsif ($mode_given =~ /^\-b$/) {
    $mode = 'b';
} elsif ($mode_given =~ /^\-s$/) {
    $mode = 's';
} elsif ($mode_given =~ /^\-S$/) {
    $mode = 'S';
} elsif ($mode_given =~ /^\-v$/) {
    $mode = 'v';
} elsif ($mode_given =~ /^\-V$/) {
    $mode = 'V';
} elsif ($mode_given =~ /^\-c$/) {
    $mode = 'c';
} elsif ($mode_given =~ /^\-C$/) {
    $mode = 'C';
} elsif ($mode_given =~ /^\-p$/) {
    $mode = 'S';
} elsif ($mode_given =~ /^\-u$/) {
    $mode = 'u';
} elsif ($mode_given =~ /^\-l$/) {
    $mode = 'l';
} elsif ($mode_given =~ /^\-L$/) {
    $mode = 'L';
} elsif ($mode_given =~ /^\-d$/) {
    $mode = 'd';
} else {
    print("Invalid selection: $mode_given\n");
    usage();
}

my $total_bytes = 0;
my $total_msg = 0;
my $total_spam = 0;
my $total_virus = 0;
my $percentvirus = 0;
my $total_content = 0;
my $percentcontent = 0;
my $percentspam = 0;
my $user_count = 0;
my $domain_count = 0;
my $total_clean = 0;
my $percentclean = 0;

my $client = StatsClient->new();

my $bytes = $client->query('GET global:size');
if ( ! defined ($bytes)  || $bytes =~ /^_/) {
    $bytes=0;
}
$total_bytes = $bytes;

my $msgs = $client->query('GET global:msg');
if ($msgs =~ /^_/) {
    $msgs=0;
}
$total_msg = $msgs;

my $spams = $client->query('GET global:spam');
if ($spams =~ /^_/) {
    $spams = 0;
}
$total_spam = $spams;

my $viruses = $client->query('GET global:virus');
if ($viruses =~ /^_/) {
    $viruses = 0;
}
$total_virus = $viruses;

my $names = $client->query('GET global:name');
if ($names =~ /^_/) {
    $names = 0;
}
my $others = $client->query('GET global:other');
if ($others =~ /^_/) {
    $others = 0;
}
$total_content = $names + $others;
if ($total_msg > 0) {
    $percentspam = int(((100/$total_msg) * $total_spam)*100)/100;
    $percentvirus = int(((100/$total_msg) * $total_virus)*100)/100;
    $percentcontent = int(((100/$total_msg) * $total_content)*100)/100;
} else {
    $percentspam = 0;
    $percentvirus = 0;
    $percentcontent = 0;
}

my $cleans = $client->query('GET global:clean');
if ($cleans =~ /^_/) {
    $cleans = 0;
}
$total_clean = $cleans;
if ($total_msg > 0) {
    $percentclean = int(((100/$total_msg) * $total_clean)*100)/100;
} else {
    $percentclean = 0;
}

my $users = $client->query('GET global:user');
if ($users =~ /^_/) {
    $users = 0;
}
$user_count = $users;

my $domains = $client->query('GET global:domain');
if ($domains =~ /^_/) {
    $domains = 0;
}
$domain_count = $domains;

if ( ! defined ($total_bytes) ) {
    $total_bytes = 0;
}
if ($mode eq "a") {
    print "total bytes:   $total_bytes\n";
    print "total msgs:    $total_msg\n";
    print "total spams:   $total_spam ($percentspam%)\n";
    print "total viruses: $total_virus ($percentvirus%)\n";
    print "total content: $total_content ($percentcontent%)\n";
    print "total clean:   $total_clean ($percentclean%)\n";
} elsif ($mode eq "A") {
    print(join('|',$total_bytes||0,$total_msg||0,$total_spam||0,$percentspam||0,$total_virus||0,$percentvirus||0,$total_content||0,$percentcontent||0,$user_count||0,$total_clean||0,$percentclean||0,$domain_count||0));
} elsif ($mode eq "B") {
    print(join('|',$total_bytes||0,$total_msg||0,$total_spam||0,$percentspam||0,$total_virus||0,$percentvirus||0,$total_content||0,$percentcontent||0,$total_clean||0,$percentclean||0));
} elsif ($mode eq "b") {
    print($total_bytes || 0);
} elsif ($mode eq "m") {
    print($total_msg || 0);
} elsif ($mode eq "s") {
    print($total_spam || 0);
} elsif ($mode eq "S") {
    print($percentspam || 0);
} elsif ($mode eq "v") {
    print($total_virus || 0);
} elsif ($mode eq "V") {
    print($percentvirus || 0);
} elsif ($mode eq "c") {
    print($total_content || 0);
} elsif ($mode eq "C") {
    print($percentcontent || 0);
} elsif ($mode eq "p") {
    print($percentspam || 0);
} elsif ($mode eq "u") {
    print($user_count || 0);
} elsif ($mode eq "l") {
    print($total_clean || 0);
} elsif ($mode eq "L") {
    print($percentclean || 0);
} elsif ($mode eq "d") {
    print($domain_count || 0);
}

sub usage
{
    print(
"Usage:
    get_today_stats.pl [-aAmbsSvVcCpulLh]
        -a: output all stats
        -A: output all stats in raw format
        -B: output all but user and domains stats in raw format
        -m: output number of messages processed
        -b: output total bytes processed
        -s: output number of spams detected
        -S: output percent of spams per messages
        -v: output number of viruses detected
        -V: output percent of viruses
        -c: output number of dangerous content detected
        -C: output percent of dangerous content
        -p: same as -S
        -u: output the number of email addresses processed by the system
        -l: output number of clean messages
        -L: output percent of clean messages
        -d: output number of filtered domains
        -h: this menu\n"
    );
    exit(1);
}
