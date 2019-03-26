#!/usr/bin/perl -w

use strict;
use DBI();
use Term::ReadKey;

my %config = readConfig("/etc/mailcleaner.conf");

my $master_dbh = DBI->connect("DBI:mysql:database=mc_config;mysql_socket=$config{'VARDIR'}/run/mysql_master/mysqld.sock",
                                        "mailcleaner","$config{'MYMAILCLEANERPWD'}", {RaiseError => 0, PrintError => 0} );
if (!$master_dbh) {
	printf ("ERROR: no master database found on this system. This script will only run on a Mailcleaner master host.\n");
	exit 1;
}

		view_slaves();
sub view_slaves {
	my $sth =  $master_dbh->prepare("SELECT id, hostname, port, ssh_pub_key  FROM slave") or die ("error in SELECT");
	$sth->execute() or die ("error in SELECT");
	my $el=$sth->rows;
	while (my $ref=$sth->fetchrow_hashref()) {
		printf $ref->{'hostname'}."\n";
	}
	$sth->finish();
}

sub readConfig {       # Reads configuration file given as argument.
        my $configfile = shift;
        my %config;
        my ($var, $value);

        open CONFIG, $configfile or die "Cannot open $configfile: $!\n";
        while (<CONFIG>) {
                chomp;                  # no newline
                s/#.*$//;                # no comments
                s/^\*.*$//;             # no comments
                s/;.*$//;                # no comments
                s/^\s+//;               # no leading white
                s/\s+$//;               # no trailing white
                next unless length;     # anything left?
                my ($var, $value) = split(/\s*=\s*/, $_, 2);
                $config{$var} = $value;
        }
        close CONFIG;
        return %config;
}

