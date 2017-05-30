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

package          module::Resolver;

require          Exporter;
require          DialogFactory;
use strict;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(get ask do);
our $VERSION    = 1.0;

sub get {

 my %dns;

 my $this = {
   domain => '',
   %dns => (),
   dnss => ''
 };

 bless $this, 'module::Resolver';
 return $this;
}

sub setDNS {
  my $this = shift;
  my $pos = shift;
  my $value = shift;

  $this->{dns}{$pos} = $value;
}

sub ask {
  my $this = shift;

  my $dfact = DialogFactory::get('InLine');
  my $dlg = $dfact->getSimpleDialog();
#  $dlg->clear();
  print "\n\nConfiguring resolver\n";
  print "--------------------\n\n";

  #############
  ## get dns
  my %dnsname = (1 => 'primary', 2 => 'secondary', 3 => 'tertiary');
  for (my $n=1; $n<4; $n++) {
    $dlg->build('Please enter a '.$dnsname{$n}.' DNS server', $this->{dns}{$n});
    my $ldns = 'none';
    while( !module::Interface::isIP($ldns)  && (! $ldns eq '' )) {
     print "Bad address format, please type again.\n" if  ! $ldns eq 'none';
     $ldns = $dlg->display();
     last if $ldns eq '';
    }
    $this->{dns}{$n} = $ldns;
    last if $ldns eq ''; 
  } 

  ##############
  ## get domain 
  $dlg->build('Please enter the DNS search domain name', $this->{domain});
  my $dom = ' none ';
  while( !module::Resolver::isDomainName($dom)  && (! $dom eq '' )) {
   $dom = $dlg->display();
  }
  $this->{domain} = $dom;

  #################
  ## set dns string
  my $dnss = "";
  foreach my $dns (sort keys %{$this->{dns}}) {
   $dnss .= " ".$this->{dns}{$dns};
  }
  $dnss =~ s/^ //;
  $this->{dnss} = $dnss;
}

sub do {
  my $this = shift;

  my $dnss = "";
  foreach my $dns (sort keys %{$this->{dns}}) {
   $dnss .= " ".$this->{dns}{$dns};
  }
  $dnss =~ s/^ //;
  $this->{dnss} = $dnss;
  print "got dns: ".$this->{dnss}."\n";
  print "got domain: ".$this->{domain}."\n";
}

sub getConfig {
  my $this = shift;

  my $str = "\n";
  foreach my $dns (sort keys %{$this->{dns}}) {
   next if $this->{dns}{$dns} eq '';
   $str .= "nameserver ".$this->{dns}{$dns}."\n";
  }

  #if (! $this->{dnss} eq '') {
  #  $str .= "nameservers ".$this->{dnss}."\n";
  #}
  if (! $this->{domain} eq '') {
    $str .= "search ".$this->{domain}."\n";
  }

  return $str;
}

sub isDomainName {
  my $domain = shift;

  if ( $domain =~ m/^[-a-zA-Z0-9_.]+$/) {
   return 1;
  }
  return 0;
}

1;
