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

package          ElementMappers::DomainMapper;
require          Exporter;
use strict;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(create setNewDefault processElement);
our $VERSION    = 1.0;


sub create {
 
  my @field_domain_o = ('name', 'destination', 'callout', 'altcallout', 'adcheck', 'forward_by_mx', 'greylist');
  
  my $this = {
     my %prefs => (),
     my %field_domain => (),
     'name' => '',
     'destination' => '',
     my @params => ()
  };
           
  bless $this, "ElementMappers::DomainMapper";
  $this->{prefs}{'name'} = '';
  $this->{prefs}{'destination'} = '';
  $this->{field_domain} = {'name' => 1, 'destination' => 1, 'callout' => 1, 'altcallout' => 1, 'adcheck' => 1, 'forward_by_mx' => 1, 'greylist' => 1};
  
  return $this;
}

sub setNewDefault {
  my $this = shift;
  my $defstr = shift;
 
  foreach my $data (split('\s', $defstr)) {
   #print "STRING: $data\n";
    if ($data =~ m/(\S+):(\S+)/) {
      #print "setting: $1 => $2\n";
      my $val = $2;
      my $key = $1;
      $val =~ s/__S__/ /g;
      $val =~ s/__P__/:/g;
      $this->{prefs}{$key} = $val;
      #print "added: $key => $val\n";
   }
  }
}

sub checkElementExistence {
  my $this = shift;
  my $name = shift;
  
  my $check_query = "SELECT name, prefs FROM domain WHERE name='$name'";
  my %check_res = $this->{db}->getHashRow($check_query);
  if (defined($check_res{'prefs'})) {
    return $check_res{'prefs'};
  }
  return 0;
}

sub processElement {
  my $this = shift;
  my $name = shift;
  my $flags = shift;
  my $params = shift;
  
  my $update = 1;
  if ($flags && $flags =~ m/noupdate/ ) {
   $update = 0;
  }

  $this->{params} = (); 
  $this->{prefs}{'name'} = $name;
  if ($params) {
    foreach my $el (split(':', $params) ) {
      chomp($el);
      $el =~ s/^\s+//;
#print "\nSetting param: $el from $params\n";
      push @{$this->{params}}, $el;
    }
  }
 
  my $pref = 0;
  $pref = $this->checkElementExistence($name);
  if ($pref > 0) {
    if (! $update ) { return 1; }
    return $this->updateElement($name, $pref);
  }
  return $this->addNewElement($name);
}

sub updateElement {
  my $this = shift;
  my $name = shift;
  my $pref = shift;
  
  my $set_prefquery = $this->getPrefQuery();
  if (! $set_prefquery eq '') {
   my $prefquery = "UPDATE domain_pref SET ".$set_prefquery." WHERE id=".$pref;
   $this->{db}->execute($prefquery);
   print $prefquery."\n";
  }
  
  my $set_domquery = $this->getDomQuery();
  if (! $set_domquery eq '') {
    my $dom_query = "UPDATE domain SET ".$set_domquery." WHERE name='$name'";
    $this->{db}->execute($dom_query);
    print $dom_query."\n";
  }
}

sub getPrefQuery() {
  my $this = shift;
  
  my $set_prefquery = '';
  foreach my $datak (keys %{$this->{prefs}}) {
    if (! defined($this->{field_domain}{$datak})) {
      my $val = $this->{prefs}{$datak};
      $val =~ s/PARAM(\d+)/$this->{params}[$1-1]/g;
     $set_prefquery .= "$datak='".$val."', ";
    }
  }
  $set_prefquery =~ s/, $//;
  return $set_prefquery;
}

sub getDomQuery() {
  my $this = shift;
  
  my $set_domquery = '';
  foreach my $datak (keys %{$this->{prefs}}) {
    if (defined($this->{field_domain}{$datak})) {
     my $val = $this->{prefs}{$datak};
     $val =~ s/PARAM(\d+)/$this->{params}[$1-1]/g;
     $set_domquery .= "$datak='".$val."', ";
    }
  }
  $set_domquery =~ s/, $//;
  return $set_domquery;
}

sub addNewElement {
 my $this = shift;
 my $name = shift;

 my $set_prefquery = $this->getPrefQuery();
 my $prefquery = "INSERT INTO domain_pref SET id=NULL";
 if (! $set_prefquery eq '') {
   $prefquery .= " , ".$set_prefquery;
 }
 print $prefquery."\n";
 $this->{db}->execute($prefquery.";");

 my $getid = "SELECT LAST_INSERT_ID() as id;";
 my %res = $this->{db}->getHashRow($getid);
 if (!defined($res{'id'})) {
   print "WARNING ! could not get last inserted id!\n";
   return;
 }
 my $prefid = $res{'id'};
 
 my $set_domquery = $this->getDomQuery();
 my $query  = "INSERT INTO domain SET prefs=".$prefid;
 if (! $set_domquery eq '') {
   $query .= ", ".$set_domquery;
 }
 $this->{db}->execute($query);
 print $query."\n";
}

sub deleteElement {
  my $this = shift;
  my $name = shift;

  my $getprefid = "SELECT prefs FROM domain WHERE name='$name'";
  my %res = $this->{db}->getHashRow($getprefid);
  if (!defined($res{'prefs'})) {
    print "WARNING ! could not get preferences id for: $name!\n";
    return;
  }
  my $prefid = $res{'prefs'};

  my $deletepref = "DELETE FROM domain_pref WHERE id=$prefid";
  $this->{db}->execute($deletepref);
  print $deletepref."\n";
  my $deletedomain = "DELETE FROM domain WHERE name='$name'";
  $this->{db}->execute($deletedomain);
  print $deletedomain."\n";
  return;
}

sub getExistingElements {
  my $this = shift;

  my $query = "SELECT name FROM domain";
  my @res = $this->{db}->getList($query); 
  
  return @res;
}

1;
