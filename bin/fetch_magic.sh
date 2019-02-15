#!/bin/bash
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2015-2017 Mentor Reka <reka.mentor@gmail.com>
#   Copyright (C) 2015-2017 Florian Billebault <florian.billebault@gmail.com>
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
#   This script will fetch the last magic
#
#   Usage:
#           fetch_magic.sh [-r]

usage()
{
  cat << EOF
usage: $0 options

This script will fetch the last magic.mgc

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

. $SRCDIR/lib/updates/download_files.sh

MC_FILE_DIR=/opt/file/share/misc/
downloadDatas "$MC_FILE_DIR" "magic" $randomize "null" "" "noexit"
log "Magic downloaded"

removeLockFile "$FILE_NAME"
exit 0
