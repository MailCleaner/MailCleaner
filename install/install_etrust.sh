#!/bin/sh

BACK=`pwd`
if [ "$SRCDIR" = "" ]; then
        SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
        if [ "SRCDIR" = "" ]; then
                SRCDIR=/var/mailcleaner
        fi
fi

apt-get install sudo

cd $SRCDIR/install/src

mkdir /usr/etrust
cp etrust.tar.gz /usr/etrust/
cd /usr/etrust
tar -xvzf etrust.tar.gz

export CAIGLBL0000=/usr/etrust

cat >> /etc/ld.so.conf << EOF
/usr/etrust/lib
/usr/etrust/ino/lib
/usr/etrust/ino/config
EOF
ldconfig

/usr/etrust/ino/scripts/InoInstall

HOST=`cat /etc/hostname`
cat >> /etc/sudoers << EOF
User_Alias      MAILCLEANER = mailcleaner
Runas_Alias     ROOT = root
Host_Alias      LOCALHOST = $HOSTNAME
Cmnd_Alias      ETRUST = $SRCDIR/bin/etrust_wrapper.sh
Cmnd_Alias	ETRUSTUPDATE = $SRCDIR/bin/etrust_updater.sh
Defaults        mailto = root

MAILCLEANER  LOCALHOST = (ROOT) NOPASSWD: ETRUST
MAILCLEANER  LOCALHOST = (ROOT) NOPASSWD: ETRUSTUPDATE
EOF

cd $BACK
