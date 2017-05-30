#!/bin/sh

BACK=`pwd`
if [ "$SRCDIR" = "" ]; then
        SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
        if [ "SRCDIR" = "" ]; then
                SRCDIR=/var/mailcleaner
        fi
fi

exit 0

cd $SRCDIR/install/src
tar -xvzf dcc-dccd.tar.Z
cd dcc-1.3.42
./configure 2>&1
make 2>&1
make install 2>&1
cp $SRCDIR/etc/dcc_conf /var/dcc/
$SRCDIR/etc/init.d/rcDCC stop 2>&1
sleep 5
$SRCDIR/etc/init.d/rcDCC start 2>&1

cd $BACK
rm -rf $SRCDIR/install/src/dcc-1.3.42
