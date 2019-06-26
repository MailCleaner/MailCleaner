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

package          dumpers::UriRBLs;


sub get_specific_config
{
    require DB;
    my $db = DB::connect('slave', 'mc_config');

	my %config = ();
	my %row = $db->getHashRow("SELECT avoidhosts FROM UriRBLs");
        my $hosts = $row{'avoidhosts'};
        if ($hosts) {
          $hosts =~ s/[\s\;]+/,/;
        } else {
          $hosts = '';
        }
	$config{'__AVOIDHOSTS__'} = $hosts;
	
	return %config;
}

1;
