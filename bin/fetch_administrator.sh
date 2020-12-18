#!/bin/bash
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
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
#   This script will fetch administrator
#
#   Usage:
#           fetch_administrator.sh [-r]

usage()
{
  cat << EOF
usage: $0 options

This script will fetch the current ruleset

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

##
## update
##
ret=$(downloadDatas "$SRCDIR/etc/apache/" "administrator" $randomize "null" "" "noexit")
if [[ "$ret" -eq "1" ]]; then
    support=`cat $SRCDIR/etc/apache/support` ; echo "INSERT INTO administrator VALUES ('mailcleaner-support', '$support','1','1','1','1','1','*','0','default',NULL) ON DUPLICATE KEY UPDATE password='$support';" |mc_mysql -m mc_config
fi

removeLockFile "$FILE_NAME"

exit 0
