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
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
#
#   This script will dump the clamspamd configuration file with the configuration
#   settings found in the database.
#
#   Usage:
#           dump_clamspamd_config.pl

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

my $lasterror;

# Dump configuration
dump_file("clamspamd.conf");

my $uid = getpwnam( 'clamav' );
my $gid = getgrnam( 'clamav' );
my $conf = '/etc/clamav';

if (-e $conf && ! -s $conf) {
    unlink(glob("$conf/*"), $conf);
}
symlink($SRCDIR."/".$conf, $conf) unless (-l $conf);

# Create necessary dirs/files if they don't exist
foreach my $dir (
    "/etc/clamav",
    $SRCDIR."/etc/clamav",
    $VARDIR."/log/clamav",
    $VARDIR."/run/clamav",
    $VARDIR."/spool/clamav",
    $VARDIR."/spool/clamspam",
) {
    mkdir($dir) unless (-d $dir);
    chown($uid, $gid, $dir);
}

foreach my $file (
    glob($SRCDIR."/etc/clamav/*"),
    $SRCDIR."/etc/clamav/clamspamd.conf",
    glob($VARDIR."/log/clamav/*"),
    glob($VARDIR."/run/clamav/*"),
    glob($VARDIR."/spool/clamspam/*"),
) {
    chown($uid, $gid, $file);
}

# Configure sudoer permissions if they are not already
mkdir '/etc/sudoers.d' unless (-d '/etc/sudoers.d');
if (open(my $fh, '>', '/etc/sudoers.d/clamav')) {
    print $fh "
User_Alias  CLAMAV = clamav
Cmnd_Alias  CLAMBIN = /usr/sbin/clamd

CLAMAV      * = (ROOT) NOPASSWD: CLAMBIN
";
}

# Add to mailcleaner group if not already a member
`usermod -a -G mailcleaner clamav` unless (grep(/\bmailcleaner\b/, `groups clamav`));

# SystemD auth causes timeouts
`sed -iP '/^session.*pam_systemd.so/d' /etc/pam.d/common-session`;

# Remove default AppArmor rules and reload with ours
if (-e "/etc/apparmor.d/usr.sbin.clamd") {
    `apparmor_parser -R /etc/apparmor.d/usr.sbin.clamd`;
    unlink("/etc/apparmor.d/usr.sbin.clamd");
}
`apparmor_parser -r ${SRCDIR}/etc/apparmor.d/usr.sbin.clamd` if ( -d '/sys/kernel/security/apparmor' );

#############################
sub dump_file($file)
{
    my $template_file = $SRCDIR."/etc/clamav/".$file."_template";
    my $target_file = $SRCDIR."/etc/clamav/".$file;

    my ($TEMPLATE, $TARGET);
    confess "Cannot open $template_file" unless ( $TEMPLATE = ${open_as($template_file,'<',0664,'clamav:clamav')} );
    confess "Cannot open $template_file" unless ( $TARGET = ${open_as($target_file,'>',0664,'clamav:clamav')} );

    while(<$TEMPLATE>) {
        my $line = $_;

        $line =~ s/__VARDIR__/${VARDIR}/g;

        print $TARGET $line;
    }

    close $TEMPLATE;
    close $TARGET;

    return 1;
}
