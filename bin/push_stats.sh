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

usage()
{
  cat << EOF
usage: $0 options

This script will push statistics

OPTIONS:
  -r   randomize start of the script, for automated process
EOF
}

randomize=false

while getopts ":r" OPTION
do
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

SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "SRCDIR" = "" ]; then
  SRCDIR=/var/mailcleaner
fi
VARDIR=`grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "VARDIR" = "" ]; then
  VARDIR=/var/mailcleaner
fi

DOMAINFILE=$VARDIR/spool/tmp/mailcleaner/domains.list
STATFILE=/var/tmp/stats_to_push
MAXSLEEPTIME=300
MINSLEEPTIME=120

. $SRCDIR/lib/lib_utils.sh
FILE_NAME=$(basename -- "$0")
FILE_NAME="${FILE_NAME%.*}"
ret=$(createLockFile "$FILE_NAME")
if [[ "$ret" -eq "1" ]]; then
        exit 0
fi

if $randomize ; then
  sleep_time=$(($RANDOM * $(($MAXSLEEPTIME - $MINSLEEPTIME)) / 32767 + $MINSLEEPTIME))
  sleep $sleep_time
fi

echo "_global:"`$SRCDIR/bin/get_stats.pl '*' -1 +0 | grep '_global' | cut -d':' -f2` > $STATFILE
for dom in `grep -v '*' $DOMAINFILE | cut -d':' -f1`; do
  echo -n $dom":" >> $STATFILE
  echo `$SRCDIR/bin/get_stats.pl $dom -1 +0 ` >> $STATFILE
done

CLIENTID=`grep 'CLIENTID' /etc/mailcleaner.conf | cut -d ' ' -f3`
HOSTID=`grep 'HOSTID' /etc/mailcleaner.conf | cut -d ' ' -f3`

DATE=`date --date "now -1 day" +%Y%m%d`
chmod g+w $STATFILE
scp -q -o PasswordAuthentication=no -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no $STATFILE mcscp@cvs.mailcleaner.net:/upload/stats/$CLIENTID-$HOSTID-$DATE.txt >/dev/null 2>&1

removeLockFile "$FILE_NAME"
