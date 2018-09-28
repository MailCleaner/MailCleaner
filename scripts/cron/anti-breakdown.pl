#! /usr/bin/perl -w
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2015-2018 Pascal Rolle <rolle@mailcleaner.net>
#   Copyright (C) 2015-2018 Mentor Reka <reka@mailcleaner.net>
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
#   This is the anti-breakdown script. To be run every 15 minutes
#
use strict;
use Net::DNS;
use Net::Ping;
use File::Touch;
use DBI;

my $max_host_failed = 2;
my $nb_tests = 3;
my $is_dns_ok = 0;
my $is_data_ok = 0;
my $dns_ko_file		= '/var/tmp/mc_checks_dns.ko';
my $data_ko_file	= '/var/tmp/mc_checks_data.ko';
my $rbl_sql_file	= '/var/tmp/mc_checks_rbls.bak';
my @rbls_to_disable	= qw/MCIPRWL MCIPRBL SIPURIRBL MCURIBL MCERBL SIPINVALUEMENT SIPDEUXQUATREINVALUEMENT MCTRUSTEDSPF/;

my %rbl_field = (
	'trustedSources'	=> 'whiterbls',
	'PreRBLs'		=> 'lists',
	'UriRBLs'		=> 'rbls',
	'mta_config'		=> 'rbls',
	'antispam'		=> 'sa_rbls'
);


my %config = readConfig("/etc/mailcleaner.conf");

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




sub getIPAddresses {
	my ($cname, $type) = @_;

	my $res   = Net::DNS::Resolver->new;
	$res->tcp_timeout( 10 );
	my $reply = $res->search($cname, $type);
	my @teams = ();

	if ($reply) {
	    foreach my $rr ($reply->answer) {
	        push @teams, $rr->address if $rr->can('address');
	    }
	}
	if ( ! @teams ) {
		my @teams_tmp = `dig $cname $type +short`;
		foreach (@teams_tmp) {
			$_ =~s/\s//g;
			push @teams, $_ if ($_ =~ m/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/);
		}
	}

	return @teams;
}

sub checkHost {
	my ($host, $port) = @_;

	my $p = Net::Ping->new('tcp', 5);
	$p->port_number($port);
	my $res = $p->ping($host);
	
	undef($p);

	return $res;
}


sub is_dns_service_available {
	my ($host) = @_;

	my $res = new Net::DNS::Resolver(
		tcp_timeout => 5,
		retry       => 3,
		retrans     => 1,
		recurse     => 0,
		debug       => 0 
	);

	$res->nameservers($host);

	if ( $res->send("mailcleaner.net", 'MX') ) {
		return 1;
	} else {
		return 0;
	}
}

# retourne false si 3 tentatives KO
sub is_port_ok {
	my ($step, $port, @hosts) = @_;
	my $nb_failed_host = 0;

	foreach my $host (@hosts) {
		if ($port == 53) {
			if ( ! is_dns_service_available($host) ) {
				$nb_failed_host++;
			}
		} elsif ( ! checkHost($host, $port) ) {
			$nb_failed_host++;
		}
	}
	if  ( $nb_failed_host >= $max_host_failed) {
		system("/usr/sbin/rndc flush");
		$step++;
		if ($step == $nb_tests) {
			return 0;
		}

		sleep 5;
		return ( is_port_ok($step, $port, @hosts) ) ;
	}
	return 1;	
}

sub remove_and_save_MC_RBLs {
	my $sth;
	my $reboot_service = 0;

	my %master_conf = get_master_config();
	my $master_dbh = DBI->connect("DBI:mysql:database=mc_config;host=$master_conf{'__MYMASTERHOST__'}:$master_conf{'__MYMASTERPORT__'}",
                           "mailcleaner", "$master_conf{'__MYMASTERPWD__'}", {RaiseError => 0, PrintError => 0});
	if ( ! defined($master_dbh) ) {
		warn "CANNOTCONNECTMASTERDB\n", $master_dbh->errstr;
		return 0;
	}

	open FH, '>', $rbl_sql_file;

	foreach my $table (keys %rbl_field) {
		my $field = $rbl_field{$table};

		$sth = $master_dbh->prepare("select $field from $table;");
		$sth->execute() or return 0;
		my $ref = $sth->fetchrow_hashref();
		my $original_field = $ref->{$field};
		$original_field =~ s/\s+/ /g;
		$original_field =~ s/\s*$//;
		my @rbls = split(' ', $original_field);
	
		my $nw;
		foreach my $w (@rbls) {
			if ( is_into($w, @rbls_to_disable) ) {
				print FH "update $table set $field = concat($field, ' $w');\n";
			} else {
				$nw .= "$w ";
			}
		}
		$nw =~ s/\s*$//;

		if ($nw ne $original_field) {
			$sth = $master_dbh->prepare("update $table set $field ='$nw';");
			$sth->execute();
			$reboot_service = 1;
		}
	}	

	close FH;
	$sth->finish() if ( defined($sth) );
	$master_dbh->disconnect();

	if ($reboot_service) {
		system('/usr/mailcleaner/etc/init.d/mailscanner restart');
	}
}

# DNS service is ok, if the previous state was KO, we enable back the RBLs which were formely configured
sub handle_dns_ok {
	# reimport all saved rbls (/var/tmp/mc_checks_rbls.bak)
	if ( -e $rbl_sql_file ) {
		my $sth;

		# Database connexion
        	my %master_conf = get_master_config();
	        my $master_dbh = DBI->connect("DBI:mysql:database=mc_config;host=$master_conf{'__MYMASTERHOST__'}:$master_conf{'__MYMASTERPORT__'}",
                           "mailcleaner", "$master_conf{'__MYMASTERPWD__'}", {RaiseError => 0, PrintError => 0});
	        if ( ! defined($master_dbh) ) {
                	warn "CANNOTCONNECTMASTERDB\n", $master_dbh->errstr;
        	        return 0;
	        }

		# The file contains the SQL statements ready to be excuted
	        open FH, $rbl_sql_file or warn "Cannot open $rbl_sql_file: $!\n";
	        while (<FH>) {
			$sth = $master_dbh->prepare($_);
			$sth->execute();
		}

		close FH;
		$sth->finish() if ( defined($sth) );
		$master_dbh->disconnect();

		# Restarting associated services
		system('/usr/mailcleaner/etc/init.d/mailscanner restart');
	
		# Removing temp files
		unlink $rbl_sql_file or warn "could not remove $rbl_sql_file\n";
		unlink $dns_ko_file or warn "could not remove $dns_ko_file\n";;
	}
}

# DNS service is KO. We remove RBLs hosted by MailCleaner from the configuration
sub handle_dns_ko {
	# There is nothing to do if MailCleaner was already away
	return if ( -e $dns_ko_file );

	# Creating the DNS KO flag file : /var/tmp/mc_checks_dns.ko
	touch($dns_ko_file);

	# Removes and saves the RBLs hosted by MailCleaner then restarts associated services
	remove_and_save_MC_RBLs();
}

# MailCleaner servers used for updating scripts and data are offline
# We set a flag which will prevent associated services to run
sub handle_data_ko {
	# Creating the Data KO flag file : /var/tmp/mc_checks_data.ko
	touch($data_ko_file);
}

sub handle_data_ok {
	unlink $data_ko_file;
}


##########################################################################################
# Exit if not registered
if ( ! defined($config{'REGISTERED'}) || $config{'REGISTERED'} != 1 ) {
	exit;
}

# Getting IPs for cvs.mailcleaner.net
my @teams = getIPAddresses('cvs.mailcleaner.net', 'A');

if ( @teams) {
	$is_dns_ok	= is_port_ok(0, 53, @teams);
	$is_data_ok	= is_port_ok(0, 22, @teams);
} else {
	$is_dns_ok	= 0;
	$is_data_ok	= 0;
}

if ($is_dns_ok)		{ handle_dns_ok();	}
else            	{ handle_dns_ko();	}
if ($is_data_ok)	{ handle_data_ok();	}
else            	{ handle_data_ko();	}
