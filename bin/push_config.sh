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

usage() {
	cat <<EOF
usage: $0 options

This script will push config for backup purpose

OPTIONS:
  -r   randomize start of the script, for automated process
EOF
}

randomize=false

while getopts ":r" OPTION; do
	case $OPTION in
	r)
		randomize=true
		;;
	?)
		usage
		exit
		;;
	esac
done

SRCDIR=$(grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3)
if [ "SRCDIR" = "" ]; then
	SRCDIR=/var/mailcleaner
fi
VARDIR=$(grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3)
if [ "VARDIR" = "" ]; then
	VARDIR=/var/mailcleaner
fi
ISMASTER=$(grep 'ISMASTER' /etc/mailcleaner.conf | cut -d ' ' -f3)
CLIENTID=$(grep 'CLIENTID' /etc/mailcleaner.conf | cut -d ' ' -f3)
HOSTID=$(grep 'HOSTID' /etc/mailcleaner.conf | cut -d ' ' -f3)

MAXSLEEPTIME=300
MINSLEEPTIME=120

. $SRCDIR/lib/lib_utils.sh
FILE_NAME=$(basename -- "$0")
FILE_NAME="${FILE_NAME%.*}"
ret=$(createLockFile "$FILE_NAME")
if [[ "$ret" -eq "1" ]]; then
	exit 0
fi

if $randomize; then
	sleep_time=$(($RANDOM * $(($MAXSLEEPTIME - $MINSLEEPTIME)) / 32767 + $MINSLEEPTIME))
	sleep $sleep_time
fi

if [ "$ISMASTER" = "Y" ] || [ "$ISMASTER" = "y" ]; then
	CONFIGFILE=/var/tmp/config.sql

	$($SRCDIR/bin/backup_config.sh $CONFIGFILE) >/dev/null 2>&1

	DATE=$(date +%Y%m%d)
	chmod g+w $CONFIGFILE
	scp -q -o PasswordAuthentication=no -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no $CONFIGFILE mcscp@team01.mailcleaner.net:/upload/configs/$CLIENTID-$HOSTID-$DATE.sql >/dev/null 2>&1
fi

removeLockFile "$FILE_NAME"
