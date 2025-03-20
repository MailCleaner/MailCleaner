#!/usr/bin/env perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
#   Copyright (C) 2023 John Mertz <git@john.me.tz>
#
#   This program is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; either version 2 of the License, or
#   (at your option) any later version.
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

use v5.36;
use strict;
use warnings;
use utf8;

use lib '__SRCDIR__/lib'

use Time::HiRes qw(gettimeofday tv_interval);
my $stime = [gettimeofday];
my $PROFILE = 1;
my (%prof_start, %prof_res) = ();
profile_start('init');

my %config = readConfig("/etc/mailcleaner.conf");
use SpamLogger;
use Email;
use Net::SMTP;

### Global variables
my $msg             = "";
my $DEBUG           = 0;
my $err             = 0;
my $is_bounce       = 0;
my $header_from     = "";
my $subject         = "";
my $score           = "unknown";
my $rbls            = "";
my $prefilters      = "";
my $bounced_add     = "";
my $spam_type       = 'MC_SPAM';
my $tag             = "";
my $store_id        = $config{'HOSTID'};
my @rbl_tags        = ('__RBLS_TAGS__');
my @prefiltertags   = ('__PREFILTERS_TAGS__');
my %prefilterscores = ('Mailfilter' => 3, 'NiceBayes' => 3, 'PreRBLs' => 3, 'ClamSpam' => 4);
my @filters         = ('SpamAssassin');
my $globalscore     = 0;

### Global server variables
my $server_port     = 12553;
my $debug           = 0;

### Get parameters values
my $exim_id         = shift;
my $to_local        = shift;
my $to_domain       = shift;
my $sender          = shift;
my $to              = $to_local . "@" . $to_domain;

## print something to Exim's log to say we are here
printf "spam detected for user: $to, from: $sender, with id: $exim_id => ";
profile_stop('init');

## fetch values in message and store it in $msg
parseInputMessage();
cleanVariables();
my $ptime = tv_interval ($stime);

# Profile fetchPref time
profile_start('fetchPref');

## get user preference
my $email = Email::create($to);

## check what to do with message
my $delivery_type = $email->getPref( 'delivery_type', 1);
my $whitelisted = $email->hasInWhiteWarnList('whitelist', $sender);
my $warnlisted = $email->hasInWhiteWarnList('warnlist', $sender);

profile_stop('fetchPref');

## if WHITELISTED, then go through, no tag
if ($whitelisted) {
    print " is whitelisted ($whitelisted) ";
    sendAnyway($whitelisted);
    exit(0);
}

## DROP
if ( $delivery_type == 3 ) {
    print " want drop";
    exit(0);
}

## if QUARANTINE but WARNLISTED, then send the warning
if ( $delivery_type == 2 && $warnlisted) {
    print " is warnlisted ($warnlisted) ";
    if ( !put_in_quarantine() ) {
        print " ** could not put in quarantine!";
        sendAnyway(0);
    }
    if ( !$email->sendWarnlistHit($sender, $warnlisted, $exim_id) ) {
       print " ** could not send warning!";
       sendAnyway(0);
    }
    sendNotice();
    exit(0);
}

## if QUARANTINE or BOUNCE then save message
if ($delivery_type == 2 || $is_bounce) {
    print " want quarantine" . ($is_bounce ? " (bounce)" : "");
    if ( !put_in_quarantine() ) {
        print " ** could not put in quarantine!";
        sendAnyway(0);
    }
    exit(0);
}

print " want tag";
sendAnyway(0);

my $gtime = tv_interval ($stime);
print " (".(int($ptime*10000)/10000)."s/".(int($gtime*10000)/10000)."s)";
profile_output();

exit(0);

sub parseInputMessage
{
    profile_start('parseMessage');
    my $start_msg   = 0;
    my $line        = "";
    my $has_subject = 0;
    my $in_score    = 0;
    my $in_header   = 1;

    while (<>) {
        # just to remove garbage line before the real headers
        if ( $start_msg != 1 && /^[A-Z][a-z]*\:\ .*/ ) {
            $start_msg = 1;
        }

        if ( $start_msg && $in_header && /^\s+$/) {
            $in_header = 0;
        }

        $line = $_;

        if ($in_header) {
            # parse if it is a bounce
            if (/^X\-Mailcleaner\-Bounce\:\ /i) {
                $is_bounce = 1;
                next;
            }
            # parse for from
            if (/^From:\s+(.*)$/i) {
                $header_from = $1;
            }
            # parse for subject
            if ( $has_subject < 1 ) {
                if (/^Subject:\s+(\{(MC_SPAM|MC_HIGHSPAM)\}\s+)?(.*)/i) {
                    if ( defined($3) ) {
                        $subject = $3;
                        $spam_type = $2;
                    } else {
                        $subject = $1;
                    }
                    $subject =~ s/\n//g;
                    # we have to remove the spam tag first
                    chomp($subject);
                    $line = "Subject: $subject\n";

                    if ($subject) {
                        $has_subject = 1;
                    }
                }
            }
            if (/^\S+:/) {
                $in_score = 0;
            }
            # parse for scores
            if (/^X-MailCleaner-SpamCheck:/ || $in_score) {
                $in_score = 1;
                if (/^X-MailCleaner-SpamCheck: (?:spam|not spam)?(.*)$/ || /^\s+(.*)/) {
                    my @tags = split(/[,()]/, $1);
                    foreach my $tag (@tags) {
                        $tag =~ s/^\s+//g;
                        $tag =~ s/\s+$//g;
                        foreach my $rbltag (@rbl_tags) {
                            if ($tag =~ m/^$rbltag/) {
                                $rbls .= ", $rbltag";
                            }
                        }
                        foreach my $pretag (@prefiltertags) {
                            if ($tag =~ m/^$pretag/) {
                                $prefilters .= ", $pretag";
                                if (defined($prefilterscores{$pretag})) {
                                    $globalscore += $prefilterscores{$pretag};
                                }
                            }
                        }
                        if ($tag =~ m/^score=(\S+)/) {
                            $score = $1;
                        }
                    }
                }
            }
            # parse for score
            if (/^X-MailCleaner-SpamCheck:.*\(.*score=(\S+)\,/) {
                $score = $1;
                $globalscore++ if (int($score) >= 5);
                $globalscore++ if (int($score) >= 7);
                $globalscore++ if (int($score) >= 10);
                $globalscore++ if (int($score) >= 15);
            }
        }
        if ( $start_msg > 0 ) {
            # save the message in a variable
            $msg = $msg . $line;
            if ( $is_bounce > 0 ) {
                if ( $line =~ /^\s+\<?([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+)\>?\s+$/ ) {
                    $bounced_add = $1;
                } elsif ( $line =~ /Final-Recipient: rfc822; \<?([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+)\>?/ ) {
                    $bounced_add = $1;
                }
            }
        }
    }
    $prefilters =~ s/^\s*,\s*//g;
    $rbls =~ s/^\s*,\s*//g;
    profile_stop('parseMessage');
}

sub cleanVariables
{
    $to_domain = clean($to_domain);
    $to_local  = clean($to_local);
    $sender    = clean($sender);

    if ( $is_bounce > 0 ) {
        $sender = "*";
        if ( $bounced_add !~ /^$/ ) {
            $sender .= "(" . clean($bounced_add) . ") ";
        } else {
            $sender .= clean($header_from);
        }
    }
    $exim_id = clean($exim_id);
    $subject = clean($subject);
}

sub sendAnyway
{
    profile_start('sendAnyway');
    my $whitelisted = shift;
    my $smtp;

    my %level = (1 => 'system', 2 => 'domain', 3 => 'user');

    if ($whitelisted) {
        $msg =~ s/X-MailCleaner-SpamCheck: spam,/X-MailCleaner-SpamCheck: spam, whitelisted by $level{$whitelisted},/i;
    } else {
        my $tag = $email->getPref( 'spam_tag', '{Spam?}');
        if ( $tag =~ /\-1/ ) {
            $tag = "{Spam?}";
        }

        if ( $tag !~ /^$/) {
            $msg =~ s/Subject:\ /Subject: $tag /i;
        }
    }

    unless ( $smtp = Net::SMTP->new('localhost:2525') ) {
        print " ** cannot connect to outgoing smtp server !\n";
        exit 0;
    }

    $err = 0;
    $err = $smtp->code();
    if ( $err < 200 || $err >= 500 ) {
        panic_log_msg();
        return;
    }

    if ( $is_bounce > 0 ) {
        $smtp->mail($bounced_add);
    } else {
        $smtp->mail($sender);
    }
    $err = $smtp->code();
    if ( $err < 200 || $err >= 500 ) {
        panic_log_msg();
        return;
    }

    $smtp->to($to);
    $err = $smtp->code();
    if ( $err < 200 || $err >= 500 ) {
        panic_log_msg();
        return;
    }

    $smtp->data();
    $err = $smtp->code();
    if ( $err < 200 || $err >= 500 ) {
        panic_log_msg();
        return;
    }

    profile_start('dataSend');
    $smtp->datasend($msg);
    $err = $smtp->code();
    if ( $err < 200 || $err >= 500 ) {
        panic_log_msg();
        return;
    }

    $smtp->dataend();
    profile_stop('dataSend');
    $err = $smtp->code();
    if ( $err < 200 || $err >= 500 ) {
        panic_log_msg();
        return;
    }

    if ($whitelisted) {
        sendNotice();
    }
    profile_stop('sendAnyway');
}

sub put_in_quarantine
{
    profile_start('putInQuar');
    if ( !-d $config{'VARDIR'} . "/spam/" . $to_domain ) {
        mkdir( $config{'VARDIR'} . "/spam/" . $to_domain );
    }
    if ( !-d $config{'VARDIR'} . "/spam/" . $to_domain . "/" . $to ) {
        mkdir( $config{'VARDIR'} . "/spam/" . $to_domain . "/" . $to );
    }

    ## save the spam file
    my $filename =
        $config{'VARDIR'} . "/spam/" . $to_domain . "/" . $to . "/" . $exim_id;
    if ( !open(my $MSGFILE, '>', $filename ) ) {
        print " cannot open quarantine file $filename for writing";
        return 0;
    }
    print $MSGFILE $msg;
    close $MSGFILE;

    if ( $store_id < 0 ) {
        print " error, store id less than 0 ";
        return 0;
    }
    profile_stop('putInQuar');

    profile_start('logSpam');
    my $logger = SpamLogger->new("Client", "etc/exim/spamlogger.conf");
    my $res = $logger->logSpam($exim_id, $to_local, $to_domain, $sender, $subject, $score, $rbls, $prefilters, $globalscore);
    chomp($res);
    if ($res !~ /LOGGED BOTH/) {
        print " WARNING, logging is weird ($res)";
    }
    profile_stop('logSpam');
    return 1;
}

sub sendNotice
{
    profile_start('sendNotice');
    if (defined($email) && defined($email->{d}) && $email->{d}->getPref('notice_wwlists_hit') == 1) {
        $email->sendWWHitNotice($whitelisted, $warnlisted, $sender, \$msg);
    }
    profile_stop('sendNotice');
}

sub panic_log_msg
{
    my $filename = $config{'VARDIR'} . "/spool/exim_stage4/paniclog/" . $exim_id;
    print " **WARNING, cannot send message ! saving mail to: $filename\n";

    open(my $PANICLOG, '>', $filename) or return;
    print $PANICLOG $msg;
    close $PANICLOG;
}

sub clean
{
    my $str = shift;

    $str =~ s/\\/\\\\/g;
    $str =~ s/\'/\\\'/g;

    return $str;
}

sub readConfig
{
    my $configfile = shift;
    my %config;
    my ( $var, $value );

    open(my $CONFIG, '<', $configfile) or die "Cannot open $configfile: $!\n";
    while (<$CONFIG>) {
        chomp;              # no newline
        s/#.*$//;           # no comments
        s/^\*.*$//;         # no comments
        s/;.*$//;           # no comments
        s/^\s+//;           # no leading white
        s/\s+$//;           # no trailing white
        next unless length; # anything left?
        my ( $var, $value ) = split( /\s*=\s*/, $_, 2 );
        $config{$var} = $value;
    }
    close $CONFIG;
    return %config;
}

sub profile_start
{
    return unless $PROFILE;
    my $var = shift;
    $prof_start{$var} = [gettimeofday];
}

sub profile_stop
{
    return unless $PROFILE;
    my $var = shift;
    return unless defined($prof_start{$var});
    my $interval = tv_interval ($prof_start{$var});
    $prof_res{$var} = (int($interval*10000)/10000)
}

sub profile_output
{
    return unless $PROFILE;
    my $out = "";
    foreach my $var (keys %prof_res) {
        $out .= " ($var:".$prof_res{$var}."s)";
    }
    print $out;
}
