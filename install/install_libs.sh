#!/bin/bash

BACK=`pwd`
if [ "$SRCDIR" = "" ]; then
        SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
        if [ "SRCDIR" = "" ]; then
                SRCDIR=/var/mailcleaner
        fi
fi

if [ "$USEDEBS" = "Y" ]; then
  echo -n " installing libraries binaries packages...";
  dpkg -i $SRCDIR/install/debs/mc-libs*.deb &> /dev/null
  echo "done.";
  exit
fi

######
## OpenSSL
#if [ ! -d /usr/openssl ]; then

echo "buils openssl ? "
read REP
if [ "$REP" = "y" ]; then

rm -rf /usr/openssl
echo -n "    building openssl ... "
cd $SRCDIR/install/src

tar -xvzf openssl.tar.gz 2>&1
cd openssl-0.9.8a
./config --prefix=/usr/openssl --shared 2>&1
make 2>&1
make install 2>&1

cd $BACK
rm -rf $SRCDIR/install/src/openssl-0.9.8a 2>&1
#fi

#echo "/usr/openssl/lib" >> /etc/ld.so.conf
ldconfig 2>&1

echo "done!"
fi

cd $SRCDIR/install/src

######
## zlib

echo "buil zlib ? "
read REP
if [ "$REP" = "y" ]; then

rm -rf /usr/zlib
tar -xvzf zlib.tar.gz 2>&1
cd zlib-1.2.3 2>&1
./configure --shared --prefix=/usr/zlib 2>&1
make 2>&1
make install 2>&1

echo "/usr/zlib/lib" >> /etc/ld.so.conf
ldconfig 2>&1

rm -rf $SRCDIR/install/src/zlib-1.2.3

echo "done!"
fi

#####
## libpng for mrtg
echo "buils libpng ? "
read REP
if [ "$REP" = "y" ]; then

rm -rf /usr/libpng
cd $SRCDIR/install/src

echo -n "    building libpng ..."

tar -xvzf libpng.tar.gz 2>&1
cd libpng-1.2.8

#./configure --prefix=/usr/libpng 2>&1
cp scripts/makefile.linux Makefile
perl -pi -e 's/prefix=\/usr\/local/prefix=\/usr\/libpng/' Makefile
perl -pi -e 's/ZLIBLIB=\.\.\/zlib/ZLIBLIB=\/usr\/zlib/' Makefile
perl -pi -e 's/ZLIBINC=\.\.\/zlib/ZLIBINC=\/usr\/zlib/' Makefile
make 2>&1
mkdir /usr/libpng
mkdir /usr/libpng/include
mkdir /usr/libpng/lib
make install 2>&1

cd $BACK
rm -rf $SRCDIR/install/src/libpng-1.2.8 2>&1
#echo "/usr/libpng/lib" >> /etc/ld.so.conf
ldconfig 2>&1
echo "done!"
fi

#####
## libjpeg

echo "buils libjpeg ? "
read REP
if [ "$REP" = "y" ]; then

rm -rf /usr/libjpeg
cd $SRCDIR/install/src

echo -n "    building libjpeg ..."

tar -xvzf libjpeg.tar.gz 2>&1
cd jpeg-6b

./configure --prefix=/usr/libjpeg --enable-shared --enable-static
make 2>&1
mkdir /usr/libjpeg
mkdir /usr/libjpeg/lib
mkdir /usr/libjpeg/include
mkdir /usr/libjpeg/bin
mkdir /usr/libjpeg/man
mkdir /usr/libjpeg/man/man1
make install 2>&1

cd $BACK
rm -rf $SRCDIR/install/src/jpeg-6b 2>&1
ldconfig 2>&1
echo "done!"

fi

#####
## gd for mrtg

echo "buils gd ? "
read REP
if [ "$REP" = "y" ]; then

rm -rf /usr/libgd
cd $SRCDIR/install/src

echo -n "    building GD ..."

tar -xvzf gd.tar.gz 2>&1
cd gd-2.0.33

export CPPFLAGS="-I /usr/libpng/include"
./configure --prefix=/usr/libgd --with-png=/usr/libpng --with-jpeg=/usr/libjpeg 2>&1
make 2>&1
make install 2>&1

cd $BACK
rm -rf $SRCDIR/install/src/gd-2.0.33 2>&1
#echo "/usr/libgd/lib" >> /etc/ld.so.conf
ldconfig 2>&1
echo "done!"

fi

#####
## imap libs for php

echo "build imap ? "
read REP
if [ "$REP" = "y" ]; then

cd $SRCDIR/install/src

echo -n "    building imap ..."

rm -rf /usr/imap-2004 2>&1
tar -xvzf imap.tar.gz 2>&1
mv imap-2004g/ /usr/imap-2004 2>&1
cd /usr/imap-2004
echo 'y' | make ldb SSLTYPE=none 2>&1
cd $BACK

echo "done!"
fi

#####
## xmlx libs for php
echo "build libxml2 ? "
read REP
if [ "$REP" = "y" ]; then

rm -rf /usr/libxml2
cd $SRCDIR/install/src 

echo -n "    building libxml ..."

tar -xvzf libxml2.tar.gz 2>&1
cd libxml2-2.6.23
export CC="gcc -w"
./configure --prefix=/usr/libxml2 2>&1
make 2>&1
make install 2>&1
cd $BACK
rm -rf $SRCDIR/install/src/libxml2-2.6.23 2>&1
#echo "/usr/libxml2/lib" >> /etc/ld.so.conf
ldconfig 2>&1
echo "done!"
fi


#####
## db for ldap
echo "build db ? "
read REP
if [ "$REP" = "y" ]; then

rm -rf /usr/db
cd $SRCDIR/install/src

echo -n "    building DB ..."

tar -xvzf db.tar.gz 2>&1
cd db-4.4.20/
cd build_unix/
../dist/configure --prefix=/usr/db 2>&1
make 2>&1
make install 2>&1
cd $BACK
rm -rf $SRCDIR/install/src/db-4.4.20/ 2>&1

#echo "/usr/db/lib" >> /etc/ld.so.conf
ldconfig 2>&1

fi

#####
## cyrus sasl for ldap
echo "build sasl ? "
read REP
if [ "$REP" = "y" ]; then
rm -rf /usr/sasl
cd $SRCDIR/install/src

echo -n "    building sasl ..."

export CPPFLAGS="-I /usr/openssl/include/openssl" 
export CFLAGS="-I /usr/openssl/include/openssl"
tar -xvzf sasl.tar.gz 2>&1
cd cyrus-sasl-2.1.21 
perl -pi -e 's/md4\.h\>/md4\.h\>\n\#include \<openssl\/md5\.h\>/' plugins/ntlm.c
./configure --prefix=/usr/sasl --with-openssl=/usr/openssl --enable-plain --enable-login --with-bdb-libdir=/usr/db/lib --with-dblib=berkeley --with-bdb-incdir=/usr/db/include --with-rc4 --with-pam --enable-shared --enable-static=no --enable-ntlm=yes --enable-login=yes --enable-plain=yes --enable-cram=yes --enable-digest=yes --enable-otp=disable 2>&1
make 2>&1
make install 2>&1
cd $BACK
rm -rf $SRCDIR/install/src/cyrus-sasl-2.1.21/ 2>&1
#echo "/usr/sasl/lib" >> /etc/ld.so.conf
ldconfig 2>&1
fi
#####
## mhash and mcrypt
echo "build mhash ? "
read REP
if [ "$REP" = "y" ]; then

rm -rf /usr/mhash
cd $SRCDIR/install/src

echo -n "    building mhash ..."

tar -xvjf mhash.tar.bz2 2>&1
cd mhash-0.9.4
./configure --prefix=/usr/mhash 2>&1
make 2>&1
make install 2>&1
cd include
../install-sh -c -m 644 'mutils/mincludes.h' '/usr/mhash/include/mutils/mincludes.h'
cd $BACK
rm -rf $SRCDIR/install/src/mhash-0.9.4
fi

echo "build mcrypt ? "
read REP
if [ "$REP" = "y" ]; then

cd $SRCDIR/install/src
echo -n "    building mcrypt ..."

rm -rf /usr/mcrypt
tar -xvzf libmcrypt.tar.gz 2>&1
cd libmcrypt-2.5.7
unset CC
unset CPPFLAGS
unset LDFLAGS
./configure --prefix=/usr/mcrypt --enable-dynamic-loading 2>&1
make 2>&1
make install 2>&1
cd $BACK
rm -rf $SRCDIR/install/src/libmcrypt-2.5.7
fi


