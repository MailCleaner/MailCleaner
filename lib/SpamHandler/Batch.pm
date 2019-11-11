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

package          SpamHandler::Batch;
require Exporter;
require SpamHandler::Message;
use Time::HiRes qw(gettimeofday tv_interval);

use strict;

our @ISA     = qw(Exporter);
our @EXPORT  = qw(new);
our $VERSION = 1.0;

sub new {
	my $dir    = shift;
	my $daemon = shift;
	my %timers;

	my $this = {
		spamdir => $dir,
		daemon  => $daemon,
		batchid => 0,
		%timers => (),
	};
	$this->{messages} = {};

	bless $this, 'SpamHandler::Batch';

	if ( !$this->{spamdir} || !-d $this->{spamdir} ) {
		return 0;
	}

	return $this;
}

sub prepareRun {
	my $this = shift;

	#generate a random id
	srand;
	$this->{batchid} = int( rand(1000000) );
	return 1;
}

sub getMessagesToProcess {
	my $this = shift;

	$this->{daemon}->profile_start('BATCHLOAD');
	chdir( $this->{spamdir} ) or return 0;
	opendir( SDIR, '.' ) or return 0;

	my $waitingcount = 0;
	my $batchsize    = 0;
	my $maxbatchsize = $this->{daemon}->{maxbatchsize};
	while ( my $entry = readdir(SDIR) ) {
		next if ( $entry !~ m/(\S+)\.env$/ );
		$waitingcount++;
		my $id = $1;

		next if ( $batchsize >= $maxbatchsize );
		next if ( $this->{daemon}->isLocked($id) );
		if ( -f $id . ".msg" ) {
			$batchsize++;
			$this->addMessage($id);
		}
		else {
			$this->{daemon}->doLog(
				$this->{batchid}
				  . ": NOTICE: message $id has envelope file but no body...",
				'spamhandler'
			);
		}
	}

	if ( $waitingcount > 0 ) {
		$this->{daemon}->doLog(
			$this->{batchid} . ": "
			  . $waitingcount
			  . " messages waiting, taken "
			  . keys %{ $this->{messages} },
			'spamhandler', 'debug'
		);
	}
	else {
		$this->{daemon}->doLog(
			$this->{batchid} . ": "
			  . $waitingcount
			  . " messages waiting, taken "
			  . keys %{ $this->{messages} },
			'spamhandler', 'debug'
		);
	}
	closedir(SDIR);
	my $btime = $this->{daemon}->profile_stop('BATCHLOAD');
	$this->{daemon}
	  ->doLog( $this->{batchid} . ": finished batch loading in $btime seconds",
		'spamhandler', 'debug' );
	return 1;
}

sub addMessage {
	my $this = shift;
	my $id   = shift;

	$this->{daemon}->addLock($id);
	$this->{messages}{$id} = $id;
}

sub run {
	my $this = shift;

	$this->{daemon}->profile_start('BATCHRUN');
	my $nbmsgs = keys %{ $this->{messages} };

	my $t   = threads->self;
	my $tid = $t->tid;

	return if ( $nbmsgs < 1 );
	$this->{daemon}->doLog( $this->{batchid} . ": starting batch run",
		'spamhanler', 'debug' );
	my $count = 0;
	foreach my $msgid ( keys %{ $this->{messages} } ) {
		$count++;
		$this->{daemon}->doLog(
			"($tid) "
			  . $this->{batchid}
			  . ": processing message: $msgid ($count/$nbmsgs)",
			'spamhandler', 'debug'
		);

		my $msg =
		  SpamHandler::Message::new( $msgid, $this->{daemon},
			$this->{batchid} );
		$msg->load();
		$msg->process();

		## then log
		my %prepared = %{ $this->{daemon}->{prepared} };
		my $inmaster = 0;
		foreach my $dbname ( keys %prepared ) {
			$msg->log( $dbname, \$inmaster );
		}
		$msg->purge();
		my $msgtimers = $msg->getTimers();
		$this->addTimers($msgtimers);
	}
	delete $this->{messages};

	$this->startTimer('Batch logging message');
	foreach my $dbname ( keys %{ $this->{daemon}->{dbs} } ) {
		if ( $this->{daemon}->{dbs}{$dbname} ) {
			$this->{daemon}->{dbs}{$dbname}->commit();
		}
	}
	$this->endTimer('Batch logging message');
	$this->logTimers();
	my $btime = $this->{daemon}->profile_stop('BATCHRUN');
	$this->{daemon}->doLog(
		$this->{batchid}
		  . ": finished batch run in $btime seconds for "
		  . $nbmsgs
		  . " messages",
		'spamhandler', 'debug'
	);
}

sub addTimers {
	my $this      = shift;
	my $msgtimers = shift;

	return if !$msgtimers;
	my %timers = %{$msgtimers};
	foreach my $t ( keys %timers ) {
		next if ( $t !~ m/^d_/ );
		$this->{'timers'}{$t} += $timers{$t};
	}
	return 1;
}

#######
## profiling timers

sub startTimer {
	my $this  = shift;
	my $timer = shift;

	$this->{'timers'}{$timer} = [gettimeofday];
}

sub endTimer {
	my $this  = shift;
	my $timer = shift;

	my $interval = tv_interval( $this->{timers}{$timer} );
	$this->{'timers'}{ 'd_' . $timer } = ( int( $interval * 10000 ) / 10000 );
}

sub getTimers {
	my $this = shift;
	return $this->{'timers'};
}

sub logTimers {
	my $this = shift;

	foreach my $t ( keys %{ $this->{'timers'} } ) {
		next if ( $t !~ m/^d_/ );
		my $tn = $t;
		$tn =~ s/^d_//;
		$this->{daemon}
		  ->doLog( 'Batch spent ' . $this->{'timers'}{$t} . "s. in " . $tn,
			'spamhanler', 'debug' );
	}
}

1;
