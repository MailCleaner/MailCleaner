#!/usr/bin/perl -w
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
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


use strict;
if ($0 =~ m/(\S*)\/\S+.pl$/) {
  my $path = $1."/../lib";
  unshift (@INC, $path);
}
require ConfigTemplate;
require DB;
require MCDnsLists;

my $db = DB::connect('slave', 'mc_config');
my $conf = ReadConfig::getInstance();

my $DEBUG = 1;
my $lasterror = "";

my $scanners = get_active_scanners();
my @prefilters = get_prefilters();
my %sys_conf = get_system_config() or fatal_error("NOSYSTEMCONFIGURATIONFOUND", "no record found for system configuration");
my %ms_conf = get_ms_config() or fatal_error("NOMAILSCANNERCONFIGURATIONFOUND", "no record found for default mailscanner configuration");
my %sa_conf = get_sa_config() or fatal_error("NOSPAMASSASSINCONFIGURATION", "no default configuration found for spamassassin");
my @dnslists = get_dnslists() or fatal_error("NODNSLISTINFORMATIONS", "no dnslists information found");

dump_ms_file() or fatal_error("CANNOTDUMPMAILSCANNERFILE", $lasterror);
dump_sa_file() or fatal_error("CANNOTDUMPSPAMASSASSINFILE", $lasterror);
dump_prefilter_files() or fatal_error("CANNOTDUMPPREFILTERS", $lasterror);
dump_virus_file() or fatal_error("CANNOTDUMPVIRUSFILE", $lasterror);
dump_filename_config() or fatal_error("NOFILENAMECONFIGURATIONFOUND", "no record found for filenames");
dump_filetype_config() or fatal_error("NOFILETYPECONFIGURATIONFOUND", "no record found for filetypes");
dump_Oscar_config();
dump_FuzzyOcr_config();
dump_saplugins_conf();
dump_dnsblacklists_conf();

unlink($conf->getOption('SRCDIR')."/share/spamassassin/mailscanner.cf");
symlink($conf->getOption('SRCDIR')."/etc/mailscanner/spam.assassin.prefs.conf", $conf->getOption('SRCDIR')."/share/spamassassin/mailscanner.cf");

print "DUMPSUCCESSFUL";
exit 0;

#############################
sub get_system_config
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
sub get_prefilters
{
  my @pfs;

  my @list = $db->getListOfHash("SELECT * FROM prefilter WHERE active=1 ORDER BY position");
  return @list;
}

#############################
sub get_ms_config
{
  my %row = $db->getHashRow("SELECT scanners, scanner_timeout, silent, file_timeout, expand_tnef, deliver_bad_tnef, tnef_timeout, usetnefcontent,
					max_message_size, max_attach_size, max_archive_depth, send_notices, notices_to, trusted_ips, max_attachments_per_message,
					spamhits, highspamhits, lists  FROM antivirus v, antispam s, PreRBLs pr WHERE v.set_id=1 AND s.set_id=1 AND pr.set_id=1");
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
  if ($row{'trusted_ips'}) { 
    $config{'__TRUSTEDIPS__'} = $row{'trusted_ips'};
  }
  $config{'__TRUSTEDIPS__'} =~ s/\n/ /g;
  $config{'__TRUSTEDIPS__'} =~ s/\s+/ /g;
  $config{'__SPAMHITS__'} = $row{'spamhits'};
  $config{'__HIGHSPAMHITS__'} = $row{'highspamhits'};
  $config{'__SPAMLISTS__'} = $row{'lists'};
  if ($row{'usetnefcontent'} =~ /^(no|add|replace)$/ ) {
    $config{'__USETNEFCONTENT__'} = $row{'usetnefcontent'};
  } else {
    $config{'__USETNEFCONTENT__'} = 'no';
  }
  if ($row{'silent'} eq "yes") { $config{'__SILENT__'} = "All-Viruses"; }

  %row = $db->getHashRow("SELECT block_encrypt, block_unencrypt, allow_passwd_archives, allow_partial, allow_external_bodies,
				allow_iframe, silent_iframe, allow_form, silent_form, allow_script, silent_script,
				allow_webbugs, silent_webbugs, allow_codebase, silent_codebase, notify_sender
				FROM dangerouscontent WHERE set_id=1");
  return unless %row;
  
  foreach my $key (keys %row) {
  	if ($row{$key} eq "") {
  	  $row{$key} = "no";
  	}
  }
  $config{'__BLOCKENCRYPT__'} = $row{'block_encrypt'};
  $config{'__BLOCKUNENCRYPT__'} = $row{'block_unencrypt'};
  $config{'__ALLOWPWDARCHIVES__'} = $row{'allow_passwd_archives'};
  $config{'__ALLOWPARTIAL__'} = $row{'allow_partial'};
  $config{'__ALLOWEXTERNAL__'} = $row{'allow_external_bodies'};
  $config{'__ALLOWIFRAME__'} = $row{'allow_iframe'};
  $config{'__ALLOWFORM__'} = $row{'allow_form'};
  $config{'__ALLOWSCRIPT__'} = $row{'allow_script'};
  $config{'__ALLOWWEBBUGS__'} = $row{'allow_webbugs'};
  $config{'__ALLOWCODEBASE__'} = $row{'allow_codebase'};
  $config{'__NOTIFYSENDER__'} = $row{'notify_sender'};

  if ($row{'silent_iframe'} eq "yes") { $config{'__SILENT__'} .= " HTML-IFrame";}
  if ($row{'silent_form'} eq "yes") { $config{'__SILENT__'} .= " HTML-Form";}
  if ($row{'silent_script'} eq "yes") { $config{'__SILENT__'} .= " HTML-Script";}
  if ($row{'silent_codebase'} eq "yes") { $config{'__SILENT__'} .= " HTML-Codebase";}

  ## get memory size
  my $memsizestr = `cat /proc/meminfo | grep MemTotal`;
  my $memsize = 0;
  if ($memsizestr =~ /MemTotal:\s+(\d+) kB/) {
    $memsize = $1;
  }
  ## and calculate the number of processes that best fit
  $config{'__NBPROCESSES__'} = 5;
  if ($memsize > 0 && $memsize < 520000) { $config{'__NBPROCESSES__'} = 2; }
  if ($memsize > 1000000) { $config{'__NBPROCESSES__'} = 3; }
  if ($memsize > 1500000) { $config{'__NBPROCESSES__'} = 4; }
  if ($memsize > 2000000) { $config{'__NBPROCESSES__'} = 5; }
  if ($memsize > 3000000) { $config{'__NBPROCESSES__'} = 6; }
  if ($memsize > 4000000) { $config{'__NBPROCESSES__'} = 10; }
  if ($memsize > 5000000) { $config{'__NBPROCESSES__'} = 12; }
  if ($memsize > 20000000) { $config{'__NBPROCESSES__'} = 20; }
  $config{'__NBSAPROCESSES__'} = $config{'__NBPROCESSES__'} + 1;
    
  ## generate prefilters option
  my $pfoption = "";
  foreach my $pfh (@prefilters) {
    my %pf = %{$pfh};
    $pfoption .= $pf{'name'}.":".$pf{'pos_decisive'}.":".$pf{'neg_decisive'}." ";
  }
  $config{'__PREFILTERS__'} = $pfoption;
    
  return %config;
}

#############################
sub get_sa_config
{
  my %config;
  
  my %row = $db->getHashRow("SELECT use_bayes, bayes_autolearn, ok_languages, ok_locales, use_rbls, rbls_timeout, use_dcc, 
				dcc_timeout, use_razor, razor_timeout, use_pyzor, pyzor_timeout, trusted_ips, sa_rbls,
				spf_timeout, use_spf, dkim_timeout, use_dkim, dmarc_follow_quarantine_policy, 
				use_fuzzyocr, use_imageinfo, use_pdfinfo, use_botnet FROM antispam WHERE set_id=1");
  return unless %row;
  
  $config{'__USE_BAYES__'} = $row{'use_bayes'};
  $config{'__BAYES_AUTOLEARN__'} = $row{'bayes_autolearn'};
  $config{'__OK_LOCALES__'} = $row{'ok_locales'};
  $config{'__OK_LANGUAGES__'} = $row{'ok_languages'};
  $config{'__SKIP_RBLS__'} = 1;
  $config{'__USE_RBLS__'} = $row{'use_rbls'};
  if ($row{'use_rbls'}) { $config{'__SKIP_RBLS__'} = 0; }
  $config{'__RBLS_TIMEOUT__'} = $row{'rbls_timeout'};
  $config{'__USE_DCC__'} = $row{'use_dcc'};
  $config{'__DCC_TIMEOUT__'} = $row{'dcc_timeout'};
  $config{'__USE_RAZOR__'} = $row{'use_razor'};
  $config{'__RAZOR_TIMEOUT__'} = $row{'razor_timeout'};
  $config{'__USE_PYZOR__'} = $row{'use_pyzor'};
  $config{'__PYZOR_TIMEOUT__'} = $row{'pyzor_timeout'};

  $config{'__TRUSTEDIPS__'} = ""; 
  if ($row{'trusted_ips'}) { 
    $config{'__TRUSTEDIPS__'} = $row{'trusted_ips'};
  }
  $config{'__TRUSTEDIPS__'} =~ s/\n/ /g;
  $config{'__TRUSTEDIPS__'} =~ s/\s+/ /g;
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
  chop($ip);
  $config{'__SYSTEMIP__'} = "";
  if (defined($ip) && ! $ip eq "") {
    foreach my $sip (split(/\s/, $ip)) {
      if ($sip =~ /^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/) {
        $config{'__SYSTEMIP__'} .= " ".$sip;
      }
    }
  }
  return %config;
}

#############################
sub get_active_scanners
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
sub get_dnslists {
  my @list = $db->getListOfHash("SELECT name FROM dnslist WHERE active=1");
  return @list;
}

#############################
sub dump_ms_file
{
  my $template = ConfigTemplate::create(
                          'etc/mailscanner/MailScanner.conf_template', 
                          'etc/mailscanner/MailScanner.conf');  
  $template->setReplacements(\%sys_conf);
  $template->setReplacements(\%ms_conf);
  return $template->dump();
}

#############################
sub dump_sa_file
{
  my $template = ConfigTemplate::create(
                          'etc/mailscanner/spam.assassin.prefs.conf_template', 
                          'etc/mailscanner/spam.assassin.prefs.conf');  
  $template->setReplacements(\%sa_conf);
  $template->setCondition('QUARANTINE_DMARC', 0);
  if ($sa_conf{'__QUARANTINE_DMARC__'}) {
    $template->setCondition('QUARANTINE_DMARC', 1);
  }

  return 0 unless $template->dump();
  
  $template = ConfigTemplate::create(
                          'etc/mailscanner/spamd.conf_template', 
                          'etc/mailscanner/spamd.conf');  
  $template->setReplacements(\%sa_conf);
  $template->setReplacements(\%ms_conf);
  return 0 unless $template->dump();

  $template = ConfigTemplate::create(
                          'etc/mailscanner/newsld.conf_template', 
                          'etc/mailscanner/newsld.conf');  
  return 0 unless $template->dump();
  
  $template = ConfigTemplate::create(
                          'share/spamassassin/92_mc_dnsbl_disabled.cf_template',
                          'share/spamassassin/92_mc_dnsbl_disabled.cf');
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

  return $template->dump();
}

#############################
sub dump_prefilter_files {
  my $basedir=$conf->getOption('SRCDIR')."/etc/mailscanner/prefilters";
	
  return 1 if ( ! -d $basedir) ;
  opendir(QDIR, $basedir) or die "Couldn't read directory $basedir";
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
          $template->setReplacements(\%specreplaces);
        }
        $template->dump();
      #}
    }
  }
  return 1;
}

sub getPrefilterSpecConfig {
 my $prefilter = shift;
 my $replace = shift;

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
sub dump_virus_file
{
  my $template = ConfigTemplate::create(
                          'etc/mailscanner/virus.scanners.conf_template', 
                          'etc/mailscanner/virus.scanners.conf');
  return $template->dump();
}

#############################
sub dump_Oscar_config
{
  my $template = ConfigTemplate::create(
                          'etc/mailscanner/OscarOcr.cf_template', 
                          'share/spamassassin/OscarOcr.cf');  
  return $template->dump();
}

#############################
sub dump_FuzzyOcr_config
{
  my $template = ConfigTemplate::create(
                          'etc/mailscanner/FuzzyOcr.cf_template', 
                          'share/spamassassin/FuzzyOcr.cf');  
  return $template->dump();
}

#############################
sub dump_saplugins_conf
{
  my $template = ConfigTemplate::create(
                          'etc/mailscanner/sa_plugins.pre', 
                          'share/spamassassin/sa_plugins.pre');
  my %replace = (
          '__IF_DCC__' => getModuleStatus('__USE_DCC__'),
          '__IF_PYZOR__' => getModuleStatus('__USE_PYZOR__'),
          '__IF_RAZOR__' => getModuleStatus('__USE_RAZOR__'),
          '__IF_BAYES__' => getModuleStatus('__USE_BAYES__'),
          '__IF_IMAGEINFO__' => getModuleStatus('__USE_IMAGEINFO__'),
          '__IF_DKIM__' => getModuleStatus('__USE_DKIM__'),
          '__IF_URIDNSBL__' => getModuleStatus('__USE_RBLS__'),
          '__IF_SPF__' => getModuleStatus('__USE_SPF__'),
          '__IF_FUZZYOCR__' => getModuleStatus('__USE_FUZZYOCR__'),
          '__IF_OSCAR__' => getModuleStatus('__USE_FUZZYOCR__'),
          '__IF_PDFINFO__' => getModuleStatus('__USE_PDFINFO__'),
          '__IF_BOTNET__' => getModuleStatus('__USE_BOTNET__'),
        );

  $template->setReplacements(\%replace);
  return $template->dump();
}

sub getModuleStatus {
  my $module = shift;
 
  if (defined($sa_conf{$module}) && $sa_conf{$module} < 1) {
  	return "#";
  }
  return "";
}

#############################
sub dump_filename_config
{
  my $template = ConfigTemplate::create(
                          'etc/mailscanner/filename.rules.conf_template', 
                          'etc/mailscanner/filename.rules.conf');  
  
  my $subtmpl = $template->getSubTemplate('FILENAME');
  my $res = "";
  my @list = $db->getListOfHash('SELECT status, rule, name, description FROM filename');
  foreach my $element (@list) {
  	my %el = %{$element};
  	my $sub = $subtmpl;
        if ($el{'name'} eq '') {
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
sub dump_filetype_config
{
   my $template = ConfigTemplate::create(
                          'etc/mailscanner/filetype.rules.conf_template', 
                          'etc/mailscanner/filetype.rules.conf');  
  
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
sub dump_dnsblacklists_conf
{
   my $template = ConfigTemplate::create(
                          'etc/mailscanner/dnsblacklists.conf_template',
                          'etc/mailscanner/dnsblacklists.conf');
   my $subtmpl = $template->getSubTemplate('DNSLIST');
   my $res = "";
   my $dnslists = new MCDnsLists(\&log_dns, 1);
   $dnslists->loadRBLs( $conf->getOption('SRCDIR')."/etc/rbls", '', 'IPRBL', '', '', '', 'dump_dnslists');
   my $rbls = $dnslists->getAllRBLs();
   foreach my $r (keys %{$rbls}) {
       my $sub = $subtmpl;
       $sub =~ s/__NAME__/$r/g;
       $sub =~ s/__URL__/$rbls->{$r}{'dnsname'}/g;
       $res .= "$sub";
   }

 #my @list = $db->getListOfHash("SELECT name, url FROM dnslist WHERE type='blacklist' AND active='1'");
 #foreach my $element (@list) {
 # 	 my %el = %{$element};
 # 	 my $sub = $subtmpl;
 # 	 $sub =~ s/__NAME__/$el{'name'}/g;
 # 	 $sub =~ s/__URL__/$el{'url'}/g;
 # 	 $res .= "$sub";
 # }
  
  my %replace = (
                 '__DNSLIST_LIST__' => $res
                );
  $template->setReplacements(\%replace);
  return $template->dump();
}
 
#############################
sub fatal_error
{
	my $msg = shift;
	my $full = shift;

	print $msg;
	if ($DEBUG) {
		print "\n Full information: $full \n";
	}
	exit(0);
}

sub log_dns
{
  my $str = shift;

  #print $str."\n";
}

