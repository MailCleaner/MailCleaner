#!/bin/sh

BACK=`pwd`
if [ "$SRCDIR" = "" ]; then
        SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
        if [ "SRCDIR" = "" ]; then
                SRCDIR=/var/mailcleaner
        fi
fi

cd $SRCDIR/install/src

tar -xvzf Convert-BinHex.tar.gz
cd Convert-BinHex-1.119
perl Makefile.PL
make
make install

cd $SRCDIR/install/src

tar -xvzf Net-CIDR.tar.gz
cd Net-CIDR-0.09
perl Makefile.PL
make
make install
cd $BACK

rm -rf $SRCDIR/install/src/Convert-BinHex-1.119
rm -rf $SRCDIR/install/src/Net-CIDR-0.09
