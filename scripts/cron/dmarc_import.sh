#!/bin/bash

SRCDIR=$(grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3)
if [ "$SRCDIR" = "" ]; then
	SRCDIR=/usr/mailcleaner
fi
VARDIR=$(grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3)
if [ "$VARDIR" = "" ]; then
	VARDIR=/var/mailcleaner
fi
echo "select hostname, password from master;" | $SRCDIR/bin/mc_mysql -s mc_config | grep -v 'password' | tr -t '[:blank:]' ':' >/var/tmp/master.conf
MHOST=$(cat /var/tmp/master.conf | cut -d':' -f1)
MPASS=$(cat /var/tmp/master.conf | cut -d':' -f2)

if [ -s $VARDIR/spool/tmp/exim/dmarc.history ]; then

	echo -n "Importing to master database at $MHOST..."
	/opt/exim4/sbin/opendmarc-import --dbhost=$MHOST --dbport=3306 --dbname=dmarc_reporting --dbuser=mailcleaner --dbpasswd=$MPASS <$VARDIR/spool/tmp/exim/dmarc.history
	/bin/rm $VARDIR/spool/tmp/exim/dmarc.history
	/bin/touch $VARDIR/spool/tmp/exim/dmarc.history
	/bin/chown mailcleaner:mailcleaner $VARDIR/spool/tmp/exim/dmarc.history
	echo "done."
fi

exit 0
