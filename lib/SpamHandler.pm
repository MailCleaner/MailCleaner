#!/usr/bin/perl -w
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004-2014 Olivier Diserens <olivier@diserens.ch>
#   Copyright (C) 2015-2017 Florian Billebault <florian.billebault@gmail.com>
#   Copyright (C) 2015-2017 Mentor Reka <reka.mentor@gmail.com>
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

package          SpamHandler;
require Exporter;
require PreForkTDaemon;
require SpamHandler::Batch;
require DB;
use threads;
use threads::shared;

our @ISA = "PreForkTDaemon";

use strict;

my %processed_ids : shared;

sub new {
    my $class        = shift;
    my $myspec_thish = shift;
    my %myspec_this;
    if ($myspec_thish) {
        %myspec_this = %$myspec_thish;
    }

	my $conf     = ReadConfig::getInstance();
	my %dbs      = ();
	my %prepared = ();

	my $spec_this = {
		interval => 3,
		spamdir  => $conf->getOption('VARDIR') . '/spool/exim_stage4/spamstore',
		maxbatchsize         => 100,
		reportspamtodnslists => 0,
		reportrbls           => '',
		rblsDefsPath         => $conf->getOption('SRCDIR') . "/etc/rbls/",
		whitelistDomainsFile => $conf->getOption('SRCDIR')
		  . "/etc/rbls/whitelisted_domains.txt",
		TLDsFiles => $conf->getOption('SRCDIR')
		  . "/etc/rbls/two-level-tlds.txt "
		  . $conf->getOption('SRCDIR')
		  . "/etc/rbls/tlds.txt",
		localDomainsFile => $conf->getOption('VARDIR')
		  . "/spool/tmp/mailcleaner/domains.list",
		maxurisreports => 10,
		configfile     => $conf->getOption('SRCDIR')
		  . "/etc/mailcleaner/spamhandler.conf",
        pidfile    => $conf->getOption('VARDIR') . "/run/spamhandler.pid",

		%dbs       => (),
		%prepared  => (),
		storeslave => $conf->getOption('HOSTID'),
        clean_thread_exit    => 1,
	};
	
	# add specific options of child object
    foreach my $sk ( keys %myspec_this ) {
        $spec_this->{$sk} = $myspec_this{$sk};
    }
	my $this = $class->SUPER::create( 'SpamHandler', undef, $spec_this );
	foreach my $key ( keys %{$this} ) {
		$this->{$key} =~ s/%([A-Z]+)%/$conf->getOption($1)/eg;
	}
	bless $this, $class;

	return $this;
}

sub clearCache {
	my $this = shift;
	my $nosh = shift;
	my $type = shift;

	lock(%processed_ids);
	foreach my $id ( keys %processed_ids ) {
		delete $processed_ids{$id};
	}
	%processed_ids = ();
	return 1;
}

sub preForkHook() {
	my $this = shift;

	return 1;
}

sub mainLoopHook() {
	my $this = shift;
	$this->doLog( "In SpamHandler mainloop", 'spamhandler' );

    $SIG{'INT'} = $SIG{'KILL'} = $SIG{'TERM'} = sub {
    	my $t = threads->self;
        $this->{tid} = $t->tid;
    
        $this->doLog(
            "Thread " . $t->tid . " got TERM! Proceeding to shutdown thread...",
            'daemon'
        );
 
        threads->detach();
        $this->doLog( "Thread " . $t->tid . " detached.", 'daemon' );
        #$this->disconnect();
        threads->exit();
        $this->doLog( "Huho... Thread " . $t->tid . " still working though...",
            'daemon', 'error' );
    };
    
	## first prepare databases access for loggin ('_Xname' are for order)
	$this->connectDatabases();

	my $spamdir = $this->{spamdir};

	if ( $this->{reportspamtodnslists} > 0 ) {
		require MCDnsLists;
		$this->{dnslists} =
		  new MCDnsLists( sub { my $msg = shift; $this->doLog($msg, 'spamhandler'); },
			$this->{debug} );
		$this->{dnslists}->loadRBLs(
			$this->{rblsDefsPath}, $this->{reportrbls},
			'URIRBL',              $this->{whitelistDomainsFile},
			$this->{TLDsFiles},    $this->{localDomainsFile},
			'dnslists'
		);
	}

	while (1) {

		my $batch = SpamHandler::Batch::new( $spamdir, $this );
		if ( !$batch ) {
			$this->doLog(
"Cannot create spam batch ($spamdir) ! sleeping for 10 seconds...",
				'spamhandler', 'error'
			);
			sleep 10;
			return 0;
		}

		$batch->prepareRun();
		$batch->getMessagesToProcess();
		$batch->run();
		sleep $this->{prefork} * $this->{interval};
	}
	$this->doLog( "Error, in thread neverland !", 'spamhandler', 'error' );
	return 1;
}

sub connectDatabases {
	my $this = shift;

	my @databases = ( 'slave', 'realmaster' );

	foreach my $db (@databases) {
		if ( !defined( $this->{dbs}{$db} ) || !$this->{dbs}{$db}->ping() ) {
			$this->doLog( "Connecting to database $db", 'spamhandler' );
			$this->{dbs}{$db} = DB::connect( $db, 'mc_spool', 0 );
		}

		if ( !defined( $this->{dbs}{$db} ) || !$this->{dbs}{$db}->ping() ) {
			$this->doLog( "Error, could not connect to db $db ",
				'spamhandler', 'error' );
			delete( $this->{dbs}{$db} );
		}
	}

	## and prepare statements
	foreach my $dbname ( keys %{ $this->{dbs} } ) {
		## desable autocommit
		$this->{dbs}{$dbname}->setAutoCommit(0);
		my $db = $this->{dbs}{$dbname};
		foreach my $t (
			(
				'a', 'b', 'c',    'd', 'e', 'f', 'g', 'h',
				'i', 'j', 'k',    'l', 'm', 'n', 'o', 'p',
				'q', 'r', 's',    't', 'u', 'v', 'w', 'x',
				'y', 'z', 'misc', 'num'
			)
		  )
		{
			my %db_prepare = ();
			$this->{prepared}{$dbname}{$t} = $db->prepare(
				    'INSERT INTO spam_' . $t
				  . ' (date_in, time_in, to_domain, to_user, sender, exim_id, M_score, M_rbls, M_prefilter, M_subject, M_globalscore, forced, in_master, store_slave, is_newsletter) 
                                                                        VALUES(NOW(),   NOW(),     ?,         ? ,     ? ,       ?,      ?,      ?,        ?,          ?,             ?,      \'0\',     ?,'
				  . $this->{storeslave} . ', ?)'
			);
            
			if ( !$this->{prepared}{$dbname}{$t} ) {
				$this->doLog( "Error in preparing statement $dbname, $t!",
					'spamhandler', 'error' );
			}
		}
	}

	return 1;
}

sub deleteLock {
	my $this = shift;
	my $id   = shift;

	lock(%processed_ids);
	if ( defined( $processed_ids{$id} ) ) {
		delete $processed_ids{$id};
	}
	return 1;
}

sub addLock {
	my $this = shift;
	my $id   = shift;

	lock(%processed_ids);
	$processed_ids{$id} = 1;
	return 1;
}

sub isLocked {
	my $this = shift;
	my $id   = shift;

	lock(%processed_ids);
	if ( exists( $processed_ids{$id} ) ) {
		return $processed_ids{$id};
	}
	return 0;
}

1;
