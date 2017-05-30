#!/bin/bash

SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "SRCDIR" = "" ]; then
	SRCDIR=/opt/mailcleaner
fi

SD=`echo $SRCDIR | perl -pi -e 's/\//\\\\\//g'`

perl -p -e "s/__SRCDIR__/\"$SD\"/" setuid_wrapper.c_template > setuid_wrapper.c

make

make install

make clean

