#!/usr/bin/perl -w
#
# Mailcleaner - SMTP Antivirus/Antispam Gateway
# Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
# Copyright (C) 2021-2023 John Mertz <git@john.me.tz>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
#
#
# This script will dump the firewall script
#
# Usage:
#	dump_firewall.pl


use strict;
use DBI();
use Net::DNS;
if ($0 =~ m/(\S*)\/\S+.pl$/) {
	my $path = $1."/../lib";
	unshift (@INC, $path);
}
require GetDNS;
our $dns = GetDNS->new();

my $DEBUG = 1;

my %config = readConfig("/etc/mailcleaner.conf");
my $start_script = $config{'SRCDIR'}."/etc/firewall/start";
my $stop_script = $config{'SRCDIR'}."/etc/firewall/stop";
my %services = (
	'web' => ['80|443', 'TCP'],
	'mysql' => ['3306:3307', 'TCP'],
	'snmp' => ['161', 'UDP'],
	'ssh' => ['22', 'TCP'],
	'mail' => ['25', 'TCP'],
	'soap' => ['5132', 'TCP']
);
my $iptables = "/sbin/iptables";
my $ip6tables = "/sbin/ip6tables";
my $ipset = "/sbin/ipset";

my $lasterror = "";
my $has_ipv6 = 0;

unlink($start_script);
unlink($stop_script);

my $dbh;
$dbh = DBI->connect(
	"DBI:mysql:database=mc_config;host=localhost;mysql_socket=$config{VARDIR}/run/mysql_slave/mysqld.sock",
	"mailcleaner",
	"$config{MYMAILCLEANERPWD}",
	{RaiseError => 0, PrintError => 0}
) or fatal_error("CANNOTCONNECTDB", $dbh->errstr);

my %masters_slaves = get_masters_slaves();

my %trustedips = ( '127.0.0.1' => 1 );
foreach (keys(%masters_slaves)) {
	if ($_ =~ m/\d+\.\d+\.\d+\.\d+/) {
		$trustedips{$_} = 1;
	} elsif ($_ =~ m/\d+::?+\d/) {
		$trustedips{$_} = 1;
	} else {
		my @a = $dns->getA($_);
		if (scalar(@a)) {
			$trustedips{$_} = 1 foreach (@a);
		}
	}
}
$trustedips{"193.246.63.0/24"} = 1 if ($config{'REGISTERED'} == 1);
$trustedips{"195.176.194.0/24"} = 1 if ($config{'REGISTERED'} == 1);
our $trusted = join(' ', keys(%trustedips));

my $dnsres = Net::DNS::Resolver->new;

# do we have ipv6 ?
if (open(my $interfaces, '<', '/etc/network/interfaces')) {
	while (<$interfaces>) {
		if ($_ =~ m/iface \S+ inet6/) {
			$has_ipv6 = 1;
			last;
		}
	}
	close($interfaces);
}


my %rules;
get_default_rules();
get_external_rules();

do_start_script() or fatal_error("CANNOTDUMPMYSQLFILE", $lasterror);;
do_stop_script();

print "DUMPSUCCESSFUL";

############################
sub get_masters_slaves
{
	my %hosts;

	my $sth = $dbh->prepare("SELECT hostname from master");
	$sth->execute() or fatal_error("CANNOTEXECUTEQUERY", $dbh->errstr);

	while (my $ref = $sth->fetchrow_hashref() ) {
		 $hosts{$ref->{'hostname'}} = 1;
	}
	$sth->finish();

	$sth = $dbh->prepare("SELECT hostname from slave");
	$sth->execute() or fatal_error("CANNOTEXECUTEQUERY", $dbh->errstr);

	while (my $ref = $sth->fetchrow_hashref() ) {
		$hosts{$ref->{'hostname'}} = 1;
	}
	$sth->finish();
	return %hosts;

}

sub get_default_rules
{
	foreach my $host (keys %masters_slaves) {
		next if ($host =~ /127\.0\.0\.1/ || $host =~ /^\:\:1$/);

		$rules{"$host mysql TCP"} = [ $services{'mysql'}[0], $services{'mysql'}[1], $host];
		$rules{"$host snmp UDP"} = [ $services{'snmp'}[0], $services{'snmp'}[1], $host];
		$rules{"$host ssh TCP"} = [ $services{'ssh'}[0], $services{'ssh'}[1], $host];
		$rules{"$host soap TCP"} = [ $services{'soap'}[0], $services{'soap'}[1], $host];
	}
	my @subs = getSubnets();
	foreach my $sub (@subs) {
		$rules{"$sub ssh TCP"} = [ $services{'ssh'}[0], $services{'ssh'}[1], $sub ];
	}
}

sub get_external_rules
{
	my $sth = $dbh->prepare("SELECT service, port, protocol, allowed_ip FROM external_access");
	$sth->execute() or fatal_error("CANNOTEXECUTEQUERY", $dbh->errstr);

	while (my $ref = $sth->fetchrow_hashref() ) {
		 #next if ($ref->{'allowed_ip'} !~ /^(\d+.){3}\d+\/?\d*$/);
		 next if ($ref->{'port'} !~ /^\d+[\:\|]?\d*$/);
		 next if ($ref->{'protocol'} !~ /^(TCP|UDP|ICMP)$/i);
		 foreach my $ip (expand_host_string($ref->{'allowed_ip'},('dumper'=>'snmp/allowedip'))) {
			 # IPs already validated and converted to CIDR in expand_host_string, just remove non-CIDR entries
			 if ($ip =~ m#/\d+$#) {
				 $rules{$ip." ".$ref->{'service'}." ".$ref->{'protocol'}} = [ $ref->{'port'}, $ref->{'protocol'}, $ip];
			 }
		 }
	}

	## check snmp UDP
	foreach my $rulename (keys %rules) {
		if ($rulename =~ m/([^,]+) snmp/) {
			$rules{$1." snmp UDP"} = [ 161, 'UDP', $rules{$rulename}[2]];
		}
	}

	## enable submission port
	foreach my $rulename (keys %rules) {
		if ($rulename =~ m/([^,]+) mail/) {
			$rules{$1." submission TCP"} = [ 587, 'TCP', $rules{$rulename}[2]];
		}
	}
	## do we need obsolete SMTP SSL port ?
	$sth = $dbh->prepare("SELECT tls_use_ssmtp_port FROM mta_config where stage=1");
	$sth->execute() or fatal_error("CANNOTEXECUTEQUERY", $dbh->errstr);
	while (my $ref = $sth->fetchrow_hashref() ) {
		if ($ref->{'tls_use_ssmtp_port'} > 0) {
			foreach my $rulename (keys %rules) {
				if ($rulename =~ m/([^,]+) mail/) {
					$rules{$1." smtps TCP"} = [ 465, 'TCP', $rules{$rulename}[2] ];
				}
			}
		}
	}
}

sub do_start_script
{
	if ( !open(START, ">$start_script") ) {
		 $lasterror = "Cannot open start script";
		 return 0;
	}

	print START "#!/bin/sh\n";

	print START "/sbin/modprobe ip_tables\n";
	if ($has_ipv6) {
		print START "/sbin/modprobe ip6_tables\n";
	}

	print START "\n# policies\n";
	print START $iptables." -P INPUT DROP\n";
	print START $iptables." -P FORWARD DROP\n";
	if ($has_ipv6) {
		print START $ip6tables." -P INPUT DROP\n";
		print START $ip6tables." -P FORWARD DROP\n";
	}

	print START "\n# bad packets:\n";
	print START $iptables." -A INPUT -p tcp ! --syn -m state --state NEW -j DROP\n";
	if ($has_ipv6) {
		print START $ip6tables." -A INPUT -p tcp ! --syn -m state --state NEW -j DROP\n";
	}

	print START "# local interface\n";
	print START $iptables." -A INPUT -p ALL -i lo -j ACCEPT\n";
	if ($has_ipv6) {
		print START $ip6tables." -A INPUT -p ALL -i lo -j ACCEPT\n";
	}

	print START "# accept\n";
	print START $iptables." -A INPUT -p ALL -m state --state ESTABLISHED,RELATED -j ACCEPT\n";
	if ($has_ipv6) {
		print START $ip6tables." -A INPUT -p ALL -m state --state ESTABLISHED,RELATED -j ACCEPT\n";
	}

	print START $iptables." -A INPUT -p ICMP --icmp-type 8 -j ACCEPT\n";
	if ($has_ipv6) {
		print START $ip6tables." -A INPUT -p ipv6-icmp -j ACCEPT\n";
	}

	my $globals = {
		'4' => {},
		'6' => {}
	};
	foreach my $description (sort keys %rules) {
		my @ports = split '\|', $rules{$description}[0];
		my @protocols = split '\|', $rules{$description}[1];
		foreach my $port (@ports) {
			foreach my $protocol (@protocols) {
				my $host = $rules{$description}[2];
				# Globals
				if ($host eq '0.0.0.0/0' || $host eq '::/0') {
					next if ($globals->{'4'}->{$port}->{$protocol});
					print START "\n# $description\n";
					print START $iptables." -A INPUT -p ".$protocol." --dport ".$port." -j ACCEPT\n";
					$globals->{'4'}->{$port}->{$protocol} = 1;
					if ($has_ipv6) {
						$globals->{'6'}->{$port}->{$protocol} = 1;
						print START $ip6tables." -A INPUT -p ".$protocol." --dport ".$port." -j ACCEPT\n";
					}
				# IPv6
				} elsif ($host =~ m/\:/) {
					next unless ($has_ipv6);
					next if ($globals->{'6'}->{$port}->{$protocol});
					print START "\n# $description\n";
					print START $ip6tables." -A INPUT -p ".$protocol." --dport ".$port." -s ".$host." -j ACCEPT\n";
				# IPv4
				} elsif ($host =~ m/^(\d+\.){3}\d+(\/\d+)?$/) {
					next if ($globals->{'4'}->{$port}->{$protocol});
					print START "\n# $description\n";
					print START $iptables." -A INPUT -p ".$protocol." --dport ".$port." -s ".$host." -j ACCEPT\n";
				# Hostname
				} else {
					next if ($globals->{'4'}->{$port}->{$protocol});
					print START "\n# $description\n";
					print START $iptables." -A INPUT -p ".$protocol." --dport ".$port." -s ".$host." -j ACCEPT\n";
					if ($has_ipv6) {
						my $reply = $dnsres->query($host, "AAAA");
						if ($reply) {
							print START $ip6tables." -A INPUT -p ".$protocol." --dport ".$port." -s ".$host." -j ACCEPT\n";
						}
					}
				}
			}
		}
	}

	my $existing = {};
	my $sets_raw = `ipset list`;
	my $set = '';
	my $members = 0;
	foreach (split(/\n/, $sets_raw)) {
		if ($_ =~ m/^Name: (.*)$/) {
			$set = $1;
			$existing->{$set} = {};
			$members = 0;
			next;
		}
		if (!$members) {
			if ($_ =~ m/Members:/) {
				$members = 1;
				next;
			} else {
				next;
			}
		}
		next if ($_ =~ /^\s*$/);
		$existing->{$set}->{$_} = 1;
	}

	my @blacklist_files = ('/usr/mailcleaner/etc/firewall/blacklist.txt', '/usr/mailcleaner/etc/firewall/blacklist_custom.txt');
	my $blacklist = 0;
	my $blacklist_script = '/usr/mailcleaner/etc/firewall/blacklist';
	my @fail2ban_sets = ('mc-exim', 'mc-ssh', 'mc-webauth');
	unlink $blacklist_script;
	open(BLACKLIST, '>>', $blacklist_script);
	foreach my $blacklist_file (@blacklist_files) {
		if ( -e $blacklist_file ) {
			if ( open(BLACK_IP, '<', $blacklist_file) ) {
				if ( $blacklist == 0 ) {
					print BLACKLIST "#! /bin/sh\n\n";
					print BLACKLIST "$ipset create BLACKLISTIP hash:ip\n" unless (defined($existing->{'BLACKLISTIP'}));
					print BLACKLIST "$ipset create BLACKLISTNET hash:net\n" unless (defined($existing->{'BLACKLISTNET'}));
					foreach my $period (qw( d w m y )) {
						foreach my $f2b (@fail2ban_sets) {
							print BLACKLIST "$ipset create $f2b-1$period hash:ip\n" unless (defined($existing->{"$f2b-1$period"}));
							dump_local_file("/usr/mailcleaner/etc/fail2ban/jail.d/${f2b}-1${period}.local_template","/usr/mailcleaner/etc/fail2ban/jail.d/${f2b}-1${period}.local");
						}
					}
				}
				$blacklist = 1;
				foreach my $IP (<BLACK_IP>) {
					chomp($IP);
					if ($IP =~ m#/\d+$#) {
						if ($existing->{'BLACKLISTNET'}->{$IP}) {
							delete($existing->{'BLACKLISTNET'}->{$IP});
						} else {
							print BLACKLIST "$ipset add BLACKLISTNET $IP\n";
						}
					} else {
						if ($existing->{'BLACKLISTIP'}->{$IP}) {
							delete($existing->{'BLACKLISTIP'}->{$IP});
						} else {
							print BLACKLIST "$ipset add BLACKLISTIP $IP\n";
						}
					}
				}
				close BLACK_IP;
			}
		}
	}
	my $remove = '';
	foreach my $list (keys(%{$existing})) {
		foreach my $IP (keys(%{$existing->{$list}})) {
			$remove .= "$ipset del $list $IP\n";
		}
	}
	if ($remove ne '') {
		print BLACKLIST "\n# Cleaning up removed IPs:\n$remove\n";
	}
	if ( $blacklist == 1 ) {
		foreach ( qw( BLACKLISTIP BLACKLISTNET ) ) {
			print BLACKLIST "$iptables -I INPUT -m set --match-set $_ src -j REJECT\n";
			print BLACKLIST "$iptables -I INPUT -m set --match-set $_ src -j LOG\n\n";
		}
		chmod 0755, $blacklist_script;
		print START "\n$blacklist_script\n";
	}
	close BLACKLIST;
	close START;

	chmod 0755, $start_script;
}

sub do_stop_script
{
	if ( !open(STOP, ">$stop_script") ) {
		$lasterror = "Cannot open stop script";
		return 0;
	}

	print STOP "#!/bin/sh\n";

	print STOP $iptables." -P INPUT ACCEPT\n";
	print STOP $iptables." -P FORWARD ACCEPT\n";
	print STOP $iptables." -P OUTPUT ACCEPT\n";
	if ($has_ipv6) {
		print STOP $ip6tables." -P INPUT ACCEPT\n";
		print STOP $ip6tables." -P FORWARD ACCEPT\n";
		print STOP $ip6tables." -P OUTPUT ACCEPT\n";
	}

	print STOP $iptables." -F\n";
	print STOP $iptables." -X\n";
	if ($has_ipv6) {
		print STOP $ip6tables." -F\n";
		print STOP $ip6tables." -X\n";
	}

	close STOP;
	chmod 0755, $stop_script;
}

sub getSubnets()
{
	my $ifconfig = `/sbin/ifconfig`;
	my @subs = ();
	foreach my $line (split("\n", $ifconfig)) {
		if ($line =~ m/\s+inet\ addr:([0-9.]+)\s+Bcast:[0-9.]+\s+Mask:([0-9.]+)/) {
			my $ip = $1;
			my $mask = $2;
			if ($mask && $mask =~ m/\d/) {
				my $ipcalc = `/usr/bin/ipcalc $ip $mask`;
				foreach my $subline (split("\n", $ipcalc)) {
					 if ($subline =~ m/Network:\s+([0-9.]+\/\d+)/) {
						push @subs, $1;
					 }
				}
			}
		}
	}
	return @subs;
}

#############################
sub readConfig
{
	my $configfile = shift;
	my %config;
	my ($var, $value);

	open CONFIG, $configfile or die "Cannot open $configfile: $!\n";
	while (<CONFIG>) {
		chomp; 			# no newline
		s/#.*$//;		# no comments
		s/^\*.*$//;		# no comments
		s/;.*$//;		# no comments
		s/^\s+//;		# no leading white
		s/\s+$//;		# no trailing white
		next unless length;	# anything left?
		($var, $value) = split(/\s*=\s*/, $_, 2);
		$config{$var} = $value;
	}
	close CONFIG;
	return %config;
}

#############################
sub dump_local_file
{
	my $template = shift;
	my $target = shift;

	if (open(my $tmp, '<', $template)) {
		my $output = "";
		$output .= $_ while (<$tmp>);
		$output =~ s/__TRUSTEDIPS__/$trusted/g;
		if (open(my $out, '>', $target)) {
			print $out $output;
		} else {
			print STDERR "Failed to open target $target\n";
		}
	} else {
		print STDERR "Failed to open template $template\n";
	}
}

sub fatal_error
{
	my $msg = shift;
	my $full = shift;

	print $msg;
	if ($DEBUG) {
		print "\n Full information: $full \n";
	}
	exit(0);
}

sub expand_host_string
{
	my $string = shift;
	my %args = @_;
	return $dns->dumper($string,%args);
}
