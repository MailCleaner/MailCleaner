#!/usr/bin/env perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
#   Copyright (C) 2025 John Mertz <git@john.me.tz>
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
#
#   TrustedIPs prefilter module for MailScanner (Custom version for MailCleaner)

package MailScanner::TrustedIPs;

use v5.36;
use strict;
use warnings;
use utf8;

use POSIX qw(:signal_h); # For Solaris 9 SIG bug workaround

my $MODULE = "TrustedIPs";
my %conf;

sub initialise
{
    MailScanner::Log::InfoLog("$MODULE module initializing...");

    my $confdir = MailScanner::Config::Value('prefilterconfigurations');
    my $configfile = $confdir."/$MODULE.cf";
    %TrustedIPs::conf = (
        header => "X-$MODULE",
        putHamHeader => 0,
        putDetailedHeader => 1,
        scoreHeader => "X-$MODULE-score",
        maxSize => 0,
        timeOut => 100,
        debug => 0,
        decisive_field => 'neg_decisive',
        neg_text => '',
        neg_decisive => 0,
        position => 0
    );

    if (open(my $CONFIG, '<', $configfile)) {
        while (<$CONFIG>) {
            if (/^(\S+)\s*\=\s*(.*)$/) {
             $TrustedIPs::conf{$1} = $2;
            }
        }
        close $CONFIG;
    } else {
        MailScanner::Log::WarnLog("$MODULE configuration file ($configfile) could not be found !");
    }

    $TrustedIPs::conf{'neg_text'} = 'position : '.$TrustedIPs::conf{'position'}.', ham decisive';
}

sub Checks($self,$message)
{
    foreach my $hl ($global::MS->{mta}->OriginalMsgHeaders($message)) {
        if ($hl =~ m/^X-MailCleaner-TrustedIPs: Ok/i) {
            my $string = 'sending IP is in Trusted IPs';
            if ($TrustedIPs::conf{debug}) {
                    MailScanner::Log::InfoLog("$MODULE result is ham ($string) for ".$message->{id});
            }
            if ($TrustedIPs::conf{'putHamHeader'}) {
                $global::MS->{mta}->AddHeaderToOriginal($message, $TrustedIPs::conf{'header'}, "is ham ($string) ".'position : '.$TrustedIPs::conf{'position'}.', ham decisive');
            }
            $message->{prefilterreport} .= ", $MODULE ($string, ".'position : '.$TrustedIPs::conf{'position'}.', ham decisive'.")";

            return 0;
        }

        if ($hl =~ m/^X-MailCleaner-White-IP-DOM: WhIPDom/i) {
            my $string = 'sending IP is whitelisted for this domain';
            if ($TrustedIPs::conf{debug}) {
                    MailScanner::Log::InfoLog("$MODULE result is ham ($string) for ".$message->{id});
            }
            if ($TrustedIPs::conf{'putHamHeader'}) {
                $global::MS->{mta}->AddHeaderToOriginal($message, $TrustedIPs::conf{'header'}, "is ham ($string) ".'position : '.$TrustedIPs::conf{'position'}.', ham decisive');
            }
            $message->{prefilterreport} .= ", $MODULE ($string, ".$TrustedIPs::conf{'position'}.', ham decisive'.")";

            return 0;
        }

    }

    return 1;
}

sub dispose
{
    MailScanner::Log::InfoLog("$MODULE module disposing...");
}

1;
