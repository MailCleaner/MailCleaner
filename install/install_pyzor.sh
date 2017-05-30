#!/bin/bash

BACK=`pwd`
if [ "$SRCDIR" = "" ]; then
        SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
        if [ "SRCDIR" = "" ]; then
                SRCDIR=/var/mailcleaner
        fi
fi

exit 0

cd $SRCDIR/install/src

tar -xvjf pyzor.tar.bz2
cd pyzor-0.4.0
python setup.py build 2>&1
python setup.py install 2>&1
#su mailcleaner -c "pyzor discover" 2>&1

cd $BACK
rm -rf $SRCDIR/install/src/pyzor-0.4.0

chmod a+rx /usr/bin/pyzor

