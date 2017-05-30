#!/bin/bash

BACK=`pwd`
if [ "$SRCDIR" = "" ]; then
        SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
        if [ "SRCDIR" = "" ]; then
                SRCDIR=/var/mailcleaner
        fi
fi

if [ "$USEDEBS" = "Y" ]; then
  echo -n " installing mysql binaries package...";
  dpkg -i $SRCDIR/install/debs/mc-mysql*.deb &> /dev/null
  echo "done.";
  exit
fi

cd $SRCDIR/install/src

tar -xvzf mysql.tar.gz
cd mysql-4.1.18

export FLAGS="-O3 -mpentiumpro" 
export CXX=gcc 
export CXXFLAGS="-O3 -mpentiumpro -felide-constructors -fno-exceptions -fno-rtti"

#./configure --prefix=/opt/mysql5 --sysconfdir=/opt/mysql5/etc --enable-assembler --with-mysqld-ldflags=-all-static \
#	--localstatedir=/var/mysql5 --with-unix-socket-path=/var/mysql5/mysql.sock  2>&1
./configure --prefix=/opt/mysql5 --sysconfdir=/opt/mysql5/etc --enable-assembler --localstatedir=/var/mysql5 --with-unix-socket-path=/var/mysql5/mysql.sock --with-openssl=/usr/openssl 2>&2
##
# cannot use openssl with all-static.. for performance, we chose not to use openssl
# for security, add this to the configure:
#	--with-openssl --with-openssl-include=/usr/include/openssl

make 2>&1
make install 2>&1

##
# to tell the system of the new libs

#echo "/opt/mysql5/lib/mysql" >> /etc/ld.so.conf
#ldconfig 

#mkdir /var/mysql5 2>&1
#chown mysql:mysql /var/mysql5 2>&1

cd $BACK
rm -rf $SRCDIR/install/src/mysql-4.1.18 2>&1
