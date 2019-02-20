#!/bin/bash
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2017 Mentor Reka <reka.mentor@gmail.com>
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
#   This script will fetch the mailcleaner binary
#
#   Usage:
#           fetch_binary.sh [-r]

usage()
{
  cat << EOF
usage: $0 options

This script will fetch mailcleaner binary

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

CONFFILE=/etc/mailcleaner.conf
SRCDIR=`grep 'SRCDIR' $CONFFILE | cut -d ' ' -f3`
if [ "$SRCDIR" = "" ]; then
  SRCDIR="/opt/mailcleaner"
fi
VARDIR=`grep 'VARDIR' $CONFFILE | cut -d ' ' -f3`
if [ "$VARDIR" = "" ]; then
  VARDIR="/var/mailcleaner"
fi


. $SRCDIR/lib/lib_utils.sh
FILE_NAME=$(basename -- "$0")
FILE_NAME="${FILE_NAME%.*}"
ret=$(createLockFile "$FILE_NAME")
if [[ "$ret" -eq "1" ]]; then
        exit 0
fi
LOGFILE=${VARDIR}/log/mailcleaner/downloadDatas.log
if [ "$(isGitupdateRunning)" -eq "1" ]; then
    log "Gitupdate running, skipping ${FILE_NAME}"
    removeLockFile "$FILE_NAME"
    exit 0
fi

. $SRCDIR/lib/updates/download_files.sh

##
## MC Binary updates
##

MC_BINARY_DIR=$SRCDIR/etc/exim/mc_binary
# Check if directory exists
if [ ! -d "$MC_BINARY_DIR" ]; then
	mkdir $MC_BINARY_DIR
fi

ret=$(downloadDatas "$SRCDIR/etc/exim/mc_binary/" "mc_binary" $randomize "null" "" "noexit")
if [[ "$ret" -eq "1" ]]; then
	$SRCDIR/etc/init.d/exim_stage1 restart &>> /dev/null
	log "Binary downloaded"
fi

removeLockFile "$FILE_NAME"

exit 0
