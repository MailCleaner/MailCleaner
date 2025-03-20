#!/bin/bash

DELAY=2

export PATH=$PATH:/sbin:/usr/sbin

SRCDIR=$(grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3)
if [ "$SRCDIR" = "" ]; then
	SRCDIR=/usr/mailcleaner
fi

$SRCDIR/etc/init.d/mailscanner stop 2>&1 >/dev/null
sleep $DELAY
PREVPROC=$(pgrep -f mailscanner/bin/MailScanner)

while $(pgrep -f mailscanner/bin/MailScanner); do
	sleep 1
	i=$(expr $i + 1)
	if [ "$i" = "$DELAY" ]; then
		echo -n "FAILED"
		exit 1
	fi
done
echo -n "SUCCESSFULL"
