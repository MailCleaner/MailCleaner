#!/usr/bin/env perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
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
#   This script creates or fixes various file/directory paths, contents,
#   permissions, and links required for mailcleaner.

use v5.36;
use strict;
use warnings;
use utf8;
use Carp qw( confess );

our ($SRCDIR);
BEGIN {
    if ($0 =~ m/(\S*)\/\S+.pl$/) {
        my $path = $1."/../lib";
        unshift (@INC, $path);
    }
    require ReadConfig;
    my $conf = ReadConfig::getInstance();
    $SRCDIR = $conf->getOption('SRCDIR');
    unshift(@INC, $SRCDIR."/lib");
}

use File::Touch qw( touch );

# Enable sudoers.d in main sudoers file
my $enabled = 0;
if (open(my $fh, '<', '/etc/sudoers')) {
    while (<$fh>) {
        if ($_ =~ m#^\@includedir\s+/etc/sudoers.d#) {
            $enabled = 1;
            last;
        }
    }
    close($fh);
}
unless ($enabled) {
    if (open(my $fh, '>>', '/etc/sudoers')) {
        print $fh "\@includedir /etc/sudoers.d\n";
        close($fh);
    }
}
mkdir '/etc/sudoers.d' unless (-d '/etc/sudoers.d');

if (open(my $fh, '>', '/etc/sudoers.d/mailcleaner')) {
    print $fh "
root    ALL=(ALL) ALL
User_Alias      MAILCLEANER = mailcleaner
Runas_Alias     ROOT = root
Cmnd_Alias      NTPSTARTER = /etc/init.d/ntp-server
Cmnd_Alias      NTPCSTARTER = /etc/init.d/ntp
Cmnd_Alias      NTPDATESTARTER = /etc/init.d/ntpdate
Cmnd_Alias      NTPDATE = /usr/sbin/ntpdate
Cmnd_Alias      DATE = /bin/date
Cmnd_Alias      IFDOWN = /sbin/ifdown
Cmnd_Alias      IFUP = /sbin/ifup
Cmnd_Alias      PASSWD = $SRCDIR/bin/setpassword
Cmnd_Alias      UPDATE = $SRCDIR/scripts/cron/mailcleaner_cron.pl
Cmnd_Alias      SSHSTARTER = /etc/init.d/ssh
Cmnd_Alias      CHGHOSTID = $SRCDIR/bin/change_hostid.sh
Cmnd_Alias      DISIF = $SRCDIR/bin/dis_config_interface.sh

Defaults        mailto = root

MAILCLEANER     * = (ROOT) NOPASSWD: NTPSTARTER
MAILCLEANER     * = (ROOT) NOPASSWD: NTPCSTARTER
MAILCLEANER     * = (ROOT) NOPASSWD: NTPDATESTARTER
MAILCLEANER     * = (ROOT) NOPASSWD: NTPDATE
MAILCLEANER     * = (ROOT) NOPASSWD: DATE
MAILCLEANER     * = (ROOT) NOPASSWD: IFDOWN
MAILCLEANER     * = (ROOT) NOPASSWD: IFUP
MAILCLEANER     * = (ROOT) NOPASSWD: PASSWD
MAILCLEANER     * = (ROOT) NOPASSWD: UPDATE
MAILCLEANER     * = (ROOT) NOPASSWD: STOPSTART
MAILCLEANER     * = (ROOT) NOPASSWD: SSHSTARTER
MAILCLEANER     * = (ROOT) NOPASSWD: CHGHOSTID
MAILCLEANER     * = (ROOT) NOPASSWD: DISIF
";
}

touch('/etc/ntp.conf') unless (! -e '/etc/ntp.conf');
my @files = (
    '/etc/ntp.conf',
    '/etc/network/interfaces',
    '/etc/network/run/ifstate',
    '/etc/resolv.conf',
);
my $gid = getgrnam('mailcleaner');
chown(0, $gid, @files);
chmod(0664, @files);

# Disable LDAP certificate unless defined
my $ldap_cert = '';
if (open(my $fh, '<', '/etc/ldap/ldap.conf')) {
    while (<$fh>) {
        $ldap_cert = $1 if ($_ =~ m#TLS_REQCERT\s+(.*)#);
    }
}
unless ($ldap_cert) {
    if (open(my $fh, '>>', '/etc/ldap/ldap.conf')) {
        print $fh "TLS_REQCERT never\n";
    }
}
