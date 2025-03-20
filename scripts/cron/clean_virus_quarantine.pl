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
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

use v5.36;
use strict;
use warnings;
use utf8;

use DBI;

my %config = readConfig("/etc/mailcleaner.conf");

my $days_to_keep = shift;

if (! $days_to_keep) {
    my $config_dbh = DBI->connect(
        "DBI:MariaDB:database=mc_config;host=localhost;mariadb_socket=$config{'VARDIR'}/run/mysql_slave/mysqld.sock",
        'mailcleaner', $config{'MYMAILCLEANERPWD'}, {'RaiseError' => 0, PrintError => 0 }
    );
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
    system("rm", "-rf", "$entry") if -d $entry && -M $entry > $days_to_keep;
}
closedir(QDIR);

sub readConfig
{
        my $configfile = shift;
        my %config;
        my ($var, $value);

        open(my $CONFIG, '<', $configfile) or die "Cannot open $configfile: $!\n";
        while (<$CONFIG>) {
                chomp;              # no newline
                s/#.*$//;           # no comments
                s/^\*.*$//;         # no comments
                s/;.*$//;           # no comments
                s/^\s+//;           # no leading white
                s/\s+$//;           # no trailing white
                next unless length; # anything left?
                my ($var, $value) = split(/\s*=\s*/, $_, 2);
                $config{$var} = $value;
        }
        close $CONFIG;
        return %config;
}
