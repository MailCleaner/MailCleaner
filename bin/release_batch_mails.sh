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
#   This script can be used to release in batch emails that were put in
#   quarantine
#
#   Usage:
# 	release_batch_emails.sh <sender>


VARDIR=`grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "VARDIR" = "" ]; then
  VARDIR=/var/mailcleaner
fi
SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "SRCDIR" = "" ]; then
  SRCDIR=/usr/mailcleaner
fi
MYMAILCLEANERPWD=`grep '^MYMAILCLEANERPWD' /etc/mailcleaner.conf | cut -d ' ' -f3`

SOCKET=$VARDIR/run/mysql_slave/mysqld.sock
COMMAND=/opt/mysql5/bin/mysql

if [[ -z $1 ]]; then
	echo "Please input a sender address"
	exit 1
fi
QUERY="SELECT exim_id,to_user,to_domain FROM spam WHERE sender=\"$1\";"

results=($(echo "$QUERY" | $COMMAND -S $SOCKET -umailcleaner -p$MYMAILCLEANERPWD -N mc_spool))
for ((i=0;i<${#results[@]};i=i+3)); do
	id="${results[i]}"
	to="${results[$((i+1))]}@${results[$((i+2))]}"
	echo -n "$id $to -> "
	$SRCDIR/bin/force_message.pl $id $to
done

