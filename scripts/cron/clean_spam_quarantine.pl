#!/usr/bin/perl -w

use Date::Calc( Today, Delta_Days, Localtime, Time_to_Date );
use strict;
use File::stat;
use DBI();

my $days_to_keep = shift;

my %config                = readConfig("/etc/mailcleaner.conf");
my $quarantine_owner_name = 'mailcleaner';
my $quarantine_owner      = getpwnam($quarantine_owner_name);
my $quarantine_group      = getgrnam($quarantine_owner_name);


my $DEBUG = 0;
if ( !$days_to_keep ) {
	my $config_dbh = DBI->connect(
"DBI:mysql:database=mc_config;host=localhost;mysql_socket=$config{'VARDIR'}/run/mysql_slave/mysqld.sock",
		'mailcleaner',
		$config{'MYMAILCLEANERPWD'},
		{ 'RaiseError' => $DEBUG, PrintError => $DEBUG }
	);
	if ($config_dbh) {
		my $config_sth =
		  $config_dbh->prepare("SELECT days_to_keep_spams FROM system_conf");
		$config_sth->execute();
		while ( my $ref_config = $config_sth->fetchrow_hashref() ) {
			$days_to_keep = $ref_config->{'days_to_keep_spams'};
		}
		$config_sth->finish();
		$config_dbh->disconnect();
	}
	if ( !$days_to_keep ) {
		$days_to_keep = 60;
	}
}

my $quarantine_dir = $config{VARDIR} . "/spam";

# Standardise the format of the directory name
die 'Path for quarantine_dir must be absolute' unless $quarantine_dir =~ /^\//;
$quarantine_dir =~ s/\/$//;    # Delete trailing slash

my $dbh;
my $sth;

## delete in databases
my @dbs = ( 'slave', 'master' );
foreach my $db (@dbs) {
	$dbh = DBI->connect(
"DBI:mysql:database=mc_spool;host=localhost;mysql_socket=$config{'VARDIR'}/run/mysql_$db/mysqld.sock",
		'mailcleaner',
		$config{'MYMAILCLEANERPWD'},
		{ 'RaiseError' => $DEBUG, PrintError => $DEBUG }
	);
	if ($dbh) {
		foreach my $letter (
			'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j',
			'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't',
			'u', 'v', 'w', 'x', 'y', 'z', 'misc', 'num',
		  )
		{
			print "cleaning letter: $letter\n";
			$sth =
			  $dbh->prepare(
"DELETE FROM spam_$letter WHERE TO_DAYS(NOW())-TO_DAYS(date_in) > $days_to_keep"
			  );
			$sth->execute();
			$sth->finish();
		}
		$dbh->disconnect();
	}
}

## delete real files
opendir( QDIR, $quarantine_dir )
  or die "Couldn't read directory $quarantine_dir";
while ( my $entry = readdir(QDIR) ) {
	next if $entry =~ /^\./;
	$entry = $quarantine_dir . '/' . $entry;
	if ( -d $entry ) {
		opendir( DDIR, $entry ) or die "Couldn't read directory $entry";
		while ( my $domain_entry = readdir(DDIR) ) {
			next if $domain_entry =~ /^\./;
			$domain_entry = $entry . '/' . $domain_entry;

			if ( -d $domain_entry ) {
				opendir( UDIR, $domain_entry )
				  or die "Couldn't read directory $domain_entry";
				while ( my $user_entry = readdir(UDIR) ) {
					next if $user_entry =~ /^\./;

					$user_entry = $domain_entry . '/' . $user_entry;
					my @statsa = stat($user_entry);
					my @stats = @{$statsa[0]};
					my @date  = Time_to_Date( $stats[9] );
					my $Ddays =
					  Delta_Days( ( $date[0], $date[1], $date[2] ), Today() );
					system("rm $user_entry >/dev/null 2>&1")
					  if $Ddays > $days_to_keep;
				}
			}
			close(UDIR);
            my $uid = stat($domain_entry)->uid;
            if ( $uid != $quarantine_owner ) {
                chown $quarantine_owner, $quarantine_group, $domain_entry;
            }
            my $gid = stat($domain_entry)->gid;
            if ( $gid != $quarantine_group ) {
                chown $quarantine_owner, $quarantine_group, $domain_entry;
            }
			$domain_entry =~ s/\|/\\\|/;
			system("rmdir $domain_entry >/dev/null 2>&1");
		}
		close(DDIR);
        my $uid = stat($entry)->uid;
        if ( $uid != $quarantine_owner ) {
            chown $quarantine_owner, $quarantine_group, $entry;
        }	
        my $gid = stat($entry)->gid;
        if ( $gid != $quarantine_group ) {
            chown $quarantine_owner, $quarantine_group, $entry;
        }
		$entry =~ s/\|/\\\|/;
		system("rmdir $entry >/dev/null 2>&1");
	}
}
closedir(QDIR);
exit;

##########################################

sub readConfig {    # Reads configuration file given as argument.
	my $configfile = shift;
	my %config;
	my ( $var, $value );

	open CONFIG, $configfile or die "Cannot open $configfile: $!\n";
	while (<CONFIG>) {
		chomp;         # no newline
		s/#.*$//;      # no comments
		s/^\*.*$//;    # no comments
		s/;.*$//;      # no comments
		s/^\s+//;      # no leading white
		s/\s+$//;      # no trailing white
		next unless length;    # anything left?
		my ( $var, $value ) = split( /\s*=\s*/, $_, 2 );
		$config{$var} = $value;
	}
	close CONFIG;
	return %config;
}

