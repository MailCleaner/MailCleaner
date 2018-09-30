#!/bin/bash
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2017 Reka Mentor <reka.mentor@gmail.com>
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
#   This script will send anonymous statistics only for registered MailCleaners
#   who have checked the "I accept to send anonymous statistics" on the "Register as a Community Edition" form
#   in Configuration > Base system > Registration
#
#   Usage:
#		send_anon.sh

CONFFILE=/etc/mailcleaner.conf

HOSTID=`grep 'HOSTID' $CONFFILE | cut -d ' ' -f3`
if [ "$HOSTID" = "" ]; then
  HOSTID=1
fi
SRCDIR=`grep 'SRCDIR' $CONFFILE | cut -d ' ' -f3`
if [ "$SRCDIR" = "" ]; then 
  SRCDIR="/opt/mailcleaner"
fi
VARDIR=`grep 'VARDIR' $CONFFILE | cut -d ' ' -f3`
if [ "$VARDIR" = "" ]; then
  VARDIR="/opt/mailcleaner"
fi

HTTPPROXY=`grep -e '^HTTPPROXY' $CONFFILE | cut -d ' ' -f3`
export http_proxy=$HTTPPROXY

REGISTERED=`grep 'REGISTERED' $CONFFILE | cut -d ' ' -f3`

# For unregistered MailCleaner there is no stats to send
if [ "$REGISTERED" != "2" ]; then
	exit 0
fi

. $SRCDIR/lib/lib_utils.sh
FILE_NAME=$(basename -- "$0")
FILE_NAME="${FILE_NAME%.*}"
ret=$(createLockFile "$FILE_NAME")
if [[ "$ret" -eq "1" ]]; then
        exit 0
fi

# Check if customer choose to send anonymous statistics
ACCEPT_SEND_STATISTICS=$(echo "SELECT accept_send_statistics FROM registration LIMIT 1\G" | $SRCDIR/bin/mc_mysql -m mc_community | grep -v "*" | cut -d ':' -f2 | tr -d '[:space:]')
if [ "$ACCEPT_SEND_STATISTICS" != "1" ]; then
	exit 0
fi

# Basic URL
URL="http://reselleradmin.mailcleaner.net/community/stats.php?"
STATS=$($SRCDIR/bin/get_stats.pl _global -1 +0)
if [ -z "$STATS" ]; then
	# No stats for last day ..
	exit 0
fi

# Send data
http_params=""
IFS='|' read -r -a stats <<< "$STATS"
keys=('msgs' 'spams' 'highspams' 'viruses' 'names' 'others' 'cleans' 'bytes' 'users' 'domains')
i=0
for element in "${stats[@]}"
do
    http_params=$http_params"${keys[${i}]}=$element&"
    i=$((i+1))
done
http_params=$(echo $http_params | sed 's/&$//')

URL="$URL$http_params"
wget -q "$URL" -O /tmp/mc_registerce.out >/tmp/mc_registerce.debug 2>&1


removeLockFile "$FILE_NAME"

echo "SUCCESS"
exit 0
