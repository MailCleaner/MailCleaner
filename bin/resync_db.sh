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
#	usage: resync_db.sh [-F] [-C] [MHOST MPASS]
#	-F     Force resync. Ignore sync test
#	-C     Run as cron. Sends STDOUT to $LOGDIR
#	MHOST  master hostname
#	MPASS  master password

LOGDIR="/var/mailcleaner/spool/resync"
MHOST=''
MPASS=''

if [ ! -d $LOGDIR ]; then
  mkdir $LOGDIR
fi

for var in "$@"; do
  if [[ $var == '-F' ]]; then
    RUN=1
  elif [[ $var == '-C' ]]; then
    exec 1>"$LOGDIR/resync_$(date +%Y-%m-%d_%H:%M).log"
    exec 2>"/dev/null"
    # If failed on previous cron run, this file will exist with a count of failures
    if [ -e $LOGDIR/fail_count ]; then
      # If it has failed 12 times stop trying
      if [[ "`cat $LOGDIR/fail_count`" -ge 12 ]]; then
        echo "Failed to resync too many times. Exitting."
        exit
      fi
    fi
  # First default is master host
  elif [[ $MHOST == '' ]]; then
    MHOST=$var
  # Second default is master pass
  elif [[ $MPASS == '' ]]; then
    MPASS=$var
  # If both of the above are set, this var is excess
  else
    echo "Invalid or excess option '$var'.
usage: $0 [-F] [MHOST MPASS]
  -F     Force resync. Ignore sync test
  -C     Run as cron. Sends STDOUT to $LOGDIR
  MHOST  master hostname
  MPASS  master password"
    exit
  fi
done

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

function test_insert() {
  # Try a write on master and read on slave to ensure they are synced

  # httpd_config is a safe table to write so long as we don't overwrite set_id=1
  SETID=2

  echo "Testing insert on master..."
  # There may be insertions from previous failed tests, so increment through ids
  while [ 1 ]; do
    echo "insert into httpd_config(set_id) values('${SETID}');"
    echo "insert into httpd_config(set_id) values('${SETID}');" | $SRCDIR/bin/mc_mysql -m mc_config
    if [[ $? == 1 ]]; then
      echo "This id already exists, probably from a previous failed resync. Trying another."
      ((SETID+=1))
    else
    break
  fi
  done
    
  echo "Testing select on slave..."
  echo "select * from httpd_config where set_id = ${SETID};"
  if [[ $(echo "select * from httpd_config where set_id = ${SETID};" | $SRCDIR/bin/mc_mysql -s mc_config) ]]; then
    echo "Insertion successfully found."
  else
    echo "Insertion not found."
    # This tells us that we are not in sync, so set the remainer of the script to run
    RUN=1
  fi

  # Remove this test write and any that might be lingering from previous runs
  echo "Cleaning test insertion(s)..."
  for i in `seq 2 ${SETID}`; do
    echo "delete from httpd_config where set_id = ${i};"
    echo "delete from httpd_config where set_id = ${i};" | $SRCDIR/bin/mc_mysql -m mc_config
  done
}

test_insert
if [[ $RUN != 1 ]]; then
  echo "DBs are already in sync. Run with -F to force resync anyways." 
  if [[ -e $LOGDIR/fail_count ]]; then
    echo "Removing fail_count file"
    rm $LOGDIR/fail_count
  fi
  exit
else
  # Clear RUN as it will be used for the post-sync test result as well
  RUN=0
  echo "Running resync..."
fi

# Resync

MYMAILCLEANERPWD=`grep 'MYMAILCLEANERPWD' /etc/mailcleaner.conf | cut -d ' ' -f3`
echo "select hostname, password from master;" | $SRCDIR/bin/mc_mysql -s mc_config | grep -v 'password' | tr -t '[:blank:]' ':' > /var/tmp/master.conf

if [ "$MHOST" != "" ]; then
  export $MHOST
else 
  export MHOST=`cat /var/tmp/master.conf | cut -d':' -f1`
fi
if [ "$MPASS" != "" ]; then
  export $MPASS
else
  export MPASS=`cat /var/tmp/master.conf | cut -d':' -f2`
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

# Run the check again and record results
test_insert
if [[ $RUN != 1 ]]; then
  echo "Resync successful." 
  # If there were previous failures, remove that flag file
  if [[ -e $LOGDIR/fail_count ]]; then
    echo "Removing fail_count file"
    rm $LOGDIR/fail_count
  fi
  exit
else
  # If there were previous failures, get count and increment
  if [[ -e $LOGDIR/fail_count ]]; then
    COUNT=`cat $LOGDIR/fail_count`
    ((COUNT+=1))
  else
    COUNT=1
  fi
  # Set failure flag
  echo $COUNT > $LOGDIR/fail_count
fi
