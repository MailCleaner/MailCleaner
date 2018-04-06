#!/bin/bash
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2015-2017 Florian Billebault <florian.billebault@gmail.com>
#   Copyright (C) 2015-2017 Mentor Reka <reka.mentor@gmail.com>
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
#   This script will be used by MailCleaner system in order to provide updates.
#   You need to be registered as an Enterprise Edition for using it.
#
#   Usage:
#           download_files.sh 

CONFFILE=/etc/mailcleaner.conf
SRCDIR=`grep 'SRCDIR' $CONFFILE | cut -d ' ' -f3`
if [ "$SRCDIR" = "" ]; then 
  SRCDIR="/opt/mailcleaner"
fi
VARDIR=`grep 'VARDIR' $CONFFILE | cut -d ' ' -f3`
if [ "$VARDIR" = "" ]; then
  VARDIR="/var/mailcleaner"
fi

LOGFILE=$VARDIR/log/mailcleaner/downloadDatas.log
MD5FILE=dbs.md5
REMOTEMD5FILE=dbs.md5
MAXSLEEPTIME=120
MINSLEEPTIME=0

function log {
    echo "["`date "+%Y-%m-%d %H:%M:%S"`"] $1" >> $LOGFILE	
}

function downloadfile {
    # $1 = file
    # $2 = local folder
    # $3 = remote folder
    # $4 = owner:group

    file=$1

    # Create sub-directory if not exists
    subDir=`echo $2$1 | sed 's/\(.*\)\/.*/\1/'`
    if [ ! -d "$subDir" ]; then
	mkdir -p $subDir
    fi

    if [ -f $2$1 ]; then
	rm $2$1 &>> $LOGFILE
    fi

    getfileres=`timeout 8m scp -o PasswordAuthentication=no -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -C mcscp@cvs.mailcleaner.net:/$3/${file} $2$1 &>> $LOGFILE`

    if [ "$getfileres" = "" ]; then
  	log "$2 - $file successfully downloaded"
        if [ "$4" != "null" ]; then
	    chown $4:$4 $file > /dev/null 2>&1
	fi
    else
	log "$2 - Could not download: $file"
    fi
}

function downloadDatas {
    # $1 = local folder
    # $2 = remote folder
    # $3 = randomize or not
    # $4 = owner:group or null
    # $5 = ignored files list, fmt: \|file1\|file2\|etc
    # $6 = if noexit, then no exit
    
    if $3 ; then
	sleep_time=$(($RANDOM * $(($MAXSLEEPTIME - $MINSLEEPTIME)) / 32767 + $MINSLEEPTIME))
	log "$2 - Sleeping for $sleep_time seconds..."
	sleep $sleep_time
    fi

    cd $1
   
    # If no dbs.md5 was there, it is the first time we download this file
    FIRSTTIME=1

    # If a dbs.md5 was there, we save it to know which files to update/delete according to differences with the new dbs.md5
    if [ -e ${MD5FILE} ]; then
	FIRSTTIME=0
	OLDMD5FILE=${MD5FILE}.old
	mv $MD5FILE ${OLDMD5FILE}
    else
	log "$2 - no previous dbs.md5"
    fi

    # Downloading the new dbs.md5
    scpres=`scp -o PasswordAuthentication=no -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no mcscp@cvs.mailcleaner.net:/${2}/dbs.md5 $MD5FILE &>> $LOGFILE`
    if [ ! -f $MD5FILE ]; then
	log "$2 - Could not fetch md5 file"
	if [ "$6" != "noexit" ]; then
                exit 0
        else
                return 0
        fi
    fi

    # For each file in the new dbs.md5, we download the file if this is needed
    for file in $(cat ${MD5FILE} | cut -d " " -f3); do
    	if [ $FIRSTTIME -eq 1 ]; then
		downloadfile $file $1 $2 $4
		log "$2 - $file downloaded"
	else
		log "$2 - getting hashes"
		OLDHASH=$(grep -E "\b$file\b" ${OLDMD5FILE} |cut -d " " -f1)
		HASH=$(grep -E "\b$file\b" ${MD5FILE} |cut -d " " -f1)

		# New file
		if [ "$OLDHASH" == "" ]; then
			downloadfile $file $1 $2 $4
			log "$2 - $file downloaded"

		# Updated file
		elif [ "$OLDHASH" != "$HASH" ]; then
			downloadfile $file $1 $2 $4
			log "$2 - $file downloaded"
		else

		# File didnt change
			log "$2 - $file doesnt need to be downloading"
		fi
	fi
    done

    # Check for removed files (without removing custom files)
    if [ $FIRSTTIME -ne 1 ]; then
	for i in $(cat ${OLDMD5FILE} | cut -d " " -f3); do
        	res=$(grep -E "\b$i\b" ${MD5FILE})
	        if [ "$res" == "" ]; then
        	        rm $i >> $LOGFILE 2>&1
                	log "$2 - Removed file $i"
	        fi
	done
    fi

    if [ $FIRSTTIME -ne 1 ]; then
	rm ${OLDMD5FILE} >> $LOGFILE 2>&1
    fi
}
