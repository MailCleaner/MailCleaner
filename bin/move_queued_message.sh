#!/bin/sh
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

SRCDIR=$(grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3)
if [ "SRCDIR" = "" ]; then
	SRCDIR=/var/mailcleaner
fi
VARDIR=$(grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3)
if [ "VARDIR" = "" ]; then
	VARDIR=/var/mailcleaner
fi

search=$1
stage=$2

if [ "$stage" != "4" ]; then
	stage=1
fi

if [ "$search" = "" ]; then
	echo "Usage: move_queued_message.sh searchstring [stage]"
	exit 1
fi

SPOOLDIR=$VARDIR"/spool/exim_stage$stage/input"
MSGLOGDIR=$VARDIR"/spool/exim_stage$stage/msglog"
BACKUPDIR=$VARDIR"/spool/exim_stage$stage/input_disabled"
BACKUPMSGLOGDIR=$VARDIR"/spool/exim_stage$stage/msglog_disabled"

if [ ! -d $BACKUPDIR/$search ]; then
	mkdir -p $BACKUPDIR/$search
fi
if [ ! -d $BACKUPMSGLOGDIR/$search ]; then
	mkdir -p $BACKUPMSGLOGDIR/$search
fi

for i in $(grep $search $SPOOLDIR/* | cut -d':' -f1 | cut -d'-' -f1-3 | sort | uniq); do
	mv $i* $BACKUPDIR/$search/
	mv $MSGLOGDIR/$i $BACKUPMSGLOGDIR/$search/
done

echo "Messages from $search disabled !"
exit 0
