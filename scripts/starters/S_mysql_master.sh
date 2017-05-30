#!/bin/bash

DELAY=4

export PATH=$PATH:/sbin:/usr/sbin

SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "$SRCDIR" = "" ]; then
  SRCDIR=/opt/mailcleaner
fi

PREVPROC=`pgrep -f /etc/mysql/my_master.cnf`
if [ ! "$PREVPROC" = "" ]; then
	echo -n "ALREADYRUNNING"
	exit	
fi

$SRCDIR/etc/init.d/mysql_master start 2>&1 > /dev/null
sleep $DELAY
PREVPROC=`pgrep -f /etc/mysql/my_master.cnf`
if [ "$PREVPROC" = "" ]; then
        echo -n "ERRORSTARTING"
        exit
else
	echo -n "SUCCESSFULL"
fi
