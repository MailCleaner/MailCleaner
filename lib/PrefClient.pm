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

package          PrefClient;
require          Exporter;
require          ReadConfig;
require			  SockClient;

use strict;
our @ISA        = "SockClient";


sub new {
  my $class = shift;
  
  my %msgs = ();
  
  my $spec_this = {
     %msgs => (),
     currentid => 0,
     socketpath => '',
     timeout => 5,
  };
  
  my $conf = ReadConfig::getInstance();
  $spec_this->{socketpath} = $conf->getOption('VARDIR')."/run/prefdaemon.sock";
  
  my $this = $class->SUPER::new($spec_this);
  
  bless $this, $class;
  return $this;
}

sub setTimeout {
   my $this = shift;
   my $timeout = shift;
   
   return 0 if ($timeout !~ m/^\d+$/);
   $this->{timeout} = $timeout;
   return 1;
}

## fetch a pref by calling de pref daemon
sub getPref {
  my $this = shift;
  my $object = shift;
  my $pref = shift;
  
  if ($object !~ m/^[-_.!\$#=*&\@a-z0-9]+$/i) {
    return '_BADOBJECT';
  }
  if ($pref !~ m/^[-_a-z0-9]+$/) {
    return '_BADPREF';
  }
  
  my $query = "PREF $object $pref";
  
  my $result = $this->query($query);
  return $result;
}

## fetch a pref, just like getPref but force pref daemon to fetch domain pref if user pref is not found or not set
sub getRecursivePref {
  my $this = shift;
  my $object = shift;
  my $pref = shift;
  
  if ($object !~ m/^[-_.!\$#=*&\@a-z0-9]+$/i) {
    return '_BADOBJECT';
  }
  if ($pref !~ m/^[-_a-z0-9]+$/) {
    return '_BADPREF';
  }
  
  my $query = "PREF $object $pref R";
  
  my $result = $this->query($query);
  return $result;
}

sub extractSRSAddress {
  my $this = shift;
  my $sender = shift;
  my $sep = '[=+-]';
  my @segments;
  if ($sender =~ m/^srs0.*/i) {
    @segments = split(/$sep/, $sender);
    my $tag = shift(@segments);
    my $hash = shift(@segments);
    my $time = shift(@segments);
    my $domain = shift(@segments);
    my $remove = "$tag$sep$hash$sep$time$sep$domain$sep";
    $remove =~ s/\//\\\//;
    $sender =~ s/^$remove(.*)\@[^\@]*$/$1/;
    $sender .= '@' . $domain;
  } elsif ($sender =~ m/^srs1.*/i) {
    my @blocks = split(/=$sep/, $sender);
    @segments = split(/$sep/, $blocks[0]);
    my $domain = $segments[scalar(@segments)-1];
    @segments = split(/$sep/, $blocks[scalar(@blocks)-1]);
    my $hash = shift(@segments);
    my $time = shift(@segments);
    my $relay = shift(@segments);
    my $remove = "$hash$sep$time$sep$relay$sep";
    $remove =~ s/\//\\\//;
    $sender = $blocks[scalar(@blocks)-1];
    $sender =~ s/^$remove(.*)\@[^\@]*$/$1/;
    $sender .= '@' . $domain;
  }
  return $sender;
}

sub extractVERP {
   my $this = shift;
   my $sender = shift;
   if ($sender =~ /^[^\+]+\+.+=[a-z0-9\-\.]+\.[a-z]+/i) {
    $sender =~ s/([^\+]+)\+.+=[a-z0-9\-]{2,}\.[a-z]{2,}\@([a-z0-9\-]{2,}\.[a-z]{2,})/$1\@$2/i;
   }
   return $sender;
}

sub extractSubAddress {
   my $this = shift;
   my $sender = shift;
   if ($sender =~ /^[^\+]+\+.+=[a-z0-9\-\.]+\.[a-z]+/i) {
    $sender =~ s/([^\+]+)\+.+\@([a-z0-9\-]{2,}\.[a-z]{2,})/$1\@$2/i;
   }
   return $sender;
}

sub extractSender {
   my $this = shift;
   my $sender = shift;
   my $orig = $sender;
   $sender = $this->extractSRSAddress($sender);
   $sender = $this->extractVERP($sender);
   $sender = $this->extractSubAddress($sender);
   if ($orig eq $sender) {
    return 0;
   }
   return $sender;
}

sub isWhitelisted {
   my $this = shift;
   my $object = shift;
   my $sender = shift;
   
   if ($object !~ m/^[-_.!\$+#=*&\@a-z0-9]+$/i) {
    return '_BADOBJECT';
   }
  
   my $query = "WHITE $object $sender";
   my $result;
   if (my $result = $this->query("WHITE $object $sender")) {
    return $result;
   }
   $sender = $this->extractSender($sender);
   if ($sender) {
    if ($result = $this->query("WHITE $object $sender")) {
      return $result;
    }
   } else {
    return 0;
   }
}

sub isWarnlisted {
   my $this = shift;
   my $object = shift;
   my $sender = shift;
   
   if ($object !~ m/^[-_.!\$+#=*&\@a-z0-9]+$/i) {
    return '_BADOBJECT';
   }
  
   my $result;
   if (my $result = $this->query("WARN $object $sender")) {
    return $result;
   }
   $sender = $this->extractSender($sender);
   if ($sender) {
    if ($result = $this->query("WARN $object $sender")) {
      return $result;
    }
   } else {
    return 0;
   }
}

sub isBlacklisted {
   my $this = shift;
   my $object = shift;
   my $sender = shift;

   if ($object !~ m/^[-_.!\$+#=*&\@a-z0-9]+$/i) {
    return '_BADOBJECT';
   }

   my $query = "BLACK $object $sender";
   my $result;
   if (my $result = $this->query("BLACK $object $sender")) {
    return $result;
   }
   $sender = $this->extractSender($sender);
   if ($sender) {
    if ($result = $this->query("BLACK $object $sender")) {
      return $result;
    }
   } else {
    return 0;
   }
}

sub logStats {
   my $this = shift;
   
   my $query = 'STATS';
   return $this->query($query);
}

1;
