#!/bin/bash

BACK=`pwd`

if [ "$SRCDIR" = "" ]; then
        SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
        if [ "SRCDIR" = "" ]; then
                SRCDIR=/var/mailcleaner
        fi
fi

if [ "$USEDEBS" = "Y" ]; then
  echo -n " installing snmp binaries package...";
  dpkg -i $SRCDIR/install/debs/mc-snmp*.deb &> /dev/null
  echo "done.";
  exit
fi

cd src/

tar -xvzf net-snmp.tar.gz
cd net-snmp-5.3.0.1

./configure --prefix=/usr/snmp --enable-embeded-perl --with-openssl=/usr/openssl/ --without-root-access --with-default-snmp-version="3" --with-sys-contact="root" --with-sys-location="Unknown" --with-logfile="/tmp/snmpd.log" --with-persistent-directory="/var/net-snmp" 2>&1
make 2>&1
make install 2>&1

cd $BACK
rm -rf $SRCDIR/install/src/net-snmp-5.3.0.1

#$SRCDIR/bin/dump_snmpd_config.pl 2>&1
