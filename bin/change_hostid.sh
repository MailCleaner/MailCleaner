#!/bin/bash
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2017 Florian Billebault <florian.billebault@gmail.com>
#
#   This program is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; either version 3 of the License, or
#   (at your option) any later version.
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program. If not, see <http://www.gnu.org/licenses/>.
#
#
#   This script let you set/change the MailCleaner Host ID
#   WARNING!:  Before using it, delete the concerned node of your cluster
#              then re-add it to the cluster after the change.
#   WARNING2!: Previously quarantined messages of this node will not be
#              deliverable anymore.
#
#   Usage:
#           change_hostid.sh NEWID [-f]


function check_parameter {
        if [ "$1" = "y" ]; then
                let RETURN=1
        else
                echo "y or Ctrl+C"
                let RETURN=0
        fi
}


CONFFILE=/etc/mailcleaner.conf
REGISTERED=`grep 'REGISTERED' $CONFFILE | cut -d ' ' -f3`
OLDID=`grep 'HOSTID' $CONFFILE | cut -d ' ' -f3`

if [ "$REGISTERED" == "1" ] || [ "$REGISTERED" == "2" ]; then
  echo "Your MailCleaner has to be unregistered first !"
  exit 1
fi

NEWID=$1
if [ "$NEWID" = "" ]; then
  echo "Usage: change_hostid.sh NEWID"
  exit 1
fi

FORCE=$2

REMOVED=""
[ "$FORCE" = "-f" ] && REMOVED="y"
if [ "$REMOVED" = "" ]; then
let RETURN=0
while [ $RETURN -lt 1 ]; do
        echo "Did you removed this host from the cluster (Mandatory) ? [y] : "
        read REMOVED
        check_parameter $REMOVED
done
fi

CONFIRM=""
[ "$FORCE" = "-f" ] && CONFIRM="y"
if [ "$CONFIRM" = "" ]; then
let RETURN=0
while [ $RETURN -lt 1 ]; do
        echo "Host ID will change to $NEWID"
	echo "WARNING: Previously quarantined messages on THIS host  will not be deliverable anymore. ok ? [y]:"
        read CONFIRM
        check_parameter $CONFIRM
done
fi

sed -i "s/^HOSTID.*$/HOSTID = $NEWID/g" $CONFFILE
echo "update slave set id=$NEWID where id=$OLDID;" |/usr/mailcleaner/bin/mc_mysql -m mc_config
echo "SUCCESS"
exit 0
