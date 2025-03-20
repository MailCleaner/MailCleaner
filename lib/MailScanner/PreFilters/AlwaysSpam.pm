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
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

package MailScanner::AlwaysSpam;

use v5.36;
use strict 'vars';
use strict 'refs';
no  strict 'subs'; # Allow bare words for parameter %'s

use POSIX qw(:signal_h); # For Solaris 9 SIG bug workaround

sub initialise
{
    MailScanner::Log::InfoLog('AlwaysSpam module initializing...');
}

sub Checks($message)
{
    MailScanner::Log::InfoLog('AlwaysSpam module checking... well guess what ? it\'s spam !');
    return 1;
}

sub dispose
{
    MailScanner::Log::InfoLog('AlwaysSpam module disposing...');
}

1;
