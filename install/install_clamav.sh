#!/bin/sh

BACK=`pwd`
if [ "$SRCDIR" = "" ]; then
        SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
        if [ "SRCDIR" = "" ]; then
                SRCDIR=/var/mailcleaner
        fi
fi
if [ "$VARDIR" = "" ]; then
        VARDIR=`grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
        if [ "VARDIR" = "" ]; then
                VARDIR=/var/mailcleaner
        fi
fi

if [ "$USEDEBS" = "Y" ]; then
  echo -n " installing clamav binaries packages...";
  aptitude install mc-clamav &> /dev/null
  echo "done.";
else

cd $SRCDIR/install/src

groupadd clamav 2>&1
useradd -g clamav -s /bin/false -c "Clam AntiVirus" clamav 2>&1

tar -xvzf clamav.tar.gz
cd clamav-0.88.2

./configure --prefix=/opt/clamav --sysconfdir=$SRCDIR/etc/clamav --with-zlib=/usr/zlib 2>&1
make 2>&1
make install 2>&1

#$SRCDIR/bin/dump_clamav_config.pl 2>&1

#/usr/local/bin/freshclam 2>&1

cd $BACK
rm -rf $SRCDIR/install/src/clamav-0.88.2 2>&1
fi

