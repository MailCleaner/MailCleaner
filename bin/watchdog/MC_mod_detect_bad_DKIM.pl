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

use v5.36;
use strict;
use warnings;
use utf8;

use File::Basename;

my $min_length = 1024;

my $script_name         = basename($0);
my $script_name_no_ext  = $script_name;
$script_name_no_ext     =~ s/\.[^.]*$//;
my $timestamp           = time();

my $PID_FILE = '/var/mailcleaner/run/watchdog/' . $script_name_no_ext . '.pid';
my $OUT_FILE = '/var/mailcleaner/spool/watchdog/' .$script_name_no_ext. '_' .$timestamp. '.out';

open(my $file, '>', $OUT_FILE);

sub my_own_exit
{
    my ($exit_code) = @_;
    $exit_code = 0  if ( ! defined ($exit_code) );

    if ( -e $PID_FILE ) {
        unlink $PID_FILE;
    }

    my $ELAPSED = time() - $timestamp;
    print $file "EXEC : $ELAPSED\n";
    print $file "RC : $exit_code\n";

    close $file;

    exit($exit_code);
}

opendir (my $dir, '/var/mailcleaner/spool/tmp/mailcleaner/dkim/');
my @short;
my @invalid;
while (my $key = readdir($dir)) {
    if ($key eq 'default.pkey' && -s '/var/mailcleaner/spool/tmp/mailcleaner/dkim/'.$key <= 1) {
        next;
    }
    if ($key =~ m/^\.+$/) {
        next;
    }
    my $length = `openssl rsa -in /var/mailcleaner/spool/tmp/mailcleaner/dkim/$key -noout -text 2> /dev/null | grep 'Private-Key:'` || 'invalid';
    chomp($length);
    $length =~ s/Private-Key: \((\d+) bit\)/$1/;
    if ($length =~ m/^\d+$/) {
        if ($length < $min_length) {
            push(@short, $key);
        }
    } else {
        push(@invalid, $key);
    }
}

my $status = '';
my $rc = 0;
if (scalar(@short)) {
    $rc += 1;
    $status .= 'Short DKIM key length: ' . join(', ', @short);
}
if (scalar(@invalid)) {
    if ($rc) {
        $status .= '<br/>';
    }
    $rc += 2;
    $status .= 'Invalid DKIM key: ' . join(', ', @invalid);
}

if ($status) {
    print $file $status."\n";
}

my_own_exit($rc);
