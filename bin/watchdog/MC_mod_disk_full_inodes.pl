#!/usr/bin/perl -w
use strict;
use File::Basename;

my $alarm_limit = 85;

my $script_name         = basename($0);
my $script_name_no_ext  = $script_name;
$script_name_no_ext     =~ s/\.[^.]*$//;
my $timestamp           = time();
my $rc			= 0;

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

my @df = `df -i`;
chomp(@df);
foreach my $line (@df) {
	my (undef, $size, $used, undef, $pc, $mount) = split(' ', $line, 6);
	$pc =~ s/%//;

	if ( ($mount eq '/') || ($mount eq '/var') ) {
		if ( $pc >= $alarm_limit ) {
			print $file "$mount : $used / $size => $pc\n";
			$rc = 1;
		}
	}
}

my_own_exit($rc);
