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

package          SMTPAuthenticator::LDAP;
require          Exporter;
use strict;
use Net::LDAP;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(create authenticate);
our $VERSION    = 1.0;


my @mailattributes = ('mail', 'maildrop', 'mailAlternateAddress', 'mailalternateaddress', 'proxyaddresses', 'proxyAddresses', 'oldinternetaddress', 'oldInternetAddress', 'cn', 'userPrincipalName');

sub create {
   my $server = shift;
   my $port = shift;
   my $params = shift;

   my $this = {
       error_text => "",
       error_code => -1,
       server => '',
       port => 389,
       use_ssl => 0,
       base => '',
       attribute => 'uid',
       binduser => '',
       bindpassword => '',
       version => 3
   };

   $this->{server} = $server;
   if ($port > 0 ) {
     $this->{port} = $port;
   }
   my @fields = split /:/, $params;
   if ($fields[4] && $fields[4] =~ /^[01]$/) {
     $this->{use_ssl} = $fields[4];
   }
   if ($fields[0]) {
     $this->{base} = $fields[0];
   }
   if ($fields[1]) {
    $this->{attribute} = $fields[1];
   }
   if ($fields[2]) {
    $this->{binduser} = $fields[2];
   }
   if ($fields[3]) {
    $this->{bindpassword} = $fields[3];
    $this->{bindpassword} =~ s/__C__/:/;
   }
   if ($fields[5] && $fields[5] == 2) {
    $this->{version} = 2;
   }

  bless $this, "SMTPAuthenticator::LDAP";
  return $this;
}

sub authenticate {
  my $this = shift;
  my $username = shift;
  my $password = shift;
 
  my $scheme = 'ldap';
  if ($this->{use_ssl} > 0) { 
    $scheme = 'ldaps';
  }
  #print "connecting to $scheme://".$this->{server}.":".$this->{port}."\n";
  my $ldap = Net::LDAP->new ( $this->{server}, port=>$this->{port}, scheme=>$scheme, timeout=>30, debug=>0 );
  
  if (!$ldap) {
    $this->{'error_text'} = "Cannot contact LDAP/AD server at $scheme://".$this->{server}.":".$this->{port}; 
    return 0;
  }

  my $userdn = $this->getDN($username);
  return 0 if ($userdn eq '');

  my $mesg = $ldap->bind ( $userdn,
                           password => $password,
                           version => $this->{version}
                         );

  $this->{'error_code'} = $mesg->code;
  $this->{'error_text'} = $mesg->error_text;
  if ($mesg->code == 0) {
     return 1;
  }
  return 0;
}

sub getDN {
  my $this = shift;
  my $username = shift;

  my $scheme = 'ldap';
  if ($this->{use_ssl} > 0) {
    $scheme = 'ldaps';
  }

  my $ldap = Net::LDAP->new ( $this->{server}, port=>$this->{port}, scheme=>$scheme, timeout=>30, debug=>0 );
  my $mesg;
  if (! $this->{binduser} eq '') {
    $mesg = $ldap->bind($this->{binduser}, password => $this->{bindpassword}, version => $this->{version});
  } else {
    $mesg = $ldap->bind ; 
  }
  if ( $mesg->code ) {
    $this->{'error_text'} = "Could not search for user DN (bind error)";
    return '';
  }
  $mesg = $ldap->search (base => $this->{base}, scope => 'sub', filter => "(".$this->{attribute}."=$username)");
  if ( $mesg->code ) {
    $this->{'error_text'} = "Could not search for user DN (search error)";
    return '';
  }
  my $numfound = $mesg->count ;
  my $dn="" ;
  if ($numfound) {
      my $entry = $mesg->entry(0);
      $dn = $entry->dn ;
  } else {
    $this->{'error_text'} = "No such user ($username)";
  }
  $ldap->unbind;   # take down session
  return $dn ;
}

sub fetchLinkedAddressesFromEmail {
  my $this = shift;
  my $email = shift;

  my $filter = '(|';
  foreach my $att (@mailattributes) {
    $filter .= '('.$att.'='.$email.')('.$att.'='.'smtp:'.$email.')'; 
  }
  $filter .= ')';
  return $this->fetchLinkedAddressFromFilter($filter);
}

sub fetchLinkedAddressesFromUsername {
  my $this = shift;
  my $username = shift;

  my $filter = $this->{attribute}."=".$username;
  return $this->fetchLinkedAddressFromFilter($filter);
}

sub fetchLinkedAddressFromFilter {
  my $this = shift;
  my $filter = shift;

  my @addresses;

  my $scheme = 'ldap';
  if ($this->{use_ssl} > 0) { 
    $scheme = 'ldaps';
  }

  my $ldap = Net::LDAP->new ( $this->{server}, port=>$this->{port}, scheme=>$scheme, timeout=>30, debug=>0 );
  my $mesg;
  if (!$ldap) {
    $mesg = 'Cannot open LDAP session';
    return @addresses;
  }
  if (! $this->{binduser} eq '') {
    $mesg = $ldap->bind($this->{binduser}, password => $this->{bindpassword}, version => $this->{version});
  } else {
    $mesg = $ldap->bind ; 
  }
  if ( $mesg->code ) {
    $this->{'error_text'} = "Could not bind";
    return @addresses;
  }
  $mesg = $ldap->search (base => $this->{base}, scope => 'sub', filter => $filter);
  if ( $mesg->code ) {
    $this->{'error_text'} = "Could not search";
    return @addresses;
  }
  my $numfound = $mesg->count ;
  my $dn="" ;
  if ($numfound) {
      my $entry = $mesg->entry(0);
      foreach my $att (@mailattributes) {
        foreach my $add ($entry->get_value($att)) {
            if ($add =~ m/\@/) {
               $add =~ s/^smtp\://gi;
               push @addresses, lc($add);
            }
        }
      }
  } else {
    #$this->{'error_text'} = "No data for filter ($filter)";
  }
  $ldap->unbind;   # take down session
  return @addresses; 
}

1;
