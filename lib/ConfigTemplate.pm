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

my $conf = ReadConfig::getInstance();
my $SRCDIR=$conf->getOption('SRCDIR');
my $VARDIR=$conf->getOption('VARDIR');

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

  my $ret;
  my $in_hidden = 0;
  my $ev_hidden = 0;
  my @if_hist = ();
  my $if_hidden = 0;
  my $lc = 0;
  while (<FILE>) {
    my $line = $_;
    $lc++;

    if ($line =~ /__IF__\s+(\S+)/) {
      if ($this->getCondition($1)) {
        push @if_hist, $1;
        #$if_hidden = 1;
      #} elsif ( scalar @if_hist != 0 ) {
        #$if_hidden = $if_hist[scalar(@if_hist)-1];
      } else {
        push @if_hist, "!".$1;
        $if_hidden++;
      }
      #push @if_hist, $if_hidden;
      next;
    }

    # __IF__ condition True, do nothing until __FI__
    if ($line =~ /__ELSE__\s+(\S+)/) {
      unless (scalar(@if_hist)) {
        die "__ELSE__ $1 without preceeding __IF__ (".$this->{templatefile}.":$lc)\n";
      }
      if ($if_hist[scalar(@if_hist)-1] eq $1) {
        $if_hist[scalar(@if_hist)-1] = '!' . $if_hist[scalar(@if_hist)-1];
        $if_hidden++;
      } elsif ($if_hist[scalar(@if_hist)-1] eq "!".$1) {
        $if_hist[scalar(@if_hist)-1] =~ s/^!//;
        $if_hidden--;
      } else {
        die "__ELSE__ tag $1 without preceeding __IF__ (".$this->{templatefile}.":$lc)\n";
      }
      next;
    }

    if ($line =~/__FI__/) {
      unless (scalar(@if_hist)) {
        die "__FI__ without preceeding __IF__ (".$this->{templatefile}.":$lc)\n";
      }
      if ($if_hist[scalar(@if_hist)-1] =~ /^!/) {
        $if_hidden--;
      }
      pop @if_hist;
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
    # Includes a file in the exim configuration
    # First looks for a equivalent customised file
    if ($line =~/__INCLUDE__ *(.*)/) {
      next if ($if_hidden );
      my $inc_file = $1;
      my $path_file;
      $inc_file =~ s/_template$//;
      # Version using .include_if_exists
      if ( -f "$SRCDIR/etc/exim/custom/$inc_file" ) {
        $path_file = "$SRCDIR/etc/exim/custom/$inc_file";
        #$ret .= ".include_if_exists __SRCDIR__/etc/exim/custom/$inc_file\n";
      } elsif ( -f "$SRCDIR/etc/exim/$inc_file" ) {
        $path_file = "$SRCDIR/etc/exim/$inc_file";
        #$ret .= ".include_if_exists __SRCDIR__/etc/exim/$inc_file\n";
      }

      open(PATHFILE, '<', $path_file);
      my @contains = <PATHFILE>;
      close(PATHFILE);
      chomp(@contains);
      foreach (@contains) {
        $ret .= "$_\n";
      }

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
    if ( defined ($ret) ) {
      $ret =~ s/$tag/$this->{replacements}{$tag}/g;
    }
  }

  foreach my $tag ( keys %wellknown ) {
    if ( defined ($ret) ) {
      $ret =~ s/$tag/$wellknown{$tag}/g;
    }
  }

  if ( defined ($ret) ) {
    print TARGET $ret;
  }
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
