#!/bin/bash
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2017 Reka Mentor <reka.mentor@gmail.com>
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
#   This script let you register MailCleaner as a CE (Community Edition)
#   You have to provide at least first_name, last_name, email, host_ip
#
#   Usage:
#	When using form webapp, the file is created by the webapp and only readed here
#	Create a file with data at /tmp/mc_register.data
#	This file contains for each line KEY=DATA

CONFFILE=/etc/mailcleaner.conf
REGISTERDATA=/tmp/mc_registerce.data

HOSTID=`grep 'HOSTID' $CONFFILE | cut -d ' ' -f3`
if [ "$HOSTID" = "" ]; then
  HOSTID=1
fi
SRCDIR=`grep 'SRCDIR' $CONFFILE | cut -d ' ' -f3`
if [ "$SRCDIR" = "" ]; then 
  SRCDIR="/opt/mailcleaner"
fi
VARDIR=`grep 'VARDIR' $CONFFILE | cut -d ' ' -f3`
if [ "$VARDIR" = "" ]; then
  VARDIR="/opt/mailcleaner"
fi
HTTPPROXY=`grep -e '^HTTPPROXY' $CONFFILE | cut -d ' ' -f3`
export http_proxy=$HTTPPROXY

REGISTERED=`grep 'REGISTERED' $CONFFILE | cut -d ' ' -f3`

# We can't register a CE Edition if you're currently on EE edition
# You need to unregister MC first
if [ "$REGISTERED" == "1" ]; then
	echo "UNREGISTER FIRST"
	exit -1
fi

# Check mandatory params
if [ -f $REGISTERDATA ]; then
	FIRST_NAME=`grep 'FIRST_NAME' $REGISTERDATA | cut -d '=' -f2`
	LAST_NAME=`grep 'LAST_NAME' $REGISTERDATA | cut -d '=' -f2`
	EMAIL=`grep 'EMAIL' $REGISTERDATA | cut -d '=' -f2`
else
	echo "BADINPUTS"
	exit 2
fi

# The request is done with all parameters even if optionnal params are empty.
COMPANY=`grep 'COMPANY_NAME' $REGISTERDATA | cut -d '=' -f2`
ADDRESS=`grep 'ADDRESS' $REGISTERDATA | cut -d '=' -f2`
POSTAL_CODE=`grep 'POSTAL_CODE' $REGISTERDATA | cut -d '=' -f2`
CITY=`grep 'CITY' $REGISTERDATA | cut -d '=' -f2`
COUNTRY=`grep 'COUNTRY' $REGISTERDATA | cut -d '=' -f2`
ACCEPT_NEWSLETTERS=`grep 'ACCEPT_NEWSLETTERS' $REGISTERDATA | cut -d '=' -f2`
ACCEPT_RELEASES=`grep 'ACCEPT_RELEASES' $REGISTERDATA | cut -d '=' -f2`
ACCEPT_SEND_STATISTICS=`grep 'ACCEPT_SEND_STATISTICS' $REGISTERDATA | cut -d '=' -f2`

# Mandatory values
HTTP_PARAMS="first_name=$FIRST_NAME&last_name=$LAST_NAME&email=$EMAIL&host_id=$HOSTID"
# Optionnal params
HTTP_PARAMS="$HTTP_PARAMS&company=$COMPANY&address=$ADDRESS&postal_code=$POSTAL_CODE&city=$CITY"
HTTP_PARAMS="$HTTP_PARAMS&country=$COUNTRY&accept_newsletters=$ACCEPT_NEWSLETTERS"
HTTP_PARAMS="$HTTP_PARAMS&accept_releases=$ACCEPT_RELEASES&accept_send_statistics=$ACCEPT_SEND_STATISTICS"

URL="http://reselleradmin.mailcleaner.net/community/registration.php?"
URL="$URL$HTTP_PARAMS"

if [ -f "/tmp/mc_registerce.out" ]; then
	rm /tmp/mc_registerce.out >/dev/null 2>&1
fi
wget -q "$URL" -O /tmp/mc_registerce.out >/tmp/mc_registerce.debug 2>&1

# RETURN CODE
# 0 => record registered
# 1 => record exists
# 2 => bad inputs
# 3 => max_record_per_ip_exceed
# 4 => internal error
if [ -f "/tmp/mc_registerce.out" ]; then
	RETURN_CODE=`cat /tmp/mc_registerce.out`
	if [ "$RETURN_CODE" = "0" ] || [ "$RETURN_CODE" = "1" ]; then
		# Registration done, we update the local db
		# First, we check if the mc_community DB exists if not we create it
                # And secondly we check if the table registration exists also
                echo "CREATE DATABASE IF NOT EXISTS mc_community;" | $SRCDIR/bin/mc_mysql -m
                cat $SRCDIR/install/dbs/t_ce_registration.sql | $SRCDIR/bin/mc_mysql -m mc_community
		sql="SELECT id FROM registration;"
	        rep=$(echo $sql | $SRCDIR/bin/mc_mysql -m mc_community)
		if [ "$rep" != "" ]; then # Update current registration
			sql="UPDATE registration SET first_name='$FIRST_NAME', last_name='$LAST_NAME', company='$COMPANY', email='$EMAIL', address='$ADDRESS', postal_code='$POSTAL_CODE', city='$CITY', country='$COUNTRY', accept_newsletters=$ACCEPT_NEWSLETTERS"
			sql="$sql, accept_releases=$ACCEPT_RELEASES, accept_send_statistics=$ACCEPT_SEND_STATISTICS, updated_at=NOW()"
			sql=`echo "$sql"|sed -e "s/=,/=NULL,/g"`
			sql=`echo "$sql"|sed -e "s/= /=NULL/g"`
			echo $sql | $SRCDIR/bin/mc_mysql -m mc_community
		else
			sql="INSERT INTO registration VALUES(NULL,"
	                sql="$sql'$FIRST_NAME', '$LAST_NAME', '$COMPANY', '$EMAIL', '$ADDRESS', '$POSTAL_CODE', '$CITY', '$COUNTRY', $ACCEPT_NEWSLETTERS, $ACCEPT_RELEASES, $ACCEPT_SEND_STATISTICS, NOW(), NULL);"
			sql=`echo "$sql"|sed -e "s/, ,/,NULL,/g"`
			echo $sql|$SRCDIR/bin/mc_mysql -m mc_community
		fi
		# Update General settings : company
		sql="UPDATE system_conf SET organisation='$COMPANY', company_name='$COMPANY', contact='$FIRST_NAME $LAST_NAME', contact_email='$EMAIL'"
		echo $sql|$SRCDIR/bin/mc_mysql -m mc_config
	else
		echo "INTERNAL ERROR"
		exit $RETURN_CODE
	fi
else
	echo "REMOTEERROR"
	exit 2;
fi
if [ -f "/tmp/mc_registerce.out" ]; then
	rm /tmp/mc_registerce.out >/dev/null 2>&1
fi
CONFFILE=/etc/mailcleaner.conf
perl -pi -e 's/(^REGISTERED.*$)//' $CONFFILE
perl -pi -e 's/^\s*$//' $CONFFILE
REGISTERED=`grep 'REGISTERED' $CONFFILE | cut -d ' ' -f3`
if [ "$REGISTERED" = "" ]; then
	echo "REGISTERED = 2" >> $CONFFILE
fi
echo "SUCCESS"
exit 0
