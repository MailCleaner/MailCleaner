#!/usr/bin/perl -w
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2021 John Mertz <git@john.me.tz>
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

package ManageServices::Cron;

use strict;
use warnings;

our @ISA = "ManageServices";

sub init 
{
	my $module = shift;
	my $class = shift;
	my $self = $class->SUPER::createModule( config($class) );
	bless $self, 'ManageServices::Cron';

	return $self;
}

sub config
{
	my $class = shift;

	my $config = {
		'name' 		=> 'cron',
		'cmndline'	=> '/usr/sbin/cron',
		'cmd'		=> '/usr/sbin/cron',
		'pidfile'	=> '/var/run/cron.pid',
		'user'		=> 'root',
		'group'		=> 'root',
		'daemonize'	=> 'no',
		'forks'		=> 0,
		'syslog_facility' => 'local1',
		'debug'		=> 0,
		'log_sets'	=> 'all',
		'loglevel'	=> 'info',
		'timeout'	=> 5,
		'actions'	=> {},
	};
	
	return $config;
}

sub setup
{
	my $self = shift;
	my $class = shift;

	return 1;
}

sub preFork
{
	my $self = shift;
	my $class = shift;

	return 0;
}

sub mainLoop
{
	my $self = shift;
	my $class = shift;
	
	my $cmd = $self->{'cmd'};
	$self->doLog("Running $cmd", 'daemon');
	system("$cmd");

	return 1;
}

1;
