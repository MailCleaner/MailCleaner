#!/bin/bash

DELAY=2

export PATH=$PATH:/sbin:/usr/sbin

SRCDIR=$(grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3)
if [ "$SRCDIR" = "" ]; then
	SRCDIR=/usr/mailcleaner
fi

$SRCDIR/etc/init.d/exim_stage1 stop 2>&1 >/dev/null
sleep $DELAY
PREVPROC=$(pgrep -f /etc/exim/exim_stage1)
if [ ! "$PREVPROC" = "" ]; then
	echo -n "FAILED"
	exit 1
fi
echo -n "SUCCESSFULL"
exit 0
