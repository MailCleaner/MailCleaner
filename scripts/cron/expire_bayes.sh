#!/bin/bash

SRCDIR=$(grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3)
if [ "$SRCDIR" = "" ]; then
	SRCDIR=/usr/mailcleaner
fi
VARDIR=$(grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3)
if [ "$VARDIR" = "" ]; then
	VARDIR=/var/mailcleaner
fi

# remove bayes_seen if > 15M
SIZE=$(ls -l $VARDIR/spool/spamassassin/bayes_seen | cut -d' ' -f5)
if [ $SIZE -gt 15000000 ]; then
	rm $VARDIR/spool/spamassassin/bayes_seen
fi
# remove sa and clamav temp files
if [ -d /dev/shm ]; then
	rm -rf /dev/shm/.spam* >/dev/null 2>&1
	rm -rf /dev/shm/clamav* >/dev/null 2>&1
	rm -rf /dev/shm/* >/dev/null 2>&1
fi

#sa-learn -p $SRCDIR/etc/mailscanner/spam.assassin.prefs.conf --force-expire 2>&1
chown -R mailcleaner:mailcleaner $VARDIR/spool/spamassassin

# purge stock
find $VARDIR/spool/learningcenter/stockham/ -ctime +3 -exec rm -rf \{\} \; >/dev/null 2>&1
find $VARDIR/spool/learningcenter/stockspam/ -ctime +3 -exec rm -rf \{\} \; >/dev/null 2>&1
