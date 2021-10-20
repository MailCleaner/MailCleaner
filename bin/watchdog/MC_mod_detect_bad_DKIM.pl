#!/usr/bin/perl -w
use strict;
use File::Basename;

my $min_length = 1024;

my $script_name         = basename($0);
my $script_name_no_ext  = $script_name;
$script_name_no_ext     =~ s/\.[^.]*$//;
my $timestamp           = time();

my $PID_FILE   = '/var/mailcleaner/run/watchdog/' . $script_name_no_ext . '.pid';
my $OUT_FILE   = '/var/mailcleaner/spool/watchdog/' .$script_name_no_ext. '_' .$timestamp. '.out';

open my $file, '>', $OUT_FILE;

sub my_own_exit {
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
    if ($key eq 'default.pkey' && -s '/var/mailcleaner/spool/tmp/mailcleaner/dkim/'.$key == 0) {
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
