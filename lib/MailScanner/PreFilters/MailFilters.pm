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
#   MailFilters prefilter module for MailScanner (Custom version for MailCleaner)

package MailScanner::MailFilters;

use v5.36;
use strict;
use warnings;
use utf8;

use POSIX qw(:signal_h); # For Solaris 9 SIG bug workaround
#use MailFilters;

my $MODULE = "MailFilters";
my %conf;
my $MFInterface;

sub initialise
{
    MailScanner::Log::InfoLog("$MODULE module initializing...");

    my $confdir = MailScanner::Config::Value('prefilterconfigurations');
    my $configfile = $confdir."/$MODULE.cf";
    %MailFilters::conf = (
        header => "X-$MODULE",
        putHamHeader => 0,
        putSpamHeader => 1,
        maxSize => 0,
        active => 1,
        timeOut => 10,
        server_host => 'localhost',
        server_port => 25080,
        threshold => 0,
        serial => '',
        decisive_field => 'none',
        pos_text => '',
        neg_text => '',
        pos_decisive => 0,
        neg_decisive => 0,
        position => 0
    );

    if (open(my $CONFIG, '<', $configfile)) {
        while (<$CONFIG>) {
            if (/^(\S+)\s*\=\s*(.*)$/) {
                $MailFilters::conf{$1} = $2;
            }
        }
        close $CONFIG;
    } else {
        MailScanner::Log::WarnLog("$MODULE configuration file ($configfile) could not be found !");
    }

    $MFInterface = MailFilters::SpamCureClientInterface->new();
    $MFInterface->Initialize($MailFilters::conf{'serial'}, $MailFilters::conf{'server_host'}, $MailFilters::conf{'server_port'});

    if ($MailFilters::conf{'pos_decisive'} && ($MailFilters::conf{'decisive_field'} eq 'pos_decisive' || $MailFilters::conf{'decisive_field'} eq 'both')) {
        $MailFilters::conf{'pos_text'} = 'position : '.$MailFilters::conf{'position'}.', spam decisive';
    } else {
        $MailFilters::conf{'pos_text'} = 'position : '.$MailFilters::conf{'position'}.', not decisive';
    }
    if ($MailFilters::conf{'neg_decisive'} && ($MailFilters::conf{'decisive_field'} eq 'neg_decisive' || $MailFilters::conf{'decisive_field'} eq 'both')) {
        $MailFilters::conf{'neg_text'} = 'position : '.$MailFilters::conf{'position'}.', ham decisive';
    } else {
        $MailFilters::conf{'neg_text'} = 'position : '.$MailFilters::conf{'position'}.', not decisive';
    }
}

sub Checks($self,$message)
{
    my $maxsize = $MailFilters::conf{'maxSize'} || 0;
    if ($maxsize > 0 && $message->{size} > $maxsize) {
        MailScanner::Log::InfoLog(
            "Message %s is too big for MailFilters checks (%d > %d bytes)",
            $message->{id}, $message->{size}, $maxsize
        );
        $global::MS->{mta}->AddHeaderToOriginal($message, $MailFilters::conf{'header'}, "too big (".$message->{size}." > $maxsize)");
        return 0;
    }

    if ($MailFilters::conf{'active'} < 1) {
        MailScanner::Log::WarnLog("$MODULE has been disabled");
        $global::MS->{mta}->AddHeaderToOriginal($message, $MailFilters::conf{'header'}, "disabled");
        return 0;
    }

### check against MailFilters
    my @WholeMessage;
    push(@WholeMessage, $global::MS->{mta}->OriginalMsgHeaders($message, "\n"));
    push(@WholeMessage, "\n");
    $message->{store}->ReadBody(\@WholeMessage, 0);
    my $msg = "";
    foreach my $line (@WholeMessage) {
        $msg .= $line;
    }

    my $tags = '';
    my $result = $MFInterface->ScanSMTPBuffer($msg, $tags);

    if ($result <= 0)    {
        MailScanner::Log::InfoLog("$MODULE returned an error (".$result.")");
        $global::MS->{mta}->AddHeaderToOriginal($message, $MailFilters::conf{'header'}, 'returned an error ('.$result.')');
        return 0;
    }

    if ($result == 2) {
        MailScanner::Log::InfoLog("$MODULE result is spam (".$result.") for ".$message->{id});
        if ($MailFilters::conf{'putSpamHeader'}) {
            $global::MS->{mta}->AddHeaderToOriginal($message, $MailFilters::conf{'header'}, "is spam ($result, ".$MailFilters::conf{'pos_text'}. ")");
        }
        $message->{prefilterreport} .= ", $MODULE ($result, ".$MailFilters::conf{'pos_text'}.")";
        return 1;
    } else {
        MailScanner::Log::InfoLog("$MODULE result is not spam (".$result.") for ".$message->{id});
        if ($MailFilters::conf{'putHamHeader'}) {
            $global::MS->{mta}->AddHeaderToOriginal($message, $MailFilters::conf{'header'}, "is not spam ($result, ".$MailFilters::conf{'pos_text'}. ")");
        }
        return 0;
    }

    return 0;
}

sub dispose
{
    MailScanner::Log::InfoLog("$MODULE module disposing...");
}

1;
