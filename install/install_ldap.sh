#!/bin/bash

BACK=`pwd`
if [ "$SRCDIR" = "" ]; then
        SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
        if [ "SRCDIR" = "" ]; then
                SRCDIR=/var/mailcleaner
        fi
fi

if [ "$USEDEBS" = "Y" ]; then
  echo -n " installing ldap binaries package...";
  dpkg -i $SRCDIR/install/debs/mc-ldap*.deb &> /dev/null
  echo "done.";
  exit
fi

cd $SRCDIR/install/src

tar -xvzf ldap.tar.gz 2>&1
cd openldap-2.3.20
export LDFLAGS="-L/usr/openssl/lib -L/usr/db/lib -L/usr/sasl/lib -L/opt/mysql5/lib"
export CPPFLAGS="-I/usr/openssl/include -I/usr/db/include -I/usr/sasl/include/sasl -I/opt/mysql5/include"
export CFLAGS="-I/usr/openssl/include -I/usr/db/include -I/usr/sasl/include/sasl -I/opt/mysql5/include"

./configure --prefix=/usr/ldap --with-cyrus-sasl=yes --with-tls --with-sql 2>&1
make depend 2>&1
make 2>&1
make install 2>&1

echo "/usr/ldap/lib" >> /etc/ld.so.conf
ldconfig 2>&1

cd $BACK
rm -rf $SRCDIR/install/src/openldap-2.3.20

