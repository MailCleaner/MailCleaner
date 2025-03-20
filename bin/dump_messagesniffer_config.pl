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
#   This script will dump the messagesniffer configuration file with the configuration
#   settings found in the database.
#
#   Usage:
#           dump_messagesniffer_config.pl


use v5.36;
use strict;
use warnings;
use utf8;
use Carp qw( confess );

our ($SRCDIR, $VARDIR, $HTTPPROXY;
BEGIN {
    if ($0 =~ m/(\S*)\/\S+.pl$/) {
        my $path = $1."/../lib";
        unshift (@INC, $path);
    }
    require ReadConfig;
    my $conf = ReadConfig::getInstance();
    $SRCDIR = $conf->getOption('SRCDIR') || '/usr/mailcleaner';
    $VARDIR = $conf->getOption('VARDIR') || '/var/mailcleaner';
    $HTTPPROXY = $conf->getOption('HTTPPROXY') || '';
}

use lib_utils qw(open_as);
require DB;

our $DEBUG = 1;

my $lasterror;

my $dbh = DB::connect('slave', 'mc_config');

my %messagesniffer_conf;
%messagesniffer_conf = get_messagesniffer_config() or fatal_error("NOMESSAGESNIFFERCONFIGURATIONFOUND", "no MessageSniffer configuration found");

if (!defined($messagesniffer_conf{'__LICENSEID__'})) {
    $messagesniffer_conf{'__LICENSEID__'} = '';
}
if (!defined($messagesniffer_conf{'__AUTHENTICATION__'})) {
    $messagesniffer_conf{'__AUTHENTICATION__'} = '';
}

our ($uid, $gid);
confess "Unable to detect 'snfuser' user" unless $uid = getpwnam( 'snfuser' );
confess "Unable to detect 'snfuser' group" unless $gid = getgrnam( 'snfuser' );

our $dir = "$SRCDIR/etc/messagesniffer";
unless ( -d $dir ) {
    confess "Cannot create dir $dir: $!\n" unless make_path($dir, { 'mode' => 0755, 'user' => $uid, 'group' => $gid });
}
dump_file("SNFServer.xml");
dump_file("identity.xml");
dump_file("getRulebase");

$dbh->disconnect();

#############################
sub dump_file($file)
{
    my $template_file = "${dir}/${file}_template";
    my $target_file = "${dir}/${file}";

    my ($TEMPLATE, $TARGET);
    confess "Cannot open $template_file: $!\n" unless ($TEMPLATE = ${open_as($template_file, '<', 0755, "snfuser:snfuser")});
    confess "Cannot open $target_file: $!\n" unless ($TARGET = ${open_as($target_file, '>', 0755, "snfuser:snfuser")});

    my $proxy_server = "";
    my $proxy_port = "";
    if ($HTTPPROXY =~ m/http\:\/\/(\S+)\:(\d+)/) {
        $proxy_server = $1;
        $proxy_port = $2;
    }

    while(<$TEMPLATE>) {
        my $line = $_;

        $line =~ s/__VARDIR__/${VARDIR}/g;
        $line =~ s/__SRCDIR__/${SRCDIR}/g;
        if ($proxy_server =~ m/\S+/) {
            $line =~ s/\#HTTPProxyServer __HTTPPROXY__/HTTPProxyServer $proxy_server/g;
            $line =~ s/\#HTTPProxyPort __HTTPPROXYPORT__/HTTPProxyPort $proxy_port/g;
        }
        $line =~ s/__LICENSEID__/$messagesniffer_conf{'__LICENSEID__'}/g;
        $line =~ s/__AUTHENTICATION__/$messagesniffer_conf{'__AUTHENTICATION__'}/g;

        print $TARGET $line;
    }

    close $TEMPLATE;
    close $TARGET;

    return 1;
}

#############################
sub get_messagesniffer_config()
{
    my %config;

    my $sth = $dbh->prepare("SELECT licenseid, authentication FROM MessageSniffer");
    $sth->execute() or fatal_error("CANNOTEXECUTEQUERY", $dbh->errstr);

    if ($sth->rows < 1) {
        return;
    }
    my $ref = $sth->fetchrow_hashref() or return;

    $config{'__LICENSEID__'} = $ref->{'licenseid'};
    $config{'__AUTHENTICATION__'} = $ref->{'authentication'};

    $sth->finish();
    return %config;
}
