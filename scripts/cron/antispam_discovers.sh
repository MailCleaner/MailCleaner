#!/bin/bash

SRCDIR=$(grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3)
if [ "SRCDIR" = "" ]; then
	SRCDIR=/usr/mailcleaner
fi
VARDIR=$(grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3)
if [ "VARDIR" = "" ]; then
	VARDIR=/var/mailcleaner
fi
MYMAILCLEANERPWD=$(grep -e '^MYMAILCLEANERPWD' /etc/mailcleaner.conf | cut -d ' ' -f3)
HTTPPROXY=$(grep -e '^HTTPPROXY' /etc/mailcleaner.conf | cut -d ' ' -f3)
export http_proxy=$HTTPPROXY

####################
## razor discover ##
####################

su mailcleaner -c "razor-admin -discover"

####################
## pyzor discover ##
####################

su mailcleaner -c "pyzor discover" 2>&1 >/dev/null

if [ ! -d $VARDIR/.pyzor ]; then
	mkdir $VARDIR/.pyzor
fi
#echo "82.94.255.100:24441" > $VARDIR/.pyzor/servers
chown -R mailcleaner:mailcleaner $VARDIR/.pyzor

if [ ! -d /root/.pyzor ]; then
	mkdir /root/.pyzor
fi
cp $VARDIR/.pyzor/servers /root/.pyzor/servers
#echo "82.94.255.100:24441" > /root/.pyzor/servers
