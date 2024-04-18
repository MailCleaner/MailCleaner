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

package ManageServices::Fail2BanServer;

use strict;
use warnings;

our @ISA = "ManageServices";

sub init 
{
	my $module = shift;
	my $class = shift;
	my $self = $class->SUPER::createModule( config($class) );
	bless $self, 'ManageServices::Fail2BanServer';

	return $self;
}

sub config
{
	my $class = shift;

	my $config = {
		'name' 		=> 'fail2ban-server',
		'cmndline'	=> 'fail2ban-server',
		'cmd'		=> '/usr/bin/fail2ban-server',
		'pidfile'	=> '/var/run/fail2ban/fail2ban.pid',
		'socket'	=> '/var/run/fail2ban/fail2ban.sock',
		'logfile'	=> $class->{'conf'}->getOption('VARDIR').'/log/fail2ban/mc-fail2ban.log',
		'user'		=> 'root',
		'group'		=> 'root',
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

	if (!-e '/var/run/fail2ban') {
		mkdir('var/run/fail2ban')
			|| die("Could not create /var/run/fail2ban");
	}

	$self->doLog('Dumping Fail2Ban config...', 'daemon');
	$ENV{'PYENV_VERSION'} = '3.7.7';
	if (system($self->{'VARDIR'}.'/.pyenv/shims/dump_fail2ban_config.py')) {
		$self->doLog('dump_fail2ban_config.py failed', 'daemon');
	}
	
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
	

	$self->doLog("Running $self->{'cmd'} -b -s $self->{'socket'} -p $self->{'pidfile'}", 'daemon');
	system($self->{'cmd'}, "-b", "-s", $self->{'socket'}, "-p", $self->{'pidfile'});
	
	return 1;
}

1;
