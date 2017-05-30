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

#tar -xvzf razor-agents-sdk.tar.gz
#cd razor-agents-sdk-2.03
#perl Makefile.PL 2>&1
#make 2>&1
#make install 2>&1
#cd ..
tar -xvjf razor-agents.tar.bz2
cd razor-agents-2.82
perl Makefile.PL 2>&1
make 2>&1
make install 2>&1

cd $BACK
#rm -rf $SRCDIR/install/src/razor-agents-sdk-2.03
rm -rf $SRCDIR/install/src/razor-agents-2.82

#su mailcleaner -c "/usr/bin/razor-admin -discover"
