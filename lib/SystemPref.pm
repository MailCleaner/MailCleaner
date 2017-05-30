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

package          SystemPref;
require          Exporter;
require          ReadConfig;
require			  DB;
use strict;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(getInstance getPref);
our $VERSION    = 1.0;

my $oneTrueSelf;

## singleton stuff
sub getInstance {
  if (! $oneTrueSelf) {
  	$oneTrueSelf = create();
  }
  return $oneTrueSelf;
}

sub create {
  my $name = shift;
  my %prefs;
  
  my $conf = ReadConfig::getInstance();
  my $preffile = $conf->getOption('VARDIR')."/spool/mailcleaner/prefs/_global/prefs.list";
  my $prefdir = $conf->getOption('VARDIR')."/spool/mailcleaner/prefs/_global/";
  my $this = {
         name => $name,
         prefdir => $prefdir,
         preffile => $preffile,
         prefs => \%prefs
         };
         
  bless $this, "SystemPref";
  return $this;
}

sub getPref {
  my $this = shift;
  my $pref = shift;
  my $default = shift;
  
  if (!defined($this->{prefs}) || !defined($this->{prefs}->{id})) {	
 #-#   my $pref_daemon = PrefDaemon::create();
 #-#   ## get system prefs
 #-# 	my $cachedpref = $pref_daemon->getPref('PREF', "global ".$pref);
 #-# 	if ($cachedpref !~ /^(BADPREF|NOTFOUND|NOCACHE|TIMEDOUT|NODAEMON)/ ) {
 #-# 	  $this->{prefs}->{$pref} = $cachedpref;
 #-# 	  return $cachedpref;
 #-# 	}
 #-# 	if ($cachedpref =~ /^(NOCACHE|TIMEDOUT|NODAEMON)/) {
 #-# 	  $this->loadPrefs();
 #-# 	}
  	
    my $prefclient = PrefClient->new();
    $prefclient->setTimeout(2);
    my $dpref = $prefclient->getPref('_global', $pref);
    if (defined($dpref) && $dpref !~ /^_/) {
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

 if ( ! -f $this->{preffile}) {
    return 0;
  }
  
  if (! open PREFFILE, $this->{preffile}) {
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
  
  my $slave_db = DB::connect('slave', 'mc_config');
  my %prefs = $slave_db->getHashRow("SELECT * FROM antispam");
  my %conf = $slave_db->getHashRow("SELECT use_ssl, servername FROM httpd_config");
  my %sysconf = $slave_db->getHashRow("SELECT summary_from, analyse_to FROM system_conf");
   
  if (! -d $this->{prefdir} && ! mkdir($this->{prefdir})) {
   	 print "CANNOTCREATESYSTEMPREFDIR\n";
     return 0;
  }
  my $uid = getpwnam( 'mailcleaner' );
  my $gid = getgrnam( 'mailcleaner' );
  chown $uid, $gid, $this->{prefdir};
   
  if ( ! open PREFFILE, ">".$this->{preffile}) {
    print "CANNOTWRITESYSTEMPREF\n";
    return 0;
  }
  foreach my $p (keys %prefs) {
    if (!defined($prefs{$p})) {
      $prefs{$p} = '';
    }
    print PREFFILE "$p ".$prefs{$p}."\n";
  }
  foreach my $p (keys %conf) {
    print PREFFILE "$p ".$conf{$p}."\n";
  }
  foreach my $p (keys %sysconf) {
    print PREFFILE "$p ".$sysconf{$p}."\n";
  }
  close PREFFILE;
  chown $uid, $gid, $this->{preffile};
  return 1;
}
1;
