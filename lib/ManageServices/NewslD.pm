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

package ManageServices::NewslD;

use strict;
use warnings;

our @ISA = "ManageServices";

sub init 
{
	my $module = shift;
	my $class = shift;
	my $self = $class->SUPER::createModule( config($class) );
	bless $self, 'ManageServices::NewslD';

	return $self;
}

sub config
{
	my $class = shift;

	my $config = {
		'name' 		=> 'newsld',
		'cmndline'	=> 'newsld.pid',
		'cmd'		=> '/usr/local/bin/newsld',
		'conffile'	=> $class->{'conf'}->getOption('SRCDIR').'/etc/mailscanner/newsld.conf',
		'pidfile'	=> $class->{'conf'}->getOption('VARDIR').'/run/newsld.pid',
		'logfile'	=> $class->{'conf'}->getOption('VARDIR').'/log/mailscanner/newsld.log',
		'socket'	=> $class->{'conf'}->getOption('VARDIR').'/run/newsld.sock',
		'children'	=> 11,
		'siteconfig'	=> $class->{'conf'}->getOption('SRCDIR').'/share/newsld/siteconfig',
		'user'		=> 'mailcleaner',
		'group'		=> 'mailcleaner',
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

	$self->doLog('Dumping MailScanner config...', 'daemon');
 	if (system($self->{'SRCDIR'}.'/bin/dump_mailscanner_config.pl 2>&1 >/dev/null')) {
 		$self->doLog('dump_mailscanner_config.pl failed', 'daemon');
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
	
	my $cmd = $self->{'cmd'};
	open(my $CONF, '<', $self->{'conffile'}) 
		|| die "Cannot open config file $self->{'conffile'}";
	while (my $line = <$CONF>) {
		if ($line =~ m/^#/) {
			next;
		} elsif ($line =~ m/^ *$/) {
			next;
		} elsif ($line =~ m/([^=]*) *= *(.*)/) {
			my ($op, $val) = ($1, $2);
			
			if ($op eq $val || $val eq "yes") {
				$cmd .= ' --' . $op;
			} elsif ($val ne "no") {
				$cmd .= ' --' . $op . '=' . $val;
			}
		} else {
			$self->doLog("Invalid configuration line: $line", 'daemon');
		}
	}
	close($CONF);

system("echo  '$cmd' > /tmp/newsld.log");
	$self->doLog("Running $cmd", 'daemon');
	system($cmd);
	
	return 1;
}

1;
