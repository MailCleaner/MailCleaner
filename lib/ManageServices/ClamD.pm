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

package ManageServices::ClamD;

use strict;
use warnings;

our @ISA = "ManageServices";

sub init 
{
	my $module = shift;
	my $class = shift;
	my $self = $class->SUPER::createModule( config($class) );
	bless $self, 'ManageServices::ClamD';

	return $self;
}

sub config
{
	my $class = shift;

	my $config = {
		'name' 		=> 'clamd',
		'cmndline'	=> 'clamav/clamd.conf',
		'cmd'		=> '/opt/clamav/sbin/clamd',
		'conffile'	=> $class->{'conf'}->getOption('SRCDIR').'/etc/clamav/clamd.conf',
		'pidfile'	=> $class->{'conf'}->getOption('VARDIR').'/run/clamav/clamd.pid',
		'logfile'	=> $class->{'conf'}->getOption('VARDIR').'/log/clamav/clamd.log',
		'localsocket'	=> $class->{'conf'}->getOption('VARDIR').'/run/clamav/clamd.sock',
		'children'	=> 1,
		'user'		=> 'clamav',
		'group'		=> 'clamav',
		'daemonize'	=> 'yes',
		'forks'		=> 0,
		'nouserconfig'  => 'yes',
		'syslog_facility' => '',
		'debug'		=> 0,
		'log_sets'	=> 'all',
		'loglevel'	=> 'info',
		'timeout'	=> 5,
		'checktimer'	=> 10,
		'actions'	=> {},
	};
	
	return $config;
}

sub setup
{
	my $self = shift;
	my $class = shift;

	$self->doLog('Dumping ClamD config...', 'daemon');
	my $dumped = 0;
	my $rc = eval
	{
		require IPC::Run;
		1;
	};
	if ($rc) {
		$dumped = 1 if IPC::Run::run([$self->{'SRCDIR'}.'/bin/dump_clamav_config.pl'], "2>&1", ">/dev/null");
	} else {
		$dumped = 1 if system($self->{'SRCDIR'}."/bin/dump_clamav_config.pl 2>&1 >/dev/null");
	}
	$self->doLog('dump_clamav_config.pl failed', 'daemon') unless ($dumped);

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
	$cmd .= ' --config-file=' . $self->{'conffile'};
	$self->doLog("Running $cmd", 'daemon');
	system(split(/ /, $cmd));
	
	return 1;
}

1;
