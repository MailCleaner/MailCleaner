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
#   This script will resync the configuration database
#   Usage: 
#           resync_db.sh 


VARDIR=`grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "VARDIR" = "" ]; then
  VARDIR=/var/mailcleaner
fi
SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "SRCDIR" = "" ]; then
  SRCDIR=/var/mailcleaner
fi
echo "starting slave db..."
$SRCDIR/etc/init.d/mysql_slave start
sleep 5

MYMAILCLEANERPWD=`grep 'MYMAILCLEANERPWD' /etc/mailcleaner.conf | cut -d ' ' -f3`
echo "select hostname, password from master;" | $SRCDIR/bin/mc_mysql -s mc_config | grep -v 'password' | tr -t '[:blank:]' ':' > /var/tmp/master.conf
export MHOST=`cat /var/tmp/master.conf | cut -d':' -f1`
export MPASS=`cat /var/tmp/master.conf | cut -d':' -f2`

if [ "$1" != "" ]; then
  export MHOST=$1
fi
if [ "$2" != "" ]; then
  export MPASS=$2
fi

/opt/mysql5/bin/mysqldump -S$VARDIR/run/mysql_slave/mysqld.sock -umailcleaner -p$MYMAILCLEANERPWD mc_config update_patch > /var/tmp/updates.sql

/opt/mysql5/bin/mysqldump -h $MHOST -umailcleaner -p$MPASS --master-data mc_config > /var/tmp/master.sql
$SRCDIR/etc/init.d/mysql_slave stop 
sleep 2
rm $VARDIR/spool/mysql_slave/master.info  >/dev/null 2>&1
rm $VARDIR/spool/mysql_slave/mysqld-relay*  >/dev/null 2>&1
rm $VARDIR/spool/mysql_slave/relay-log.info >/dev/null 2>&1
$SRCDIR/etc/init.d/mysql_slave start nopass
sleep 5
echo "STOP SLAVE;" | $SRCDIR/bin/mc_mysql -s 
sleep 2
rm $VARDIR/spool/mysql_slave/master.info >/dev/null 2>&1
rm $VARDIR/spool/mysql_slave/mysqld-relay* >/dev/null 2>&1 
rm $VARDIR/spool/mysql_slave/relay-log.info >/dev/null 2>&1

$SRCDIR/bin/mc_mysql -s mc_config < /var/tmp/master.sql

sleep 2
echo "CHANGE MASTER TO master_host='$MHOST', master_user='mailcleaner', master_password='$MPASS'; " | $SRCDIR/bin/mc_mysql -s 
$SRCDIR/bin/mc_mysql -s mc_config < /var/tmp/master.sql
echo "START SLAVE;" | $SRCDIR/bin/mc_mysql -s 
sleep 5

$SRCDIR/etc/init.d/mysql_slave restart
sleep 5
$SRCDIR/bin/mc_mysql -s mc_config < /var/tmp/updates.sql

