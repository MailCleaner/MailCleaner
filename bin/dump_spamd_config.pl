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
#           dump_spamd_config.pl

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

our %sa_conf = get_sa_config() or fatal_error("NOSPAMASSASSINCONFIGURATION", "no default configuration found for spamassassin");
my @dnslists = get_dnslists() or fatal_error("NODNSLISTINFORMATIONS", "no dnslists information found");

dump_sa_file() or fatal_error("CANNOTDUMPSPAMASSASSINFILE", $!);
dump_saplugins_conf();

unlink("${SRCDIR}/share/spamd/mailscanner.cf");
symlink("${SRCDIR}/etc/mailscanner/spam.assassin.prefs.conf", "${SRCDIR}/share/spamd/mailscanner.cf");

# Set proper permissions
mkdir $_ foreach (
    $VARDIR.'/run/spamd',
    $VARDIR.'/spool/spamd',
    $VARDIR.'/spool/dcc/',
    $VARDIR.'/run/dcc/',
);
chown($uid, $gid,
    $SRCDIR.'/etc/mailscanner/spam.assassin.prefs.conf',
    $SRCDIR.'/share/spamassassin',
    glob($SRCDIR.'/share/spamassassin/*'),
    $SRCDIR.'/share/spamd/mailscanner.cf',
    $VARDIR.'/run/spamd',
    $VARDIR.'/spool/spamd',
    glob($VARDIR.'/spool/spamd/*'),
    $SRCDIR.'/share/spamd',
    glob($SRCDIR.'/share/spamd/*'),
    $SRCDIR.'/share/spamd/plugins',
    glob($SRCDIR.'/share/spamd/plugins/*'),
    $SRCDIR.'/share/spamd/plugins/iXhash',
    glob($SRCDIR.'/share/spamd/plugins/iXhash/*'),
    glob($VARDIR.'/log/mailscanner/newsld*'),
);

my $dccuid = getpwnam('dcc');
my $dccgid = getgrnam('dcc');
chown($dccuid, $dccgid,
    '/usr/lib/dcc',
    glob('/usr/lib/dcc/*'),
    '/var/lib/dcc',
    glob('/var/lib/dcc/*'),
    $VARDIR.'/spool/dcc/',
    glob($VARDIR.'/spool/dcc/*'),
    $VARDIR.'/run/dcc/',
    glob($VARDIR.'/run/dcc/*'),
);

# Configure sudoer permissions if they are not already
mkdir '/etc/sudoers.d' unless (-d '/etc/sudoers.d');
if (open(my $fh, '>', '/etc/sudoers.d/spamd')) {
    print $fh "
User_Alias  SPAMD = mailcleaner
Cmnd_Alias  SPAMDBIN = /usr/sbin/spamd

SPAMD       * = (ROOT) NOPASSWD: SPAMDBIN
";
}

# Remove default AppArmor rules and reload with ours
if (-e "/etc/apparmor.d/usr.sbin.spamd") {
    `apparmor_parser -R /etc/apparmor.d/usr.sbin.spamd`;
    unlink("/etc/apparmor.d/usr.sbin.spamd");
}
`apparmor_parser -r ${SRCDIR}/etc/apparmor.d/usr.sbin.spamd` if ( -d '/sys/kernel/security/apparmor' );

# SystemD auth causes timeouts
`sed -iP '/^session.*pam_systemd.so/d' /etc/pam.d/common-session`;

#############################
sub get_sa_config()
{
    my %config;

    my %row = $db->getHashRow(
        "SELECT use_bayes, bayes_autolearn, use_rbls, rbls_timeout,
        use_dcc, dcc_timeout, use_razor, razor_timeout, use_pyzor, pyzor_timeout, trusted_ips,
        sa_rbls, spf_timeout, use_spf, dkim_timeout, use_dkim, dmarc_follow_quarantine_policy,
        use_fuzzyocr, use_imageinfo, use_pdfinfo, use_botnet FROM antispam WHERE set_id=1"
    );
    return unless %row;

    $config{'__USE_BAYES__'} = $row{'use_bayes'};
    $config{'__BAYES_AUTOLEARN__'} = $row{'bayes_autolearn'};
    $config{'__OK_LOCALES__'} = $row{'ok_locales'};
    $config{'__OK_LANGUAGES__'} = $row{'ok_languages'};
    $config{'__SKIP_RBLS__'} = 1;
    $config{'__USE_RBLS__'} = $row{'use_rbls'};
    $config{'__SKIP_RBLS__'} = 0 if ($row{'use_rbls'});
    $config{'__RBLS_TIMEOUT__'} = $row{'rbls_timeout'};
    $config{'__USE_DCC__'} = $row{'use_dcc'};
    $config{'__DCC_TIMEOUT__'} = $row{'dcc_timeout'};
    $config{'__USE_RAZOR__'} = $row{'use_razor'};
    $config{'__RAZOR_TIMEOUT__'} = $row{'razor_timeout'};
    $config{'__USE_PYZOR__'} = $row{'use_pyzor'};
    $config{'__PYZOR_TIMEOUT__'} = $row{'pyzor_timeout'};
    $config{'__TRUSTEDIPS__'} = "";
    if ($row{'trusted_ips'} && $row{'trusted_ips'} ne 'no') {
        $config{'__TRUSTEDIPS__'} = join(" ", expand_host_string($row{'trusted_ips'},('dumper'=>'mailscanner/trustedips')));
    }
    $config{'__SA_RBLS__'} = $row{'sa_rbls'};
    $config{'__USE_SPF__'} = $row{'use_spf'};
    $config{'__SPF_TIMEOUT__'} = $row{'spf_timeout'};
    $config{'__USE_DKIM__'} = $row{'use_dkim'};
    $config{'__DKIM_TIMEOUT__'} = $row{'dkim_timeout'};
    $config{'__USE_FUZZYOCR__'} = $row{'use_fuzzyocr'};
    $config{'__USE_IMAGEINFO__'} = $row{'use_imageinfo'};
    $config{'__USE_PDFINFO__'} = $row{'use_pdfinfo'};
    $config{'__USE_BOTNET__'} = $row{'use_botnet'};
    $config{'__QUARANTINE_DMARC__'} = $row{'dmarc_follow_quarantine_policy'};
    my $cmd = 'ifconfig  | grep \'inet addr:\'| grep -v \'127.0.0.1\' | cut -d: -f2 | awk \'{ print $1}\'';
    my $ip = `$cmd`;
    chomp($ip);
    $config{'__SYSTEMIP__'} = "";
    if (defined($ip) && ! $ip eq "") {
        foreach my $sip (split(/\s/, $ip)) {
            if ($sip =~ /^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/) {
                $config{'__SYSTEMIP__'} .= " ".$sip;
            }
        }
    }
    ## get memory size
    my $memsizestr = `cat /proc/meminfo | grep MemTotal`;
    my $memsize = 0;
    if ($memsizestr =~ /MemTotal:\s+(\d+) kB/) {
        $memsize = $1;
    }
    ## and calculate the number of processes that best fit
    $config{'__NBPROCESSES__'} = 2 + int($memsize/1000000);
    $config{'__NBPROCESSES__'} = 20 if ($config{'__NBPROCESSES__'} > 20);

    return %config;
}

#############################
sub dump_sa_file()
{
    my $template = ConfigTemplate::create(
        'etc/mailscanner/spam.assassin.prefs.conf_template',
        'etc/mailscanner/spam.assassin.prefs.conf'
    );
    $template->setReplacements(\%sa_conf);
    $template->setCondition('QUARANTINE_DMARC', 0);
    if ($sa_conf{'__QUARANTINE_DMARC__'}) {
        $template->setCondition('QUARANTINE_DMARC', 1);
    }

    return 0 unless $template->dump();

    $template = ConfigTemplate::create(
        'etc/mailscanner/spamd.conf_template',
        'etc/mailscanner/spamd.conf'
    );
    $template->setReplacements(\%sa_conf);
    return 0 unless $template->dump();

    $template = ConfigTemplate::create(
        'etc/mailscanner/newsld.conf_template',
        'etc/mailscanner/newsld.conf'
    );
    $template->setReplacements(\%sa_conf);
    return 0 unless $template->dump();

    $template = ConfigTemplate::create(
        'share/spamd/92_mc_dnsbl_disabled.cf_template',
        'share/spamd/92_mc_dnsbl_disabled.cf'
    );
    my @givenlist = split ' ', $sa_conf{'__SA_RBLS__'};
    if (!$sa_conf{'__SKIP_RBLS__'}) {
        foreach my $list (@givenlist) {
            $template->setCondition($list, 1);
        }
    }
    foreach my $list (@dnslists) {
        my %l = %{$list};
        my $lname = $l{'name'};
        if ($sa_conf{'__SA_RBLS__'} =~ /\b$lname\b/ && !$sa_conf{'__SKIP_RBLS__'}) {
            $template->setCondition($lname, 1);
        }
    }
    return 0 unless $template->dump();


    $template = ConfigTemplate::create(
        'share/spamd/70_mc_spf_scores.cf_template',
        'share/spamd/70_mc_spf_scores.cf'
    );
    $template->setCondition('__USE_SPF__', $sa_conf{'__USE_SPF__'});
    return 0 unless $template->dump();

    $template = ConfigTemplate::create(
        'share/spamd/70_mc_dkim_scores.cf_template',
        'share/spamd/70_mc_dkim_scores.cf'
    );
    $template->setCondition('__USE_DKIM__', $sa_conf{'__USE_DKIM__'});
    return 0 unless $template->dump();

    return 1;
}

#############################
sub dump_saplugins_conf()
{
    my $template = ConfigTemplate::create(
        'etc/mailscanner/sa_plugins.pre',
        'share/spamd/sa_plugins.pre'
    );
    my %replace = (
        '__IF_DCC__' => getModuleStatus('__USE_DCC__'),
        '__IF_PYZOR__' => getModuleStatus('__USE_PYZOR__'),
        '__IF_RAZOR__' => getModuleStatus('__USE_RAZOR__'),
        '__IF_BAYES__' => getModuleStatus('__USE_BAYES__'),
        '__IF_IMAGEINFO__' => getModuleStatus('__USE_IMAGEINFO__'),
        '__IF_DKIM__' => getModuleStatus('__USE_DKIM__'),
        '__IF_URIDNSBL__' => getModuleStatus('__USE_RBLS__'),
        '__IF_SPF__' => getModuleStatus('__USE_SPF__'),
        '__IF_PDFINFO__' => getModuleStatus('__USE_PDFINFO__'),
        '__IF_BOTNET__' => getModuleStatus('__USE_BOTNET__'),
    );

    $template->setReplacements(\%replace);
    return $template->dump();
}

sub get_dnslists()
{
    my @list = $db->getListOfHash("SELECT name FROM dnslist WHERE active=1");
    return @list;
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
