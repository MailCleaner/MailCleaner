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
#   This script will dump the domains configuration
#
#   Usage:
#           dump_archiving.pl

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
    $SRCDIR = $conf->getOption('SRCDIR');
    $VARDIR = $conf->getOption('VARDIR');
    unshift(@INC, $SRCDIR."/lib");
}

use lib_utils qw( open_as );

require DB;
require Domain;
require SystemPref;
require ConfigTemplate;
use File::Copy;

my $domain = shift;

my $uid = getpwnam( 'mailcleaner' );
my $gid = getgrnam( 'mailcleaner' );

my $slave_db = DB::connect('slave', 'mc_config');

dumpArchivedDomains();
dumpCopyto();
dumpBypassFiltering();

$slave_db->disconnect();

sub dumpArchivedDomains()
{
    my @adomains = $slave_db->getListOfHash("SELECT d.name FROM domain d, domain_pref dp WHERE dp.archive_mail='1' AND d.name != '__global__' AND d.prefs=dp.id");

    my $archive_path = "${VARDIR}/spool/tmp/exim_stage1/archiver";
    if (! -d $archive_path) {
        mkdir($archive_path);
    }
    if (defined($archive_path) && $archive_path ne '') {
        `rm $archive_path/* >/dev/null 2>&1`;
    }

    my %doms;
    foreach my $d (@adomains) {
        my $domfile = $archive_path."/".$d->{'name'};
        if ( my $DFILE = ${open_as($domfile)} ) {
            print $DFILE "*";
            close $DFILE;
            $doms{$d->{'name'}} = 1;
        }
    }

    my @aemail = $slave_db->getListOfHash("SELECT address from email e, user_pref p WHERE p.archive_mail=1 AND e.pref=p.id");
    foreach my $e (@aemail) {
        if (defined($e->{'address'}) && $e->{'address'} =~ /(\S+)\@(\S+)/) {
            my $edom = $2;
            my $euser = $1;
            if (!defined($doms{$edom})) {
                my $domfile = $archive_path."/".$edom;
                if ( my $DFILE = ${open_as($domfile, '>>')} ) {
                    print $DFILE $e->{'address'}."\n";
                    print $DFILE $euser."\n";
                    close $DFILE;
                }
            #} else {
                # print "user ".$e->{'address'}." not added as domain: $edom already fully archived\n";
            }
        }
    }
}

sub dumpCopyto()
{
    my @cdomains = $slave_db->getListOfHash("SELECT d.name, dp.copyto_mail FROM domain d, domain_pref dp WHERE dp.copyto_mail != '' AND d.name != '__global__' AND d.prefs=dp.id");

    my $copyto_path = "${VARDIR}/spool/tmp/exim_stage1/copyto";
    if (! -d $copyto_path) {
        mkdir($copyto_path);
    }
    if (defined($copyto_path) && $copyto_path ne '') {
        `rm $copyto_path/* >/dev/null 2>&1`;
    }

    my %doms;
    foreach my $d (@cdomains) {
        my $domfile = $copyto_path."/".$d->{'name'};
        if ( my $DFILE = ${open_as($domfile)} ) {
            print $DFILE "*:".$d->{'copyto_mail'};
            close $DFILE;
            $doms{$d->{'name'}} = 1;
        }
    }

    my @cemail = $slave_db->getListOfHash("SELECT e.address, p.copyto_mail from email e, user_pref p WHERE p.copyto_mail != '' AND e.pref=p.id");
    foreach my $e (@cemail) {
        if (defined($e->{'address'}) && $e->{'address'} =~ /(\S+)\@(\S+)/) {
            my $edom = $2;
            my $euser = $1;
            if (!defined($doms{$edom})) {
                my $domfile = $copyto_path."/".$edom;
                if ( my $DFILE = ${open_as($domfile, '>>')} ) {
                    print $DFILE $e->{'address'}.":".$e->{'copyto_mail'}."\n";
                    print $DFILE $euser.":".$e->{'copyto_mail'}."\n";
                    close $DFILE;
                }
            #} else {
                # print "user ".$e->{'address'}." not added as domain: $edom already fully archived\n";
            }
        }
    }
}

sub dumpBypassFiltering()
{
    my $bypassfiltering_path = "${VARDIR}/spool/tmp/exim_stage1/bypass";

    my @cemail = $slave_db->getListOfHash("SELECT e.address, p.bypass_filtering from email e, user_pref p WHERE p.bypass_filtering != '' AND e.pref=p.id");

    if (defined($bypassfiltering_path) && $bypassfiltering_path ne '') {
        if ( ! -d $bypassfiltering_path ) {
            mkdir($bypassfiltering_path);
        }
        `rm $bypassfiltering_path/* >/dev/null 2>&1`;
    }

    foreach my $e (@cemail) {
        if (defined($e->{'address'}) && $e->{'address'} =~ /(\S+)\@(\S+)/) {
            my $edom = $2;
            my $euser = $1;
            my $domfile = $bypassfiltering_path."/".$edom;
            if ( my $DFILE = ${open_as($domfile, '>>')} ) {
                print $DFILE $euser."\n";
                close $DFILE;
            }
        }
    }
}
