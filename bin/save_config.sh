#!/bin/bash
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
#   This script will backup the Mailcleaner configuration to a sql file that
#   can be reimported in another system.
#
#   Usage:
#           save_config.sh

VARDIR=`grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "VARDIR" = "" ]; then
  VARDIR=/var/mailcleaner
fi

SOCKET=$VARDIR/run/mysql_master/mysqld.sock
MYMAILCLEANERPWD=`grep '^MYMAILCLEANERPWD' /etc/mailcleaner.conf | cut -d ' ' -f3`

DATE=`date '+%d-%m-%Y'`;
SAVECONFIG=/tmp/mailcleaner_config_$DATE.sql

/opt/mysql5/bin/mysqldump -S $SOCKET -umailcleaner -p$MYMAILCLEANERPWD -ntce  mc_config > $SAVECONFIG

perl -pi -e 's/INSERT/REPLACE/g' $SAVECONFIG

echo "**************************************"
echo "config saved in: $SAVECONFIG"
echo "--------------------------------------"
echo "to reimport datas in mailcleaner config: mc_mysql -m < backupfile"
echo "**************************************"

exit 0
