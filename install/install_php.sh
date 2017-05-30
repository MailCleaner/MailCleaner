#!/bin/bash

BACK=`pwd`
if [ "$SRCDIR" = "" ]; then
        SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
        if [ "SRCDIR" = "" ]; then
                SRCDIR=/var/mailcleaner
        fi
fi

#####
## PHP
cd $SRCDIR/install/src
tar -xvjf php.tar.bz2 2>&1
cd php-5.0.5

./configure --prefix=/opt/php5 --with-apxs2=/opt/apache/bin/apxs --enable-safe-mode --enable-magic-quotes --enable-versioning --enable-calendar --with-inifile --enable-ftp --with-mysql=/opt/mysql5/ --with-pear --enable-shared --with-openssl=/usr/openssl --with-imap=/usr/imap-2004 --with-imaps=/usr/imap-2004 --enable-trans-sid --with-libxml-dir=/usr/libxml2 --with-ldap=/usr/ldap --with-zlib-dir=/usr --enable-soap --with-snmp=/usr/snmp 2>&1
make 2>&1
make install 2>&1

/opt/php5/bin/pear install DB
/opt/php5/bin/pear install Net_Socket
/opt/php5/bin/pear install Net_POP3
/opt/php5/bin/pear install Auth
/opt/php5/bin/pear install Auth_SASL
/opt/php5/bin/pear install Net_SMTP
/opt/php5/bin/pear install Mail

cd $BACK
