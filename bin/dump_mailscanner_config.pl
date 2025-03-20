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
#   This script will dump the MailScanner configuration files from the configuration
#   setting found in the database.
#
#   Usage:
#           dump_mailscanner_config.pl

use v5.36;
use strict;
use warnings;
use utf8;
use Carp qw( confess );
use File::Path qw(make_path);

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

my $lasterror = "";

my $scanners = get_active_scanners();
my @prefilters = get_prefilters();
our %sys_conf = get_system_config() or fatal_error("NOSYSTEMCONFIGURATIONFOUND", "no record found for system configuration");
our %ms_conf = get_ms_config() or fatal_error("NOMAILSCANNERCONFIGURATIONFOUND", "no record found for default mailscanner configuration");
my @dnslists = get_dnslists() or fatal_error("NODNSLISTINFORMATIONS", "no dnslists information found");

dump_ms_file() or fatal_error("CANNOTDUMPMAILSCANNERFILE", $lasterror);
dump_prefilter_files() or fatal_error("CANNOTDUMPPREFILTERS", $lasterror);
dump_virus_file() or fatal_error("CANNOTDUMPVIRUSFILE", $lasterror);
dump_filename_config() or fatal_error("NOFILENAMECONFIGURATIONFOUND", "no record found for filenames");
dump_filetype_config() or fatal_error("NOFILETYPECONFIGURATIONFOUND", "no record found for filetypes");
dump_dnsblacklists_conf();
dump_reports_files();

# Create necessary directories
foreach (
    $VARDIR.'/spam',
    $VARDIR.'/spool/mailscanner',
    $VARDIR.'/spool/spamassassin',
    $VARDIR.'/spool/exim_stage2',
    $VARDIR.'/spool/exim_stage2/input',
    $VARDIR.'/spool/exim_stage4',
    $VARDIR.'/spool/exim_stage4/input',
    $VARDIR.'/spool/exim_stage4/spamstore',
    $VARDIR.'/spool/mailscanner',
    $VARDIR.'/spool/mailscanner/quarantine',
    $VARDIR.'/spool/mailscanner/incoming',
    $VARDIR.'/spool/mailscanner/incoming/Locks',
    $VARDIR.'/spool/tmp',
    $VARDIR.'/spool/tmp/mailscanner',
    $VARDIR.'/spool/tmp/mailscanner/spamassassin',
    $VARDIR.'/spool/tmp/mailscanner/incoming',
    $VARDIR.'/spool/tmp/mailscanner/incoming/Locks',
    $VARDIR.'/log/mailscanner',
    $VARDIR.'/run/mailscanner',
    $VARDIR.'/run/mailscanner',
    $VARDIR.'/log/mailscanner',
    '/var/lock/subsys/MailScanner',
) {
    mkdir $_ unless (-d $_);
    chown($uid, $gid, $_);
    chown($uid, $gid, glob($_.'/*'));
    chmod(0750, $_);
}

# Set file permissions
foreach (
    $SRCDIR.'/etc/mailscanner',
    glob($SRCDIR.'/etc/mailscanner/*'),
    $SRCDIR.'/etc/mailscanner/mcp',
    glob($SRCDIR.'/etc/mailscanner/mcp/*'),
    $SRCDIR.'/etc/mailscanner/prefilters',
    glob($SRCDIR.'/etc/mailscanner/prefilters/*'),
    $SRCDIR.'/etc/mailscanner/prefilters/UriRBLs',
    glob($SRCDIR.'/etc/mailscanner/prefilters/UriRBLs/*'),
    $SRCDIR.'/etc/mailscanner/reports_templates',
    glob($SRCDIR.'/etc/mailscanner/reports_templates/*'),
    glob($SRCDIR.'/etc/mailscanner/reports_templates/*/*'),
    $SRCDIR.'/etc/mailscanner/rules',
    glob($SRCDIR.'/etc/mailscanner/rules'),
    $VARDIR.'/log/mailcleaner/SpamLogger.log',
    $VARDIR.'/spool/tmp/mailscanner/incoming/Locks',
) {
    chown($uid, $gid, $_);
    make_path($_, {'mode'=>0755,'user'=>$uid,'group'=>$gid}) unless ( -e $_ );
}

# Reload AppArmor rules
`apparmor_parser -r ${SRCDIR}/etc/apparmor.d/usr.bin.MailScanner` if ( -d '/sys/kernel/security/apparmor' );

# SystemD auth causes timeouts
`sed -iP '/^session.*pam_systemd.so/d' /etc/pam.d/common-session`;

#############################
sub get_system_config()
{
    my %config = ();
    my %row = $db->getHashRow("SELECT hostname, organisation, sysadmin, clientid, default_language, summary_from FROM system_conf");
    $config{'__HOSTNAME__'} = $row{'hostname'};
    $config{'__SYSADMIN__'} = $row{'summary_from'};
    if (defined($row{'sysadmin'}) && $row{'sysadmin'} ne '') {
        $config{'__SYSADMIN__'} = $row{'sysadmin'};
    }
    $config{'__ORGNAME__'} = $row{'organisation'};
    $config{'__LANG__'} = $row{'default_language'};

    return %config;
}

#############################
sub get_prefilters()
{
    my @pfs;

    my @list = $db->getListOfHash("SELECT * FROM prefilter WHERE active=1 ORDER BY position");
    return @list;
}

#############################
sub get_ms_config()
{
    my %row = $db->getHashRow(
        "SELECT scanners, scanner_timeout, silent, file_timeout, expand_tnef, deliver_bad_tnef,
        tnef_timeout, usetnefcontent, max_message_size, max_attach_size, max_archive_depth,
        send_notices, notices_to, trusted_ips, max_attachments_per_message, spamhits, highspamhits,
        lists, global_max_size  FROM antivirus v, antispam s, PreRBLs pr WHERE v.set_id=1 AND
        s.set_id=1 AND pr.set_id=1"
    );
    return unless %row;

    foreach my $key (keys %row) {
        if (!defined($row{$key}) || $row{$key} eq "") {
            #print " changin $key to no\n";
            $row{$key} = "no";
        }
    }

    my %config;
    $config{'__VIRUSSCANNERS__'} = $scanners;
    $config{'__SCANNERTIMEOUT__'} = $row{'scanner_timeout'};
    $config{'__FILETIMEOUT__'} = $row{'file_timeout'};
    $config{'__EXPANDTNEF__'} = $row{'expand_tnef'};
    $config{'__DELIVERBADTNEF__'} = $row{'deliver_bad_tnef'};
    $config{'__TNEFTIMEOUT__'} = $row{'tnef_timeout'};
    $config{'__MAXMSGSIZE__'} = $row{'max_message_size'};
    $config{'__MAXATTACHSIZE__'} = $row{'max_attach_size'};
    $config{'__MAXARCDEPTH__'} = $row{'max_archive_depth'};
    $config{'__MAXATTACHMENTS__'} = $row{'max_attachments_per_message'};
    $config{'__SENDNOTICE__'} = $row{'send_notices'};
    $config{'__NOTICETO__'} = $row{'notices_to'};

    $config{'__TRUSTEDIPS__'} = "";
    if ($row{'trusted_ips'} && $row{'trusted_ips'} ne 'no') {
        $config{'__TRUSTEDIPS__'} = join(",", expand_host_string($row{'trusted_ips'},('dumper'=>'mailscanner/trustedips')));
    }
    $config{'__SPAMHITS__'} = $row{'spamhits'};
    $config{'__GLOBALMAXSIZE__'} = $row{'global_max_size'}.'k';
    $config{'__HIGHSPAMHITS__'} = $row{'highspamhits'};
    $config{'__SPAMLISTS__'} = $row{'lists'};
    if ($row{'usetnefcontent'} =~ /^(no|add|replace)$/ ) {
        $config{'__USETNEFCONTENT__'} = $row{'usetnefcontent'};
    } else {
        $config{'__USETNEFCONTENT__'} = 'no';
    }
    if ($row{'silent'} eq "yes") { $config{'__SILENT__'} = "All-Viruses"; }

    %row = $db->getHashRow(
        "SELECT block_encrypt, block_unencrypt, allow_passwd_archives, allow_partial,
        allow_external_bodies, allow_iframe, silent_iframe, allow_form, silent_form, allow_script,
        silent_script, allow_webbugs, silent_webbugs, allow_codebase, silent_codebase,
        notify_sender, wh_passwd_archives FROM dangerouscontent WHERE set_id=1"
    );
    return unless %row;

    foreach my $key (keys %row) {
        if (defined($row{$key})) {
            if ($row{$key} eq "") {
                $row{$key} = "no";
            }
        }
    }
    $config{'__BLOCKENCRYPT__'} = $row{'block_encrypt'};
    $config{'__BLOCKUNENCRYPT__'} = $row{'block_unencrypt'};
    my $FH;
    if ($row{'allow_passwd_archives'} eq 'yes')   {
        $config{'__ALLOWPWDARCHIVES__'} = 'yes';
        unlink "${VARDIR}/spool/tmp/mailscanner/whitelist_password_archives" if ( -e "${VARDIR}/spool/tmp/mailscanner/whitelist_password_archives");
    } else {
        $config{'__ALLOWPWDARCHIVES__'} = "${VARDIR}/spool/tmp/mailscanner/whitelist_password_archives";
        unless ($FH = ${open_as($config{'__ALLOWPWDARCHIVES__'})}) {
            confess "Cannot open $config{'__ALLOWPWDARCHIVES__'}: $!\n";
        }
        if (defined($row{wh_passwd_archives})) {
            my @wh_dom = split('\n', $row{wh_passwd_archives});
            foreach my $wh_dom (@wh_dom) {
                next if ( ! ($wh_dom =~ /\./) );
                print $FH "FromOrTo:\t$wh_dom\tyes\n";
            }
        }
        print $FH "FromOrTo:\tdefault\tno";
        close $FH;
    }

    $config{'__ALLOWPARTIAL__'} = $row{'allow_partial'};
    $config{'__ALLOWEXTERNAL__'} = $row{'allow_external_bodies'};
    $config{'__ALLOWIFRAME__'} = $row{'allow_iframe'};
    $config{'__ALLOWFORM__'} = $row{'allow_form'};
    $config{'__ALLOWSCRIPT__'} = $row{'allow_script'};
    $config{'__ALLOWWEBBUGS__'} = $row{'allow_webbugs'};
    $config{'__ALLOWCODEBASE__'} = $row{'allow_codebase'};
    $config{'__NOTIFYSENDER__'} = $row{'notify_sender'};

    $config{'__SILENT__'} .= " HTML-IFrame" if ($row{'silent_iframe'} eq "yes");
    $config{'__SILENT__'} .= " HTML-Form" if ($row{'silent_form'} eq "yes");
    $config{'__SILENT__'} .= " HTML-Script" if ($row{'silent_script'} eq "yes");
    $config{'__SILENT__'} .= " HTML-Codebase" if ($row{'silent_codebase'} eq "yes");

    ## get memory size
    my $memsizestr = `cat /proc/meminfo | grep MemTotal`;
    my $memsize = 0;
    if ($memsizestr =~ /MemTotal:\s+(\d+) kB/) {
        $memsize = $1;
    }
    ## and calculate the number of processes that best fit
    $config{'__NBPROCESSES__'} = 2 + int($memsize/1000000);
    $config{'__NBPROCESSES__'} = 20 if ($config{'__NBPROCESSES__'} > 20);

    ## generate prefilters option
    my $pfoption = "";
    foreach my $pfh (@prefilters) {
        my %pf = %{$pfh};
        $pfoption .= $pf{'name'}.":".$pf{'pos_decisive'}.":".$pf{'neg_decisive'}." ";
    }
    $config{'__PREFILTERS__'} = $pfoption;
    $config{'__EXIM_COMMAND__'} = 'Exim Command = /opt/exim4/bin/exim';

    return %config;
}

#############################
sub get_active_scanners()
{
    my @list = $db->getListOfHash("SELECT name from scanner WHERE active=1");
    return "none" unless @list;
    return "none" if @list < 1;

    my $ret = "";
    foreach my $elh (@list) {
        my %el = %{$elh};
        $ret .= $el{'name'}." ";
    }
    return $ret;
}

#############################
sub get_dnslists()
{
    my @list = $db->getListOfHash("SELECT name FROM dnslist WHERE active=1");
    return @list;
}

#############################
sub dump_ms_file()
{
    my $template = ConfigTemplate::create(
        'etc/mailscanner/MailScanner.conf_template',
        'etc/mailscanner/MailScanner.conf'
    );
    $template->setReplacements(\%sys_conf);
    $template->setReplacements(\%ms_conf);
    return $template->dump();
}

#############################
sub dump_prefilter_files()
{
    my $basedir=$conf->getOption('SRCDIR')."/etc/mailscanner/prefilters";

    return 1 if ( ! -d $basedir) ;
    opendir(QDIR, $basedir) or confess "Couldn't read directory $basedir";
    while(my $entry = readdir(QDIR)) {
        if ($entry =~ /(\S+)(\.cf)_template$/) {
            my $templatefile = $basedir."/".$entry;
            my $destfile = $basedir."/".$1.$2;
            my $pfname = $1;

            my $prefilter;
            foreach my $pf (@prefilters) {
                if ($pf->{'name'} eq $pfname) {
                    $prefilter = $pf;
                }
            }
            #if (defined($prefilter->{'name'})) {
            my $template = ConfigTemplate::create($templatefile, $destfile);

            my %replace = ();
            $replace{'__HEADER__'} = $prefilter->{'header'} || 'X-'.$pfname;
            $replace{'__PREFILTERNAME__'} = $prefilter->{'name'} || $pfname;
            $replace{'__TIMEOUT__'} = $prefilter->{'timeOut'} || '0';
            $replace{'__MAXSIZE__'} = $prefilter->{'maxSize'} || '0';
            $replace{'__PUTSPAMHEADER__'} = $prefilter->{'putSpamHeader'} || '0';
            $replace{'__PUTHAMHEADER__'} = $prefilter->{'putHamHeader'} || '0';
            $replace{'__POSITION__'} = $prefilter->{'position'} || '0';
            $replace{'__DECISIVE_FIELD__'} = $prefilter->{'decisive_field'} || 'none';
            $replace{'__POS_DECISIVE__'} = $prefilter->{'pos_decisive'} || '0';
            $replace{'__NEG_DECISIVE__'} = $prefilter->{'neg_decisive'} || '0';
            $template->setReplacements(\%replace);

            my %spec_replace = ();
            if (getPrefilterSpecConfig($prefilter->{'name'}, \%spec_replace)) {
                $template->setReplacements(\%spec_replace);
            }

            my $specmodule = "dumpers::$pfname";
            my $specmodfile = "dumpers/$pfname.pm";
            if ( -f $conf->getOption('SRCDIR')."/lib/$specmodfile") {
                require $specmodfile;
                my %specreplaces = $specmodule->get_specific_config();
                if (defined($specreplaces{'__AVOIDHOSTS__'})) {
                    $specreplaces{'__AVOIDHOSTS__'} = join(" ",expand_host_string($specreplaces{'__AVOIDHOSTS__'},('dumper'=>'mailscanner/avoidhosts')));
                }
                $template->setReplacements(\%specreplaces);
            }
            $template->dump();
            #}
        }
    }
    return 1;
}

sub getPrefilterSpecConfig($prefilter,$replace)
{
    return 0 if ! $prefilter;
    if (! -f $conf->getOption('SRCDIR')."/install/dbs/t_cf_$prefilter.sql") {
        return 0;
    }
    my %row = $db->getHashRow("SELECT * FROM $prefilter");
    return 0 if ! %row;

    foreach my $key (keys %row) {
        $replace->{'__'.uc($key).'__'} = $row{$key};
    }

    return 1;
}

#############################
sub dump_virus_file()
{
    my $template = ConfigTemplate::create(
        'etc/mailscanner/virus.scanners.conf_template',
        'etc/mailscanner/virus.scanners.conf'
    );
    return $template->dump();
}

#############################
sub dump_filename_config()
{
    my $template = ConfigTemplate::create(
        'etc/mailscanner/filename.rules.conf_template',
        'etc/mailscanner/filename.rules.conf'
    );

    my $subtmpl = $template->getSubTemplate('FILENAME');
    my $res = "";
    my @list = $db->getListOfHash('SELECT status, rule, name, description FROM filename where status="deny"');
    foreach my $element (@list) {
        my %el = %{$element};
        my $sub = $subtmpl;
        if ( ( ! defined( $el{'name'} ) ) || $el{'name'} eq '') {
            $el{'name'} = '+';
        }
        if ($el{'description'} eq '') {
            $el{'description'} = '-';
        }
        $sub =~ s/__STATUS__/$el{'status'}/g;
        $sub =~ s/__RULE__/$el{'rule'}/g;
        $sub =~ s/__NAME__/$el{'name'}/g;
        $sub =~ s/__DESCRIPTION__/$el{'description'}/g;
        $res .= "$sub";
    }

    my %replace = (
        '__FILENAME_LIST__' => $res
    );
    $template->setReplacements(\%replace);
    return $template->dump();
}

#############################
sub dump_filetype_config()
{
    my $template = ConfigTemplate::create(
        'etc/mailscanner/filetype.rules.conf_template',
        'etc/mailscanner/filetype.rules.conf'
    );

    my $subtmpl = $template->getSubTemplate('FILETYPE');
    my $res = "";
    my @list = $db->getListOfHash('SELECT status, type, name, description FROM filetype');
    foreach my $element (@list) {
        my %el = %{$element};
        my $sub = $subtmpl;
        $sub =~ s/__STATUS__/$el{'status'}/g;
        $sub =~ s/__TYPE__/$el{'type'}/g;
        $sub =~ s/__NAME__/$el{'name'}/g;
        $sub =~ s/__DESCRIPTION__/$el{'description'}/g;
        $res .= "$sub";
    }

    my %replace = (
        '__FILETYPE_LIST__' => $res
    );
    $template->setReplacements(\%replace);
    return $template->dump();
}

#############################
sub dump_dnsblacklists_conf()
{
    my $template = ConfigTemplate::create(
        'etc/mailscanner/dnsblacklists.conf_template',
        'etc/mailscanner/dnsblacklists.conf'
    );
    my $subtmpl = $template->getSubTemplate('DNSLIST');
    my $res = "";
    my $dnslists = MCDnsLists->new(\&log_dns, 1);
    $dnslists->loadRBLs( $conf->getOption('SRCDIR')."/etc/rbls", '', 'IPRBL', '', '', '', 'dump_dnslists');
    my $rbls = $dnslists->getAllRBLs();
    foreach my $r (keys %{$rbls}) {
        my $sub = $subtmpl;
        $sub =~ s/__NAME__/$r/g;
        $sub =~ s/__URL__/$rbls->{$r}{'dnsname'}/g;
        $res .= "$sub";
    }

    my %replace = (
        '__DNSLIST_LIST__' => $res
    );
    $template->setReplacements(\%replace);
    return $template->dump();
}

sub dump_reports_files($lang=0) {
    # This is a placeholder function. Currently there are no variables in the reports files, so
    # they just need to be copies templates
    my @langs;
    if ($lang) {
        @langs = ( $lang );
    } else {
        @langs = glob($SRCDIR.'/etc/mailscanner/reports_templates/*');
    }
    foreach my $lang (@langs) {
        my $dst = $lang;
        $dst =~ s/_templates//;
        mkdir($dst) unless (-e $dst);
        foreach my $src (glob($lang.'/*')) {
            my $file = $src;
            $file =~ s#.*/([^/]*)#$1#;
            symlink($src, "${dst}/${file}") unless (-e "${dst}/${file}");
        }
    }
}

#############################
sub fatal_error($msg, $full)
{
    confess $msg . ( $DEBUG ? "\n Full information: $full \n" : "\n" );
}

sub log_dns($str)
{
  #print $str."\n";
}

sub expand_host_string($string, %args)
{
    my $dns = GetDNS->new();
    return $dns->dumper($string,%args);
}
