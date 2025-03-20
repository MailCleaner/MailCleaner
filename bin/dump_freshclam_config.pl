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
#   This script will dump the freshclam configuration file with the configuration
#   settings found in the database.
#
#   Usage:
#           dump_freshclam_config.pl [--agree-to-unofficial|--remove_unofficial]

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

my $unofficial = shift || 0;

use lib_utils qw( open_as );
use File::Touch qw( touch );

my $lasterror;

my $uid = getpwnam( 'clamav' );
my $gid = getgrnam( 'clamav' );
my $conf = '/etc/clamav';

if (-e $conf && ! -s $conf) {
	unlink(glob("$conf/*"), $conf);
}
symlink($SRCDIR."/".$conf, $conf) unless (-l $conf);

# Create necessary dirs/files if they don't exist
foreach my $dir (
    $SRCDIR."/etc/clamav/",
    $VARDIR."/log/clamav/",
    $VARDIR."/run/clamav/",
    $VARDIR."/spool/clamspam/",
    $VARDIR."/spool/clamav/",
) {
    mkdir($dir) unless (-d $dir);
    chown($uid, $gid, $dir);
}

foreach my $file (
    glob($SRCDIR."/etc/clamav/*"),
    $VARDIR."/log/clamav/freshclam.log",
) {
    touch($file) unless (-e $file);
}

foreach my $file (
    $VARDIR."/log/clamav",
    glob($VARDIR."/log/clamav/*"),
    $VARDIR."/run/clamav",
    glob($VARDIR."/run/clamav/*"),
    $VARDIR."/spool/clamspam",
    glob($VARDIR."/spool/clamspam/*"),
    $VARDIR."/spool/clamav",
    glob($VARDIR."/spool/clamav/*"),
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

# Remove default AppArmor rules and reload with ours
if (-e "/etc/apparmor.d/usr.bin.freshclam") {
    `apparmor_parser -R /etc/apparmor.d/usr.bin.freshclam`;
    unlink("/etc/apparmor.d/usr.bin.freshclam");
}
`apparmor_parser -r ${SRCDIR}/etc/apparmor.d/usr.bin.freshclam` if ( -d '/sys/kernel/security/apparmor' );

# SystemD auth causes timeouts
`sed -iP '/^session.*pam_systemd.so/d' /etc/pam.d/common-session`;

# Dump configuration
dump_file("freshclam.conf");

print STDERR "To enable ClamAV Unofficial Signatures, either run with '--agree-to-unofficial' or add *exactly* the following to $VARDIR/spool/mailcleaner/clamav-unofficial-sigs:
I have read the terms of use at: https://sanesecurity.com/usage/linux-scripts/\n" unless update_unofficial($unofficial);

sub remove_unofficial() {
    my @dest = glob("${VARDIR}/spool/clamav/*");
    foreach my $d (@dest) {
        my $s = $d;
        $s =~ s/clamav/clamav\/unofficial-sigs/;
        if (-l $d && $s eq readlink($d)) {
            unlink($d);
            unlink($s);
        }
    }
    rmdir("${VARDIR}/spool/clamav/unofficial-sigs/");
    return 0;
}

sub update_unofficial($unofficial) {
    return remove_unofficial() if ($unofficial eq '--remove-unofficial');
    if ($unofficial eq '--agree-to-unofficial') {
        print "By running with '--agree-to-unofficial', you are confirming that you have read and agree to the terms at https://sanesecurity.com/usage/linux-scripts/\n";
        if (open(my $fh, '>', "${VARDIR}/spool/mailcleaner/clamav-unofficial-sigs")) {
            print $fh "I have read the terms of use at: https://sanesecurity.com/usage/linux-scripts/";
            close $fh;
        }
    } else {
        return remove_unofficial() unless (-e "${VARDIR}/spool/mailcleaner/clamav-unofficial-sigs");
        use Digest::SHA;
        my $sha = Digest::SHA->new();
        $sha->addfile("${VARDIR}/spool/mailcleaner/clamav-unofficial-sigs");
        return remove_unofficial unless ($sha->hexdigest() eq "69c58585c04b136a3694b9546b77bcc414b52b12");
    }

    # First time install
	if (! -d "$VARDIR/spool/clamav/unofficial-sigs") {
	    mkdir("$VARDIR/spool/clamav/unofficial-sigs");
        `${SRCDIR}/scripts/cron/clamav-unofficial-sigs.sh`;
    }

    # Create links if missing
    foreach my $s (glob("${VARDIR}/spool/clamav/unofficial-sigs/*")) {
        my $d = $s;
        $d =~ s/unofficial-sigs\///;
        symlink($s, $d) unless (-e $d);
    }
    return 1;
}

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
