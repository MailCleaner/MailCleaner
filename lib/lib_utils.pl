#!/usr/bin/perl -w
use strict;
use DateTime;
use File::Path qw/make_path/;

sub is_into {
        my ($what, @list) = @_;
        my $c;

        foreach $c (@list) {
                if ($c eq $what) {
                        return(1);
                }
        }

        return(0);
}


# Returns 0 if $file cannot be opened
# (1, $file content) otherwise
sub Slurp_file {
    my ($file) = @_;
    my @contains = ();

    if ( ! open(FILE, '<', $file) ) {
        return (0, @contains);
    }

    @contains = <FILE>;
    close(FILE);
    chomp(@contains);

    return(1, @contains)
}


sub create_lock_file {
        my ($fullpathname, $timeout, $process_name) = @_;

        return 0 if ( ! open(FILE, '>', $fullpathname) );
        print FILE "$$\n";
        print FILE "$timeout\n$process_name\n"          if ( defined($timeout) && defined($process_name) );
        close FILE;

        return 1;
}

sub create_lockfile {
        my ($filename, $path, $timeout, $process_name) = @_;
        my $fullpathname;

        return 0 if ( ! defined($filename) );

        # Creating full path for this file
        if ( ! defined($path) ) {
                $path = '/var/mailcleaner/spool/tmp/';
        } elsif ( $path !~ /^\// ) {
                $path = '/var/mailcleaner/spool/tmp/' . $path;
        }

        $path .= '/' if ($path  !~ /\/$/);
        make_path($path, {mode => 0777});

        $fullpathname = $path . $filename;

        # if the lock file is not already existing, we create it
        if ( ! -e $fullpathname ) {
                my $rc = create_lock_file($fullpathname, $timeout, $process_name);

                return $rc;
        # if it is already existing, we check if we can remove it
        } else {
                # Slurp fichier
                my ($rc, $pid, $old_timeout, $old_process_name) = Slurp_file($fullpathname);
                return 0 if ($rc == 0);

                # If the file exists and there is no defined timeout, we cannot go
                if ( ! defined ($old_timeout) ) {
                        return 0;
                }

                # If we can remove the old lock file
                if ( time - $old_timeout > 0) {
			kill 'KILL', $pid;
			unlink $fullpathname;

			$rc = create_lock_file($fullpathname, $timeout, $process_name);
                        return $rc;
                } else {
                        return 0;
                }
        }
}

sub remove_lockfile {
        my ($filename, $path) = @_;
        my $fullpathname;

        return 0 if ( ! defined($filename) );

        # Creating full path for this file
        if ( ! defined($path) ) {
                $path = '/var/mailcleaner/spool/tmp/';
        } elsif ( $path !~ /^\// ) {
                $path = '/var/mailcleaner/spool/tmp/' . $path;
        }

        $path .= '/' if ($path  !~ /\/$/);
        $fullpathname = $path . $filename;

        my $rc = unlink $fullpathname;

        return $rc;
}

sub modify_lockfile {
        my ($filename, $path, $new_timeout) = @_;
        my $fullpathname;

        return 0 if ( ! defined($filename) );

        # Creating full path for this file
        if ( ! defined($path) ) {
                $path = '/var/mailcleaner/spool/tmp/';
        } elsif ( $path !~ /^\// ) {
                $path = '/var/mailcleaner/spool/tmp/' . $path;
        }

        $path .= '/' if ($path  !~ /\/$/);
        $fullpathname = $path . $filename;

	my ($rc, $pid, $timeout, $process_name) = Slurp_file($fullpathname);
	return 0 if ( ! $rc);
        $rc = unlink $fullpathname;
	return 0 if ( ! $rc);

	return( create_lock_file($fullpathname, $new_timeout, $process_name) );
}

sub log_to_file {
    my ($message, $logfile) = @_;
    my $log_time = DateTime->now;
    open(my $LOG, ">>", $logfile) or die("Error opening $logfile: $!");
    do {
        select $LOG;
        print("[". $log_time->ymd . " " . $log_time->hms . " - " . $$ ."] $message");
    };
}

1;
