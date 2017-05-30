#!/bin/bash

SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "$SRCDIR" = "" ]; then
  SRCDIR=/opt/mailcleaner
fi

$SRCDIR/etc/init.d/greylistd start 2>&1 > /dev/null

echo -n "SUCCESSFULL"
