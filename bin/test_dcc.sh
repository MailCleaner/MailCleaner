#!/bin/bash
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
#   This script will test the DCC network connectivity
#
#   Usage:
#           test_dcc.sh


RES=`echo "Received: by stage2 with id 1CRoEF-00079h-3m 
        for <noone@nowhere>; Wed, 10 Nov 2004 09:53:23 +0100
Subject: test
X-MailCleaner-Information: Please contact postmaster@fastnet.ch for more information
X-MailCleaner: Found to be clean
X-MailCleaner-SpamCheck: polluriel, SpamAssassin (score=1001.654, requis 5,
        GTUBE 1000.00, MISSING_DATE 0.02, RAZOR2_CF_RANGE_51_100 1.49,
        RAZOR2_CHECK 0.15)
X-MailCleaner-SpamScore: oooooooooooooooooooooooooooooooooooooooooooooooooooooooooooo

XJS*C4JDBQADN1.NSBN3*2IDNEN*GTUBE-STANDARD-ANTI-UBE-TEST-EMAIL*C.34X"  | /opt/dcc/bin/dccproc -Q -d -H | cut -d'-' -f-2`

if [ "$RES" = "X-DCC" ]; then
    echo "DCCOK"
else
    echo "DCCERROR"
fi;

