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
#   This script will force a spam message stored on the quarantine
#
#   Usage:
#           force_message.pl msg_id destination
#   where msg_id is the id of the message
#   and destination is the address of the original recipient

use v5.36;
use strict;
use warnings;
use utf8;
use Carp qw( confess );

my ($SRCDIR, $VARDIR);
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

use lib_utils qw(open_as);
require DB;

use Net::SMTP;

my %master_conf;

my $msg_id = shift;
my $for = shift;

if ( (!$msg_id) || !($msg_id =~ /^[a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{6,11}-[a-z,A-Z,0-9]{2,4}$/)) {
    print "INCORRECTMSGID\n";
        exit 0;
}

if ( (!$for) || !($for =~ /^(\S+)\@(\S+)$/)) {
    print "BADADDRESSFORMAT\n";
    exit 0;
}

my $for_local = $1;
my $for_domain = $2;

my $msg_file = $conf->getOption('VARDIR')."/spam/".$for_domain."/".$for."/".$msg_id;

if ( open(my $MSG, '<', $msg_file) ) {
    my $start_msg = 0;
    my $msg = "";
    my $has_from = 0;
    my $from = "";
    my $in_dkim = 0;
    while (<$MSG>) {
        # remove garbage line before the real headers
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
            if (!$in_dkim) {
                $msg = $msg.$line;
            }
        }
    }
    close($MSG);

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
    mark_forced();

    print("MSGFORCED\n");
} else {
    print "MSGFILENOTFOUND\n";
}


exit 1;

##########################################
sub mark_forced
{
    my $dbh;
    $dbh = DB::connect('realmaster','mc_spool');

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

############################################
sub print_usage
{
    print "bad usage...\n";
    print "Usage: force_message.pl message_id destination_address\n";
    exit 0;
}
