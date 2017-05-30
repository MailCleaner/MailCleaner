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


################################################
# updateDatabases
#
# param  integer  is critical or not
#        if yes and function failed, then will exit script
# return 1 on success, 0 on failure
################################################
function updateDatabases {
  export RETURN_VALUE=0
  CRITICAL=$1
  if [ -f /tmp/cvs.log ]; then
   rm /tmp/cvs.log
  fi
  ### first get the sources directory
  SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
  if [ "SRCDIR" = "" ]; then
    SRCDIR=/src/mailcleaner
  fi

  ## then update database references
  echo "-- Updating cvs for database references..." >> $LOGFILE
  cd $SRCDIR/install/dbs
#
#  $SRCDIR/bin/fetch_databases.sh
  OUTRES=$?

  if [ ! "$OUTRES" = "0" ]; then
    export ERRSTR="could not update database descriptions"
    export RETURN_VALUE=0
    if [ "$CRITICAL" = "1" ]; then
      echo $ERRSTR
      echo ABORTED
      rm /tmp/update_$PATCHNUM.lock 2>&1 >> $LOGFILE
      exit 1
    fi
  fi
  echo "--  ...OK !" >> $LOGFILE
  
  if [ -f /tmp/update_db.log ]; then
   rm /tmp/update_db.log
  fi
  ISMASTER=`grep 'ISMASTER' /etc/mailcleaner.conf | cut -d ' ' -f3`
  if [ "$ISMASTER" = "Y" ] || [ "$ISMASTER" = "y" ]; then
  	echo "-- Checking and updating master database..." >> $LOGFILE
    RES=`$SRCDIR/bin/check_db.pl -m --update 2>&1 | tee /tmp/update_db.log >> $LOGFILE`
    OUTRES=$?
    OUT=`cat /tmp/update_db.log`;
    if [ ! "$?" = "0" ]; then
  	  export ERRSTR="Could not update master database !"
  	  echo ABORTED
  	  rm /tmp/update_$PATCHNUM.lock 2>&1 >> $LOGFILE
  	  exit 1
  	fi
  	echo "--  ...OK !" >> $LOGFILE
  else
    echo "-- Checking slave database..." >> $LOGFILE
    RES=`$SRCDIR/bin/check_db.pl -s 2>&1 | tee /tmp/update_db.log >> $LOGFILE`
    OUTRES=$?
    OUT=`cat /tmp/update_db.log`;
    if [ ! "$?" = "0" ] | [ ! "$OUT" = "" ]; then
  	  export ERRSTR="Database not in sync, waiting for master to get updated first !"
  	  echo $ERRSTR >> $LOGFILE
  	  echo WAITINGMASTER
  	  rm /tmp/update_$PATCHNUM.lock 2>&1 >> $LOGFILE
  	  exit 0
  	fi
  	echo "--   ...OK !" >> $LOGFILE
  fi 
  
  return $RETURN_VALUE
 }
  
  
