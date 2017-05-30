#!/bin/bash
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2017 Mentor Reka <reka.mentor@gmail.com>
#   Copyright (C) 2017 Florian Billebault <florian.billebault@gmail.com>
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
#   This script will fetch the rbls files
#
#   Usage:
#           fetch_rbls.sh [-r]

usage()
{
  cat << EOF
usage: $0 options

This script will fetch the rbls files

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

. $SRCDIR/lib/updates/download_files.sh

##
## Watchdog modules updates
##
COMMUNITY_RBLS_LIST="\|two-level-tlds.txt\|SORBS.cf\|URIBL.cf\|SPAMCOP.cf\|UCEPROTECTC.cf\|BBARRACUDACENTRALORG.cf\|UCEPROTECTB.cf\|IPSBACKSCATTERERORG.cf\|SURBL.cf\|whitelisted_domains.txt\|SPAMHAUS.cf\|IXDNSBLMANITUNET.cf\|UCEPROTECTA.cf\|domains_hostnames_map.txt\|tlds.txt\|SPAMHAUSDBL.cf\|effective_tlds.txt\|DNSWL.cf\|url_shorteners.txt"
downloadDatas "$SRCDIR/etc/rbls/" "rbls" $randomize "null" "$COMMUNITY_RBLS_LIST"

log "RBLs checked"

exit 0
