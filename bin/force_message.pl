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
#           force_message.pl msg_id destination
#   where msg_id is the id of the message
#   and destination is the address of the original recipient


use strict;
use Net::SMTP;
use DBI();

my %config = readConfig("/etc/mailcleaner.conf");
my %master_conf;

my $msg_id = shift;
my $for = shift;

if ( (!$msg_id) || !($msg_id =~ /^[a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{2}$/)) {
	print "INCORRECTMSGID\n";
        exit 0;
}

if ( (!$for) || !($for =~ /^(\S+)\@(\S+)$/)) {
	print "BADADDRESSFORMAT\n";
	exit 0;
}
 
my $for_local = $1;
my $for_domain = $2;

my $msg_file = $config{'VARDIR'}."/spam/".$for_domain."/".$for."/".$msg_id;

if ( open(MSG, $msg_file)) {
	my $start_msg = 0;
	my $msg = "";
	my $has_from = 0;
	my $from = "";
        my $in_dkim = 0;
	while (<MSG>)
        {
                ## just to remove garbage line before the real headers  
                if ($start_msg != 1 && /^[A-Z][a-z]*\:\ .*/) {
                        $start_msg = 1;
                }
                if ($start_msg > 0) {
                        if ($in_dkim && /^\S/) {
                           $in_dkim = 0;
                        }
                        if (/^DKIM-Signature:/) {
                           $in_dkim = 1;
                        }
			if (!$has_from && /^\s+from \<(\S+\@\S+)>\;/) {
				$from = $1;
				$has_from = 1;
			}
                        my $line = $_;
                        if ($line =~ m/Message-ID: (\S+)\@(\S+)/) {
                           $line = "Message-ID: $1-".int(rand(10000))."\@$2\n";
                        }
                        #$line =~ s/Message-ID: (\S+)/\-forced/;
                        if (!$in_dkim) {
			    $msg = $msg.$line;
                        }
                }
        }
	my $smtp;
        unless ($smtp = Net::SMTP->new('localhost:2525')) {
                print "ERRORSENDING $for\n";
                exit 1;
        }

        #$smtp->debug(3);
        $smtp->mail($from);
        $smtp->to($for);
        my $err = $smtp->code();
        if ($err == 550)  {
                print "NOSUCHADDR $for\n";
                exit 1;
        }
        if ($err >= 500) {
                print "ERRORSENDING $for\n";
                exit 1;
        } 
        $smtp->data();
        $smtp->datasend("X-MailCleaner-Forced: message forced\n");
	$smtp->datasend($msg);
	$smtp->dataend();
	close(MSG);
	%master_conf = get_master_config();
	mark_forced();

	print("MSGFORCED\n");
} 
else {
	print "MSGFILENOTFOUND\n";
}


exit 1;

##########################################
sub get_master_config
{
	my $dbh;
	my %mconfig;

	$dbh = DBI->connect("DBI:mysql:database=mc_config;host=localhost;mysql_socket=$config{VARDIR}/run/mysql_slave/mysqld.sock",
                        "mailcleaner", "$config{MYMAILCLEANERPWD}", {RaiseError => 0, PrintError => 0})
                or fatal_error("CANNOTCONNECTDB", $dbh->errstr);

	my $sth = $dbh->prepare("SELECT hostname, port, password FROM master");
        $sth->execute() or fatal_error("CANNOTEXECUTEQUERY", $dbh->errstr);

        if ($sth->rows < 1) {
                return;
        }
        my $ref = $sth->fetchrow_hashref() or return;

        $mconfig{'__MYMASTERHOST__'} = $ref->{'hostname'};
        $mconfig{'__MYMASTERPORT__'} = $ref->{'port'};
        $mconfig{'__MYMASTERPWD__'} = $ref->{'password'};

        $sth->finish();
        return %mconfig;
}

##########################################
sub mark_forced
{
	my $dbh;
	my $mdn = "DBI:mysql:database=mc_spool;host=$master_conf{'__MYMASTERHOST__'};port=$master_conf{'__MYMASTERPORT__'}";

	$dbh = DBI->connect($mdn,
                        "mailcleaner", "$master_conf{'__MYMASTERPWD__'}", {RaiseError => 0, PrintError => 0})
                or return;


 	my $table = "misc";
        if ($for_local =~ /^([a-z,A-Z])/) {
                $table = lc($1);
        } elsif ($for_local =~ /^[0-9]/) {
                $table = 'num';
        } else {
                $table = 'misc';
        }	
	my $query = "UPDATE spam_$table SET forced='1' WHERE to_domain='$for_domain' AND to_user='$for_local' AND exim_id='$msg_id'";
	my $sth = $dbh->prepare($query);
        $sth->execute() or return;

	$dbh->disconnect();	
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

############################################
sub print_usage
{
        print "bad usage...\n";
        print "Usage: force_message.pl message_id destination\@adresse\n";
        exit 0;
}
