#!/usr/bin/env perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch> (dump_mailscanner_config.pl)
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
#   This script will dump the MailScanner configuration files from the configuration
#   setting found in the database.
#
#   Usage:
#           dump_newsld_config.pl

use v5.36;
use strict;
use warnings;
use utf8;
use Carp qw( confess );

my ($conf, $SRCDIR, $VARDIR);
BEGIN {
    if ($0 =~ m/(\S*)\/\S+.pl$/) {
        my $path = $1."/../lib";
        unshift (@INC, $path);
    }
    require ReadConfig;
    $conf = ReadConfig::getInstance();
    $SRCDIR = $conf->getOption('SRCDIR') || '/usr/mailcleaner';
    $VARDIR = $conf->getOption('VARDIR') || '/var/mailcleaner';
}

use lib_utils qw(open_as);

require ConfigTemplate;
require DB;
require MCDnsLists;
require GetDNS;

my $db = DB::connect('slave', 'mc_config');

our $DEBUG = 1;
our $uid = getpwnam('mailcleaner');
our $gid = getgrnam('mailcleaner');

# Currently there are no substitutions for the Newsletter module, however some defualts expected.
our %sa_conf = ( 'MODULE' => 'newsl' ) or fatal_error("NOSPAMASSASSINCONFIGURATION", "no default configuration found for spamassassin");

dump_sa_file() or fatal_error("CANNOTDUMPSPAMASSASSINFILE", $!);

# Set proper permissions
mkdir $_ foreach (
    $VARDIR.'/run/spamd',
    $VARDIR.'/spool/newsld',
);
chown($uid, $gid,
    $VARDIR.'/run/spamd',
    $VARDIR.'/spool/newsld',
    glob($VARDIR.'/spool/newsld/*'),
    glob($SRCDIR.'/share/newsld/*'),
    glob($VARDIR.'/log/mailscanner/newsld*'),
);

# Configure sudoer permissions if they are not already
mkdir '/etc/sudoers.d' unless (-d '/etc/sudoers.d');
if (open(my $fh, '>', '/etc/sudoers.d/spamd')) {
    print $fh "
User_Alias  SPAMD = mailcleaner
Cmnd_Alias  BIN = /usr/local/bin/spamd

SPAMD       * = (ROOT) NOPASSWD: BIN
";
}

# SystemD auth causes timeouts
`sed -iP '/^session.*pam_systemd.so/d' /etc/pam.d/common-session`;

# Remove default AppArmor rules and reload with ours
if (-e "/etc/apparmor.d/usr.sbin.spamd") {
    `apparmor_parser -R /etc/apparmor.d/usr.sbin.spamd`;
    unlink("/etc/apparmor.d/usr.sbin.spamd");
}
`apparmor_parser -r ${SRCDIR}/etc/apparmor.d/usr.sbin.spamd` if ( -d '/sys/kernel/security/apparmor' );

#############################
sub dump_sa_file()
{
    my $template = ConfigTemplate::create(
        'etc/mailscanner/newsld.conf_template',
        'etc/mailscanner/newsld.conf'
    );
    $template->setReplacements(\%sa_conf);
    return 0 unless $template->dump();
    return 1;
}

sub getModuleStatus($module)
{
    return ( (defined($sa_conf{$module}) && $sa_conf{$module} < 1) ? "#" : "" );
}

sub fatal_error($msg, $full)
{
    print $msg . ( $DEBUG ? "\n Full information: $full \n" : "\n" );
}

sub expand_host_string($string, %args)
{
    my $dns = GetDNS->new();
    return $dns->dumper($string,%args);
}
