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
#   This script will check for updates service availabilty
#
#   Requires : libconfig-simple-perl, libxml-simple-perl, libstring-random-perl

use v5.36;
use strict;
use warnings;
use utf8;
use Carp qw( confess );

our ($SRCDIR, $CLIENTID, $HOSTID, $REGISTERED);
BEGIN {
    if ($0 =~ m/(\S*)\/\S+.pl$/) {
        my $path = $1."/../lib";
        unshift (@INC, $path);
    }
    require ReadConfig;
    my $conf = ReadConfig::getInstance();
    $SRCDIR = $conf->getOption('SRCDIR') || '/usr/mailcleaner';
    $CLIENTID = $conf->getOption('CLIENTID') || undef;
    $HOSTID = $conf->getOption('HOSTID') || 1;
}

use LWP::UserAgent;
use XML::Simple;
use Net::DNS::Resolver;
use Config::Simple;
use Data::Dumper;
use String::Random;
use Getopt::Std;

sub usage
{
  print STDERR << "EOF";
usage: $0 [-rh]

-r     : randomize start of script, for periodic calls
-h     : display usage
EOF
  exit;
}

my $minsleeptime=0;
my $maxsleeptime=20;

my %options=();
getopts(":rh", \%options);

if (defined $options{r}) {
    my $delay = int(rand($maxsleeptime)) + $minsleeptime;
    print STDOUT "Sleeping for $delay seconds...\n";
    sleep($delay);
}
if (defined $options{h}) {
  usage();
}

if (!$REGISTERED) {
    print STDERR "** ERROR ** Useless on unregistered host. You won't be validated.\n";
    exit 1;
}

my $updates_config_file = "${SRCDIR}/etc/mailcleaner/updates.cf";
if (! -f $updates_config_file ) {
    print STDERR "** ERROR ** No updates configuration found. Aborting.\n";
    exit 1;
}
my $updates_config = Config::Simple->new($updates_config_file);

my %services = (
    'http' => { 'call' => \&checkHTTP, 'params' => {'service' => 'http'}},
    'https' => { 'call' => \&checkHTTP, 'params' => {'service' => 'https'}},
    'dns' => { 'call' => \&checkDNS, 'params' => {'service' => 'dns'}},
    'ssh' => { 'call' => \&checkHTTP, 'params' => {'service' => 'ssh'}}
);

foreach my $service (keys %services) {
    my $s = $services{$service};
    my %result = &{$services{$service}{'call'}}($services{$service}{'params'});

    if ($result{'status'}) {
        print STDOUT "Service: ".$service." - OK\n";
    } else {
        print STDOUT "Service: ".$service." - NOK (".$result{'message'}.")\n";
    }
}

exit 0;

sub checkHTTP($params)
{
    my $timeout = 10;

    my %return = ('status' => 0, 'message' => 'no check done');
    my $service = 'http';
    if ($params->{'service'} ne 'http') {
        $service = 'https';
    }
    $service = $params->{'service'};
    my $checkURL=$updates_config->param('service-check.'.$service.'URL');
    my $license='xxxx-xxxx-xxxx-xxxx';
    my $agent='MailCleaner host/0.1 ';

    my $ua = LWP::UserAgent->new;
    $ua->timeout($timeout);
    $ua->agent($agent);
    my $req = HTTP::Request->new( GET => $checkURL );
    $req->content_type('text/xml');

    my $xml = XML::Simple->new(ForceArray => 1, KeepRoot => 0);
    my $data = {
        'clientID' => $CLIENTID,
        'hostID' => $HOSTID,
        'license' => $license
    };


    $req->content($xml->XMLout($data));

    my $res = $ua->request($req);

    if ($res->is_success) {
        $return{'status'} = 1;
        $return{'message'} = 'success';
    } else {
        $return{'message'} = $res->status_line;
    }

    return %return;
}

sub checkDNS
{
    my %return = ('status' => 0, 'message' => 'no check done');

    my $random = String::Random->new();
    my $query = $CLIENTID.'-'.$HOSTID.'-'.$random->randpattern("cccccccccc").'.'.$updates_config->param('service-check.dnsDomain');
    my $dnsResult = gethostbyname( $query );
    if ($dnsResult) {
        $dnsResult = Socket::inet_ntoa($dnsResult);
        $return{'status'} = 1;
        $return{'message'} = 'success';
    } else {
        $return{'message'} = 'Error with global DNS query: '.$query;
        return %return;
    }

    my $res = Net::DNS::Resolver->new;
    foreach my $server ($res->nameservers) {
        $res->nameservers($server);
        my $reply = $res->query($query, 'A');

        if (!$reply || !$reply->answer) {
            $return{'status'} = 0;
            $return{'message'} = 'Error with DNS query '.$query.' on '.$server.' with message: No answer.';
            return %return;
        }
        foreach my $rr ($reply->answer) {
            if ($rr->type eq 'A') {
                #print "Result from ".$server." is: ".$rr->address."\n";
            } else {
                $return{'status'} = 0;
                $return{'message'} = 'Error with DNS query '.$query.' on '.$server.' with message: Type is not A but '.$rr->type;
                return %return;
            }
        }
    }
    return %return;
}
