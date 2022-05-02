#!/usr/bin/perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2021 John Mertz <git@john.me.tz>
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
#   This script will output the count of messages/spams/viruses for a domain/user or globaly for a given period

sub usage
{
	print "\nUsage: $0 [a|aaaa|mx|spf] domain <ip>\n
  	a		query A record
  	aaaa		query AAAA record
  	mx		query MX record
  	spf		query SPF record
  	domain	the domain to query
  	ip		(optional) check if given IP is in the list of results\n\n";
	exit();
	
}

use strict;
use warnings;

if ($0 =~ m/(\S*)\/\S+.pl$/) {
  my $path = $1."/../lib";
  unshift (@INC, $path);
}
require GetDNS;

unless (defined($ARGV[1]) && $ARGV[0] =~ m/^(a|aaaa|mx|spf)$/i) {
	usage();
}

my $dns = GetDNS->new();

my ($target,$v);
if (defined $ARGV[2]) {
	$target = $ARGV[2];
	unless ( $dns->{'validator'}->is_ipv4($ARGV[2])
		|| $dns->{'validator'}->is_ipv6($ARGV[2]) ) 
	{
		print "\n'$target' is not a IPv4 or IPv6 address\n";
		usage();
	}
}

my @ips;
if ($ARGV[0] eq 'a' || $ARGV[0] eq 'A') {
	@ips = $dns->getA($ARGV[1]);
} elsif ($ARGV[0] eq 'aaaa' || $ARGV[0] eq 'AAAA') {
	@ips = $dns->getAAAA($ARGV[1]);
} elsif ($ARGV[0] eq 'mx' || $ARGV[0] eq 'MX') {
	@ips = $dns->getMX($ARGV[1]);
} elsif ($ARGV[0] eq 'spf' || $ARGV[0] eq 'SPF') {
	@ips = $dns->getSPF($ARGV[1]);
} else {
	die "Invalid record type\n";
}

if ($target) {
	print $dns->inIPList($target,@ips) . "\n";
} else {
	foreach (@ips) {
		print("$_\n");
	}
}
