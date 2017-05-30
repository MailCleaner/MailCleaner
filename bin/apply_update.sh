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
#   This script will apply the patch given in parameter
#   Usage: 
#           apply_update.sh patch_id


VARDIR=`grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "$VARDIR" = "" ]; then
  VARDIR=/var/mailcleaner
fi
SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "$SRCDIR" = "" ]; then
  SRCDIR=/var/mailcleaner
fi

MYMAILCLEANERPWD=`grep 'MYMAILCLEANERPWD' /etc/mailcleaner.conf | cut -d ' ' -f3`

LOGFILE=$VARDIR/log/mailcleaner/update.log
PATCHID=$1

if [ $PATCHID = "" ]; then
	echo "bad usage: no patch id given";
	exit 1
fi

PATCHFILE=$SRCDIR/updates/$PATCHID

if [ ! -x $PATCHFILE ]; then
	echo "ERROR: patch file $PATCHFILE not found or not executable";
	exit 1
fi

DESC=`grep "# DESCRIPTION: " $PATCHFILE | cut -d':' -f2`

EXISTS=`echo "SELECT id FROM update_patch WHERE id='$PATCHID';" | /opt/mysql5/bin/mysql -umailcleaner -p$MYMAILCLEANERPWD -S$VARDIR/run/mysql_slave/mysqld.sock mc_config`
if [ ! "$EXISTS" = "" ]; then
	echo "ERROR: patch $PATCHID already applied";
	exit 1
fi

echo "["`date "+%Y-%m-%d %H:%M:%S"`"] [$PATCHID] applying update $PATCHFILE ..." >> $LOGFILE
if [ ! "$DESC" = "" ]; then
	echo "["`date "+%Y-%m-%d %H:%M:%S"`"] [$PATCHID] description: $DESC" >> $LOGFILE
fi

RES=`$PATCHFILE`
echo "res is: $RES"
if [ "$RES" = "OK" ]; then
  echo "["`date "+%Y-%m-%d %H:%M:%S"`"] [$PATCHID] done with update, status: $RES" >> $LOGFILE
  echo "INSERT INTO update_patch VALUES('$PATCHID', NOW(), NOW(), '$RES', '$DESC');" | /opt/mysql5/bin/mysql -umailcleaner -p$MYMAILCLEANERPWD -S$VARDIR/run/mysql_slave/mysqld.sock mc_config
else
  echo "["`date "+%Y-%m-%d %H:%M:%S"`"] [$PATCHID] aborted, will retry later, reason is: $RES" >> $LOGFILE 
fi
echo $RES
