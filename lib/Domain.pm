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
#   This module will just read the configuration file
#

package          Domain;
require          Exporter;
require          ReadConfig;
require          PrefClient;
require          SystemPref;
require          ConfigTemplate;
use strict;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(create getPref);
our $VERSION    = 1.0;


sub create {
  my $name = shift;
  my %prefs;
#-#  my $pref_daemon = PrefDaemon::create();

  my $this = {
         name => $name,
         prefs => \%prefs,
#-#         prefdaemon => $pref_daemon,
         };
         
  bless $this, "Domain";
  return $this;
}

sub getPref {
  my $this = shift;
  my $pref = shift;
  my $default = shift;
  
  if (!defined($this->{prefs}) || !defined($this->{prefs}{$pref})) {
  	## get domain prefs
 #-# 	my $cachedpref = $this->{prefdaemon}->getPref('PREF', '@'.$this->{name}." ".$pref);
 #-# 	if ($cachedpref !~ /^(BADPREF|NOTFOUND|NOCACHE|TIMEDOUT|NODAEMON)/ ) {
 #-# 	  $this->{prefs}->{$pref} = $cachedpref;
 #-# 	  return $cachedpref;
 #-# 	}
 #-# 	if ($cachedpref =~ /^(NOCACHE|TIMEDOUT|NODAEMON)/) {
 #-# 	  $this->loadPrefs();
 #-# 	}
  	
    my $prefclient = PrefClient->new();
    $prefclient->setTimeout(2);
    my $dpref = $prefclient->getPref($this->{name}, $pref);
    if (defined($dpref) && $dpref !~ /^_/) {
      if ($pref eq 'support_email' && $dpref eq 'NOTFOUND') {
        $dpref = '';
      }
      $this->{prefs}->{$pref} = $dpref;
      return $dpref;
    }
    ## fallback loading
    $this->loadPrefs();
  }

  if (defined($this->{prefs}->{$pref})) {
    return $this->{prefs}->{$pref};
  }
  if (defined($default)) {
    return $default;
  }
  return "";
}

sub loadPrefs {
  my $this = shift;

  my $conf = ReadConfig::getInstance();
  my $preffile = $conf->getOption('VARDIR')."/spool/mailcleaner/prefs/".$this->{name}."/prefs.list";

  my @dlist = ($this->{name}, '*', '_joker', '_global');
  
  ## try to load from db
  require DB;
  my $db = DB::connect('slave', 'mc_config', 0);
  
  my %res;
  if ($db && $db->ping()) {
    for my $d ( @dlist ) {
	  my $query = "SELECT p.* FROM domain d, domain_pref p WHERE d.prefs=p.id AND d.name='".$d."'";
	  %res = $db->getHashRow($query);
      if ( %res && $res{id} ) {
        foreach my $p (keys %res) {
	      $this->{prefs}->{$p} = $res{$p};
	    }
	    return 1;
      }
    }
  }
	
  ## finaly try to find a valid preferences file
  my $found = 0;
  for my $d ( @dlist ) {
    $preffile = 	$conf->getOption('VARDIR')."/spool/mailcleaner/prefs/".$d."/prefs.list";
    if ( -f $preffile) {
      $found = 1;
      last;
    }
  }
  if (!$found) {
    return 0;
  }
  if (! open PREFFILE, $preffile) {
   return 0;
  }
  while (<PREFFILE>) {
    if (/^(\S+)\s+(.*)$/) {
      $this->{prefs}->{$1} = $2;
    
    }
  }
  close PREFFILE;
}

sub dumpPrefs {
  my $this = shift;
  my $slave_db = shift;
  
  require DB;
  
  if (!$slave_db) {
     $slave_db = DB::connect('slave', 'mc_config');
  }
  my $query = "SELECT d.id, p.viruswall, p.spamwall, p.virus_subject, p.content_subject, p.spam_tag,
                       p.language, p.report_template, p.support_email, p.delivery_type, 
                       p.enable_whitelists, p.enable_warnlists, p.enable_blacklists, p.notice_wwlists_hit, p.warnhit_template 
               FROM domain d, domain_pref p WHERE d.prefs=p.id AND d.name='".$this->{name}."'";
               
  my %res = $slave_db->getHashRow($query);

  $this->dumpPrefsFromRow(\%res);
}
 
sub dumpPrefsFromRow {
  my $this = shift;
  my $row = shift;
  my %res = %{$row};

  if (!%res || !defined($res{id})) {
    print "CANNOTFINDPREFS";
  }
  my $conf = ReadConfig::getInstance();
  my $prefdir = $conf->getOption('VARDIR')."/spool/mailcleaner/prefs/".$this->{name};
  my $preffile = $prefdir."/prefs.list";

  my $mcuid = getpwnam('mailcleaner');

  my @prefs_to_dump = ('viruswall', 'report_template', 'virus_subject', 'enable_whitelists', 'language',
          'warnhit_template', 'support_email', 'spamwall', 'enable_warnlists', 'content_subject',
          'notice_wwlists_hit', 'spam_tag', 'delivery_type');
  
  if (! -d $prefdir ) {
    if (! mkdir($prefdir)) {
      print "CANNOTMAKEPREFDIR ($prefdir)";
      return 0;
    }
    my $uid = getpwnam( 'mailcleaner' );
    my $gid = getgrnam( 'mailcleaner' );
    chown $uid, $gid, $prefdir;
  }
  if (! open PREFFILE, ">".$preffile) {
  	print "CANNOTWRITEPREFFILE";
  	return 0;
  }
  foreach my $k (keys %res) {
    if (grep $_ eq $k, @prefs_to_dump) {
  	  if (! defined($res{$k})) {
  	    $res{$k} = "";
  	  }
      print PREFFILE "$k ".$res{$k}."\n";
    }
  }    
  close PREFFILE;
  chown $mcuid, $mcuid, $preffile;

  ## dump ldap callout file
  if ($this->getPref('adcheck') eq 'true') {

    my $syspref = SystemPref::getInstance();
    my $conf = ReadConfig::getInstance();
    my $ldapserver = $syspref->getPref('ad_server');
    my ($ad_basedn, $ad_binddn, $ad_pass) = split(':', $syspref->getPref('ad_param'));

    if ($this->getPref('ldapcallout') ne 'NOTFOUND' && $this->getPref('ldapcallout') != 0) {
     print "specific ldap config\n";
    }

    my $template = ConfigTemplate::create(
                                 "etc/exim/ldapcallout_template", 
                                 $conf->getOption('VARDIR')."/spool/mailcleaner/callout/".$this->getPref('name').".ldapcallout");
  
    my %rep;
    $rep{'__AD_BINDDN__'} = $ad_binddn; 
    $rep{'__AD_PASS__'} = $ad_pass;
    $rep{'__AD_SERVERS__'} = $ldapserver;
    $rep{'__AD_BASEDN__'} = $ad_basedn;

    my $specserver = $this->getPref('ldapcalloutserver');
    my $specparams = $this->getPref('ldapcalloutparam');
    if ($specserver ne '') {
      $rep{'__AD_SERVERS__'} = $specserver;
    } 
    if ($specserver ne '' && $specparams =~ m/^([^:]+):([^:]*):([^:]*)$/) {
      $rep{'__AD_BINDDN__'} = $2;
      $rep{'__AD_PASS__'} = $3;
      $rep{'__AD_SERVERS__'} = $specserver;
      $rep{'__AD_BASEDN__'} = $1;
    } 
    $template->setReplacements(\%rep);
    my $ret = $template->dump();
    
  }
}

sub dumpLocalAddresses {
  my $this = shift;
  my $slave_db = shift;
  
  my $mcuid = getpwnam('mailcleaner');
  require DB;

  my $conf = ReadConfig::getInstance();
  
  if (!$slave_db) {
     $slave_db = DB::connect('slave', 'mc_config');
  }
  my $query = "SELECT e.address
               FROM email e WHERE e.address LIKE '%@".$this->{name}."'";
              
  my $file = $conf->getOption('VARDIR')."/spool/mailcleaner/addresses/".$this->{name}.".addresslist";
  if ( !open(OUTFILE, ">".$file) ) {
      if (-e $file) { ## in case we cannot write to file, try to remove it
          unlink($file);
      }
      return 0;
  }
  my @res = $slave_db->getListOfHash($query);
  foreach my $addrow (@res) {
      if (defined($addrow->{'address'}) && $addrow->{'address'} =~ m/(\S+)\@/) {
        print OUTFILE $1."\n";
      }
  }
  close OUTFILE; 
  chown $mcuid, $file;
}
1;
