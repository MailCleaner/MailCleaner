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
#   This module will dump a configuration file based on template
#

package          ConfigTemplate;
require          Exporter;
require          ReadConfig;

use strict;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(create dumpFile);
our $VERSION    = 1.0;

###
# create the dumper
# @param  $template    string  base template file
# @param  $targetfile  string  target config file
# @return              this
###
sub create {
  my $templatefile = shift;
  my $targetfile = shift;
  
  return if !$templatefile || $templatefile eq "";
  return if !$targetfile || $targetfile eq "";
  my $conf = ReadConfig::getInstance();
  my $SRCDIR=$conf->getOption('SRCDIR');
  my $VARDIR=$conf->getOption('VARDIR');
  
  $templatefile =~ s/__SRCDIR__/$SRCDIR/g;
  $templatefile =~ s/__VARDIR__/$VARDIR/g;
  if ($templatefile =~ m/^[^\/]/) {
  	$templatefile = $conf->getOption('SRCDIR')."/".$templatefile;
  }
  $targetfile =~ s/__SRCDIR__/$SRCDIR/g;
  $targetfile =~ s/__VARDIR__/$VARDIR/g;
  if ($targetfile =~ m/^[^\/]/) {
  	$targetfile = $conf->getOption('SRCDIR')."/".$targetfile;
  }
  
  my %replacements = ();
  my %subtemplates = ();
  my %conditions = ();
  
  my $this = {
         templatefile => $templatefile,
         targetfile => $targetfile,
         %replacements => (),
         %subtemplates => (),
         %conditions => ()
         };
  
  #print "Will use template: ".$this->{templatefile}."\n";    
  bless $this, "ConfigTemplate";
  
  $this->preParseTemplate();
  return $this;
}

###
# preparse template and variables
# @return        boolean   true on success, false on failure
###
sub preParseTemplate {
  my $this = shift;	

  my $in_template = "";
  return 0 if (!open(FILE, $this->{templatefile}));
  while (<FILE>) {
  	my $line = $_;
  	
  	if ($line =~ /\_\_TMPL\_([A-Z0-9]+)\_START\_\_/) {
      $in_template = $1;
      $this->{subtemplates}{$in_template} = "";
      next;
  	}
  	if ($line =~ /\_\_TMPL\_([A-Z0-9]+)\_STOP\_\_/) {
      $in_template = "";
      next;
  	}
  	if ($in_template !~ /^$/) {
  	  $this->{subtemplates}{$in_template} .= $line;
  	  next;
  	}
  }  
  close FILE;
  return 1;
}

sub getSubTemplate {
  my $this = shift;
  my $tmplname = shift;
  
  if (defined($this->{subtemplates}{$tmplname})) {
  	return $this->{subtemplates}{$tmplname};
  }
  return "";
}

###
# set the tag replacement values
# @param  replace   array_h  handle of array of rplacements with tag as keys
# @return           boolean  true on success, false on failure
###
sub setReplacements {
  my $this = shift;
  my $replace_h = shift;
  my %replace = %{$replace_h};
  
  foreach my $tag (keys %replace) {
  	$this->{replacements}{$tag} = $replace{$tag};
  }
  return 1;
}


###
# dump to destination file
###
sub dump {
  my $this = shift;
  
  return 0 if (!open(FILE, $this->{templatefile}));
  return 0 if (!open(TARGET, ">".$this->{targetfile}));
  
  #print "Will dump to target: ".$this->{targetfile}."\n";
  my $ret;
  my $in_hidden = 0;
  my $if_hidden = 0;
  my $ev_hidden = 0;
  while (<FILE>) {
  	my $line = $_;
  	
  	if ($line =~ /__IF__\s+(\S+)/) {
  	  if (!$this->getCondition($1)) {
  	  	$if_hidden = 1;
  	  }
  	  next;
  	}
  	if ($line =~ /__ELSE__\s+(\S+)/) {
  	  if ($this->getCondition($1)) {
  	  	$if_hidden = 1;
  	  } else {
  	  	$if_hidden = 0;
  	  }
  	  next;
  	}
  	if ($line =~/__FI__/) {
  	  $if_hidden = 0;
  	  next;
  	}

  	if ($line =~  /__EVAL__\s+(.*)$/) {
	  if (! eval "$1") {
            $ev_hidden = 1;
	  } else {
	    $ev_hidden = 0;
	  }
          next;
  	}
  	if ($line =~/__LAVE__/) {
  	  $ev_hidden = 0;
  	  next;
  	}        
  	
  	if ($line =~  /\_\_TMPL\_([A-Z0-9]+)\_START\_\_/) {
      $in_hidden = 1;
      next;
  	}
  	if ($line =~ /\_\_TMPL\_([A-Z0-9]+)\_STOP\_\_/) {
      $in_hidden = 0;
      next;
  	}
  	
  	if (!$in_hidden && !$if_hidden && !$ev_hidden) {
          $ret .= $line;
  	}
  }
  close FILE;
  
  ## do the replacements
  
  ## replace well known tags
  my $conf = ReadConfig::getInstance();
  
  my %wellknown = (
    '__SRCDIR__' => $conf->getOption('SRCDIR'),
    '__VARDIR__' => $conf->getOption('VARDIR'),
  );
	
  ## replace given tags
  foreach my $tag (keys %{$this->{replacements}}) {
     if (!defined($this->{replacements}{$tag})) {
       $this->{replacements}{$tag} = "";
     }
     $ret =~ s/$tag/$this->{replacements}{$tag}/g;
  }
  
  foreach my $tag ( keys %wellknown ) {
    $ret =~ s/$tag/$wellknown{$tag}/g;
  }
  
  print TARGET $ret;
  close TARGET;
  my $uid = getpwnam( 'mailcleaner' );
  my $gid = getgrnam( 'mailcleaner' );
  chown $uid, $gid, $this->{targetfile};
  return 1;
}

sub setCondition {
  my $this = shift;
  my $condition = shift;
  my $value = shift;
  
  $this->{conditions}{$condition} = $value;
  return 1;
}

sub getCondition {
  my $this = shift;
  my $condition = shift;
  
  if (defined($this->{conditions}{$condition})) {
  	return $this->{conditions}{$condition};
  }
  return 0;
}

1;
