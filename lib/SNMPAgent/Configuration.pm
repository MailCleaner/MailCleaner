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

package          SNMPAgent::Configuration;
require          Exporter;

use strict;
use NetSNMP::agent;
use NetSNMP::OID (':all'); 
use NetSNMP::agent (':all'); 
use NetSNMP::ASN (':all');
use lib qw(/usr/rrdtools/lib/perl/);
use ReadConfig;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(init getMIB isMaster);
our $VERSION    = 1.0;

my $mib_root_position = 2;

my %mib_global = (1 => \&isMaster);
my %mib_status = ( 1 => \%mib_global);

my $conf;

sub initAgent() {
   doLog('Agent Configuration initializing', 'status', 'debug');

   $conf = ReadConfig::getInstance();
   
   return $mib_root_position;
}


sub getMIB() {
   return \%mib_status;	
}

sub doLog() {
	my $message = shift;
	my $cat = shift;
	my $level = shift;
	
	SNMPAgent::doLog($message, $cat, $level);
}


##### Handlers
sub isMaster() {
	my $ismaster = 0;
	if ($conf->getOption('ISMASTER') eq 'Y') {
		$ismaster = 1;
	}
	return (ASN_INTEGER, int($ismaster));
}

1;