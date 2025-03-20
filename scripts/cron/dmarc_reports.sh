#!/bin/bash

SRCDIR=$(grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3)
if [ "$SRCDIR" = "" ]; then
	SRCDIR=/usr/mailcleaner
fi
VARDIR=$(grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3)
if [ "$VARDIR" = "" ]; then
	VARDIR=/var/mailcleaner
fi

DOIT=$(echo "SELECT dmarc_enable_reports FROM mta_config WHERE stage=1;" | $SRCDIR/bin/mc_mysql -s mc_config | grep -v 'dmarc_enable_reports')
if [ "$DOIT" != "1" ]; then
	exit 0
fi
echo "select hostname, password from master;" | $SRCDIR/bin/mc_mysql -s mc_config | grep -v 'password' | tr -t '[:blank:]' ':' >/var/tmp/master.conf
MHOST=$(cat /var/tmp/master.conf | cut -d':' -f1)
MPASS=$(cat /var/tmp/master.conf | cut -d':' -f2)
ISMASTER=$(grep 'ISMASTER' /etc/mailcleaner.conf | cut -d ' ' -f3)

SYSADMIN=$(echo "SELECT summary_from FROM system_conf;" | $SRCDIR/bin/mc_mysql -s mc_config | grep '\@')
if [ "$SYSADMIN" != "" ]; then
	SYSADMIN=" --report-email $SYSADMIN"
fi

if [ "$ISMASTER" == "Y" ] || [ "$ISMASTER" == "y" ]; then
	echo -n "Generating DMARC reports..."
	if [ ! -d /tmp/dmarc_reports ]; then
		mkdir /tmp/dmarc_reports
	fi
	CURDIR=$(pwd)
	cd /tmp/dmarc_reports
	echo "*****************************" >>$VARDIR/log/mailcleaner/dmarc_reporting.log
	/opt/exim4/sbin/opendmarc-reports --dbhost=$MHOST --dbport=3306 --dbname=dmarc_reporting --dbuser=mailcleaner --dbpasswd=$MPASS --smtp-port 587 --verbose --verbose --interval=86400 $SYSADMIN 2>>$VARDIR/log/mailcleaner/dmarc_reporting.log
	echo "**********" >>$VARDIR/log/mailcleaner/dmarc_reporting.log
	echo "Expiring database..." >>$VARDIR/log/mailcleaner/dmarc_reporting.log
	/opt/exim4/sbin/opendmarc-expire --dbhost=$MHOST --dbport=3306 --dbname=dmarc_reporting --dbuser=mailcleaner --dbpasswd=$MPASS --expire=180 --verbose 2 &>>$VARDIR/log/mailcleaner/dmarc_reporting.log
	echo "Done expiring." >>$VARDIR/log/mailcleaner/dmarc_reporting.log
	echo "*****************************" >>$VARDIR/log/mailcleaner/dmarc_reporting.log
	cd $CURDIR
	echo "done."
fi

exit 0
