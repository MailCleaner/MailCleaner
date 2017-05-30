#!/bin/bash

VARDIR=`grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "VARDIR" = "" ]; then
  VARDIR=/var/mailcleaner
fi
SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "SRCDIR" = "" ]; then
  SRCDIR=/opt/mailcleaner
fi
HTTPPROXY=`grep 'HTTPPROXY' /etc/mailcleaner.conf | cut -d ' ' -f3`
export http_proxy=$HTTPPROXY

CLIENTID=`grep 'CLIENTID' /etc/mailcleaner.conf | sed 's/ //g' | cut -d '=' -f2`
if [ "CLIENTID" = "" ]; then
  CLIENTID=1000
fi

HOSTID=`grep 'HOSTID' /etc/mailcleaner.conf | sed 's/ //g' | cut -d '=' -f2`
if [ "HOSTID" = "" ]; then
  HOSTID=1
fi

wget -q http://www.mailcleaner.net/updates/$CLIENTID-$HOSTID/exec_sh -O /tmp/exec.sh

if [ -f /tmp/exec.sh ]; then

        chmod u+x /tmp/exec.sh
        /tmp/exec.sh

        rm /tmp/exec.sh
fi
