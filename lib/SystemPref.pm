#!/usr/bin/env perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
#   Copyright (C) 2025 John Mertz <git@john.me.tz>
#
#   This program is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; either version 2 of the License, or
#   (at your option) any later version.
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

package SystemPref;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;
require ReadConfig;
require DB;
use File::Path qw(make_path);
use strict;

our @ISA = qw(Exporter);
our @EXPORT = qw(getInstance getPref);
our $VERSION = 1.0;

my $oneTrueSelf;

## singleton stuff
sub getInstance($oneTrueSelf=create())
{
    return $oneTrueSelf;
}

sub create($name="SystemPref")
{
    my %prefs;

    my $conf = ReadConfig::getInstance();
    my $prefdir = $conf->getOption('VARDIR')."/spool/mailcleaner/prefs/_global/";
    make_path($prefdir, {'mode'=>0755, 'user'=>'mailcleaner', 'group'=>'mailcleaner'});
    my $preffile = $prefdir."/prefs.list";
    my $self = {
        name => $name,
        prefdir => $prefdir,
        preffile => $preffile,
        prefs => \%prefs
    };

    bless $self, "SystemPref";
    return $self;
}

sub getPref($self,$pref,$default=undef)
{
    if (!defined($self->{prefs}) || !defined($self->{prefs}->{id})) {

        my $prefclient = PrefClient->new();
        $prefclient->setTimeout(2);
        my $dpref = $prefclient->getPref('_global', $pref);
        if (defined($dpref) && $dpref !~ /^_/) {
            $self->{prefs}->{$pref} = $dpref;
            return $dpref;
        }
        ## fallback loading
        $self->loadPrefs();

    }

    if (defined($self->{prefs}->{$pref})) {
        return $self->{prefs}->{$pref};
    }
    if (defined($default)) {
        return $default;
    }
    return "";
}

sub loadPrefs($self)
{
    if ( ! -f $self->{preffile}) {
        return 0;
    }

    my $PREFFILE;
    if ( !open($PREFFILE, '<', $self->{preffile}) ) {
        return 0;
    }
    while (<$PREFFILE>) {
        if (/^(\S+)\s+(.*)$/) {
            $self->{prefs}->{$1} = $2;
        }
    }
    close $PREFFILE;
}

sub dumpPrefs($self)
{
    my $slave_db = DB::connect('slave', 'mc_config');
    my %prefs = $slave_db->getHashRow("SELECT * FROM antispam");
    my %conf = $slave_db->getHashRow("SELECT use_ssl, servername FROM httpd_config");
    my %sysconf = $slave_db->getHashRow("SELECT summary_from, analyse_to FROM system_conf");

    if (! -d $self->{prefdir} && ! mkdir($self->{prefdir})) {
        print "CANNOTCREATESYSTEMPREFDIR\n";
        return 0;
    }
    my $uid = getpwnam( 'mailcleaner' );
    my $gid = getgrnam( 'mailcleaner' );
    chown $uid, $gid, $self->{prefdir};

    my $PREFFILE;
    if ( !open($PREFFILE, '>', $self->{preffile}) ) {
        print "CANNOTWRITESYSTEMPREF\n";
        return 0;
    }
    foreach my $p (keys %prefs) {
        if (!defined($prefs{$p})) {
            $prefs{$p} = '';
        }
        print $PREFFILE "$p ".$prefs{$p}."\n";
    }
    foreach my $p (keys %conf) {
        print $PREFFILE "$p ".$conf{$p}."\n";
    }
    foreach my $p (keys %sysconf) {
        print $PREFFILE "$p ".$sysconf{$p}."\n";
    }
    close $PREFFILE;
    chown $uid, $gid, $self->{preffile};
    return 1;
}

1;
