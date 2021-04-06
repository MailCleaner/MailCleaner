#!/usr/bin/perl -w
use strict;
use File::Basename;

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

my $slave_status = `echo 'show slave status\\G' |/usr/mailcleaner/bin/mc_mysql -s`;

if ($slave_status eq '') {
	# Réparer resync_db.sh
	print $file "Show slave status : Retour vide, faire un sync_db\n";
    my_own_exit(1);

} elsif ( ($slave_status !~ /Slave_SQL_Running: Yes/) || ($slave_status !~ /Slave_IO_Running: Yes/) ) {
	print $file "Show slave status : au moins un des process retourne No, faire un sync_db\n";
    my_own_exit(2);
}

my_own_exit(0);
