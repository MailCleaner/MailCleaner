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

    getfileres=`timeout 8m scp -q -o PasswordAuthentication=no -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -C mcscp@cvs.mailcleaner.net:/$3/${file} $2$1 &>> $LOGFILE`

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
    
    # For removed file: save local md5 file as old
    OLDMD5FILE=${MD5FILE}.old
    mv $MD5FILE ${OLDMD5FILE}

    scpres=`scp -q -o PasswordAuthentication=no -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no mcscp@cvs.mailcleaner.net:/${2}/dbs.md5 $MD5FILE`

    if [ ! -f $MD5FILE ]; then
	log "$2 - Could not fetch md5 file"
	if [ "$6" != "noexit" ]; then
                exit 0
            else
                return 0
        fi
    fi

    md5check=`/usr/bin/md5sum -c $MD5FILE 2>&1 | grep -i 'FAILED'`
    if [ "$md5check" = "" ]; then
	md5check=`/usr/bin/md5sum -c $MD5FILE 2>&1 | grep -i '.CHEC'`
	if [ "$md5check" = "" ]; then
	    log "$2 - datas are up-to-date"
	    if [ "$6" != "noexit" ]; then
		exit 0
	    else
		return 0
	    fi
	fi
    fi

    OLDIFS=$IFS
    IFS=$'\n'

    ## check for removed files (without removing custom files)
    for i in $(cat ${OLDMD5FILE} | cut -d " " -f3); do
        res=$(grep -E "\b$i\b" ${MD5FILE})
        if [ "$res" == "" ]; then
                rm $i >> /dev/null 2>&1
                log "$2 - Removed file $i"
        fi
    done
    rm ${OLDMD5FILE} >> /dev/null 2>&1

    ## check for missing files
    misres=`echo "$md5check" | grep -i "open or read"`
    for l in $misres; do
	misfile=`echo $l | cut -d':' -f1`
	log "$2 - Missing file $misfile"
	downloadfile $misfile $1 $2 $4
    done

    ## check for outdated files
    outres=`echo "$md5check" | grep -i 'FAILED$'`
    for l in $outres; do
	outfile=`echo $l | cut -d":" -f1`
	log "$2 - File: $outfile is out-dated"
	downloadfile $outfile $1 $2 $4
    done

    ## check for outdated files
    outres=`echo "$md5check" | grep -i '?CHEC$'`
    for l in $outres; do
	outfile=`echo $l | cut -d":" -f1`
	log "$2 - File: $outfile is out-dated"
	downloadfile $outfile $1 $2 $4
    done

}
