#!/bin/bash
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
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
#   This script will backup the configuration database
#   Usage: 
#           restore_config.sh 


VARDIR=`grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "VARDIR" = "" ]; then
  VARDIR=/var/mailcleaner
fi
SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "SRCDIR" = "" ]; then
  SRCDIR=/var/mailcleaner
fi

MYMAILCLEANERPWD=`grep 'MYMAILCLEANERPWD' /etc/mailcleaner.conf | cut -d ' ' -f3`

BACKUPFILE=$1
if [ "$BACKUPFILE" = "" ]; then
  BACKUPFILE="mailcleaner_config_.sql"
fi

if [ ! -f $BACKUPFILE ]; then
  echo "Backup file NOT found: $BACKUPFILE"
  exit 1
fi

/opt/mysql5/bin/mysql -u mailcleaner -p$MYMAILCLEANERPWD -S $VARDIR/run/mysql_master/mysqld.sock mc_config < $BACKUPFILE

for p in dump_apache_config.pl dump_clamav_config.pl dump_exim_config.pl dump_firewall.pl dump_mailscanner_config.pl dump_mysql_config.pl dump_snmpd_config.pl; do
  RES=`$SRCDIR/bin/$p 2>&1`
  if [ "$RES" != "DUMPSUCCESSFUL" ]; then
    echo "ERROR dumping: $p"
  fi
done

/etc/init.d/mailcleaner stop >/dev/null 2>&1
sleep 3
killall -q -KILL exim httpd snmpd mysqld mysqld_safe MailScanner >/dev/null 2>&1
/etc/init.d/mailcleaner start >/dev/null 2>&1
