#!/bin/bash

BACK=`pwd`

if [ "$SRCDIR" = "" ]; then
        SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
        if [ "SRCDIR" = "" ]; then
                SRCDIR=/var/mailcleaner
        fi
fi

if [ "$USEDEBS" = "Y" ]; then
  echo -n " installing exim binaries packages...";
  aptitude install mc-exim &> /dev/null
  #dpkg -i $SRCDIR/install/debs/mc-exim*.deb &> /dev/null
  echo "done.";
else

if [ ! -d /opt/exim4 ]; then
	mkdir /opt/exim4
fi

cd $SRCDIR/install/src/

tar -xvjf exim.tar.bz2
cd exim-4.61

cp ../exim4_Makefile Local/Makefile

make 2>&1
make install 2>&1

cd $BACK
rm -rf $SRCDIR/install/src/exim-4.61
fi
