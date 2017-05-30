#!/bin/bash

BACK=`pwd`
if [ "$SRCDIR" = "" ]; then
        SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
        if [ "SRCDIR" = "" ]; then
                SRCDIR=/opt/mailcleaner
        fi
fi

sudoersfile=/etc/sudoers
if [ ! -f $sudoersfile ]; then
  sudoersfile=/usr/sudo/etc/sudoers
fi
cat > $sudoersfile << EOF
root    ALL=(ALL) ALL
User_Alias      MAILCLEANER = mailcleaner
Runas_Alias     ROOT = root
Cmnd_Alias      NTPSTARTER = /etc/init.d/ntp-server
Cmnd_Alias      NTPDATESTARTER = /etc/init.d/ntpdate
Cmnd_Alias      DATE = /bin/date
Cmnd_Alias      IFDOWN = /sbin/ifdown
Cmnd_Alias      IFUP = /sbin/ifup
Cmnd_Alias      PASSWD = $SRCDIR/bin/setpassword
Cmnd_Alias      UPDATE = $SRCDIR/scripts/cron/mailcleaner_cron.pl
Cmnd_Alias      STOPSTART = $SRCDIR/scripts/starters/[S|H|R]_*

Defaults        mailto = root

MAILCLEANER     * = (ROOT) NOPASSWD: NTPSTARTER
MAILCLEANER     * = (ROOT) NOPASSWD: NTPDATESTARTER
MAILCLEANER     * = (ROOT) NOPASSWD: DATE
MAILCLEANER     * = (ROOT) NOPASSWD: IFDOWN
MAILCLEANER     * = (ROOT) NOPASSWD: IFUP
MAILCLEANER     * = (ROOT) NOPASSWD: PASSWD
MAILCLEANER     * = (ROOT) NOPASSWD: UPDATE
MAILCLEANER     * = (ROOT) NOPASSWD: STOPSTART
EOF

touch /etc/ntp.conf 2>&1
chgrp mailcleaner /etc/ntp.conf 2>&1
chmod g+w /etc/ntp.conf 2>&1
chgrp mailcleaner /etc/network/interfaces 2>&1
chmod g+w /etc/network/interfaces 2>&1
chgrp mailcleaner /etc/network/run/ifstate 2>&1
chmod g+w /etc/network/run/ifstate 2>&1
chgrp mailcleaner /etc/resolv.conf 2>&1
chmod g+w /etc/resolv.conf 2>&1

if [ "$USEDEBS" = "Y" ]; then
  exit
else
######
## sudo
cd $SRCDIR/install/src

echo "buils sudo ? "
read REP
if [ "$REP" = "y" ]; then

tar -xvzf sudo.tar.gz
cd sudo-1.6.8p12
./configure --prefix=/usr/sudo --enable-noargs-shell --with-logging=syslog --with-logfac=auth --sysconfdir=/usr/sudo/etc 2>&1
make 2>&1
make install 2>&1

cd $BACK
rm -rf $SRCDIR/install/src/sudo-1.6.8p12

fi

cat > /usr/sudo/etc/sudoers << EOF
root    ALL=(ALL) ALL
User_Alias      MAILCLEANER = mailcleaner
Runas_Alias     ROOT = root
Cmnd_Alias      NTPSTARTER = /etc/init.d/ntp-server
Cmnd_Alias      NTPDATESTARTER = /etc/init.d/ntpdate
Cmnd_Alias      DATE = /bin/date
Cmnd_Alias      IFDOWN = /sbin/ifdown
Cmnd_Alias      IFUP = /sbin/ifup
Cmnd_Alias	PASSWD = $SRCDIR/bin/setpassword
Cmnd_Alias      UPDATE = $SRCDIR/scripts/cron/mailcleaner_cron.pl

Defaults        mailto = root

MAILCLEANER     * = (ROOT) NOPASSWD: NTPSTARTER
MAILCLEANER     * = (ROOT) NOPASSWD: NTPDATESTARTER
MAILCLEANER     * = (ROOT) NOPASSWD: DATE
MAILCLEANER     * = (ROOT) NOPASSWD: IFDOWN
MAILCLEANER     * = (ROOT) NOPASSWD: IFUP
MAILCLEANER     * = (ROOT) NOPASSWD: PASSWD
MAILCLEANER     * = (ROOT) NOPASSWD: UPDATE
EOF

touch /etc/ntp.conf 2>&1
chgrp mailcleaner /etc/ntp.conf 2>&1
chmod g+w /etc/ntp.conf 2>&1
chgrp mailcleaner /etc/network/interfaces 2>&1
chmod g+w /etc/network/interfaces 2>&1
chgrp mailcleaner /etc/network/run/ifstate 2>&1
chmod g+w /etc/network/run/ifstate 2>&1
chgrp mailcleaner /etc/resolv.conf 2>&1
chmod g+w /etc/resolv.conf 2>&1


if [ "$USEDEBS" = "Y" ]; then
  exit
fi

fi

######
## Apache
cd $SRCDIR/install/src

echo "buils apache ? "
read REP
if [ "$REP" = "y" ]; then

tar -xvzf apache.tar.gz
#tar -xvzf mod_ssl.tar.gz
#cd mod_ssl-2.8.25-1.3.34/
#./configure --with-apache=../apache_1.3.34 --with-openssl=/usr/openssl --enable-shared=ssl --prefix=/opt/apache 2>&1
#cd ..
cd httpd-2.2.2
./configure --prefix=/opt/apache --datadir=/var/www --sysconfdir=/etc/apache --enable-file-cache --enable-echo --enable-charset-lite --enable-cache --enable-disk-cache  --enable-mem-cache --enable-logio --enable-mime-magic --enable-cern-meta --enable-expires --enable-headers --enable-usertrack --enable-unique-id --enable-ssl --enable-http --enable-info --enable-rewrite --enable-so --with-ssl=/usr/openssl
#export SSL_BASE=/usr/openssl
#./configure --prefix=/opt/apache --enable-module=ssl --enable-shared=ssl --enable-module=log_referer --disable-module=userdir --enable-module=so --server-uid=mailcleaner --server-gid=mailcleaner 2>&1

make 2>&1
make install 2>&1


cd $BACK
#rm -rf $SRCDIR/install/src/apache_1.3.34 2>&1
#rm -rf $SRCDIR/install/src/mod_ssl-2.8.25-1.3.34 2>&1

fi

#####
## module auth_mysql
#cd $SRCDIR/install/src

#tar -xvzf mod_auth_mysql.tgz 2>&1
#cd mod_auth_mysql-2.6.1
#/opt/apache/bin/apxs -c -D APACHE1 -lmysqlclient -lm -lz -L/opt/mysql5/lib/mysql/ -I/opt/mysql5/include/mysql/  mod_auth_mysql.c 2>&1
#/opt/apache/bin/apxs -i mod_auth_mysql.so 2>&1

#cd $BACK
#rm -rf $SRCDIR/install/src/mod_auth_mysql-2.6.1 2>&1

#####
## PHP
cd $SRCDIR/install/src

echo "buils php ? "
read REP
if [ "$REP" = "y" ]; then

#tar -xvjf php.tar.bz2 2>&1
echo "REMEMBER TO PATCH radius extention !! and include it in the configure script !"
echo "1) cp radius sources in php-5.x.x/ext/radius"
echo "2) edit config.m4 to ensure it will add the extention"
echo "3) run php-5.x.x/buildconf --force"
echo "press a key to continue..."
read $tmp
cd php-5.1.3

export LDFLAGS="-L/usr/sasl/lib"
./configure --prefix=/opt/php5 --with-apxs2=/opt/apache/bin/apxs --enable-safe-mode --enable-magic-quotes --enable-versioning --enable-calendar --with-inifile --enable-ftp --with-mysql=/opt/mysql5/ --with-pear --enable-shared --with-openssl=/usr/openssl --with-imap=/usr/imap-2004 --with-imaps=/usr/imap-2004 --enable-trans-sid --with-libxml-dir=/usr/libxml2 --with-ldap=/usr/ldap --with-ldap-sasl=/usr/sasl --with-zlib --with-snmp=/usr/snmp --with-mhash=/usr/mhash --with-mcrypt=/usr/mcrypt --with-config-file-path=$SRCDIR/etc/apache/ --enable-soap --enable-radius 2>&1
make 2>&1
make install 2>&1
ln -s /opt/php5 /usr/local/php

/opt/php5/bin/pear install --alldeps XML_RPC
/opt/php5/bin/pear install --alldeps DB
/opt/php5/bin/pear install --alldeps Net_Socket
/opt/php5/bin/pear install --alldeps Net_POP3
/opt/php5/bin/pear install --alldeps Auth
/opt/php5/bin/pear install --alldeps Auth_SASL
/opt/php5/bin/pear install --alldeps Net_SMTP
/opt/php5/bin/pear install --alldeps Mail
/opt/php5/bin/pear install --alldeps Log

#/opt/php5/bin/pear install $SRCDIR/install/src/pear/DB-1.6.8.tgz 2>&1
#/opt/php5/bin/pear install $SRCDIR/install/src/pear/Net_Socket-1.0.2.tgz 2>&1
#/opt/php5/bin/pear install $SRCDIR/install/src/pear/Net_POP3-1.3.4.tgz 2>&1
#/opt/php5/bin/pear install $SRCDIR/install/src/pear/Auth-1.3.0r3.tgz 2>&1
#/opt/php5/bin/pear install $SRCDIR/install/src/pear/Auth_SASL-1.0.1.tgz 2>&1
#/opt/php5/bin/pear install $SRCDIR/install/src/pear/Net_SMTP-1.2.6.tgz 2>&1
#/opt/php5/bin/pear install $SRCDIR/install/src/pear/Mail-1.1.4.tgz 2>&1
#/opt/php5/bin/pear install $SRCDIR/install/src/pear/radius-1.2.4.tgz 2>&1
#/opt/php5/bin/pear install $SRCDIR/install/src/pear/Auth_RADIUS-1.0.4.tgz 2>&1
#/opt/php5/bin/pear install $SRCDIR/install/src/pear/Crypt_CHAP-1.0.0.tgz 2>&1

########
## patching Auth::IMAP, Net::POP3 and Auth::RADIUS.php

#cp $SRCDIR/install/src/patches/auth_imap.patch /opt/php5/lib/php/Auth/Container/
#cp $SRCDIR/install/src/patches/net_pop3.patch /opt/php5/lib/php/Auth/Container/
#cp $SRCDIR/install/src/patches/RADIUS.php.patch /opt/php5/lib/php/Auth/Container/
#cd /opt/php5/lib/php/Auth/Container/
#patch -N -p0 <auth_imap.patch 
#patch -N -p0 <RADIUS.php.patch

#cp $SRCDIR/install/src/patches/net_pop3.patch /opt/php5/lib/php/Net/
#cd /opt/php5/lib/php/Net/
#patch -N -p0 <net_pop3.patch

fi
cd $SRCDIR
#########

cd $BACK
#rm -rf $SRCDIR/install/src/php-5.1.2 2>&1
