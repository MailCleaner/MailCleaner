#!/usr/bin/perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
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
#   This script will force a spam message stored on the quarantine
#
#   Usage:
#           sync_spams.pl [-D]
#             -D: output debug information
#   synchronize slave spam quarantine database with master


use strict;
use Net::SMTP;
use DBI();

my %config = readConfig("/etc/mailcleaner.conf");

# get master config
my %master_conf = get_master_config();

my $debug = 0;
my $opt = shift;
if ($opt && $opt =~ /\-D/) {
  $debug = 1;
}
##########################################


# connect to slave database
my $slave_dbh;
$slave_dbh = DBI->connect("DBI:mysql:database=mc_spool;host=localhost;mysql_socket=$config{VARDIR}/run/mysql_slave/mysqld.sock", 
                           "mailcleaner", "$config{MYMAILCLEANERPWD}", {RaiseError => 0, PrintError => 0})
               or die("CANNOTCONNECTSLAVEDB\n", $slave_dbh->errstr);

# connect to master database
my $master_dbh;
$master_dbh = DBI->connect("DBI:mysql:database=mc_spool;host=$master_conf{'__MYMASTERHOST__'}:$master_conf{'__MYMASTERPORT__'}", 
               "mailcleaner", "$master_conf{'__MYMASTERPWD__'}", {RaiseError => 0, PrintError => 0})
        or die("CANNOTCONNECTMASTERDB\n", $master_dbh->errstr);

my $total = 0;

foreach my $letter ('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','misc') {
  if ($debug) {
    print "doing letter: $letter... fetching spams: ";
  }
  my $sth = $slave_dbh->prepare("SELECT * FROM spam_$letter WHERE in_master='0'");
  $sth->execute() or next;
  if ($debug) { 
    print $sth->rows." found\n";
  }
  
  while (my $row = $sth->fetchrow_hashref()) {
    # build query
    my $query = "INSERT IGNORE INTO spam_$letter SET ";
    foreach my $col (keys %{$row}) {
     my $value = $row->{$col};
     $value =~ s/'/\\'/g;
     $query .= $col."='".$value."', ";
    }
    $query =~ s/,\s*$//;

    # save in master
    my $res = $master_dbh->do($query);
    if (!$res) { 
      if ($debug) {
        print "failed for: ".$row->{exim_id}."\n   with message: ".$master_dbh->errstr."\n   query was: $query\n";
      }
      next;
    }
    $total = $total + 1;

    # update slave record
    $query = "UPDATE spam_$letter SET in_master='1' WHERE exim_id='".$row->{exim_id}."'";
    $slave_dbh->do($query);
  } 
}
print "SUCCESSFULL|$total\n";
#my $sth = $dbh->prepare("SELECT hostname, port, password FROM master");

sub get_master_config
{
    my %mconfig;
    my $dbh;
        $dbh = DBI->connect("DBI:mysql:database=mc_config;host=localhost;mysql_socket=$config{VARDIR}/run/mysql_slave/mysqld.sock",
                        "mailcleaner", "$config{MYMAILCLEANERPWD}", {RaiseError => 0, PrintError => 0})
                or die("CANNOTCONNECTDB", $dbh->errstr);
 
    my $sth = $dbh->prepare("SELECT hostname, port, password FROM master");
        $sth->execute() or die("CANNOTEXECUTEQUERY", $dbh->errstr);

        if ($sth->rows < 1) {
                return;
        }
        my $ref = $sth->fetchrow_hashref() or return;

        $mconfig{'__MYMASTERHOST__'} = $ref->{'hostname'};
        $mconfig{'__MYMASTERPORT__'} = $ref->{'port'};
        $mconfig{'__MYMASTERPWD__'} = $ref->{'password'};

        $sth->finish();
        $dbh->disconnect();
        return %mconfig;
}

##########################################
sub readConfig
{       # Reads configuration file given as argument.
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

