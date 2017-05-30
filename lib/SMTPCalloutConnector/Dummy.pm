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

package          SMTPCalloutConnector::Dummy;
require          Exporter;
use strict;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(create authenticate);
our $VERSION    = 1.0;


sub new {
   my $class = shift;
   my $paramsh = shift;
   my @params = @{$paramsh};
   
   my $this = {
   	    'last_message' => '',
   	    'useable' => 1,
        'default_on_error' => 1 ## we accept in case of any failure, to avoid false positives
         };
         
  bless $this, $class;
  return $this;
}

sub verify {
	my $this = shift;
	my $address = shift;

    $this->{last_message} = 'Dummy callout will always answer yes';
    return 1;
}

sub isUseable {
	my $this = shift;
	
	return $this->{useable};
}

sub lastMessage {
	my $this = shift;
	
	return $this->{last_message};
}

1;