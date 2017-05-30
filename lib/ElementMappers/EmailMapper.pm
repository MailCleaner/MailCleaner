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

package          ElementMappers::EmailMapper;
require          Exporter;
use strict;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(create setNewDefault processElement);
our $VERSION    = 1.0;


sub create {
 
  my $this = {
     my %prefs => (),
     my %field_email => (),
  };
           
  bless $this, "ElementMappers::EmailMapper";
  $this->{prefs}{'address'} = '';
  $this->{field_email} = {'address' => 1, 'user' => 1, 'is_main' => 1};
  
  return $this;
}

sub setNewDefault {
  my $this = shift;
  my $defstr = shift;
 
  foreach my $data (split('\s', $defstr)) {
    if ($data =~ m/(\S+):(\S+)/) {
      #print "setting: $1 => $2\n";
      $this->{prefs}{$1} = $2;
    }
  }
}

sub checkElementExistence {
  my $this = shift;
  my $address = shift;
  
  my $check_query = "SELECT address, pref FROM email WHERE address='$address'";
  my %check_res = $this->{db}->getHashRow($check_query);
  if (defined($check_res{'prefs'})) {
    return $check_res{'prefs'};
  }
  return 0;
}

sub processElement {
  my $this = shift;
  my $address = shift;
  my $flags = shift;
  my $params = shift;
  
  my $update = 1;
  if ($flags && $flags =~ m/noupdate/ ) {
   $update = 0;
  } 
  $this->{prefs}{'address'} = lc($address);
 
  my $pref = 0;
  $pref = $this->checkElementExistence($this->{prefs}{'address'});
  if ($pref > 0 && $update) {
    if (! $update ) { return 1; }
    return $this->updateElement($this->{prefs}{'address'}, $pref);
  }
  return $this->addNewElement($this->{prefs}{'address'});
}

sub updateElement {
  my $this = shift;
  my $address = shift;
  my $pref = shift;
  
  my $set_prefquery = $this->getPrefQuery();
  if (! $set_prefquery eq '') {
   my $prefquery = "UPDATE user_pref SET ".$set_prefquery." WHERE id=".$pref;
   $this->{db}->execute($prefquery);
   print $prefquery."\n";
  }
  
  my $set_emailquery = $this->getEmailQuery();
  if (! $set_emailquery eq '') {
    my $email_query = "UPDATE email SET ".$set_emailquery." WHERE address='$address'";
    $this->{db}->execute($email_query);
    print $email_query."\n";
  }
}

sub getPrefQuery() {
  my $this = shift;
  
  my $set_prefquery = '';
  foreach my $datak (keys %{$this->{prefs}}) {
    if (! defined($this->{field_email}{$datak})) {
     $set_prefquery .= "$datak='".$this->{prefs}{$datak}."', ";
    }
  }
  $set_prefquery =~ s/, $//;
  return $set_prefquery;
}

sub getEmailQuery() {
  my $this = shift;
  
  my $set_emailquery = '';
  foreach my $datak (keys %{$this->{prefs}}) {
    if (defined($this->{field_email}{$datak})) {
     $set_emailquery .= "$datak='".$this->{prefs}{$datak}."', ";
    }
  }
  $set_emailquery =~ s/, $//;
  return $set_emailquery;
}

sub addNewElement {
 my $this = shift;
 my $address = shift;

 my $set_prefquery = $this->getPrefQuery();
 my $prefquery = "INSERT INTO user_pref SET id=NULL";
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
 
 my $set_emailquery = $this->getEmailQuery();
 my $query  = "INSERT INTO email SET pref=".$prefid;
 if (! $set_emailquery eq '') {
   $query .= ", ".$set_emailquery;
 }
 $this->{db}->execute($query);
 print $query."\n";
}

sub deleteElement {
  my $this = shift;
  my $name = shift;

}

sub getExistingElements {
  my $this = shift;

  my $query = "SELECT address FROM email";
  my @res = $this->{db}->getList($query);

  return @res;
}


1;
