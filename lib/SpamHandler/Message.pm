#!/usr/bin/perl -w
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004-2014 Olivier Diserens <olivier@diserens.ch>
#   Copyright (C) 2015-2017 Florian Billebault <florian.billebault@gmail.com>
#   Copyright (C) 2015-2017 Mentor Reka <reka.mentor@gmail.com>
#   Copyright (C) 2020 John Mertz <git@john.me.tz>
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

package SpamHandler::Message;
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
        sc_spamc          => 'NULL',
        sc_newsl          => 0,
        sc_prerbls        => 0,
        sc_clamspam       => 0,
        sc_trustedsources => 0,
        sc_urirbls        => 0,
        sc_machinelearning=> 0,
        sc_global         => 0,
        prefilters        => '',

        decisive_module   => {
            module => undef,
            position => 100,
            action => undef
        },

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

    if ( $this->{id} =~ m/^([A-Za-z0-9]{6}-[A-Za-z0-9]{6,11}-[A-Za-z0-9]{2,4})/ ) {
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
            'spamhandler', 'debug'
        );
        return 0;
    }

    $this->{daemon}->doLog(
        $this->{batchid} . ": " . $this->{id} . " message loaded",
        'spamhandler', 'debug'
        );
    $this->endTimer('Message load');
}

sub process {
    my $this = shift;

    $this->startTimer('Message processing');
    my $email = Email::create( $this->{env_rcpt} );
    return 0 if !$email;

    my $status;

    ## check what to do with message
    
    # If uncheckable (consumed licenses, etc. don't filter
    if ( defined($this->{accounting}) && !$this->{accounting}->checkCheckeableUser( $this->{env_rcpt} ) ) {
        $status = $this->{accounting}->getLastMessage();
        $this->manageUncheckeable($status);

    # Otherwise get policies and determine action
    } else {
        $this->startTimer('Message fetch prefs');
        my $delivery_type = int( $email->getPref( 'delivery_type', 1 ) );
        $this->endTimer('Message fetch prefs');
        $this->startTimer('Message fetch ww');

        # Policies
        my $whitelisted;
	# if the flag file to activate whitelist also on msg_from is there
	if ( -e '/var/mailcleaner/spool/mailcleaner/mc-wl-on-both-from') {
		$whitelisted = (
	   	  $email->hasInWhiteWarnList( 'whitelist', $this->{env_sender} ) ||
		  $email->hasInWhiteWarnList( 'whitelist', $this->{msg_from} )
		);
	# else whitelists are only applied to SMTP From
	} else {
		$whitelisted = $email->hasInWhiteWarnList( 'whitelist', $this->{env_sender} );
	}
        my $warnlisted;
	if ( -e '/var/mailcleaner/spool/mailcleaner/mc-wl-on-both-from') {
		$warnlisted = (
		  $email->hasInWhiteWarnList( 'warnlist', $this->{env_sender} ) ||
		  $email->hasInWhiteWarnList( 'warnlist', $this->{msg_from} )
		);
	} else {
		$warnlisted = $email->hasInWhiteWarnList( 'warnlist', $this->{env_sender} );
	}
        my $blacklisted =
            $email->hasInWhiteWarnList( 'blacklist', $this->{env_sender} ) || $email->hasInWhiteWarnList( 'blacklist', $this->{msg_from});
        my @level = ('NOTIN','System','Domain','User');
        $this->{nwhitelisted} =
            $email->loadedIsWWListed( 'wnews', $this->{msg_from} ) || $email->loadedIsWWListed( 'wnews', $this->{env_sender} );
        $this->{news_allowed} = $email->getPref('allow_newsletters') || 0;
        $this->endTimer('Message fetch ww');

        # Action

        ## Whitelist
        if ($whitelisted) {

            ## Newsletter
            if ( $this->{sc_newsl} >= 5 ) {
                if ($this->{news_allowed}) {
                    $status = "is desired (Newsletter) and whitelisted by " . $level[$whitelisted];
                    $this->{decisive_module}{module} = undef;
                    $this->manageWhitelist($whitelisted,3);
                } elsif ($this->{nwhitelisted}) {
                    $status = "is newslisted by " . $level[$this->{nwhitelisted}] . " and whitelisted by " . $level[$whitelisted];
                    $this->{decisive_module}{module} = undef;
                    $this->manageWhitelist($whitelisted,$this->{nwhitelisted});
                } else {
                    $status = "is whitelisted by " . $level[$whitelisted] . " but newsletter";
                    $this->{decisive_module}{module} = 'Newsl';
                    if ( $delivery_type == 1 ) {
                        $status .= ": want tag";
                        $this->{fullheaders} =~
                            s/(.*Subject:\s+)(\{(MC_SPAM|MC_HIGHSPAM)\})?(.*)/$1\{MC_SPAM\}$4/i;
                        my $tag = $email->getPref( 'spam_tag', '{Spam?}' );
                        $this->manageTagMode($tag);
                    } elsif ( $warnlisted ) {
                        $status .= ": warn";
                        $this->{decisive_module}{module} = 'warnlisted';
                        $this->quarantine();
                        my $id =
                            $email->sendWarnlistHit( $this->{env_sender}, $warnlisted, $this->{exim_id} );
                        if ($id) {
                            $this->{daemon}->doLog(
                                $this->{batchid}
                                    . ": message "
                                    . $this->{exim_id}
                                    . " warn message ready to be delivered with new id: "
                                    . $id,
                                'spamhandler', 'info'
                            );
                        } else {
                            $this->{daemon}->doLog(
                                $this->{batchid}
                                    . ": message "
                                    . $this->{exim_id}
                                    . " warn message could not be delivered.",
                                'spamhandler', 'error'
                            );
                        }
                    } elsif ( $delivery_type == 3 ) {
                        $status .= ": want drop";
                    } elsif ( $this->{bounce} ) {
                        $status .= ": (bounce)";
                        $this->quarantine();
                    } else {
                        $status .= ": want quarantine";
                        $this->quarantine();
                    }
                }

            ## Not Newsletter
            } else {
                $status = "is whitelisted ($whitelisted)";
                $this->{decisive_module}{module} = undef;
                $this->manageWhitelist($whitelisted);
            }
        ## Blacklist
	} elsif ($blacklisted) {
            $status = "is blacklisted ($blacklisted)";
            $this->manageBlacklist($blacklisted);
            $this->{decisive_module}{module} = 'blacklisted';
            if ($delivery_type == 1) {
                $status .= ": want tag";
                my $tag = $email->getPref( 'spam_tag', '{Spam?}' );
                $this->manageTagMode($tag);
            } elsif ( $warnlisted ) {
                $status .= ": warn";
                $this->{decisive_module}{module} = 'warnlisted';
                $this->quarantine();
                my $id =
                    $email->sendWarnlistHit( $this->{env_sender}, $warnlisted, $this->{exim_id} );
                if ($id) {
                    $this->{daemon}->doLog(
                        $this->{batchid}
                            . ": message "
                            . $this->{exim_id}
                            . " warn message ready to be delivered with new id: "
                            . $id,
                        'spamhandler', 'info'
                    );
                } else {
                    $this->{daemon}->doLog(
                        $this->{batchid}
                            . ": message "
                            . $this->{exim_id}
                            . " warn message could not be delivered.",
                        'spamhandler', 'error'
                    );
                }
            } elsif ( $delivery_type == 3 ) {
                $status .= ": want drop";
            } elsif ( $this->{bounce} ) {
                $status .= ": (bounce)";
                $this->quarantine();
            } else {
                $status .= ": want quarantine";
                $this->quarantine();
            }


        ## Spam
        } elsif ( defined $this->{decisive_module}{module} && $this->{decisive_module}{action} eq 'positive' ) {

            # Is spam, and no warnlist
            $status = "is spam";
            if ( $delivery_type == 1 ) {
                $status .= ": want tag";
                my $tag = $email->getPref( 'spam_tag', '{Spam?}' );
                $this->manageTagMode($tag);
            } elsif ( $warnlisted ) {
                $status .= ": warn";
                $this->{decisive_module}{module} = 'warnlisted';
                $this->quarantine();
                my $id =
                    $email->sendWarnlistHit( $this->{env_sender}, $warnlisted, $this->{exim_id} );
                if ($id) {
                    $this->{daemon}->doLog(
                        $this->{batchid}
                            . ": message "
                            . $this->{exim_id}
                            . " warn message ready to be delivered with new id: "
                            . $id,
                        'spamhandler', 'info'
                    );
                } else {
                    $this->{daemon}->doLog(
                        $this->{batchid}
                            . ": message "
                            . $this->{exim_id}
                            . " warn message could not be delivered.",
                        'spamhandler', 'error'
                    );
                }
            } elsif ( $delivery_type == 3 ) {
                $status .= ": want drop";
            } elsif ( $this->{bounce} ) {
                $status .= ": (bounce)";
                $this->quarantine();
            } else {
                $status .= ": want quarantine";
                $this->quarantine();
            }
    
        ## Newsletter
        } elsif ($this->{sc_newsl} >= 5 ) {
            if ($email->getPref('allow_newsletters')) {
                $status = "is desired (Newsletter)";
                $this->{decisive_module}{module} = undef;
                $this->manageWhitelist(undef,3);
            } elsif ($this->{nwhitelisted}) {
                $status = "is newslisted by " . $level[$this->{nwhitelisted}];
                $this->{decisive_module}{module} = undef;
                $this->manageWhitelist(undef,$this->{nwhitelisted});
            } else {
                $status = "is newsletter";
                $this->{decisive_module}{module} = 'Newsl';
                if ( $delivery_type == 1 ) {
                    $status .= ": want tag";
                    $this->{fullheaders} =~
                        s/(.*Subject:\s+)(\{(MC_SPAM|MC_HIGHSPAM)\})?(.*)/$1\{MC_SPAM\}$4/i;
                    my $tag = $email->getPref( 'spam_tag', '{Spam?}' );
                    $this->manageTagMode($tag);
                } elsif ( $warnlisted ) {
                    $status .= ": warn";
                    $this->{decisive_module}{module} = 'warnlisted';
                    $this->quarantine();
                    my $id =
                        $email->sendWarnlistHit( $this->{env_sender}, $warnlisted, $this->{exim_id} );
                    if ($id) {
                        $this->{daemon}->doLog(
                            $this->{batchid}
                                . ": message "
                                . $this->{exim_id}
                                . " warn message ready to be delivered with new id: "
                                . $id,
                            'spamhandler', 'info'
                        );
                    } else {
                        $this->{daemon}->doLog(
                            $this->{batchid}
                                . ": message "
                                . $this->{exim_id}
                                . " warn message could not be delivered.",
                            'spamhandler', 'error'
                        );
                    }
                } elsif ( $delivery_type == 3 ) {
                    $status .= ": want drop";
                } elsif ( $this->{bounce} ) {
                    $status .= ": (bounce)";
                    $this->quarantine();
                } else {
                    $status .= ": want quarantine";
                    $this->quarantine();
                }
            }

        # Neither newsletter, nor spam
        } else {
            $status = 'not spam';
            unless ($this->{decisive_module}{action} == 'negative') {
                $this->{decisive_module}{module} = undef;
            }
            $this->{fullheaders} =~ s/Subject:\s+\{(MC_SPAM|MC_HIGHSPAM)\}/Subject:/i;
            $this->sendMeAnyway();
        }
    }

    my $log = $this->{batchid}
        . ": message "
        . $this->{exim_id} . " R:<"
        . $this->{env_rcpt} . "> S:<"
        . $this->{env_sender}
        . "> status "
        . $status;
    if (defined $this->{decisive_module}{module}) {
        $log .= ", module: ".$this->{decisive_module}{module};
    }
    ## log status and finish
    $this->{daemon}->doLog($log, 'spamhandler', 'info');
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
        } else {
            if (/([^\s]+)/) { # untaint
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
                } elsif (/Final-Recipient: rfc822; \<?([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+)\>?/) {
                    $this->{bounced_add} = $1;
                }
            }

            if ( $this->{daemon}->{reportrbls} && $uriscount <= $this->{daemon}->{maxurisreports} ) {
                my $uri = $this->{daemon}->{dnslists}->findUri( $_, $this->{batchid} . ": " . $this->{id} );
                if ($uri) {
                    $uriscount++;
                    $uri = $uri . ".isspam";
                    $this->{daemon}->{dnslists}->check_dns( $uri, 'URIRBL',
                    $this->{batchid} . ": " . $this->{id} );
                }
                my $email = $this->{daemon}->{dnslists}->findEmail( $_, $this->{batchid} . ": " . $this->{id} );
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
	if ( $this->{headers}{'from'} =~ m/<.*>/ ) {
		$this->{msg_from} = $this->{headers}{'from'};
		$this->{msg_from} =~ s/.*<([^>]*)>/$1/;
	} else {
		$this->{msg_from} = $this->{headers}{'from'};
	}
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
    my $line;

    if ( !defined( $this->{headers}{'x-mailcleaner-spamcheck'} ) ) {
        %{$this->{decisive_module}} = (
            'module' => 'NOHEADER',
            'position' => 0,
            'action' => 'negative'
        );
        $this->{daemon}->doLog(
        $this->{batchid} . ": " . $this->{id} . " no spamcheck header",
            'spamhandler', 'warn'
        );
        return 0;
    }

    if ( defined( $this->{headers}{'x-mailcleaner-status'} ) ) {
        if ( $this->{headers}{'x-mailcleaner-status'} =~ /Blacklisted/ ) {
            $this->{prefilters} .= ", Blacklist";
        }
    }

    $line = $this->{headers}{'x-mailcleaner-spamcheck'};
    $this->{daemon}->doLog(
        $this->{batchid} . ": " . $this->{id} . " Processing spamcheck header: " . $line,
        'spamhandler', 'info'
    );

    if ( $line =~ /.*Newsl \(score=(\d+\.\d+),.*/ ) {
        $this->{sc_newsl} = $1;
        # Not processed as decisive module
        if ( $this->{sc_newsl} >= 5 )  {
            $this->{sc_global} += 1; 
            $this->{prefilters} .= ", Newsl";
        }
    }

    if ( $line =~ /.*TrustedSources \(.*/ ) {
        $this->decisiveModule('TrustedSources',$line);
        $this->{prefilters} .= ", TrustedSources";
    }

    if ( $line =~ /.*NiceBayes \(([\d.]+)%.*/ ) {
        $this->{sc_nicebayes} = $1;
        $this->decisiveModule('NiceBayes',$line);
        $this->{sc_global} += 3;
        $this->{prefilters} .= ", NiceBayes";
    }

    if ( $line =~ /.*(Commtouch|MessageSniffer) \(([^\)]*)/ ) {
        if ($2 ne 'too big' && $2 !~ m/^0 \-.*/) {
            $this->decisiveModule($1,$line);
            $this->{sc_global} += 3;
            $this->{prefilters} .= ", ".$1;
        }
    }

    if ( $line =~ /.*PreRBLs \(([^\)]*), ?position/ ) {
        my $rbls = scalar(split( ',', $1 ));
        $this->{sc_prerbls} = $rbls;
        $this->decisiveModule('PreRBLs',$line);
        $this->{sc_global} += $rbls + 1;
        $this->{prefilters} .= ", PreRBLs";
    }

    if ( $line =~ /.*UriRBLs \(([^\)]*), ?position/ ) {
        my $rbls = scalar(split( ',', $1 ));
        $this->{sc_urirbls} = $rbls;
        $this->decisiveModule('UriRBLs',$line);
        $this->{sc_global} += $rbls + 1;
        $this->{prefilters} .= ", UriRBLs";
    }

    if ( $line =~ /.*Spamc \(score=(\d+\.\d+),([^\)]*)\)/ ) {
        unless ($2 =~ m/, NONE,/) {
            $this->{sc_spamc} = $1;
            $this->decisiveModule('Spamc',$line);
            if ( int( $this->{sc_spamc} ) >= 5 )  {
                $this->{sc_global}++;
                $this->{prefilters} .= ", SpamC";
            }
            if ( int( $this->{sc_spamc} ) >= 7 )  { $this->{sc_global}++; }
            if ( int( $this->{sc_spamc} ) >= 10 ) { $this->{sc_global}++; }
            if ( int( $this->{sc_spamc} ) >= 15 ) { $this->{sc_global}++; }
        }
    }

    if ( $line =~ /.*ClamSpam \(([^,]*),/ ) {
        $this->decisiveModule('ClamSpam',$line);
        $this->{sc_clamspam} = $1;
        if ($1 ne 'too big') {
            $this->{sc_global} += 4;
        }
        $this->{prefilters} .= ", ClamSpam";
    }

    if ( $line =~ /.*MachineLearning \((not applied \()?([\d.]+)%.*/ ) {
        $this->decisiveModule('MachineLearning',$line);
        $this->{sc_machinelearning} = $2;
        $this->{prefilters} .= ", MachineLearning";
    }

    if ( $line =~ /spam, / && !defined($this->{decisive_module}->{module}) ) {
        %{$this->{decisive_module}} = (
            'module' => 'Unknown',
            'position' => 0,
            'action' => 'positive'
        );
        $this->{prefilters} .= ", Unknown";
        $this->{daemon}->doLog(
            "$this->{exim_id} Flagged as spam, but unable to parse a recognized module: '$line', must quarantine to prevent loop at Stage 4.",
            'spamhandler', 'error'
        );
    }

    $this->{prefilters} =~ s/^, //;

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
    my $newslevel  = shift || undef;

    my %level = ( 1 => 'system', 2 => 'domain', 3 => 'user' );
    my $str;
    if (defined($whitelevel)) {
        $str = "whitelisted by " . $level{$whitelevel};
        if (defined($newslevel)) {
            $str .= " and newslisted by " . $level{$newslevel};
        }
    } elsif (defined($newslevel)) {
        $str = "newslisted by " . $level{$newslevel};
    }

    ## modify the X-MailCleaner-SpamCheck header
    $this->{fullheaders} =~
        s/X-MailCleaner-SpamCheck: ([^,]*),/X-MailCleaner-SpamCheck: not spam, $str,/i;

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

    $this->{fullheaders} =~
        s/(.*Subject:\s+)(\{(MC_SPAM|MC_HIGHSPAM)\})?(.*)/$1\{MC_SPAM\}$4/i;
    
    ## modify the X-MailCleaner-SpamCheck header
    $this->{fullheaders} =~
        s/X-MailCleaner-SpamCheck: ([^,]*),/X-MailCleaner-SpamCheck: spam, $str,/i;
    
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

    $this->{daemon}->doLog(
        $this->{batchid}
            . ": message "
            . $this->{exim_id}
            . " Message will be delivered using SendMeAnyway",
        'spamhandler', 'info'
    );

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
    } else {
        $smtp->mail( $this->{env_sender} );
    }
    $err = $smtp->code();
    if ( $err < 200 || $err >= 500 ) {
        ## smtpError
        $this->{daemon}->doLog(
            $this->{batchid}
                . ": message "
                . $this->{exim_id}
                . " Could not set MAIL FROM",
            'spamhandler', 'error'
        );
        return 0;
    }
    $smtp->to( $this->{env_rcpt} );
    $err = $smtp->code();
    if ( $err < 200 || $err >= 500 ) {
        ## smtpError
        $this->{daemon}->doLog(
            $this->{batchid}
                . ": message "
                . $this->{exim_id}
                . " Could not set RCPT TO",
            'spamhandler', 'error'
        );
        return;
    }
    $smtp->data();
    $err = $smtp->code();
    if ( $err < 200 || $err >= 500 ) {
        ## smtpError
    
        $this->{daemon}->doLog(
            $this->{batchid}
              . ": message "
              . $this->{exim_id}
              . " Could not set DATA",
            'spamhandler', 'error'
        );
        return;
    }

    #print $this->getRawMessage();
    $smtp->datasend( $this->getRawMessage() );
    $err = $smtp->code();
    if ( $err < 200 || $err >= 500 ) {
        ## smtpError
        $this->{daemon}->doLog(
            $this->{batchid}
                . ": message "
                . $this->{exim_id}
                . " Could not set DATA content",
            'spamhandler', 'error'
        );
        return;
    }
    $smtp->dataend();
    $err = $smtp->code();
    if ( $err < 200 || $err >= 500 ) {
        ## smtpError
        $this->{daemon}->doLog(
            $this->{batchid}
                . ": message "
                . $this->{exim_id}
                . " Could not set end of DATA (.)",
            'spamhandler', 'error'
        );
        return;
    }
    my $returnmessage = $smtp->message();
    my $id            = 'unknown';
    if ( $returnmessage =~ m/id=(\S+)/ ) {
        $id = $1;
    }

    if ($id == 'unknown') {
        $this->{daemon}->doLog(
            $this->{batchid}
                . ": message "
                . $this->{exim_id}
                . " Could not deliver the classical way, had to force the dataend, cause was :"
                . $returnmessage,
            'spamhandler', 'info'
        );

            $smtp->rawdatasend('\n.\n');
               $smtp->dataend();

            $err = $smtp->code();
        if ( $err < 200 || $err >= 500 ) {
                    ## smtpError
            $this->{daemon}->doLog(
                $this->{batchid}
                    . ": message "
                    . $this->{exim_id}
                    . " Could not deliver the classical way, had to force the dataend ",
                'spamhandler', 'error'
            );
            return;
        }
        $returnmessage = $smtp->message();
        if ( $returnmessage =~ m/id=(\S+)/ ) {
            $id = $1;
        }
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
        mkpath(
            $config->getOption('VARDIR') . '/spam/'
                . $this->{env_domain} . '/'
                . $this->{env_rcpt},
            {error => \my $err}
        );
        if ($err && @$err) {
            for my $diag (@$err) {
                my ($file, $message) = %$diag;
                if ($file eq '') {
                    $this->{daemon}->doLog('Batch : ' .$this->{batchid} . ' ; message : ' . $this->{exim_id} . " => general error: $message", 'spamhandler' );
                } else {
                    $this->{daemon}->doLog('Batch : ' .$this->{batchid} . ' ; message : ' . $this->{exim_id} . " problem creating $file: $message", 'spamhandler' );
                }
            }
            exit 0;
        }
    }

    ## save the spam file
    my $filename =
        $config->getOption('VARDIR') . "/spam/"
            . $this->{env_domain} . "/"
            . $this->{env_rcpt} . "/"
            . $this->{exim_id};

    if ( !open( MSGFILE, ">" . $filename ) ) {
        print " cannot open quarantine file $filename for writing";
        $this->{daemon}->doLog(
            "Cannot open quarantine file $filename for writing",
            'spamhandler', 'error'
        );
        return 0;
    }
    print MSGFILE $this->getRawMessage();
    close MSGFILE;

    $this->{quarantined} = 1;
    $this->endTimer('Message quarantining');

#    my $hostid = $config->getOption('HOSTID');
#    if ( $hostid < 0 ) {
#        print " error, store id less than 0 ";
#        return 0;
#    }

#    my $logger = SpamLogger->new("Client", "etc/exim/spamlogger.conf");
#    my $res = $logger->logSpam($this->{exim_id}, $this->{env_tolocal}, $this->{env_domain}, $this->{env_sender}, $this->{headers}{subject}, $this->{sc_spamc}, $this->{sc_prerbls}, $this->{prefilters}, $this->{sc_global});
#    chomp($res);
#    if ($res !~ /LOGGED BOTH/) {
#        print " WARNING, logging is weird ($res)";
#    }

    return 1;
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
    } elsif ( $this->{env_tolocal} =~ /^[0-9]/ ) {
        $table = 'num';
    }
    my $p = $prepared{$table};
    if ( !$p ) {
        $this->{daemon}
          ->doLog( "Error, could not get prepared statement for table: $table",
            'spamhandler', 'error' );
        return 0;
    }

    my $isNewsletter = ( $this->{sc_newsl} >= 5 && !$this->{nwhitelisted} && !$this->{news_allowed}) || 0;
    
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
    } else {
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

sub decisiveModule {
    my $this = shift;
    my ($module, $line) = @_;
    
    $line =~ s/.*$module \((.*)/$1/;
    $line =~ s/decisive\).*/decisive/;
    my $position = my $decisive = $line;
    $decisive =~ s/.*, ?([^ ]*) decisive.*/$1/;
    $position =~ s/.*, ?position ?: ?(\d+).*/$1/;
    $this->{daemon}->doLog('Current decisive module is "'.$this->{decisive_module}{'module'}.'" with action "'.$this->{decisive_module}{'action'}.'" and position "'.$this->{decisive_module}{'position'}.'"','spamhandler', 'debug');
    if (!defined $decisive || !defined $position) {
        $this->{daemon}->doLog("Failed to discover decisive or position value for $module: $line", 'spamhandler', 'debug');
        return 0;
    }
    if ($position >= $this->{decisive_module}{position}) {
        $this->{daemon}->doLog("Found $module of lower priority $position, not updating decisive_module", 'spamhandler', 'debug');
    # If there is two modules of the same position (this would be a bug), then prefer the spam
    } elsif ( ($position == $this->{decisive_module}{position}) && ($decisive eq 'spam') ) {
        $this->{daemon}->doLog("Found positively decisive module $module of equal priority $position, updating decisive_module", 'spamhandler', 'debug');
        %{$this->{decisive_module}} = (
            'module' => $module,
            'position' => $position,
            'action' => 'positive'
        );
    } elsif ($decisive eq 'not') {
        $this->{daemon}->doLog("Found undecisive $module of priority $position, not updating decisive_module", 'spamhandler', 'debug');
    } elsif ($decisive eq 'spam') {
        $this->{daemon}->doLog("Updating decisive_module $module $position positive", 'spamhandler', 'debug');
        %{$this->{decisive_module}} = (
            'module' => $module,
            'position' => $position,
            'action' => 'positive'
        );
    } elsif ($decisive eq 'ham') {
        $this->{daemon}->doLog("Updating decisive_module $module $position negative", 'spamhandler', 'debug');
        %{$this->{decisive_module}} = (
            'module' => $module,
            'position' => $position,
            'action' => 'negative'
        );
    } else {
        $this->{daemon}->doLog("Found $module with unrecognized decisive value '$decisive', not updating decisive_module", 'spamhandler', 'debug');
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
    $this->{timers}{$timer} = 0;
    $this->{'timers'}{ 'd_' . $timer } = ( int( $interval * 10000 ) / 10000 );
}

sub getTimers {
    my $this = shift;
    return $this->{'timers'};
}

1;
