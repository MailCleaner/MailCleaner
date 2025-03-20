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
#   This script will test the Razor2 network connectivity
#
#   Usage:
#           test_razor.sh


TIMEOUT=10

if [ "$1" != "" ]; then
  TIMEOUT=$1
fi

echo "testing" | /usr/bin/razor-check &  >/dev/null 2>&1

i=0
while pgrep razor-check >/dev/null; do
    sleep 1
        i=`expr $i + 1`
    if [ "$i" = "$TIMEOUT" ]; then
        echo "RAZORTIMEDOUT"
        killall -TERM razor-check >/dev/null 2>&1
        exit 1
    fi
done

echo "RAZOROK"

