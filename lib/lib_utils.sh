#!/bin/bash
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004-2014 Olivier Diserens <olivier@diserens.ch>
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
#   This lib permits to use useful function such as the LockFile process handling.

CONFFILE=/etc/mailcleaner.conf
SRCDIR=`grep 'SRCDIR' $CONFFILE | cut -d ' ' -f3`
if [ "$SRCDIR" = "" ]; then
  SRCDIR="/usr/mailcleaner"
fi
VARDIR=`grep 'VARDIR' $CONFFILE | cut -d ' ' -f3`
if [ "$VARDIR" = "" ]; then
  VARDIR="/var/mailcleaner"
fi

LOCKFILEDIRECTORY=${VARDIR}/spool/tmp/

function createLockFile()
{
	find ${LOCKFILEDIRECTORY} -type f -name "${1}" -mtime +1 -exec rm {} \;
	LOCKFILE=${LOCKFILEDIRECTORY}${1}
	if [ -f ${LOCKFILE} ]; then
		echo 1
	else
		echo $$ > ${LOCKFILE}
		echo 0
	fi
}

function removeLockFile()
{
	LOCKFILE=${LOCKFILEDIRECTORY}${1}
	rm -f ${LOCKFILE}
	echo $?
}

function isLocked()
{
    process=$1
    if [ -n "$(find ${LOCKFILEDIRECTORY} -type f -name "${process}")" ]; then
        echo 1
    else
        echo 0
    fi
}

function hasFetchersRunning()
{
    echo $(isLocked "fetch_*")
}

function isGitupdateRunning()
{
    echo $(isLocked "gitupdate_running")
}

function slaveSynchronized()
{
    slave_status=$(echo "SHOW SLAVE STATUS\G" | ${SRCDIR}/bin/mc_mysql -s)
    Last_IO_Errno=$(echo "${slave_status}" | awk '/Last_IO_Errno/{print $NF}')
    Last_SQL_Errno=$(echo "${slave_status}" | awk '/Last_SQL_Errno/{print $NF}')
    if [[ $Last_IO_Errno == "0" && $Last_SQL_Errno == "0" ]]; then
        echo "true"
    else
        echo "false"
    fi
}

function isMaster()
{
    is_master=`grep 'ISMASTER' $CONFFILE | cut -d ' ' -f3`
    if [[ "${is_master}" == "Y" || "${is_master}" == "y" ]]; then
        echo 1
    else
        echo 0
    fi
}

function log {
    echo "["`date "+%Y/%m/%d %H:%M:%S"`"] $1" >> $LOGFILE
}
