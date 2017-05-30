#!/bin/bash

DELAY=2

export PATH=$PATH:/sbin:/usr/sbin

SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "$SRCDIR" = "" ]; then
  SRCDIR=/opt/mailcleaner
fi

$SRCDIR/etc/init.d/mysql_slave stop 2>&1 > /dev/null
sleep $DELAY
PREVPROC=`pgrep -f /etc/mysql/my_slave.cnf`
if [ ! "$PREVPROC" = "" ]; then
        echo -n "FAILED"
        exit    
else
	echo -n "SUCCESSFULL"
fi
