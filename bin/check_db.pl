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
#
#
#   This script will compare the actual slave or master database with
#   the up-to-date database from Mailcleaner Update Services
#
#   Usage:
#           check_db.pl [-s|-m] [--dbs=database] [--update|--mycheck|--myrepair] [-r|-R]

use v5.36;
use strict;
use warnings;
use utf8;
use Carp qw( confess );

our ($SRCDIR, $VARDIR);
BEGIN {
    if ($0 =~ m/(\S*)\/\S+.pl$/) {
        my $path = $1."/../lib";
        unshift (@INC, $path);
    }
    require ReadConfig;
    my $conf = ReadConfig::getInstance();
    $SRCDIR = $conf->getOption('SRCDIR') || '/usr/mailcleaner';
    $VARDIR = $conf->getOption('VARDIR') || '/var/mailcleaner';
    unshift(@INC, $SRCDIR."/lib");
}

require DB;

my $VERBOSE = 0;
## default behaviour
my $dbtype = 'master';
my $databases = 'mc_config,mc_spool,mc_stats';
my $updatemode = 0;
my $checkmode = 0;
my $repairmode = 0;
my $repcheck = 0;
my $repfix = 0;

## parse arguments
while (my $arg=shift) {
    if ($arg eq "-s") {
        $dbtype='slave';
    } elsif ($arg eq '-m') {
        $dbtype='master';
    } elsif ($arg eq '--update') {
        $updatemode=1;
    } elsif ($arg eq '--mycheck') {
        $checkmode=1;
    } elsif ($arg eq '--myrepair') {
        $repairmode=1;
    } elsif ($arg =~ /\-\-dbs=(\S+)/) {
        $databases=$1;
    } elsif ($arg eq "-r") {
        $repcheck=1;
    } elsif ($arg eq "-R") {
        $repfix=1;
    } else {
        print "unknown argmuent: $arg\n";
        print "Usage: check_db.pl [-s|-m] [--dbs=database] [--update] [--mycheck|--myrepair] [-r|-R]\n";
        exit 1;
    }
}
## check given mode
if (($updatemode + $checkmode + $repairmode) > 1) {
    print "Cannot do more than one thing at once, please choose between --update, --mycheck or --myrepair\n";
    exit 1;
}

## check replication if wanted
if ($repcheck > 0) {
    checkReplicationStatus($repfix);
    exit 0;
}
if ($repfix > 0) {
    if (checkReplicationStatus($repfix)) {
        exit 0;
    }
    sleep 5;
    checkReplicationStatus(0);
    exit 0;
}

## process each database
foreach my $database (split(',', $databases)) {
    output("Processing database: $database");
    if ($database eq "mc_stats" && $dbtype eq 'master') {
        output(" avoiding mc_stats on a master database");
        next;
    }

    ## connect to database
    my $db = DB::connect($dbtype, $database);
    output("Connected to database");

    if ($checkmode) {
        ## mysql check mode
        myCheckRepairDatabase(\$db, 0);
    } elsif ($repairmode) {
        ## mysql repair mode
        myCheckRepairDatabase(\$db, 1);
    } elsif ($updatemode) {
       compareUpdateDatabase(\$db, $database, 1);
    } else {
        ## output status
        compareUpdateDatabase(\$db, $database, 0);
    }

    $db->disconnect();
    output("Disconnected from database");
}

if ($updatemode && $dbtype eq 'master') {
    foreach my $dbname ('dmarc_reporting') {
        my $db = DB::connect($dbtype, $dbname, 0);
        if ($db && !$db->getType()) {
            print "Need to create new database $dbname, proceeding...\n";
            addDatabase($dbtype, $dbname);
        }
    }
}
exit 0;

#######################
# output
#######################
sub output($message)
{
    print("$message\n") if ($VERBOSE);
}

#######################
# getRefTables
#######################
sub getRefTables($dbname)
{
    my %tables;

    my $prefix = 'cf';
    if ($dbname eq 'mc_stats') {
        $prefix='st';
    } elsif ($dbname eq 'mc_spool') {
        $prefix='sp';
    }

    my $install_dir = "$SRCDIR/install/dbs";
    if ($dbname eq 'mc_spool') {
        $install_dir .= "/spam";
    }
    opendir(IDIR, $install_dir) or die "could not open table definition directory $install_dir\n";
    while( my $table_file = readdir(IDIR)) {
        next if $table_file =~ /^\./;
        if ($table_file =~ /^t\_$prefix\_(\S+)\.sql/) {
            $tables{$1} = $install_dir."/".$table_file;
        }
    }
    closedir(IDIR);
    return %tables;
}

#######################
# getActualTables
#######################
sub getActualTables($db_ref)
{
    my $db = $$db_ref;
    my %tables_hash;

    my $sql = "SHOW tables;";
    my @tables = $db->getList($sql);

    foreach my $table (@tables) {
        $tables_hash{$table} = $table;
    }
    return %tables_hash;
}


#######################
# getRefFields
#######################
sub getRefFields($file)
{
    my %fields;
    my $previous = 0;
    my $order = 0;

    open(my $TABLEFILE, '<', $file) or die("ERROR, cannot open reference database file $file\nABORTED\n");
    my $in_desc = 0;
    while(<$TABLEFILE>) {
        chomp;
        if ( $_ =~ /CREATE\s+TABLE\s+(\S+)\s+\(/ ) {
            $in_desc = 1;
            next;
        }
        if ( $_ =~ /^\s*\)(TYPE\=MyISAM)?\;\s*$/ ) {
            $in_desc = 0;
        }
        if ( $_ =~ /INSERT/) {
            $in_desc = 0;
            next;
        }
        if (! $in_desc) {
            next;
        }
        if ( $_ =~ /^\s*PRIMARY|INDEX|UNIQUE KEY|KEY|^\-\-/ ) {
            next;
        }
        if ( $_ =~ /\s+(\S+)\s+(\S+)(.*)\,?\s*$/ ) {
            my $deffull = $2.$3;
            my $def = $2;
            $def =~ s/\ //g;
            my $n = $1;
            $deffull =~ s/\s*\,\s*$//g;
            $fields{$order."_".$n} = { previous => $previous, def => $def, deffull => $deffull };
            $previous = $n;
            $order = $order + 1;
            next;
        }
    }
    close($TABLEFILE);
    return %fields;
}

#######################
# getActualFields
#######################
sub getActualFields($db_ref,$tablename)
{
    my $db = $$db_ref;
    my %fields;
    my $previous = "";

    my $sql = "DESCRIBE $tablename;";
    my @afields = $db->getListOfHash($sql);

    foreach my $f (@afields) {
        my $fname = $f->{Field};
        my $ftype = $f->{Type};
        $fields{$fname} = { previous => $previous, def => $ftype };
        $previous = $1;
    }

    return %fields;
}

#######################
# myCheckDatabase
#######################
sub myCheckRepairDatabase($db_ref,$repair)
{
    my $db = $$db_ref;
    my $sql = "";

    my %tables = getActualTables(\$db);

    foreach my $tname (keys %tables) {
        if ($repair) {
            print "   repairing table: $tname...";
            $sql = "REPAIR TABLE $tname EXTENDED;";
        } else {
            print "   checking table: $tname...";
            $sql = "CHECK TABLE $tname EXTENDED;";
        }
        my %result = $db->getHashRow($sql);
        print " ".$result{'Msg_text'}."\n";
    }
}

#######################
# add permission to database
#######################
sub addDatabase($dbtype,$dbname='slave')
{
    if ($dbtype ne 'slave') {
        $dbtype = 'master';
    }

    my $mysqld = "${SRCDIR}/etc/init.d/mysql_${dbtype}";
    print "Restarting $dbtype database to change permissions...\n";
    `$mysqld restart nopass 2>&1`;
    sleep(20);
    my $dbr = DB::connect($dbtype, 'mysql');
    print "Creating database $dbname...\n";
    $dbr->execute("CREATE DATABASE $dbname");
    print "Adding new permissions...\n";
    $dbr->execute("INSERT INTO db VALUES('%', '".$dbname."', 'mailcleaner', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y')");
    print "Restarting $dbtype database with new permissions...\n";
    `$mysqld restart 2>&1`;
    sleep(20);
    my $descfile = "${SRCDIR}/install/dbs/${dbname}.sql";
    if (-f $descfile) {
        print "Creating schema...\n";
        my $mysql = "${SRCDIR}/bin/mc_mysql";
        if ($dbtype eq 'slave') {
            $mysql .= " -s $dbname";
        } else {
            $mysql .= " -m $dbname";
        }
        `$mysql < $descfile 2>&1`;
    }

    print "Done.\n";
}

#######################
# check replication status and try to fix if wanted
#######################
sub checkReplicationStatus($fix)
{
    my $haserror = 0;
    my $logfile = "${VARDIR}/log/mysql_slave/mysql.log";
    if (! -f $logfile) {
        print "WARNING: slave mysql log file not found ! ($logfile)\n";
        return 0;
    }
    my $outlog = `tail -4 $logfile`;
    if ($outlog =~ /replication started/ && $outlog =~ /starting replication in log/) {
        print "Replication status: OK\n";
        return 1;
    }
    if (! $fix) {
        print "Replication status: NOT OK !\n";
        return 0;
    }
    $outlog = `grep '[ERROR]' $logfile`;
    if ( $outlog =~ m/Duplicate column name '(\S+)''.*database: '(\S+)'.*TABLE (\S+)/) {
        print "WARNING: a duplicate column has been detected: $1 on table $3 ($2)\n";
        if ($fix) {
            print " ...trying to fix... ";
            my $query = "ALTER TABLE $3 DROP COLUMN $1;";
            my $dbr = DB::connect('slave', $2);
            if ( $dbr->execute($query)) {
                my $cmd = "${SRCDIR}/etc/init.d/mysql_slave restart >/dev/null 2>&1";
                my $resexec = `$cmd`;
                print " should be fixed!\n";
            } else {
                print " could not modify database. fix failed\n";
                return 0;
            }
        }
    }
    return 0;
}

#######################
# compareUpdateDatabase
#######################
sub compareUpdateDatabase($db_ref,$dbname,$update)
{
    my $db = $$db_ref;

    my %reftables = getRefTables($dbname);
    my %actualtables = getActualTables(\$db);

    # check missing things in actual database (from ref to actual) check tables presence
    foreach my $table (keys %reftables) {
        output "  processing table $table\n";
        # if missing table
        if (! defined($actualtables{$table})) {
            print "     MISSING table $table..";
            if ($update) {
                my $type = '-m';
                if ($dbtype eq 'slave') {
                    $type = '-s';
                }
                my $cmd = "${SRCDIR}/bin/mc_mysql ${type} < ${reftables{$table}} 2>&1";
                my $res = `$cmd`;
                if (! $res eq '' ) {
                    print "ERROR, cannot create database: $res\nABORTED\n";
                    exit 1;
                } else {
                    print " FIXED !";
                }
            }
            print "\n";
            next;
        }

        # compare and repair table
        if (!compareUpdateTable(\$db, $table, $reftables{$table}, $update)) {
            print "ERROR, cannot update table $table\nABORTED\n";
            exit 1;
        }
    }

    # check useless tables
    # ...
}

#######################
# compareUpdateTable
#######################
sub compareUpdateTable($db_ref,$tablename,$tablefile,$update)
{
    my $db = $$db_ref;

    my %reffields = getRefFields($tablefile);
    my %actualfields = getActualFields(\$db, $tablename);
    my %nonofields;

    # check missing columns
    foreach my $reff (sort (keys %reffields)) {
        my $f = $reff;
        $f =~ s/^(\d+\_)//;
        $nonofields{$f} = $reffields{$reff};
        if (! defined($actualfields{$f})) {
            print "     MISSING column $tablename.$f (after ".$reffields{$reff}{previous}.")";
            if ($update) {
                my $after = "";
                if (! $reffields{$reff}{previous} eq '') {
                    $after = " AFTER ".$reffields{$reff}{previous};
                }
                my $sql = "ALTER TABLE $tablename ADD COLUMN ".$f." ".$reffields{$reff}{deffull}.$after.";";
                if (! $db->execute($sql)) {
                    print "ERROR, cannot create column: ".$db->getError()."\nABORTED\n";
                    exit 1;
                } else {
                    print " FIXED !\n";
                }
            }
            print "\n";
        } else {
            my $clean = $reff;
            $clean =~ s/^\d+_//;
            if (lc($reffields{$reff}{'def'}) ne lc($actualfields{$clean}{'def'})) {
                print "     INCORRECT column type '".$actualfields{$clean}{'def'}."' != '".$reffields{$reff}{'def'}."' $tablename.$f";
                if ($update) {
                    my $sql = "ALTER TABLE $tablename MODIFY $clean $reffields{$reff}{'deffull'};";
                    if (! $db->execute($sql)) {
                        print " ERROR, cannot alter column $clean in $tablename: ".$db->getError()."\nABORTED\n";
                        exit 1;
                    } else {
                        print " FIXED !\n";
                    }
                }
            }
        }
    }

    # check useless columns
    foreach my $f (keys %actualfields) {
        if (! defined($nonofields{$f})) {
            print "     USELESS column $tablename.$f..";
            if ($update) {
                my $sql = "ALTER TABLE $tablename DROP COLUMN ".$f.";";
                if (! $db->execute($sql)) {
                    print "ERROR, cannot remove column: ".$db->getError()."\nABORTED\n";
                    exit 1;
                } else {
                    print " FIXED !";
                }
            }
            print "\n";
        }
    }
    return 1;
}
