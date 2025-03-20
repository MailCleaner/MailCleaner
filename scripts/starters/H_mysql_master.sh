#!/bin/bash

DELAY=2

export PATH=$PATH:/sbin:/usr/sbin

SRCDIR=$(grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3)
if [ "$SRCDIR" = "" ]; then
	SRCDIR=/usr/mailcleaner
fi

$SRCDIR/etc/init.d/mysql_master stop 2>&1 >/dev/null
sleep $DELAY
PREVPROC=$(pgrep -f /etc/mysql/my_master.cnf)
if [ ! "$PREVPROC" = "" ]; then
	echo -n "FAILED"
	exit
else
	echo -n "SUCCESSFULL"
fi
