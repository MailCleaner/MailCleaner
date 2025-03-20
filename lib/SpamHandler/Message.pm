#!/usr/bin/env perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004-2014 Olivier Diserens <olivier@diserens.ch>
#   Copyright (C) 2015-2017 Florian Billebault <florian.billebault@gmail.com>
#   Copyright (C) 2015-2017 Mentor Reka <reka.mentor@gmail.com>
#   Copyright (C) 2025 John Mertz <git@john.me.tz>
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

package SpamHandler::Message;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;
require Email;
require ReadConfig;
require Net::SMTP;
use File::Path qw(mkpath);
use Time::HiRes qw(gettimeofday tv_interval);

use threads;

our @ISA     = qw(Exporter);
our @EXPORT  = qw(new load process purge);
our $VERSION = 1.0;

sub new($id,$daemon,$batchid)
{
    my $t   = threads->self;
    my $tid = $t->tid;
    my %timers;

    my $self = {
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

    $self->{headers} = {};

    $self->{accounting} = undef;
    my $class = "MailScanner::Accounting";
    if (eval "require $class") {
        $self->{accounting} = MailScanner::Accounting::new('post');
    }

    if ( $self->{id} =~ m/^([A-Za-z0-9]{6}-[A-Za-z0-9]{6,11}-[A-Za-z0-9]{2,4})/ ) {
        $self->{exim_id} = $1;
    }
    bless $self, 'SpamHandler::Message';

    return $self;
}

sub load($self)
{
    $self->startTimer('Message load');
    if ( -f $self->{envfile} ) {
        ## open env file
        $self->startTimer('Message envelope load');
        $self->loadEnvFile();
        $self->endTimer('Message envelope load');
    } else {
        $self->{daemon}->doLog(
            $self->{batchid} . ": "
                . $self->{id}
                . " No enveloppe file found !",
            'spamhandler'
        );
        return 0;
    }

    if ( -f $self->{msgfile} ) {
        ## open msg file
        $self->startTimer('Message body load');
        $self->loadMsgFile();
        $self->endTimer('Message body load');
    } else {
        $self->{daemon}->doLog(
            $self->{batchid} . ": " . $self->{id} . " No message file found !",
            'spamhandler', 'debug'
        );
        return 0;
    }

    $self->{daemon}->doLog(
        $self->{batchid} . ": " . $self->{id} . " message loaded",
        'spamhandler', 'debug'
        );
    $self->endTimer('Message load');
}

sub process($self)
{
    $self->startTimer('Message processing');
    my $email = Email::create( $self->{env_rcpt} );
    return 0 if !$email;

    my $status;

    ## check what to do with message

    # If uncheckable (consumed licenses, etc. don't filter
    if ( defined($self->{accounting}) && !$self->{accounting}->checkCheckeableUser( $self->{env_rcpt} ) ) {
        $status = $self->{accounting}->getLastMessage();
        $self->manageUncheckeable($status);

    # Otherwise get policies and determine action
    } else {
        $self->startTimer('Message fetch prefs');
        my $delivery_type = int( $email->getPref( 'delivery_type', 1 ) );
        $self->endTimer('Message fetch prefs');
        $self->startTimer('Message fetch ww');

        # Policies
        my $whitelisted;
	# if the flag file to activate whitelist also on msg_from is there
	if ( -e '/var/mailcleaner/spool/mailcleaner/mc-wl-on-both-from') {
		$whitelisted = (
	   	  $email->hasInWhiteWarnList( 'whitelist', $self->{env_sender} ) ||
		  $email->hasInWhiteWarnList( 'whitelist', $self->{msg_from} )
		);
	# else whitelists are only applied to SMTP From
	} else {
		$whitelisted = $email->hasInWhiteWarnList( 'whitelist', $self->{env_sender} );
	}
        my $warnlisted;
	if ( -e '/var/mailcleaner/spool/mailcleaner/mc-wl-on-both-from') {
		$warnlisted = (
		  $email->hasInWhiteWarnList( 'warnlist', $self->{env_sender} ) ||
		  $email->hasInWhiteWarnList( 'warnlist', $self->{msg_from} )
		);
	} else {
		$warnlisted = $email->hasInWhiteWarnList( 'warnlist', $self->{env_sender} );
	}
        my $blacklisted =
            $email->hasInWhiteWarnList( 'blacklist', $self->{env_sender} ) || $email->hasInWhiteWarnList( 'blacklist', $self->{msg_from});
        my @level = ('NOTIN','System','Domain','User');
        $self->{nwhitelisted} =
            $email->loadedIsWWListed( 'wnews', $self->{msg_from} ) || $email->loadedIsWWListed( 'wnews', $self->{env_sender} );
        $self->{news_allowed} = $email->getPref('allow_newsletters') || 0;
        $self->endTimer('Message fetch ww');

        # Action

        ## Whitelist
        if ($whitelisted) {

            ## Newsletter
            if ( $self->{sc_newsl} >= 5 ) {
                if ($self->{news_allowed}) {
                    $status = "is desired (Newsletter) and whitelisted by " . $level[$whitelisted];
                    $self->{decisive_module}{module} = undef;
                    $self->manageWhitelist($whitelisted,3);
                } elsif ($self->{nwhitelisted}) {
                    $status = "is newslisted by " . $level[$self->{nwhitelisted}] . " and whitelisted by " . $level[$whitelisted];
                    $self->{decisive_module}{module} = undef;
                    $self->manageWhitelist($whitelisted,$self->{nwhitelisted});
                } else {
                    $status = "is whitelisted by " . $level[$whitelisted] . " but newsletter";
                    $self->{decisive_module}{module} = 'Newsl';
                    if ( $delivery_type == 1 ) {
                        $status .= ": want tag";
                        $self->{fullheaders} =~
                            s/(.*Subject:\s+)(\{(MC_SPAM|MC_HIGHSPAM)\})?(.*)/$1\{MC_SPAM\}$4/i;
                        my $tag = $email->getPref( 'spam_tag', '{Spam?}' );
                        $self->manageTagMode($tag);
                    } elsif ( $warnlisted ) {
                        $status .= ": warn";
                        $self->{decisive_module}{module} = 'warnlisted';
                        $self->quarantine();
                        my $id =
                            $email->sendWarnlistHit( $self->{env_sender}, $warnlisted, $self->{exim_id} );
                        if ($id) {
                            $self->{daemon}->doLog(
                                $self->{batchid}
                                    . ": message "
                                    . $self->{exim_id}
                                    . " warn message ready to be delivered with new id: "
                                    . $id,
                                'spamhandler', 'info'
                            );
                        } else {
                            $self->{daemon}->doLog(
                                $self->{batchid}
                                    . ": message "
                                    . $self->{exim_id}
                                    . " warn message could not be delivered.",
                                'spamhandler', 'error'
                            );
                        }
                    } elsif ( $delivery_type == 3 ) {
                        $status .= ": want drop";
                    } elsif ( $self->{bounce} ) {
                        $status .= ": (bounce)";
                        $self->quarantine();
                    } else {
                        $status .= ": want quarantine";
                        $self->quarantine();
                    }
                }

            ## Not Newsletter
            } else {
                $status = "is whitelisted ($whitelisted)";
                $self->{decisive_module}{module} = undef;
                $self->manageWhitelist($whitelisted);
            }
        ## Blacklist
	} elsif ($blacklisted) {
            $status = "is blacklisted ($blacklisted)";
            $self->manageBlacklist($blacklisted);
            $self->{decisive_module}{module} = 'blacklisted';
            if ($delivery_type == 1) {
                $status .= ": want tag";
                my $tag = $email->getPref( 'spam_tag', '{Spam?}' );
                $self->manageTagMode($tag);
            } elsif ( $warnlisted ) {
                $status .= ": warn";
                $self->{decisive_module}{module} = 'warnlisted';
                $self->quarantine();
                my $id =
                    $email->sendWarnlistHit( $self->{env_sender}, $warnlisted, $self->{exim_id} );
                if ($id) {
                    $self->{daemon}->doLog(
                        $self->{batchid}
                            . ": message "
                            . $self->{exim_id}
                            . " warn message ready to be delivered with new id: "
                            . $id,
                        'spamhandler', 'info'
                    );
                } else {
                    $self->{daemon}->doLog(
                        $self->{batchid}
                            . ": message "
                            . $self->{exim_id}
                            . " warn message could not be delivered.",
                        'spamhandler', 'error'
                    );
                }
            } elsif ( $delivery_type == 3 ) {
                $status .= ": want drop";
            } elsif ( $self->{bounce} ) {
                $status .= ": (bounce)";
                $self->quarantine();
            } else {
                $status .= ": want quarantine";
                $self->quarantine();
            }


        ## Spam
        } elsif ( defined $self->{decisive_module}{module} && $self->{decisive_module}{action} eq 'positive' ) {

            # Is spam, and no warnlist
            $status = "is spam";
            if ( $delivery_type == 1 ) {
                $status .= ": want tag";
                my $tag = $email->getPref( 'spam_tag', '{Spam?}' );
                $self->manageTagMode($tag);
            } elsif ( $warnlisted ) {
                $status .= ": warn";
                $self->{decisive_module}{module} = 'warnlisted';
                $self->quarantine();
                my $id =
                    $email->sendWarnlistHit( $self->{env_sender}, $warnlisted, $self->{exim_id} );
                if ($id) {
                    $self->{daemon}->doLog(
                        $self->{batchid}
                            . ": message "
                            . $self->{exim_id}
                            . " warn message ready to be delivered with new id: "
                            . $id,
                        'spamhandler', 'info'
                    );
                } else {
                    $self->{daemon}->doLog(
                        $self->{batchid}
                            . ": message "
                            . $self->{exim_id}
                            . " warn message could not be delivered.",
                        'spamhandler', 'error'
                    );
                }
            } elsif ( $delivery_type == 3 ) {
                $status .= ": want drop";
            } elsif ( $self->{bounce} ) {
                $status .= ": (bounce)";
                $self->quarantine();
            } else {
                $status .= ": want quarantine";
                $self->quarantine();
            }

        ## Newsletter
        } elsif ($self->{sc_newsl} >= 5 ) {
            if ($email->getPref('allow_newsletters')) {
                $status = "is desired (Newsletter)";
                $self->{decisive_module}{module} = undef;
                $self->manageWhitelist(undef,3);
            } elsif ($self->{nwhitelisted}) {
                $status = "is newslisted by " . $level[$self->{nwhitelisted}];
                $self->{decisive_module}{module} = undef;
                $self->manageWhitelist(undef,$self->{nwhitelisted});
            } else {
                $status = "is newsletter";
                $self->{decisive_module}{module} = 'Newsl';
                if ( $delivery_type == 1 ) {
                    $status .= ": want tag";
                    $self->{fullheaders} =~
                        s/(.*Subject:\s+)(\{(MC_SPAM|MC_HIGHSPAM)\})?(.*)/$1\{MC_SPAM\}$4/i;
                    my $tag = $email->getPref( 'spam_tag', '{Spam?}' );
                    $self->manageTagMode($tag);
                } elsif ( $warnlisted ) {
                    $status .= ": warn";
                    $self->{decisive_module}{module} = 'warnlisted';
                    $self->quarantine();
                    my $id =
                        $email->sendWarnlistHit( $self->{env_sender}, $warnlisted, $self->{exim_id} );
                    if ($id) {
                        $self->{daemon}->doLog(
                            $self->{batchid}
                                . ": message "
                                . $self->{exim_id}
                                . " warn message ready to be delivered with new id: "
                                . $id,
                            'spamhandler', 'info'
                        );
                    } else {
                        $self->{daemon}->doLog(
                            $self->{batchid}
                                . ": message "
                                . $self->{exim_id}
                                . " warn message could not be delivered.",
                            'spamhandler', 'error'
                        );
                    }
                } elsif ( $delivery_type == 3 ) {
                    $status .= ": want drop";
                } elsif ( $self->{bounce} ) {
                    $status .= ": (bounce)";
                    $self->quarantine();
                } else {
                    $status .= ": want quarantine";
                    $self->quarantine();
                }
            }

        # Neither newsletter, nor spam
        } else {
            $status = 'not spam';
            unless ($self->{decisive_module}{action} == 'negative') {
                $self->{decisive_module}{module} = undef;
            }
            $self->sendMeAnyway();
        }
    }

    my $log = $self->{batchid}
        . ": message "
        . $self->{exim_id} . " R:<"
        . $self->{env_rcpt} . "> S:<"
        . $self->{env_sender}
        . "> status "
        . $status;
    if (defined $self->{decisive_module}{module}) {
        $log .= ", module: ".$self->{decisive_module}{module};
    }
    ## log status and finish
    $self->{daemon}->doLog($log, 'spamhandler', 'info');
    $self->endTimer('Message processing');
    $self->startTimer('Message deleting');
    $self->deleteFiles();
    $self->endTimer('Message deleting');
    return 1;
}

sub loadEnvFile($self)
{
    open(my $ENV, '<', $self->{envfile}) or return 0;

    my $fromfound = 0;
    while (<$ENV>) {
        if ( !$fromfound ) {
            $fromfound = 1;
            $self->{env_sender} = lc($_);
            chomp( $self->{env_sender} );
        } else {
            if (/([^\s]+)/) { # untaint
                $self->{env_rcpt} = lc($1);
                chomp( $self->{env_rcpt} );
                }
        }
    }
    close $ENV;

    if ( $self->{env_rcpt} =~ m/^(\S+)@(\S+)$/ ) {
        $self->{env_tolocal} = $1;
        $self->{env_domain}  = $2;
    }
}

sub loadMsgFile($self)
{
    my $has_subject = 0;
    my $in_score    = 0;
    my $in_header   = 1;
    my $last_header = '';
    my $last_hvalue = '';
    my $uriscount   = 0;

    open(my $BODY, '<', $self->{msgfile}) or return 0;
    while (<$BODY>) {

        ## check for end of headers
        if ( $in_header && /^\s*$/ ) {
            $in_header = 0;
        }

        ## parse for headers
        if ($in_header) {
            ## found a new header
            if (/^([A-Za-z]\S+):\s*(.*)/) {
                $last_header = lc($1);
                $self->{headers}{$last_header} .= $2;
            }
            ## found a new line in multi-line header
            if (/^\s+(.*)/) {
                $self->{headers}{$last_header} .= $1;
            }
            $self->{fullheaders} .= $_;
        ## parse for body
        } else {
            $self->{fullbody} .= $_;

            ## try to find bounced address if this is a bounce
            if ( $self->{bounce} > 0 ) {
                if (/^\s+\<?([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+)\>?\s+$/) {
                    $self->{bounced_add} = $1;
                } elsif (/Final-Recipient: rfc822; \<?([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+)\>?/) {
                    $self->{bounced_add} = $1;
                }
            }

            if ( $self->{daemon}->{reportrbls} && $uriscount <= $self->{daemon}->{maxurisreports} ) {
                my $uri = $self->{daemon}->{dnslists}->findUri( $_, $self->{batchid} . ": " . $self->{id} );
                if ($uri) {
                    $uriscount++;
                    $uri = $uri . ".isspam";
                    $self->{daemon}->{dnslists}->check_dns( $uri, 'URIRBL',
                    $self->{batchid} . ": " . $self->{id} );
                }
                my $email = $self->{daemon}->{dnslists}->findEmail( $_, $self->{batchid} . ": " . $self->{id} );
                if ($email) {
                    $uriscount++;
                    $email = $email . ".isspam";
                    $self->{daemon}->{dnslists}->check_dns( $email, 'ERBL',
                    $self->{batchid} . ": " . $self->{id} );
                }
            }

        }

        # store full message
        # $self->{fullmsg} .= $_;
    }
    close $BODY;

    ## check if message is a bounce
    if ( defined( $self->{headers}{'x-mailcleaner-bounce'} ) ) {
        $self->{bounce} = 1;
    }
    ## check for standard (but untrusted) headers
    if ( defined( $self->{headers}{'from'} ) ) {
	    if ( $self->{headers}{'from'} =~ m/<.*>/ ) {
		    $self->{msg_from} = $self->{headers}{'from'};
		    $self->{msg_from} =~ s/.*<([^>]*)>/$1/;
	    } else {
		    $self->{msg_from} = $self->{headers}{'from'};
	    }
    }
    if ( defined( $self->{headers}{'date'} ) ) {
        $self->{msg_date} = $self->{headers}{'date'};
    }
    if ( defined( $self->{headers}{'subject'} ) ) {
        $self->{msg_subject} = $self->{headers}{'subject'};
    }

    $self->loadScores();
}

sub loadScores($self)
{
    my $line;

    if ( !defined( $self->{headers}{'x-mailcleaner-spamcheck'} ) ) {
        %{$self->{decisive_module}} = (
            'module' => 'NOHEADER',
            'position' => 0,
            'action' => 'negative'
        );
        $self->{daemon}->doLog(
        $self->{batchid} . ": " . $self->{id} . " no spamcheck header",
            'spamhandler', 'warn'
        );
        return 0;
    }

    if ( defined( $self->{headers}{'x-mailcleaner-status'} ) ) {
        if ( $self->{headers}{'x-mailcleaner-status'} =~ /Blacklisted/ ) {
            $self->{prefilters} .= ", Blacklist";
        }
    }

    $line = $self->{headers}{'x-mailcleaner-spamcheck'};
    $self->{daemon}->doLog(
        $self->{batchid} . ": " . $self->{id} . " Processing spamcheck header: " . $line,
        'spamhandler', 'info'
    );

    if ( $line =~ /.*Newsl \(score=(\d+\.\d+),.*/ ) {
        $self->{sc_newsl} = $1;
        # Not processed as decisive module
        if ( $self->{sc_newsl} >= 5 )  {
            $self->{sc_global} += 1;
            $self->{prefilters} .= ", Newsl";
        }
    }

    if ( $line =~ /.*TrustedSources \(.*/ ) {
        $self->decisiveModule('TrustedSources',$line);
        $self->{prefilters} .= ", TrustedSources";
    }

    if ( $line =~ /.*NiceBayes \(([\d.]+)%.*/ ) {
        $self->{sc_nicebayes} = $1;
        $self->decisiveModule('NiceBayes',$line);
        $self->{sc_global} += 3;
        $self->{prefilters} .= ", NiceBayes";
    }

    if ( $line =~ /.*(Commtouch|MessageSniffer) \(([^\)]*)/ ) {
        if ($2 ne 'too big') {
            $self->decisiveModule($1,$line);
            $self->{sc_global} += 3;
            $self->{prefilters} .= ", ".$1;
        }
    }

    if ( $line =~ /.*PreRBLs \(([^\)]*), ?position/ ) {
        my $rbls = scalar(split( ',', $1 ));
        $self->{sc_prerbls} = $rbls;
        $self->decisiveModule('PreRBLs',$line);
        $self->{sc_global} += $rbls + 1;
        $self->{prefilters} .= ", PreRBLs";
    }

    if ( $line =~ /.*UriRBLs \(([^\)]*), ?position/ ) {
        my $rbls = scalar(split( ',', $1 ));
        $self->{sc_urirbls} = $rbls;
        $self->decisiveModule('UriRBLs',$line);
        $self->{sc_global} += $rbls + 1;
        $self->{prefilters} .= ", UriRBLs";
    }

    if ( $line =~ /.*Spamc \(score=(\d+\.\d+),.*/ ) {
        $self->{sc_spamc} = $1;
        $self->decisiveModule('Spamc',$line);
        if ( int( $self->{sc_spamc} ) >= 5 )  {
            $self->{sc_global}++;
            $self->{prefilters} .= ", SpamC";
        }
        if ( int( $self->{sc_spamc} ) >= 7 )  { $self->{sc_global}++; }
        if ( int( $self->{sc_spamc} ) >= 10 ) { $self->{sc_global}++; }
        if ( int( $self->{sc_spamc} ) >= 15 ) { $self->{sc_global}++; }
    }

    if ( $line =~ /.*ClamSpam \(([^,]*),/ ) {
        $self->decisiveModule('ClamSpam',$line);
        $self->{sc_clamspam} = $1;
        if ($1 ne 'too big') {
            $self->{sc_global} += 4;
        }
        $self->{prefilters} .= ", ClamSpam";
    }

    if ( $line =~ /.*MachineLearning \((not applied \()?([\d.]+)%.*/ ) {
        $self->decisiveModule('MachineLearning',$line);
        $self->{sc_machinelearning} = $2;
        $self->{prefilters} .= ", MachineLearning";
    }

    $self->{prefilters} =~ s/^, //;

    return 1;
}

sub deleteFiles($self)
{
    unlink( $self->{envfile} );
    unlink( $self->{msgfile} );
    $self->{daemon}->deleteLock( $self->{id} );
    return 1;
}

sub purge($self)
{
    delete( $self->{fullheaders} );
    delete( $self->{fullmsg} );
    delete( $self->{fullbody} );
    delete( $self->{headers} );
    foreach my $k ( keys %{$self} ) {
        $self->{$k} = '';
        delete $self->{$k};
    }
}

sub manageUncheckeable($self,$status)
{
    $self->{fullheaders} =~
        s/X-MailCleaner-SpamCheck: [^\n]+(\r?\n\s+[^\n]+)*/X-MailCleaner-SpamCheck: cannot be checked against spam ($status)/mi;

    ## remove the spam tag
    $self->{fullheaders} =~ s/Subject:\s+\{(MC_SPAM|MC_HIGHSPAM)\}/Subject:/i;

    $self->sendMeAnyway();
    return 1;
}

sub manageWhitelist($self,$whitelevel=undef,$newslevel=undef)
{
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
    $self->{fullheaders} =~
        s/X-MailCleaner-SpamCheck: ([^,]*),/X-MailCleaner-SpamCheck: not spam, $str,/i;

    ## remove the spam tag
    $self->{fullheaders} =~ s/Subject:\s+\{(MC_SPAM|MC_HIGHSPAM)\}/Subject:/i;

    $self->sendMeAnyway();
    return 1;
}

sub manageBlacklist($self,$blacklevel)
{
    my %level = ( 1 => 'system', 2 => 'domain', 3 => 'user' );
    my $str   = "blacklisted by " . $level{$blacklevel};

    $self->{fullheaders} =~
        s/(.*Subject:\s+)(\{(MC_SPAM|MC_HIGHSPAM)\})?(.*)/$1\{MC_SPAM\}$4/i;

    ## modify the X-MailCleaner-SpamCheck header
    $self->{fullheaders} =~
        s/X-MailCleaner-SpamCheck: ([^,]*),/X-MailCleaner-SpamCheck: spam, $str,/i;

    return 1;
}

sub manageTagMode($self,$tag)
{
    ## change the spam tag
    $self->{fullheaders} =~
        s/Subject:\s+\{(MC_SPAM|MC_HIGHSPAM)\}/Subject:$tag /i;

    $self->sendMeAnyway();
    return 1;
}

sub sendMeAnyway($self)
{
    $self->{daemon}->doLog(
        $self->{batchid}
            . ": message "
            . $self->{exim_id}
            . " Message will be delivered using SendMeAnyway",
        'spamhandler', 'info'
    );

    my $smtp;
    unless ( $smtp = Net::SMTP->new('localhost:2525') ) {
        $self->{daemon}->doLog(
            $self->{batchid}
                . ": message "
                . $self->{exim_id}
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
    if ( $self->{bounce} > 0 ) {
        $smtp->mail( $self->{bounced_add} );
    } else {
        $smtp->mail( $self->{env_sender} );
    }
    $err = $smtp->code();
    if ( $err < 200 || $err >= 500 ) {
        ## smtpError
        $self->{daemon}->doLog(
            $self->{batchid}
                . ": message "
                . $self->{exim_id}
                . " Could not set MAIL FROM",
            'spamhandler', 'error'
        );
        return 0;
    }
    $smtp->to( $self->{env_rcpt} );
    $err = $smtp->code();
    if ( $err < 200 || $err >= 500 ) {
        ## smtpError
        $self->{daemon}->doLog(
            $self->{batchid}
                . ": message "
                . $self->{exim_id}
                . " Could not set RCPT TO",
            'spamhandler', 'error'
        );
        return;
    }
    $smtp->data();
    $err = $smtp->code();
    if ( $err < 200 || $err >= 500 ) {
        ## smtpError

        $self->{daemon}->doLog(
            $self->{batchid}
              . ": message "
              . $self->{exim_id}
              . " Could not set DATA",
            'spamhandler', 'error'
        );
        return;
    }

    #print $self->getRawMessage();
    $smtp->datasend( $self->getRawMessage() );
    $err = $smtp->code();
    if ( $err < 200 || $err >= 500 ) {
        ## smtpError
        $self->{daemon}->doLog(
            $self->{batchid}
                . ": message "
                . $self->{exim_id}
                . " Could not set DATA content",
            'spamhandler', 'error'
        );
        return;
    }
    $smtp->dataend();
    $err = $smtp->code();
    if ( $err < 200 || $err >= 500 ) {
        ## smtpError
        $self->{daemon}->doLog(
            $self->{batchid}
                . ": message "
                . $self->{exim_id}
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
        $self->{daemon}->doLog(
            $self->{batchid}
                . ": message "
                . $self->{exim_id}
                . " Could not deliver the classical way, had to force the dataend, cause was :"
                . $returnmessage,
            'spamhandler', 'info'
        );

            $smtp->rawdatasend('\n.\n');
               $smtp->dataend();

            $err = $smtp->code();
        if ( $err < 200 || $err >= 500 ) {
                    ## smtpError
            $self->{daemon}->doLog(
                $self->{batchid}
                    . ": message "
                    . $self->{exim_id}
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

    $self->{daemon}->doLog(
        $self->{batchid}
            . ": message "
            . $self->{exim_id}
            . " ready to be delivered with new id: "
            . $id,
        'spamhandler', 'info'
    );
    return 1;
}

sub getRawMessage($self)
{
    my $msg = $self->{fullheaders};
    $msg .= "\n";
    $msg .= $self->{fullbody};

    return $msg;
}

sub quarantine($self)
{
    $self->startTimer('Message quarantining');
    my $config = ReadConfig::getInstance();

    ## remove the spam tag
    $self->{fullheaders} =~ s/Subject:\s+\{(MC_SPAM|MC_HIGHSPAM)\}/Subject:/i;
    if ( $self->{headers}{subject} ) {
        $self->{headers}{subject} =~ s/^\S*\{(MC_SPAM|MC_HIGHSPAM)\}//i;
    } else {
        $self->{headers}{subject} = "";
    }
    if ( !-d $config->getOption('VARDIR') . "/spam/" . $self->{env_domain} ) {
        mkdir( $config->getOption('VARDIR') . "/spam/" . $self->{env_domain} );
    }
    if (  !-d $config->getOption('VARDIR') . "/spam/"
        . $self->{env_domain} . "/"
        . $self->{env_rcpt} )
    {
        mkpath(
            $config->getOption('VARDIR') . '/spam/'
                . $self->{env_domain} . '/'
                . $self->{env_rcpt},
            {error => \my $err}
        );
        if ($err && @$err) {
            for my $diag (@$err) {
                my ($file, $message) = %$diag;
                if ($file eq '') {
                    $self->{daemon}->doLog('Batch : ' .$self->{batchid} . ' ; message : ' . $self->{exim_id} . " => general error: $message", 'spamhandler' );
                } else {
                    $self->{daemon}->doLog('Batch : ' .$self->{batchid} . ' ; message : ' . $self->{exim_id} . " problem creating $file: $message", 'spamhandler' );
                }
            }
            exit 0;
        }
    }

    ## save the spam file
    my $filename =
        $config->getOption('VARDIR') . "/spam/"
            . $self->{env_domain} . "/"
            . $self->{env_rcpt} . "/"
            . $self->{exim_id};

    my $MSGFILE;
    if ( !open($MSGFILE, ">", $filename) ) {
        print " cannot open quarantine file $filename for writing";
        $self->{daemon}->doLog(
            "Cannot open quarantine file $filename for writing",
            'spamhandler', 'error'
        );
        return 0;
    }
    print $MSGFILE $self->getRawMessage();
    close $MSGFILE;

    $self->{quarantined} = 1;
    $self->endTimer('Message quarantining');

    return 1;
}

sub log($self,$dbname,$inmasterh)
{
    return 1 if ( $self->{quarantined} < 1 );

    $self->startTimer('Message logging');
    my $loggedonce = 0;

    my %prepared = %{ $self->{daemon}->{prepared}{$dbname} };
    return 0 if ( !%prepared );

    ## find out correct table
    my $table = "misc";
    if ( $self->{env_tolocal} =~ /^([a-z,A-Z])/ ) {
        $table = lc($1);
    } elsif ( $self->{env_tolocal} =~ /^[0-9]/ ) {
        $table = 'num';
    }
    my $p = $prepared{$table};
    if ( !$p ) {
        $self->{daemon}
          ->doLog( "Error, could not get prepared statement for table: $table",
            'spamhandler', 'error' );
        return 0;
    }

    my $isNewsletter = ( $self->{sc_newsl} >= 5 && !$self->{nwhitelisted} && !$self->{news_allowed}) || 0;

    my $res = $p->execute(
        $self->{env_domain}, $self->{env_tolocal},
        $self->{env_sender}, $self->{exim_id},
        $self->{sc_spamc},   $self->{sc_prerbls},
        $self->{prefilters}, $self->{headers}{subject},
        $self->{sc_global},  $$inmasterh, $isNewsletter
    );
    if ( !$res ) {
        $self->{daemon}->doLog(
            "Error while logging msg "
                . $self->{exim_id}
                . " to db $dbname, retrying, if no further message, it's ok",
            'spamhandler', 'error'
        );
        $self->{daemon}->connectDatabases();
        ## and try again
        $res = $p->execute(
            $self->{env_domain}, $self->{env_tolocal},
            $self->{env_sender}, $self->{exim_id},
            $self->{sc_spamc},   $self->{sc_prerbls},
            $self->{prefilters}, $self->{headers}{subject},
            $self->{sc_global},  $$inmasterh, $isNewsletter
        );

        if ( !$res ) {
            $self->{daemon}->doLog(
                "Error while executing log query (msgid="
                    . $self->{exim_id}
                    . ", db=$dbname): "
                    . $p->errstr,
                'spamhandler', 'error'
            );
            return 0;
        }
    } else {
        $loggedonce = 1;
    }
    $self->{daemon}->doLog(
        " Message " . $self->{exim_id} . " logged in database \"$dbname\"",
        'spamhandler', 'debug' );
    if ( $dbname eq 'realmaster' ) {
        $$inmasterh = 1;
    }
    $self->startTimer('Message logging');
    return $loggedonce;
}

sub decisiveModule($self, $module, $line)
{
    $line =~ s/.*$module \((.*)/$1/;
    $line =~ s/decisive\).*/decisive/;
    my $position = my $decisive = $line;
    $decisive =~ s/.*, ?([^ ]*) decisive.*/$1/;
    $position =~ s/.*, ?position ?: ?(\d+).*/$1/;
    $self->{daemon}->doLog('Current decisive module is "'.$self->{decisive_module}{'module'}.'" with action "'.$self->{decisive_module}{'action'}.'" and position "'.$self->{decisive_module}{'position'}.'"','spamhandler', 'debug');
    if (!defined $decisive || !defined $position) {
        $self->{daemon}->doLog("Failed to discover decisive or position value for $module: $line", 'spamhandler', 'debug');
        return 0;
    }
    if ($position >= $self->{decisive_module}{position}) {
        $self->{daemon}->doLog("Found $module of lower priority $position, not updating decisive_module", 'spamhandler', 'debug');
    # If there is two modules of the same position (this would be a bug), then prefer the spam
    } elsif ( ($position == $self->{decisive_module}{position}) && ($decisive eq 'spam') ) {
        $self->{daemon}->doLog("Found positively decisive module $module of equal priority $position, updating decisive_module", 'spamhandler', 'debug');
        %{$self->{decisive_module}} = (
            'module' => $module,
            'position' => $position,
            'action' => 'positive'
        );
    } elsif ($decisive eq 'not') {
        $self->{daemon}->doLog("Found undecisive $module of priority $position, not updating decisive_module", 'spamhandler', 'debug');
    } elsif ($decisive eq 'spam') {
        $self->{daemon}->doLog("Updating decisive_module $module $position positive", 'spamhandler', 'debug');
        %{$self->{decisive_module}} = (
            'module' => $module,
            'position' => $position,
            'action' => 'positive'
        );
    } elsif ($decisive eq 'ham') {
        $self->{daemon}->doLog("Updating decisive_module $module $position negative", 'spamhandler', 'debug');
        %{$self->{decisive_module}} = (
            'module' => $module,
            'position' => $position,
            'action' => 'negative'
        );
    } else {
        $self->{daemon}->doLog("Found $module with unrecognized decisive value '$decisive', not updating decisive_module", 'spamhandler', 'debug');
    }
    return 1;
}

#######
## profiling timers

sub startTimer($self,$timer)
{
    $self->{'timers'}{$timer} = [gettimeofday];
}

sub endTimer($self,$timer)
{
    my $interval = tv_interval( $self->{timers}{$timer} );
    $self->{timers}{$timer} = 0;
    $self->{'timers'}{ 'd_' . $timer } = ( int( $interval * 10000 ) / 10000 );
}

sub getTimers($self)
{
    return $self->{'timers'};
}

1;
