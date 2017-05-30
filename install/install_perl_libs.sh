#!/bin/sh
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004-2014 Olivier Diserens <olivier@diserens.ch>
#   Copyright (C) 2015-2017 Mentor Reka <reka.mentor@gmail.com>
#   Copyright (C) 2015-2017 Florian Billebault <florian.billebault@gmail.com>
#
#   This program is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; either version 2 of the License, or
#   (at your option) any later version.
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
#
#   This script will install needed perl lib dependancies
#
#   Usage:
#           install_perl_libs.sh

BACK=`pwd`
if [ "$SRCDIR" = "" ]; then
        SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
        if [ "SRCDIR" = "" ]; then
                SRCDIR=/var/mailcleaner
        fi
fi

ISSQUEEZE=`grep ' squeeze ' /etc/apt/sources.list`
VERSIONFILE='VERSIONS.squeeze'
if [ "$ISSQUEEZE" = "" ]; then
  VERSIONFILE='VERSIONS.lenny'
fi

## export some environment variables
export DB_FILE_INCLUDE=/usr/db/include
export DB_FILE_LIB=/usr/db/lib
export PATH=$PATH:/opt/mysql5/bin:/usr/rrdtools/bin:/opt/clamav/bin
export ZLIB_INCLUDE=/usr/zlib/include
export ZLIB_LIB=/usr/zlib/lib
export BUILD_ZLIB=no
export PERL5LIB=$PERL5LIB:/usr/rrdtools/lib/perl/

ldconfig 2>&1 > /dev/null

cd $SRCDIR/install/src/perl

ISLENNY=`grep ' lenny ' /etc/apt/sources.list`
if [ "$ISLENNY" = "" ]; then
  VERSIONFILE='VERSIONS.squeeze'
fi

## clean old DBD::MySQL
for i in 5.8.4 5.8.8 5.10.0 5.10.1; do
  rm -rf /usr/local/lib/perl/$i/DBD/mysql* 2>&1 > /dev/null
  rm -rf /usr/local/lib/perl/$i/Bundle/DBD/mysql.pm 2>&1 > /dev/null
  rm -rf /usr/local/lib/perl/$i/auto/DBD/mysql 2>&1 > /dev/null 
done

for line in `cat $VERSIONFILE`; do
 module=`echo $line | cut -d'=' -f1`
 version=`echo $line | cut -d'=' -f2`

 echo "********"
 echo "will build and install module $module, version $version"
 echo "********" 2>&1
 cd $SRCDIR/install/src/perl
 tar -xvzf $module.tar.gz 2>&1
 cd $module-$version 2>&1

 if [ "$module" = "RRDTools-OO" ]; then
  export PERL5LIB=$PERL5LIB:/usr/rrdtools/lib/perl/
 fi
 if [ "$module" = "Inline" ]; then
   echo 'y' | perl Makefile.PL 2>&1
 else 
   echo 'n' | perl Makefile.PL 2>&1
 fi
 make 2>&1
 make install 2>&1
 cd ..
 rm -rf $module-$version 2>&1
 echo "********"
 echo "done"
 echo "********" 2>&1
# echo "press return to continue.."
# read
done

## patch RRD OO modules
for i in 5.8.4 5.8.8 5.10.0 5.10.1 5.20.2; do
  if [ -d /usr/local/share/perl/$i/RRDTool ]; then
    cd /usr/local/share/perl/$i/RRDTool
    cp $SRCDIR/install/src/perl/OO.pm.patch .
    patch -N -p0 < OO.pm.patch
  fi
done

exit 0

