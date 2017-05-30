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

package          SMTPCalloutConnector;
require          Exporter;
require          SystemPref;
require          Domain;
use strict;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(create verify lastMessage);
our $VERSION    = 1.0;


sub create {
   my $domainname = shift;
 
   if (!defined($domainname) || $domainname eq "") {
     my $system = SystemPref::create();
     $domainname = $system->getPref('default_domain');
   }
   my $domain = Domain::create($domainname);
   if (!$domain) {
     return 0;
   }
   
   my $useable = 1;
   my $last_message = '';
   
   my $domain_pref =  $domain->getPref('extcallout');
   if ($domain_pref ne 'true') {
   	 $useable = 0;
   	 $last_message = 'not using external callout';
   	 return;
   }
   
   my $this = {
   	  'domain' => $domain,
   	  'last_message' => $last_message,
   	  'useable' => $useable,
   	  'default_on_error' => 1 ## we accept in case of any failure, to avoid false positives
  };
         
  bless $this, "SMTPCalloutConnector";
  return $this;
}

sub verify {
   my $this = shift;
   my $address = shift;
   
   if (! $this->{useable}) {
   	  return $this->{default_on_error};
   }
   if (!defined($address) || $address !~ m/@/) {
   	  $this->{last_message} = 'the address to check is invalid';
      return $this->{default_on_error};
   }
   my $type = $this->{domain}->getPref('extcallout_type');
   if (!defined($type) || $type eq '' || $type eq 'NOTFOUND') {
   	  $this->{last_message} = 'no external callout type defined';
      return $this->{default_on_error};
   }
   my $class = "SMTPCalloutConnector::".ucfirst($type);
   if (! eval "require $class") {
   	  $this->{last_message} = 'define external callout type does not exists';
      return $this->{default_on_error};
   }
   
   my @callout_params = ();
   my $params = $this->{domain}->getPref('extcallout_param');
   foreach my $p (split /:/, $params) {
   	  if ($p eq 'NOTFOUND') {
   	  	next;
   	  }
   	  $p =~ s/__C__/:/;
   	  push @callout_params, $p;
   }
   
   my $connector = new $class(\@callout_params);
   
   if ($connector->isUseable()) {
     my $res = $connector->verify($address);     
     $this->{last_message} = $connector->lastMessage();
     return $res;
   }
   $this->{last_message} = $connector->lastMessage();
   return $this->{default_on_error};
}

sub lastMessage {
   my $this = shift;
 
   my $msg = $this->{last_message};
   chomp($msg);
   return $msg;
}

1;
