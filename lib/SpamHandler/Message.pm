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

package          SpamHandler::Message;
require Exporter;
require Email;
require ReadConfig;
require Net::SMTP;  
use File::Path qw(mkpath);
use Time::HiRes qw(gettimeofday tv_interval);

use strict;

use threads;

our @ISA     = qw(Exporter);
our @EXPORT  = qw(new load process purge);
our $VERSION = 1.0;

sub new {
	my $id      = shift;
	my $daemon  = shift;
	my $batchid = shift;

	my $t   = threads->self;
	my $tid = $t->tid;
	my %timers;

	my $this = {
		daemon   => $daemon,
		batchid  => $batchid,
		threadid => $tid,
		id       => $id,
		envfile  => $id . ".env",
		msgfile  => $id . ".msg",
		exim_id  => '',

		env_sender  => '',
		env_rcpt    => '',
		env_domain  => '',
		envtolocal  => '',
		bounce      => 0,
		bonced_add  => '',
		msg_from    => '',
		msg_date    => '',
		msg_subject => '',

		sc_nicebayes      => 0,
		sc_spamc          => 0,
		sc_newsl          => 0,
		sc_prerbls        => 0,
		sc_clamspam       => 0,
		sc_trustedsources => 0,
		sc_urirbls        => 0,
		sc_global         => 0,
		prefilters        => '',

		quarantined => 0,

		fullmsg     => '',
		fullheaders => '',
		fullbody    => '',

		%timers => (),
	};

	$this->{headers} = {};

	$this->{accounting} = undef;
	my $class = "MailScanner::Accounting";
	if (eval "require $class") {
		$this->{accounting} = MailScanner::Accounting::new('post');
	}

	if ( $this->{id} =~ m/^([A-Za-z0-9]{6}-[A-Za-z0-9]{6}-[A-Za-z0-9]{2})/ ) {
		$this->{exim_id} = $1;
	}
	bless $this, 'SpamHandler::Message';

	return $this;
}

sub load {
	my $this = shift;

	$this->startTimer('Message load');
	if ( -f $this->{envfile} ) {
		## open env file
		$this->startTimer('Message envelope load');
		$this->loadEnvFile();
		$this->endTimer('Message envelope load');
	}
	else {
		$this->{daemon}->doLog(
			$this->{batchid} . ": "
			  . $this->{id}
			  . " No enveloppe file found !",
			'spamhandler'
		);
		return 0;
	}

	if ( -f $this->{msgfile} ) {
		## open msg file
		$this->startTimer('Message body load');
		$this->loadMsgFile();
		$this->endTimer('Message body load');
	}
	else {
		$this->{daemon}->doLog(
			$this->{batchid} . ": " . $this->{id} . " No message file found !",
			'spamhandler'
		);
		return 0;
	}

	$this->{daemon}
	  ->doLog( $this->{batchid} . ": " . $this->{id} . " message loaded",
		'spamhandler', 'debug' );
	$this->endTimer('Message load');
}

sub process {
	my $this = shift;

	$this->startTimer('Message processing');
	my $email = Email::create( $this->{env_rcpt} );
	return 0 if !$email;

	my $status = '';

	## check what to do with message
	if ( defined($this->{accounting}) && !$this->{accounting}->checkCheckeableUser( $this->{env_rcpt} ) ) {
		$status = $this->{accounting}->getLastMessage();
		$this->manageUncheckeable($status);
	}
	else {
	        $this->startTimer('Message fetch prefs');
		my $delivery_type = int( $email->getPref( 'delivery_type', 1 ) );
		$this->endTimer('Message fetch prefs');
		$this->startTimer('Message fetch ww');
		my $whitelisted =
		  $email->hasInWhiteWarnList( 'whitelist', $this->{env_sender} );
		my $warnlisted =
		  $email->hasInWhiteWarnList( 'warnlist', $this->{env_sender} );
		my $blacklisted =
                  ($email->hasInWhiteWarnList( 'blacklist', $this->{env_sender} ) || $email->hasInWhiteWarnList( 'blacklist', $this->{msg_from} ));
		my @res_wnews = ('NOTIN','System','Domain','User');
		my $nwhitelisted =
		  $email->loadedIsWWListed( 'wnews', $this->{msg_from} );
		$this->endTimer('Message fetch ww');

		$this->{daemon}->doLog("Delivery_type: " . $delivery_type . " Blacklisted: " . $blacklisted);		
		## Black list
		if ($blacklisted) {
			$this->manageBlacklist($blacklisted);
			$status = "is blacklisted ($blacklisted)";
			if ( $delivery_type == 3 ) {
                                $status = " want drop";
                        }
                        elsif ( $delivery_type == 2 || $this->{bounce} ) {
                                $status = " want quarantine";
				if ( $this->{bounce} ) { $status = " (bounce)"; }
                                $this->quarantine();
                        }
			else {
				## The blacklisted mail is delivered with header X-MailCleaner-Status: blacklisted (level)
				$status = " want tag";
				$this->sendMeAnyway();
			}
		}
		elsif ($whitelisted) {
			$status = "is whitelisted ($whitelisted)";
			$this->manageWhitelist($whitelisted);
		}
		elsif ((int($this->{sc_newsl}) >= 5) && (int($this->{sc_global}) == 0) && (int($email->getPref('allow_newsletters')))) {
			$status = "is desired (Newsletter)";
			$this->manageWhitelist(3);
		}
		elsif ((int($this->{sc_newsl}) >= 5) && (int($this->{sc_global}) == 0) && ($nwhitelisted)) {
			$status = "is whitelisted by " . $res_wnews[$nwhitelisted] . " (Newsletter)";
			$this->manageWhitelist(3);			
		}
		else {
			if ( $delivery_type == 3 ) {
				$status = " want drop";
			}
			elsif ( $delivery_type == 2 && $warnlisted ) {
				$status = " is warnlisted ($warnlisted) ";
				$this->quarantine();
				my $id =
				  $email->sendWarnlistHit( $this->{env_sender}, $warnlisted,
					$this->{exim_id} );
				if ($id) {
					$this->{daemon}->doLog(
						$this->{batchid}
						  . ": message "
						  . $this->{exim_id}
						  . " warn message ready to be delivered with new id: "
						  . $id,
						'spamhandler', 'info'
					);
				}
				else {
					$this->{daemon}->doLog(
						$this->{batchid}
						  . ": message "
						  . $this->{exim_id}
						  . " warn message could not be delivered.",
						'spamhandler', 'error'
					);
				}
			}
			elsif ( $delivery_type == 2 || $this->{bounce} ) {
				$status = " want quarantine";
				if ( $this->{bounce} ) { $status = " (bounce)"; }
				$this->quarantine();
			}
			else {
				$status = " want tag";
				my $tag = $email->getPref( 'spam_tag', '{Spam?}' );
				$this->manageTagMode($tag);
			}
		}
	}
	
	## log status and finish
	$this->{daemon}->doLog(
		$this->{batchid}
		  . ": message "
		  . $this->{exim_id} . " R:<"
		  . $this->{env_rcpt} . "> S:<"
		  . $this->{env_sender}
		  . "> score: "
		  . $this->{sc_global}
		  . " status: "
		  . $status,
		'spamhandler'
	);
	$this->endTimer('Message processing');
	$this->startTimer('Message deleting');
	$this->deleteFiles();
	$this->endTimer('Message deleting');
	return 1;
}

sub loadEnvFile() {
	my $this = shift;

	open( ENV, $this->{envfile} ) or return 0;

	my $fromfound = 0;
	while (<ENV>) {
		if ( !$fromfound ) {
			$fromfound = 1;
			$this->{env_sender} = lc($_);
			chomp( $this->{env_sender} );
		}
		else {
                        if (/([^\/\s]+)/) { # untaint
			  $this->{env_rcpt} = lc($1);
			  chomp( $this->{env_rcpt} );
                        }
		}
	}
	close ENV;

	if ( $this->{env_rcpt} =~ m/^(\S+)@(\S+)$/ ) {
		$this->{env_tolocal} = $1;
		$this->{env_domain}  = $2;
	}
}

sub loadMsgFile() {
	my $this = shift;

	my $has_subject = 0;
	my $in_score    = 0;
	my $in_header   = 1;
	my $last_header = '';
	my $last_hvalue = '';
	my $uriscount   = 0;

	open( BODY, $this->{msgfile} ) or return 0;
	while (<BODY>) {

		## check for end of headers
		if ( $in_header && /^\s*$/ ) {
			$in_header = 0;
		}

		## parse for headers
		if ($in_header) {
			## found a new header
			if (/^([A-Za-z]\S+):\s*(.*)/) {
				$last_header = lc($1);
				$this->{headers}{$last_header} .= $2;
			}
			## found a new line in multi-line header
			if (/^\s+(.*)/) {
				$this->{headers}{$last_header} .= $1;
			}
			$this->{fullheaders} .= $_;
		}
		## parse for body
		else {
			$this->{fullbody} .= $_;

			## try to find bounced address if this is a bounce
			if ( $this->{bounce} > 0 ) {
				if (/^\s+\<?([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+)\>?\s+$/) {
					$this->{bounced_add} = $1;
				}
				elsif (
/Final-Recipient: rfc822; \<?([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+)\>?/
				  )
				{
					$this->{bounced_add} = $1;
				}
			}

			if (   $this->{daemon}->{reportrbls}
				&& $uriscount <= $this->{daemon}->{maxurisreports} )
			{
				my $uri =
				  $this->{daemon}->{dnslists}
				  ->findUri( $_, $this->{batchid} . ": " . $this->{id} );
				if ($uri) {
					$uriscount++;
					$uri = $uri . ".isspam";
					$this->{daemon}->{dnslists}->check_dns( $uri, 'URIRBL',
						$this->{batchid} . ": " . $this->{id} );
				}
				my $email =
				  $this->{daemon}->{dnslists}
				  ->findEmail( $_, $this->{batchid} . ": " . $this->{id} );
				if ($email) {
					$uriscount++;
					$email = $email . ".isspam";
					$this->{daemon}->{dnslists}->check_dns( $email, 'ERBL',
						$this->{batchid} . ": " . $this->{id} );
				}
			}

		}

		# store full message
		# $this->{fullmsg} .= $_;
	}
	close BODY;

	## check if message is a bounce
	if ( defined( $this->{headers}{'x-mailcleaner-bounce'} ) ) {
		$this->{bounce} = 1;
	}
	## check for standard (but untrusted) headers
	if ( defined( $this->{headers}{'from'} ) ) {
		$this->{msg_from} = $this->{headers}{'from'};
	}
	if ( defined( $this->{headers}{'date'} ) ) {
		$this->{msg_date} = $this->{headers}{'date'};
	}
	if ( defined( $this->{headers}{'subject'} ) ) {
		$this->{msg_subject} = $this->{headers}{'subject'};
	}

	$this->loadScores();
}

sub loadScores {
	my $this = shift;

	$this->{sc_global} = 0;

	if ( !defined( $this->{headers}{'x-mailcleaner-spamcheck'} ) ) {
		return 0;
	}

	my $line = $this->{headers}{'x-mailcleaner-spamcheck'};

	if ( $line =~ /NiceBayes \(([\d.]+%)\)/ ) {
		$this->{sc_nicebayes} = $1;
		$this->{sc_global} += 3;
		$this->{prefilters} .= ", NiceBayes";

		#print "FOUND NiceBayes: ".$this->{sc_nicebayes}."\n";
	}
        if ( $line =~ /(Commtouch|MessageSniffer) \([^)]+\)/ ) {
		if ($1 ne 'too big') {
	                $this->{sc_global} += 3;
		}
                $this->{prefilters} .= ", ".$1;
        }
	if ( $line =~ /PreRBLs \(([^\)]*)\)/ ) {
		my @rbls = split( ',', $1 );
		$this->{sc_prerbls} = @rbls;
		$this->{sc_global} += 2;    ## one rbl scores 2
		if ( $this->{sc_prerbls} > 1 ) {    ## two rbls scores 3
			$this->{sc_global}++;
		}
		if ( $this->{sc_prerbls} > 2 ) {    ## more rbls scores 4
			$this->{sc_global}++;
		}
		$this->{prefilters} .= ", PreRbls";

		#print "FOUND PreRBLS: ".$this->{sc_prerbls}."\n";
	}
	if ( $line =~ /Spamc \(score=([\d.]+)/ ) {
		$this->{sc_spamc} = $1;
		if ( int( $this->{sc_spamc} ) >= 5 )  { $this->{sc_global}++; }
		if ( int( $this->{sc_spamc} ) >= 7 )  { $this->{sc_global}++; }
		if ( int( $this->{sc_spamc} ) >= 10 ) { $this->{sc_global}++; }
		if ( int( $this->{sc_spamc} ) >= 15 ) { $this->{sc_global}++; }
		$this->{prefilters} .= ", SpamC";

		#print "FOUND SpamC: ".$this->{sc_spamc}."\n";
	}
	if ( $line =~ /Newsl \(score=([\d.]+)/ ) {
		$this->{sc_newsl} = $1;
		#if ( int( $this->{sc_newsl} ) >= 5 )  { $this->{sc_global} += 1; }
		#$this->{prefilters} .= ", Newsl";

		#print "FOUND Newsl: ".$this->{sc_newsl}."\n";
	}
	if ( $line =~ /ClamSpam \(([^\)]*)\)/ ) {
		$this->{sc_clamspam} = $1;
		if ($1 ne 'too big') {
			$this->{sc_global} += 4;
		}
		$this->{prefilters} .= ", ClamSpam";

		#print "FOUND ClamSpam: ".$this->{sc_clamspam}."\n";
	}
	if ( $line =~ /UriRBLs \(([^\)]*)\)/ ) {
		$this->{sc_urirbls} = $1;
		if ($1 ne 'too big') {
			$this->{sc_global} += 4;
		}
		$this->{prefilters} .= ", UriRBLs";

		#print "FOUND UriRBLs: ".$this->{sc_urirbls}."\n";
	}

	$this->{prefilters} =~ s/^\s*,\s*//;
	return 1;
}

sub deleteFiles {
	my $this = shift;

	unlink( $this->{envfile} );
	unlink( $this->{msgfile} );
	$this->{daemon}->deleteLock( $this->{id} );
	return 1;
}

sub purge {
	my $this = shift;

	delete( $this->{fullheaders} );
	delete( $this->{fullmsg} );
	delete( $this->{fullbody} );
	delete( $this->{headers} );
	foreach my $k ( keys %{$this} ) {
		$this->{$k} = '';
		delete $this->{$k};
	}
}

sub manageUncheckeable {
	my $this   = shift;
	my $status = shift;

	## modify the X-MailCleaner-SpamCheck header
	$this->{fullheaders} =~
s/X-MailCleaner-SpamCheck: [^\n]+(\r?\n\s+[^\n]+)*/X-MailCleaner-SpamCheck: cannot be checked against spam ($status)/mi;

	## remove the spam tag
	$this->{fullheaders} =~ s/Subject:\s+\{(MC_SPAM|MC_HIGHSPAM)\}/Subject:/i;

	$this->sendMeAnyway();
	return 1;
}

sub manageWhitelist {
	my $this       = shift;
	my $whitelevel = shift;

	my %level = ( 1 => 'system', 2 => 'domain', 3 => 'user' );
	my $str   = "whitelisted by " . $level{$whitelevel};

	## modify the X-MailCleaner-SpamCheck header
	$this->{fullheaders} =~
	  s/X-MailCleaner-SpamCheck: spam,/X-MailCleaner-SpamCheck: spam, $str,/i;

	## remove the spam tag
	$this->{fullheaders} =~ s/Subject:\s+\{(MC_SPAM|MC_HIGHSPAM)\}/Subject:/i;

	$this->sendMeAnyway();
	return 1;
}

sub manageBlacklist {
        my $this       = shift;
        my $blacklevel = shift;

        my %level = ( 1 => 'system', 2 => 'domain', 3 => 'user' );
        my $str   = "blacklisted by " . $level{$blacklevel};

        ## modify the X-MailCleaner-SpamCheck header
        $this->{fullheaders} =~
          s/X-MailCleaner-SpamCheck: spam,/X-MailCleaner-SpamCheck: spam, $str,/i;
	
        return 1;
}


sub manageTagMode {
	my $this = shift;
	my $tag  = shift;

	## change the spam tag
	$this->{fullheaders} =~
	  s/Subject:\s+\{(MC_SPAM|MC_HIGHSPAM)\}/Subject:$tag /i;

	$this->sendMeAnyway();
	return 1;
}

sub sendMeAnyway {
	my $this = shift;

	my $smtp;
	unless ( $smtp = Net::SMTP->new('localhost:2525') ) {
		$this->{daemon}->doLog(
			$this->{batchid}
			  . ": message "
			  . $this->{exim_id}
			  . " ** cannot connect to outgoing smtp server !",
			'spamhandler', 'error'
		);
		return 0;
	}
	my $err = 0;
	$err = $smtp->code();
	if ( $err < 200 || $err >= 500 ) {
		## smtpError
		return 0;
	}

	#$smtp->debug(3);
	if ( $this->{bounce} > 0 ) {
		$smtp->mail( $this->{bounced_add} );
	}
	else {
		$smtp->mail( $this->{env_sender} );
	}
	$err = $smtp->code();
	if ( $err < 200 || $err >= 500 ) {
		## smtpError
		return 0;
	}
	$smtp->to( $this->{env_rcpt} );
	$err = $smtp->code();
	if ( $err < 200 || $err >= 500 ) {
		## smtpError
		return;
	}
	$smtp->data();
	$err = $smtp->code();
	if ( $err < 200 || $err >= 500 ) {
		## smtpError
		return;
	}

	#print $this->getRawMessage();
	$smtp->datasend( $this->getRawMessage() );
	$err = $smtp->code();
	if ( $err < 200 || $err >= 500 ) {
		## smtpError
		return;
	}
	$smtp->dataend();
	$err = $smtp->code();
	if ( $err < 200 || $err >= 500 ) {
		## smtpError
		return;
	}
	my $returnmessage = $smtp->message();
	my $id            = 'unknown';
	if ( $returnmessage =~ m/id=(\S+)/ ) {
		$id = $1;
	}
	$this->{daemon}->doLog(
		$this->{batchid}
		  . ": message "
		  . $this->{exim_id}
		  . " ready to be delivered with new id: "
		  . $id,
		'spamhandler', 'info'
	);
	return 1;
}

sub getRawMessage {
	my $this = shift;

	my $msg = $this->{fullheaders};
	$msg .= "\n";
	$msg .= $this->{fullbody};

	return $msg;
}

sub quarantine {
	my $this = shift;

	$this->startTimer('Message quarantining');
	my $config = ReadConfig::getInstance();

	## remove the spam tag
	$this->{fullheaders} =~ s/Subject:\s+\{(MC_SPAM|MC_HIGHSPAM)\}/Subject:/i;
	if ( $this->{headers}{subject} ) {
		$this->{headers}{subject} =~ s/^\S*\{(MC_SPAM|MC_HIGHSPAM)\}//i;
	}
	else {
		$this->{headers}{subject} = "";
	}
	if ( !-d $config->getOption('VARDIR') . "/spam/" . $this->{env_domain} ) {
		mkdir( $config->getOption('VARDIR') . "/spam/" . $this->{env_domain} );
	}
	if (  !-d $config->getOption('VARDIR') . "/spam/"
		. $this->{env_domain} . "/"
		. $this->{env_rcpt} )
	{
		mkpath(  $config->getOption('VARDIR') . '/spam/'
			  . $this->{env_domain} . '/'
		          . $this->{env_rcpt} );
	}

	## save the spam file
	my $filename =
	    $config->getOption('VARDIR') . "/spam/"
	  . $this->{env_domain} . "/"
	  . $this->{env_rcpt} . "/"
	  . $this->{exim_id};
	if ( !open( MSGFILE, ">" . $filename ) ) {
		print " cannot open quarantine file $filename for writing";
		$this->{daemon}
		  ->doLog( "Cannot open quarantine file $filename for writing",
			'spamhandler', 'error' );
		return 0;
	}
	print MSGFILE $this->getRawMessage();
	close MSGFILE;

	$this->{quarantined} = 1;
	$this->endTimer('Message quarantining');

	#    my $hostid = $config->getOption('HOSTID');
	#	if ( $hostid < 0 ) {
	#		print " error, store id less than 0 ";
	#		return 0;
	#	}

#   my $logger = SpamLogger->new("Client", "etc/exim/spamlogger.conf");
#   my $res = $logger->logSpam($this->{exim_id}, $this->{env_tolocal}, $this->{env_domain}, $this->{env_sender}, $this->{headers}{subject}, $this->{sc_spamc}, $this->{sc_prerbls}, $this->{prefilters}, $this->{sc_global});
#   chomp($res);
#   if ($res !~ /LOGGED BOTH/) {
#     print " WARNING, logging is weird ($res)";
#   }
}

sub log {
	my $this      = shift;
	my $dbname    = shift;
	my $inmasterh = shift;

	return 1 if ( $this->{quarantined} < 1 );

	$this->startTimer('Message logging');
	my $loggedonce = 0;

	my %prepared = %{ $this->{daemon}->{prepared}{$dbname} };
	return 0 if ( !%prepared );

	## find out correct table
	my $table = "misc";
	if ( $this->{env_tolocal} =~ /^([a-z,A-Z])/ ) {
		$table = lc($1);
	}
	elsif ( $this->{env_tolocal} =~ /^[0-9]/ ) {
		$table = 'num';
	}
	my $p = $prepared{$table};
	if ( !$p ) {
		$this->{daemon}
		  ->doLog( "Error, could not get prepared statement for table: $table",
			'spamhandler', 'error' );
		return 0;
	}

	# Florian Billebault - Newsletter - 201512
	my $isNewsletter = 0;
	if ( int( $this->{sc_newsl} ) >= 5 )  { $isNewsletter = 1; }
	
	my $res = $p->execute(
		$this->{env_domain}, $this->{env_tolocal},
		$this->{env_sender}, $this->{exim_id},
		$this->{sc_spamc},   $this->{sc_prerbls},
		$this->{prefilters}, $this->{headers}{subject},
		$this->{sc_global},  $$inmasterh, $isNewsletter
	);
	if ( !$res ) {
		$this->{daemon}->doLog(
			"Error while logging msg "
			  . $this->{exim_id}
			  . " to db $dbname, retrying, if no further message, it's ok",
			'spamhandler', 'error'
		);
		$this->{daemon}->connectDatabases();
		## and try again
		$res = $p->execute(
			$this->{env_domain}, $this->{env_tolocal},
			$this->{env_sender}, $this->{exim_id},
			$this->{sc_spamc},   $this->{sc_prerbls},
			$this->{prefilters}, $this->{headers}{subject},
			$this->{sc_global},  $$inmasterh, $isNewsletter
		);

		if ( !$res ) {
			$this->{daemon}->doLog(
				"Error while executing log query (msgid="
				  . $this->{exim_id}
				  . ", db=$dbname): "
				  . $p->errstr,
				'spamhandler', 'error'
			);
			return 0;
		}
	}
	else {
		$loggedonce = 1;
	}
	$this->{daemon}->doLog(
		" Message " . $this->{exim_id} . " logged in database \"$dbname\"",
		'spamhandler', 'debug' );
	if ( $dbname eq 'realmaster' ) {
		$$inmasterh = 1;
	}
	$this->startTimer('Message logging');
	return $loggedonce;
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
	$this->{timers}{$timer} = 0;
	$this->{'timers'}{ 'd_' . $timer } = ( int( $interval * 10000 ) / 10000 );
}

sub getTimers {
	my $this = shift;
	return $this->{'timers'};
}
1;
