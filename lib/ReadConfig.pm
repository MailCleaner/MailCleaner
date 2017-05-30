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
#   This module will just read the configuration file
#

package          ReadConfig;
require          Exporter;
use strict;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(getInstance new getOption);
our $VERSION    = 1.0;

my $CONFIGFILE = "/etc/mailcleaner.conf";
my %config_options;

my $oneTrueSelf;

## singleton stuff
sub getInstance {
  if (! $oneTrueSelf) {
  	$oneTrueSelf = new();
  }
  return $oneTrueSelf;
}
  
## constructor
sub new {
  %config_options = readConfig();
  #print "loading config...\n";
  my $this = {
         configs => \%config_options
         };
         
  return bless $this, "ReadConfig";
}

sub getOption {
  my $this = shift;
  my $o = shift;
  
  if (exists($this->{configs}->{$o})) {
     return $this->{configs}->{$o};
  }
  return "";
}

#############################
sub readConfig
{
	my $configfile = $CONFIGFILE;

	my %config;
        my ($var, $value);

	open CONFIG, $configfile or die "Cannot open $configfile: $!\n";
        while (<CONFIG>) {
                chomp;                  # no newline
                s/#.*$//;                # no comments
                s/^\*.*$//;             # no comments
                s/;.*$//;                # no comments
                s/^\s+//;               # no leading white
                s/\s+$//;               # no trailing white
                next unless length;     # anything left?
                my ($var, $value) = split(/\s*=\s*/, $_, 2);
                ## untainting
                if ($value =~ m/(.*)/) {
                  $config{$var} = $1;
                }
                $config{$var} = $value;
        }
        close CONFIG;
	return %config;
}

1;
