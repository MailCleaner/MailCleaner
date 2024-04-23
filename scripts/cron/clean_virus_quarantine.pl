#!/usr/bin/perl

use strict;
use DBI;

my %config = readConfig("/etc/mailcleaner.conf");

my $days_to_keep = shift;

if (! $days_to_keep) {
	if (! $days_to_keep) {
        my $config_dbh = DBI->connect("DBI:mysql:database=mc_config;host=localhost;mysql_socket=$config{'VARDIR'}/run/mysql_slave/mysqld.sock",
                                'mailcleaner', $config{'MYMAILCLEANERPWD'}, {'RaiseError' => 0, PrintError => 0 });
        if ($config_dbh) {
                my $config_sth = $config_dbh->prepare("SELECT days_to_keep_virus FROM system_conf");
                $config_sth->execute();
                while (my $ref_config=$config_sth->fetchrow_hashref()) {
                        $days_to_keep = $ref_config->{'days_to_keep_virus'};
                }
                $config_sth->finish();
                $config_dbh->disconnect();
        }
        if (! $days_to_keep) {
                $days_to_keep = 60;
        }
}
}

my $quarantine_dir = $config{VARDIR}."/spool/mailscanner/quarantine";

# Standardise the format of the directory name
die 'Path for quarantine_dir must be absolute' unless $quarantine_dir =~ /^\//;
$quarantine_dir =~ s/\/$//; # Delete trailing slash

# Now get the content list for the directory.
opendir(QDIR, $quarantine_dir) or die "Couldn't read directory $quarantine_dir";

# Loop through this list looking for any *directory* which hasn't been
# modified in the last $days_to_keep days.
# Unfortunately this will do nothing if the filesystem is backed up using tar.
while(my $entry = readdir(QDIR)) { 
        next if $entry =~ /^\./;
        $entry = $quarantine_dir . '/' . $entry;
        system("rm", "-rf", "$entry") if (-d $entry && -M $entry > $days_to_keep);
}
closedir(QDIR);



#############################
sub readConfig
{
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
