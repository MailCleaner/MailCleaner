#!/bin/bash

export PATH=$PATH:/sbin:/usr/sbin

SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "$SRCDIR" = "" ]; then
  SRCDIR=/opt/mailcleaner
fi

$SRCDIR/etc/init.d/snmpd restart 2>&1 > /dev/null
if test $? -ne 0 ; then
  echo -n "FAILED"
  exit 1
fi
echo -n "SUCCESSFULL"
exit 0
