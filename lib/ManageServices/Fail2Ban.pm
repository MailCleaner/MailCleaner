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

package ManageServices::Fail2Ban;

use strict;
use warnings;

our @ISA = "ManageServices";

sub init 
{
	my $module = shift;
	my $class = shift;
	my $self = $class->SUPER::createModule( config($class) );
	bless $self, 'ManageServices::Fail2Ban';

	return $self;
}

sub config
{
	my $class = shift;

	my $config = {
		'name' 		=> 'fail2ban',
		'cmndline'	=> 'fail2ban-server',
		'cmd'		=> '/usr/bin/fail2ban-server',
		'confdir'	=> $class->{'conf'}->getOption('SRCDIR').'/etc/fail2ban/',
		'localsocket'	=> $class->{'conf'}->getOption('VARDIR').'/run/fail2ban.sock',
		'pidfile'	=> $class->{'conf'}->getOption('VARDIR').'/run/fail2ban.pid',
		'children'	=> 1,
		'user'		=> 'root',
		'group'		=> 'root',
				
#here
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

	# Load Default options and other rcS variables
	foreach my $file ( qw( /etc/default/fail2ban /etc/default/rcS ) ) {
		if ( -r $file ) {
			open(my $fh, '<', $file);
			while (<$fh>) {
				if ($_ =~ m/^([A-Z_])=['"](.*)['"]$/) {
					$ENV{$1} = $2;
				}
			}
		}
	}

	$ENV{'PYENV_ROOT'} = "/var/mailcleaner/.pyenv";
        $ENV{'PATH'} = $ENV{'PYENV_ROOT'} . "/bin:" . $ENV{'PATH'};
	if (system("which pyenv > /dev/null")) {
          	system("pyenv init --path");
	}

	unless ( -d "/var/run/fail2ban" ) {
		mkdir("/var/run/fail2ban");
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
	if (defined($ENV{'FAIL2BAN_OPTS'})) {
		$cmd .= ' ' . $ENV{'FAIL2BAN_OPTS'};
	}
        system("dump_fail2ban_config.py");

	$cmd .= ' ' . $self->{'confdir'};
	system($cmd);
	return 0;
	unless ($self->{'user'} eq 'root') {
		chown($self->{'user'}, $self->{'group'}, ( "/var/run/fail2ban" ));
	}
	
	return 1;
}

1;
