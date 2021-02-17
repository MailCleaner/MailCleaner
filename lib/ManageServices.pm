package          ManageServices;

use strict;
use warnings;
no warnings 'uninitialized';
use lib '/usr/mailcleaner/lib/ManageServices';
use threads ();
use threads::shared;
use Time::HiRes qw( gettimeofday tv_interval );
use POSIX;
require ReadConfig;
require ConfigTemplate;

our $initDir = '/usr/mailcleaner/etc/init.d';
our $restartDir = '/var/mailcleaner/run';
our $logLevels = { 'error' => 0, 'info' => 1, 'debug' => 2 };

our %defaultConfigs = (
	'VARDIR'	=> '/var/mailcleaner',
	'SRCDIR'	=> '/usr/mailcleaner',
	'debug'		=> 1,
	'uid'		=> 0,
	'gid'		=> 0,
	'timeout'	=> 5,
);

our %defaultActions = (
	#TODO
	'start'		=> {
		'desc'		=> 'start service or report if already running',
		'cmd'		=> sub { 
			my $self = shift;
			return $self->start();
		},
	},
	'stop'		=> {
		'desc'		=> 'stop serivce, if running',
		'cmd'		=> sub {
			my $self = shift;
			return $self->stop();
		},
	},
	#TODO
	'restart'	=> {
		'desc'		=> 'stop serivce, if running, then start fresh',
		'cmd'		=> sub {
			my $self = shift;
			return $self->restart();
		},
	},
	'status'	=> {
		'desc'		=> 'get current status',
		'cmd'		=> sub { 
			my $self = shift;
			return $self->status(0);
		},
	},
	'enable'	=> {
		'desc'		=> 'enable service and start automatically',
		'cmd'		=> sub {
			my $self = shift;
			return $self->enable();
		},
	},
	'disable'	=> {
		'desc'		=> 'stop and prevent from starting until enabled',
		'cmd'		=> sub {
			my $self = shift;
			return $self->disable();
		},
	},
	'pids'		=> {
		'desc'		=> 'get process id(s) for service',
		'cmd'		=> sub {
			my $self = shift;
			if ($self->status(0) == 7) {
				return $self->clearFlags(7);
			}
			my @pids = $self->pids();
			if (scalar(@pids)) {
				return 'running with pid(s) ' . join(', ', @pids);
			} else {
				return 0;
			}
		},
	},
	'dump_config'	=> {
		'desc'		=> 'list module configuration',
		'cmd'		=> sub {
			my $self = shift || die "Failed to load object";
			foreach my $key (keys(%{$self->{'module'}})) {
				print "$key = $self->{'module'}->{$key}\n";
			}
			return undef;
		},
	}
);

sub new 
{
	my $self = shift;
	my %params = @_;

	my $conf = ReadConfig::getInstance();

	require Proc::ProcessTable;
	$self = { 
		'initDir' 	=> $params{'initDir'} || $initDir,
		'restartDir' 	=> $params{'restartDir'} || $restartDir,
		'processTable' 	=> Proc::ProcessTable->new(),
		'codes'		=> getCodes(),
		'autoStart'	=> $params{'autoStart'}	|| 0,
		'conf'		=> $conf,
		'timeout'	=> 5,
	};
	$self->{'services'} = getServices($self->{'initDir'});
	bless $self;
}

sub getCodes
{
	my %codes = (
		-1	=> {
			'verbose'	=> 'unknown service',
			'suffix'	=> undef,
		},
		0	=> {
			'verbose'	=> 'critical (not running and required)',
			'suffix'	=> 'stopped',
		},
		1	=> {
			'verbose'	=> 'running',
			'suffix'	=> undef,
		},
		2	=> {
			'verbose'	=> 'stopped (not running but not required)',
			'suffix'	=> 'stopped',
		},
		3	=> {
			'verbose'	=> 'needs restart',
			'suffix'	=> 'rn',
		},
		4	=> {
			'verbose'	=> 'currently stopping',
			'suffix'	=> 'start.rs',
		},
		5	=> {
			'verbose'	=> 'currently starting',
			'suffix'	=> 'stop.rs',
		},
		6	=> {
			'verbose'	=> 'currently restarting (currently processing stop/start script)',
			'suffix'	=> 'restart.rs',
		},
		7	=> {
			'verbose'	=> 'disabled',
			'suffix'	=> 'disabled',
		},
	);
	return \%codes;
}

sub getServices
{
	my $init = shift || $initDir;
	my %services = (
		#'apache' 	=> {
			#'name'		=> 'Web access',
			#'module'	=> 'Apache',
			#'critical'	=> 1,
		#},
		'clamd'		=> {
			'name'		=> 'ClamAV daemon',
			'module'	=> 'ClamD',
			'critical'	=> 1,
		},
		'clamspamd'	=> {
			'name'		=> 'ClamSpam daemon',
			'module'	=> 'ClamSpamD',
			'critical'	=> 1,
		},
		#'cron'		=> {
			#'name'		=> 'Scheduler',
			#'module'	=> 'Cron',
			#'critical'	=> 1,
		#},
		#'dccifd' 	=> {
			#'name'		=> 'DCC client daemon',
			#'module'	=> 'Apache',
			#'critical'	=> 1,
		#},
		#'exim_stage1' 	=> {
			#'name'		=> 'Incoming MTA',
			#'module'	=> 'Exim',
			#'critical'	=> 1,
		#},
		#'exim_stage2' 	=> {
			#'name'		=> 'Filtering MTA',
			#'module'	=> 'Exim',
			#'critical'	=> 1,
		#},
		#'exim_stage4' 	=> {
			#'name'		=> 'Outgoing MTA',
			#'module'	=> 'Exim',
			#'critical'	=> 1,
		#},
		#'fail2ban'	=> {
			#'name'		=> 'Fail2Ban',
			#'module'	=> 'Fail2Ban',
			#'critical'	=> 1,
		#},
		#'greylistd'	=> {
			#'name'		=> 'Greylist daemon',
			#'module'	=> 'GreylistD',
			#'critical'	=> 1,
		#},
		#'mailscanner'	=> {
			#'name'		=> 'Filtering engine',
			#'module'	=> 'MailScanner',
			#'critical'	=> 1,
		#},
		#'mysql_master'	=> {
			#'name'		=> 'Master database',
			#'module'	=> 'MySQL',
			#'critical'	=> 1,
		#},
		#'mysql_slave'	=> {
			#'name'		=> 'Slave database',
			#'module'	=> 'MySQL',
			#'critical'	=> 1,
		#},
		'newsld'	=> {
			'name'		=> 'Newsletters daemon',
			'module'	=> 'NewslD',
			'critical'	=> 1,
		},
		#'preftdaemon'	=> {
			#'name'		=> 'Preferences daemon',
			#'module'	=> 'PrefTDaemon',
			#'critical'	=> 1,
		#},
		#'snmpd'		=> {
			#'name'		=> 'SNMP daemon',
			#'module'	=> 'SNMPD',
			#'critical'	=> 1,
		#},
		'spamd' 	=> {
			'name'		=> 'SpamAssassin daemon',
			'module'	=> 'SpamD',
			'critical'	=> 1,
		},
		#'spamhandler'	=> {
			#'name'		=> 'Filtering engine',
			#'module'	=> 'SpamHandler',
			#'critical'	=> 1,
		#},
	);
	return \%services;
}

sub loadModule
{
	my $self = shift;
	my $service = shift;

	if ( defined($self->{'module'}->{'syslog_facility'}) &&
		$self->{'module'}->{'syslog_facility'} ne '' )
	{
		closelog( $self->{'service'},
			'ndelay,pid,nofatal', $self->{'module'}->{'syslog_facility'} );
	}

	$self->{'config'} = \%defaultConfigs;

	$self->{'service'} = $service;

	my $module = $self->{'services'}->{$self->{'service'}}->{'module'} ||
		die "$self->{'service'} has no declared 'module'\n";

	require "ManageServices/" . $module . ".pm";
	$module = "ManageServices::".$module;
	$self->{'module'} = init $module $self;
	$self->getConfig() || return 0;
	$self->getActions() || return 0;

	foreach my $file (('pidfile', 'logfile', 'socketpath')) {
		if (defined($self->{'module'}->{$file}) && -f $self->{'module'}->{$file}) {
			chown(
				$self->{'module'}->{'uid'},
				$self->{'module'}->{'gid'},
				$self->{'module'}->{$file}
			);
		}
	}
	
	if ( defined($self->{'module'}->{'syslog_facility'}) &&
		$self->{'module'}->{'syslog_facility'} ne '' )
	{
		openlog( $self->{'service'},
			'ndelay,pid,nofatal', $self->{'module'}->{'syslog_facility'} );
	}

	$self->{'module'}->{'state'} = $self->status();
	unless (defined($self->{'module'}->{'state'})) {
		$self->{'module'}->{'state'} = 0;
	}
	
	return $self;
}
	
sub getConfig
{
	my $self = shift;
	
	unless (defined $self->{'module'}) {
		die "must first loadModule('service')";
	}

	foreach (keys %defaultConfigs) {
		unless (defined($self->{'module'}->{$_})) {
			$self->{'module'}->{$_} = $defaultConfigs{$_};
		}
	}

	my $conffile = $self->{'module'}->{'conffile'};
	if ( -f $conffile.'_template' ) {
		my $template = ConfigTemplate::create( $conffile."_template", $conffile );
		my $ret = $template->dump();
	}
	if ( open CONFFILE, $self->{'module'}->{'conffile'} ) {
		while (<CONFFILE>) {
			chomp;
			next if /^\#/;
			if (/^(\S+)\s*\=\s*(.*)$/) {
    				$self->{'module'}->{$1} = $2;
			}
		}
		close CONFFILE;
	}

	$self->{'module'}->{'uid'} = $self->{'module'}->{'user'} ? getpwnam( $self->{'module'}->{'user'} ) : 0;
	$self->{'module'}->{'gid'} = $self->{'module'}->{'group'} ? getpwnam( $self->{'module'}->{'group'} ) : 0;

	foreach my $key (keys %{$self->{'module'}}) {
		if (!defined($self->{'module'}->{$key})) {
			next;
		} elsif ($self->{'module'}->{$key} =~ m#^(false|no)$#i) {
			$self->{'module'}->{$key} = 0;
		} elsif ($self->{'module'}->{$key} =~ m#^(true|yes)$#i) {
			$self->{'module'}->{$key} = 1;
		}
	}
			
	return 1;
}

sub getActions
{
	my $self = shift;

	my %actions = %defaultActions;
	my $modActions = $self->{'module'}->{'actions'};
	foreach my $action (keys %{$modActions}) {
		foreach my $attr (keys %{$modActions->{$action}}) {
			$actions{$action}->{$attr} = $modActions->{$action}->{$attr};
		}
	}

	$self->{'module'}->{'actions'} = \%actions;
}

sub findProcess
{
	my $self = shift;

	foreach my $p ( @{ $self->{'processTable'}->table() } ) {
		if ($p->{'pid'} == $$) {
			next;
		}
		if ($p->{'cmndline'} =~ m#$self->{'module'}->{'cmndline'}#) {
			return $p->{'pid'};
		}
	}
	return 0;
}

sub pids
{
	my $self = shift;
	unless (defined($self->{'module'})) {
		my $service = shift || die "Either run \$self->loadModule('service_name') first\nor run with service name: \$self->pids('service_name')";
		$self->loadModule($service);
	}

	unless ($self->findProcess()) {
	 	$self->clearFlags(0);
		return ();
	}

	my @pids;
	foreach my $p ( @{ $self->{'processTable'}->table() } ) {
		if ($p->{'pid'} == $$) {
			next;
		}
		if ($p->{'cmndline'} =~ m#$self->{'module'}->{'cmndline'}#) {
			push(@pids,$p->{'pid'});
		}
	}

	my @filePids = $self->readPidFile();
	if (scalar(@pids) != scalar(@filePids)) {
		$self->writePidFile(@pids);
	} else {
		foreach my $pid (@pids) {
			unless (scalar(grep(/^$pid$/, @filePids))) {
				print STDERR "PIDs in process table do not match pidfile, updating\n";
				$self->writePidFile(@pids);
				last;
			}
		}
	}

	return @pids;
}
	
sub createModule
{
	my $self = shift;
	my $defs = shift;

	my $file = $defs->{'conffile'} || $self->{'conf'}->getOption('SRCDIR').'/etc/mailcleaner/'.$self->{'service'}.".cf";
	my $module = {};
	foreach my $key (keys %defaultConfigs) {
		$module->{$key} = $defaultConfigs{$key}
	}
	foreach my $key (keys %$defs) {
		$module->{$key} = $defs->{$key}
	}
	bless $module, 'ManageServices';

	my $tfile = $file . "_template";
	if ( -f $tfile ) {
		my $template = ConfigTemplate::create( $tfile, $file );
		my $ret = $template->dump();
	}

	if ( open CONFFILE, $file ) {
		while (<CONFFILE>) {
    			chomp;
    			next if /^\#/;
    			if (/^(\S+)\s*\=\s*(.*)$/) {
        			$module->{$1} = $2;
    			}
		}
		close CONFFILE;
	}
	return $module;
}
	
sub status
{
	my $self = shift;
	unless (defined($self->{'module'})) {
		my $service = shift || die "Either run \$self->loadModule('service_name') first\nor run with service name: \$self->status('service_name')";
		$self->loadModule($service);
	}
	my $autoStart = shift;
	unless (defined($autoStart)) {
		$autoStart = $self->{'autoStart'};
	}

	my $status = 0;
	my $running = $self->findProcess();
  	if (defined($self->{'services'}->{$self->{'service'}})) {
		if ( -e $self->{'restartDir'}.'/'.$self->{'service'}.".disabled" ) {
			if ($running) {
				$running = $self->stop(); 
				if ( $running =~ /[02]/ ) {
					return $self->clearFlags(7);
				} else {
					return $self->clearFlags($running);
				}
			} else {
				return $self->clearFlags(7);
			}
		}
		for (my $i = 3; $i < 7; $i++) {
			if ( -e $self->{'restartDir'}.'/'.$self->{'service'} . "." .
				$self->{'codes'}->{$i}->{'suffix'} )
			{
				if ( $i == 4 && !$running ) {
					$status = $self->clearFlags(0);
				} elsif ( $i == 5 && $running ) {
					$status = $self->clearFlags(1);
				} elsif ( $i > 3 &&
					( stat($self->{'restartDir'} .
					'/' . $self->{'service'} . "." .
					$self->{'codes'}->{$i}->{'suffix'}))[9] 
					< (time() - 60) )
				{
					if ($autoStart) {
						return $self->clearFlags($self->restart());
					} else {
						return $self->clearFlags(3);
					}
				}
				last;
			}
		}
		if ($running) {
			return $self->clearFlags(1);
		} else {
			if ($autoStart) {
				return $self->clearFlags($self->start());
			} else {
				return $self->clearFlags(0);
			}
		}
  	} else {
		return $self->clearFlags(-1);
  	}
}

sub start
{
	my $self = shift;
	unless (defined($self->{'module'})) {
		my $service = shift || die "Either run \$self->loadModule('service_name') first\nor run with service name: \$self->start('service_name')";
		$self->loadModule($service);
	}

	if ($self->status(0) == 7) {
		return $self->clearFlags(7);
	}

	$self->doLog( 'starting ' . $self->{'service'} . '...', 'daemon' );
	
	if ($self->{'module'}->{'state'} =~ m/^[0235]$/) {
		$self->clearFlags(5);
	} else {
		return $self->{'module'}->{'state'};
	}

	if ($self->findProcess()) {
		$self->doLog( $self->{'service'} . ' already running.', 'daemon' );
		$self->clearFlags(1);
		return 1;
	} 

	$self->setup();

	$self->doLog('Initializing Daemon', 'daemon');

	$SIG{ALRM} = sub { $self->doLog( "Got alarm signal.. nothing to do", 'daemon' ); };
	$SIG{PIPE} = sub { $self->doLog( "Got PIPE signal.. nothing to do", 'daemon' ); };

	if ($self->{'module'}->{'daemonize'}) {
		my $pid = fork;
        	if ($pid) {
			$self->doLog( 'Deamonized with PID ' . $pid, 'daemon' );
			sleep(1);
			my @pids = $self->pids();
			my @remaining = ();
			foreach my $testing ( @pids ) {
				foreach ( @{ $self->{'processTable'}->table } ) {
					if ($_->{'pid'} == $testing && 
						$_->{'cmndline'} =~ m#$self->{'module'}->{'cmndline'}#i ) 
					{
						if ($_->{'pid'} == $pid) {
							$self->doLog( 'Started successfully',
								'daemon' );
						} else {
							$self->doLog( 'Started child ' . $_->{'pid'},
								'daemon' );
						}
						push @remaining, $_->{'pid'};
						last;
					}
				}
			}
			if (scalar(@remaining)) {
				$self->writePidFile( @remaining );
				return $self->clearFlags(1);
			} else {
				$self->doLog( 'Deamon doesn\'t exist after start. Failed', 'daemon' );
				return 0;
			}
        	} elsif ($pid == -1) {
			$self->doLog( 'Failed to fork', 'daemon' );
			$self->clearFlags(0);
			return $self->{'module'}->{'state'};
		} else {
			if ( $self->{'module'}->{'gid'} ) {
				$) = $self->{'module'}->{'gid'} || 
					die "failed to set gid\n";
				unless ( grep( $), split( ' ', $self->{'module'}->{'gid'} ) ) ) {
					print STDERR "Can't set GID " . 
						$self->{'module'}->{'gid'} . 
						" (" . $( . " " . $) . ")\n";
					return 0;
				}
			}
			$self->doLog('Set GID to ' . $self->{'module'}->{'group'} . 
				" (" . $( . ")", 'daemon');

			if ( $self->{'module'}->{'uid'} ) {
				$> = $self->{'module'}->{'uid'};
				unless ($> == $self->{'module'}->{'uid'}) {
					print STDERR "Can't set UID " .
						$self->{'module'}->{'uid'} . 
						" (" . $< . " " . $> . ")\n";
					return 0;
				}
			}
			$self->doLog('Set UID to ' . $self->{'module'}->{'user'} .
				" (" . $< . ")", 'daemon');

			open STDIN,  '/dev/null';
			open STDOUT, '>>/dev/null';
			open STDERR, '>>/dev/null';
			setsid();
			umask 0;
		}
	} else {
		$self->writePidFile( ($$) );
	}
	
	$self->preFork();
	if (defined($self->{'module'}->{'forks'}) && $self->{'module'}->{'forks'} > 0) {
		$self->forkChildren();	
	} else {
		my $ret = $self->mainLoop();
		$self->clearFlags($ret);
		return $ret;
	}

	$self->clearFlags(0);
	return $self->{'module'}->{'state'};
}

sub stop
{
	my $self = shift;
	unless (defined($self->{'module'})) {
		my $service = shift || die "Either run \$self->loadModule('service_name') first\nor run with service name: \$self->stop('service_name')";
		$self->loadModule($service);
	}

	my $running = $self->findProcess();
	if ( $self->{'module'}->{'state'} =~ m/^[1356]$/ ) {
		$self->clearFlags(4);
	} elsif ( $self->{'module'}->{'state'} == 4 && $running == 0 ) {
		return $self->clearFlags(0);
	} else {
		return $self->{'module'}->{'state'};
	}

	$self->doLog( 'Stopping ' . $self->{'service'} . '...', 'daemon' );
	my $disabled = 0;
	if ( -e $self->{'restartDir'}.'/'.$self->{'service'}.".disabled" ) {
		$self->doLog( $self->{'service'} . ' is disabled. Looking for remaining processes.', 'daemon' );
		$disabled = 1;
	}
	
	my @pids = $self->pids();
	my $pidstillhere = 1;
	my $start_ps = [gettimeofday];
	my $level = 15;
	while (scalar(@pids)) {
		if ( tv_interval($start_ps) > $self->{'module'}->{'timeout'} ) {
			$level = 9;
		}
		foreach my $p (@pids) {
			my $pid = fork();
			unless ($pid) {
				foreach ( @{ $self->{'processTable'}->table } ) {
					if ($_->{'pid'} == $p) {
						$_->kill($level);
						last;
					}
				}
				exit();
			}
		}
		sleep(1);
		if ($level == 9) {
			@pids = ();
		} else {
			@pids = $self->pids();
		}
	}
	
	if ($self->findProcess()) {
		return $self->clearFlags(1);
	} else {
		$self->writePidFile( () );
		if ($self->{'module'}->{'state'} == 6) {
			return $self->clearFlags(6)
		} elsif ($disabled) {
			return $self->clearFlags(7);
		} else {
			return $self->clearFlags(0);
		}
	}
}

sub restart
{
	my $self = shift;
	unless (defined($self->{'module'})) {
		my $service = shift || die "Either run \$self->loadModule('service_name') first\nor run with service name: \$self->restart('service_name')";
		$self->loadModule($service);
	}

	if ( $self->{'module'}->{'state'} =~ m/^[0123]$/ ) {
		$self->clearFlags(6);
	} elsif ( $self->{'module'}->{'state'} =~ m/^[456]$/ ) {
		if ( -e $self->{'restartDir'} . '/' . $self->{'service'} . "." .
			$self->{'codes'}->{$self->{'module'}->{'state'}}->{'suffix'} )
		{
			if ( (stat($self->{'restartDir'} . '/' . $self->{'service'} . "." .
				$self->{'codes'}->{$self->{'module'}->{'state'}}->{'suffix'} 
				))[9] < (time() - 60) ) 
			{
				$self->clearFlags(3);
			} else {
				return $self->{'module'}->{'state'};
			}
		}
	} else {
		return $self->clearFlags(7);
	}

	if ($self->findProcess($self->{'services'}->{$self->{'service'}}->{cmndline})) {
		if ($self->stop()) {
			return 3;
		} else {
			$self->clearFlags(5);
		}
	} else {
		$self->clearFlags(5);
	}
	return $self->start();
}
	
sub enable
{
	my $self = shift;
	unless (defined($self->{'module'})) {
		my $service = shift || die "Either run \$self->loadModule('service_name') first\nor run with service name: \$self->enable('service_name')";
		$self->loadModule($service);
	}

	$self->clearFlags(1);
	return $self->restart();
}

sub disable
{
	my $self = shift;
	unless (defined($self->{'module'})) {
		my $service = shift || die "Either run \$self->loadModule('service_name') first\nor run with service name: \$self->disable('service_name')";
		$self->loadModule($service);
	}

	if ($self->stop() == 1) {
		return 1;
	} else {
		$self->clearFlags(7);
		return 7;
	}
}

sub checkAll
{
	my $self = shift;

	my %results;
	foreach my $service (keys(%{$self->{'services'}})) {
		$self->{'service'} = $service;
		$self->loadModule($service);
		$results{$service} = $self->status();
	}
	return \%results;
}

sub setup
{
	my $self = shift;
	
	$self->doLog( 'Setting up ' . $self->{'service'} . '...', 'daemon' );
	if ($self->{'module'}->setup($self)) {
		$self->doLog( 'Setup complete', 'daemon' );
	} else {
		$self->doLog( 'No Setup defined for ' . $self->{'service'} . '...', 'daemon' );
	}
		
}

sub preFork
{
	my $self = shift;
	
	$self->doLog( 'Running PreFork for ' . $self->{'service'} . '...', 'daemon' );
	if ($self->{'module'}->preFork($self)) {
		$self->doLog( 'PreFork complete', 'daemon' );
	} else {
		$self->doLog( 'No PreFork defined for ' . $self->{'service'} . '...', 'daemon' );
	}
		
}

sub mainLoop
{
	my $self = shift;
	
	$self->doLog( 'Running MainLoop for ' . $self->{'service'} . '...', 'daemon' );
	if ($self->{'module'}->mainLoop($self)) {
		$self->doLog( 'MainLoop complete', 'daemon' );
	} else {
		$self->doLog( 'In dummy main loop...', 'debug' );
		while (1) {
			sleep 5;
			$self->doLog( 'Continuing dummy main loop...', 'debug' );
		}
	}
	return 1;
}

sub forkChildren
{
	my $self = shift;

	$SIG{'TERM'} = sub {
		$self->doLog(
			'Main thread got a TERM signal. Proceeding to shutdown...',
			'daemon'
		);
		foreach my $t ( threads->list(threads::running) ) {
			$t->kill('TERM');
		}
		my $wait = 0;
		while ( threads->list() > 0 ) {
			$self->doLog( "Still " . treads->list() . " threads running...",
				'daemon' );
			$wait++;
			if ($wait == $self->{'module'}->{'timeout'}) {
				$self->doLog( "Taking too long. Detaching..." );
				foreach my $t ( threads->list() ) {
					$t->detach();
				}
			}
			sleep(1);
		}
		while ( threads->list(threads::running) > 0 ) {
			$self->doLog( "Still " . treads->list() . " threads running...",
				'daemon' );
			$wait++;
			if ($wait == $self->{'module'}->{'timeout'}) {
				$self->doLog( "Taking too long. Detaching..." );
				foreach my $t ( threads->list() ) {
					$t->detach();
				}
			}
			sleep(1);
		}
		foreach my $t ( threads->list(threads::joinable) ) {
			$self->doLog( "Joining thread " . $t->tid, 'daemon');
			my $res = $t->join();
		}

		$self->doLog( "Threads all stopped, cleaning...", 'daemon' );
		exit();
	};
	
	my $leaving = 0;
	while (!$leaving) {
		my $thread_count = threads->list(threads::running);
		for (my $ctr = 0; $ctr <= $self->{'module'}->{'forks'}; $ctr++) {
			$self->newChild();
			sleep($self->{'module'}->{'interval'});
		}

		$self->doLog(
			"Population check done (" . $thread_count .
			"), waiting " . $self->{'module'}->{'checktimer'} .
			" seconds for next check...", 'daemon', 'debug'
		);

		sleep($self->{'module'}->{'checktimer'});
	}
	$self->doLog("Error, in main thread neverland !", 'daemon', 'error' );
}

sub newChild
{
	my $self = shift;
	my $pid;

	my $thread_count = scalar(threads->list);
	if ($thread_count >= $self->{'module'}->{'children'}) {
		return 0;
	}
	$self->doLog(
		"Launching new thread (" . ($thread_count+=1) . "/" .
		$self->{'module'}->{'children'} . ") ...", 'daemon'
	);
	my $t = threads->create( { 'void' => 1 }, sub { $self->mainLoop($self->{'module'}); } );
}

sub readPidFile
{
	my $self = shift;

	my @pids;
	if ( open( PIDFILE, '<', $self->{'module'}->{'pidfile'} ) ) {
		while (<PIDFILE>) {
			if ($_ =~ m/^\d+$/) {
				push(@pids, $_);
			}
		}
	} else {
		return ();
	}
	return @pids;
}

sub writePidFile
{
	my $self = shift;
	my @pids = @_;

	unless (scalar(@pids)) {
		unlink($self->{'module'}->{'pidfile'});
		return 1;
	}
	if ( open( PIDFILE, '>', $self->{'module'}->{'pidfile'} ) ) {
		foreach (@pids) {
			print PIDFILE "$_\n";
		}
		return 1;
	} else {
		print STDERR "Warning: $self->{'module'}->{'pidfile'} is not writable\n";
		return 0;
	}
}

sub clearFlags
{
	my $self = shift;
	my $status = shift;
	
	if ($status == 0 && !$self->{'services'}->{$self->{'service'}}->{'critical'}) {
		$status = 2;
	}
	$self->{'module'}->{'state'} = $status;

	opendir my($dh), $self->{'restartDir'};
	my @files = readdir $dh;
	closedir $dh;

	my @suffixList;
	foreach my $code (keys(%{$self->{'codes'}})) {
		if (defined($self->{'codes'}->{$code}->{'suffix'})) {
			if ( -e $self->{'restartDir'}.	'/' . $self->{'service'} .
				'.' . $self->{'codes'}->{$code}->{'suffix'} )
			{
				if ($status =~ m/[456]/) {
					return $code;
				}
				unlink( $self->{'restartDir'}. '/' . $self->{'service'} .
					'.' . $self->{'codes'}->{$code}->{'suffix'} );
			}
		}
	}

	if ( defined($self->{'codes'}->{$status}->{'suffix'}) ) {
		if ( $status < 3 || 
			$status > 6 ||
			( !-e $self->{'restartDir'} . '/' .
			$self->{'service'} . '.' .
			$self->{'codes'}->{$status}->{'suffix'} ) )
 		{
			open(my $fh, '>', 
				$self->{'restartDir'} . '/' . 
				$self->{'service'} . '.' .
				$self->{'codes'}->{$status}->{'suffix'}
			);
			close($fh);
		}
	} 
	return $status;
}
	
sub usage
{
	my $self = shift;

	if (defined($self->{'module'})) {
		print "Available actions:\n\n";
		my @actions;
		foreach my $action (keys %{$self->{'module'}->{'actions'}}) {
			if (defined($self->{'module'}->{'actions'}->{$action}->{'cmd'})) {
				unless (defined($self->{'module'}->{'actions'}->{$action}->{'desc'})) {
					$self->{'module'}->{'actions'}->{$action}->{'desc'} = 
						'No description';
				}
				push(@actions,$action);
			}
		}
		foreach ( sort(@actions) ) {
			printf("%-16s%-64s\n",$_,
			$self->{'module'}->{'actions'}->{$_}->{'desc'});
		}
	} else {
		my @services;
		foreach my $service (keys %{$self->{'services'}}) {
			push(@services,$service);
		}
		foreach ( sort(@services) ) {
			printf("%-16s%-24s\n",$_,$self->{'services'}->{$_}->{'name'});
		}
		print "\nRun without action for list of available actions for that service.";
	}
	print "\n";
}

sub doLog
{
	my $self = shift;
	my $message = shift;
	my $given_set = shift;
	my $priority = shift;

	if ( !defined($priority) ) {
		$priority = 'info';
	}

	unless ( defined($self->{'module'}) ) {
		$self->{'module'} = $self;
	}

	foreach my $set ( split( /,/, $self->{'module'}->{'log_sets'} ) ) {
		if ( $set eq 'all' || !defined($given_set) || $set eq $given_set ) {
			if ( $logLevels->{$priority} <= $logLevels->{$self->{'module'}->{'loglevel'}} ) {
				confirmedLog($self,$message);
			}
			last;
		}
	}
}

sub confirmedLog
{
	my $self    = shift;
	my $message = shift;

	foreach my $line ( split( /\n/, $message ) ) {
		if ( $self->{'module'}->{'logfile'} ne '' ) {
			$self->writeLog($line);
		}
		if ( $self->{'module'}->{'syslog_facility'} ne '' && $self->{'module'}->{'syslog_progname'} ne '' ) {
			syslog( 'info', '(' . $self->getThreadID() . ') ' . $line );
		}
	}
}

sub writeLog
{
	my $self = shift;
	my $message = shift;
	chomp($message);

	if ( $self->{'module'}->{'logfile'} eq '' ) {
		return;
	}

	my $LOCK_SH = 1;
	my $LOCK_EX = 2;
	my $LOCK_NB = 4;
	my $LOCK_UN = 8;
	$| = 1;

	if ( !defined( fileno(LOGGERLOG) ) || !-f $self->{'module'}->{'logfile'} ) {
		open LOGGERLOG, ">>" . $self->{'module'}->{'logfile'};
		if ( !defined( fileno(LOGGERLOG) ) ) {
			open LOGGERLOG, ">>/tmp/" . $self->{'module'}->{'logfile'};
			$| = 1;
		}
		$self->doLog( 'Log file has been opened, hello !', 'daemon' );
	}
	my ( $sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst ) =
		localtime(time);
	$mon++;
	$year += 1900;
	my $date = sprintf( "%d-%.2d-%.2d %.2d:%.2d:%.2d",
		$year, $mon, $mday, $hour, $min, $sec );
	flock( LOGGERLOG, $LOCK_EX );
	print LOGGERLOG "$date (" . $self->getThreadID() . ") " . $message . "\n";
	flock( LOGGERLOG, $LOCK_UN );
}

sub getThreadID
{
	my $self = shift;
	
	my $thread = threads->self;
	return $thread->tid();
}

our @ISA = qw(Exporter);

our $VERSION = '1.0';

1;

__END__

=head1 NAME

MailCleaner Service Management

=head1 SYNOPSIS

Load package

	use ManageServices;

Create object (enable autoStart for services that are not running)

	my $manager = ManageServices->( 'autoStart' => 1 ) || die "$!\n";

If the object is created with the 'autoStart' option enabled, services will
be restarted when possible, including with 'checkAll', 'status', and 'pids'.

Object contains a list of services as a hash

	foreach my $service (keys(%{$manager->{'services'}})) {
		print "Key: " . $service . "\n";
		print "Name: " . $manager->{'services'}->{$service}->{'name'} . "\n";
	}

All individual actions will return the current state of the service after the
action has been processed. The state is represented by a code. You can get all
available codes from

        foreach my $code (keys(%{$manager->{'codes'}})) {
                print "Code: " . $code . "\n";
                print "Meaning: " . $manager->{'codes'}->{$code}->{'verbose'} . "\n";
        }

Except when using the checkAll function, you need to load your desired service

	$manager->loadModule($service);

Upon loading the module, it will be available as a nested object. You can find
the last known state of the module with

	$manager->{'module'}->{'state'};

Although it is better to freshly retrieve the state with

	$manager->status();

For this and the other core functions you can simply run them on the base object

	$manager->start();
	$manager->stop();
	$manager->restart();
	$manager->enable();
	$manager->disable();
	$manager->pids();

Each module is capable of loading custom actions that may not exist for other
services. When the module is loaded, all available actions, including the
universal ones, will be loaded into the 'module' member as an 'actions' has

	foreach my $action (keys(%{$manager->{'module'}->{'actions'}) {
		print "Action: " . $action . "\n";
		print "Description: " . 
			$manager->{'module'}->{'actions'}->{$action}->{'desc'} . "\n";
	}

In order to run these custom actions you must execute the 'cmd' member of that
hash which will be a code refernce within that specific module. This requires
that the base object be provided as an argument so that the child object has
access to all the parent functions.

	$manager->{'module'}->{'actions'}->{'custom_action'}->{'cmd'}($manager);

Finally, there is the 'checkAll' function which will return a hashref of the
status of all available services.

	my $status = $manager->checkAll();

	foreach my $service (keys(%$status)) {
		print "$service: " .
			$manager->{'codes'}->{$status->{$service}}->{'verbose'} . "\n";
	}

=head1 DESCRIPTION

This module provides a robust way to manage services while removing ambiguity
as to the success of an action or subsequent changes to a services state for
all or select MailCleaner services.

=head2 EXPORT

none.

=back

=head1 AUTHOR

John Mertz <git@john.me.tz>

=head1 COPYRIGHT AND LICENSE

MailCleaner - SMTP Antivirus/Antispam Gateway
Copyright (C) 2021 by John Mertz <git@john.me.tz>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

=cut
