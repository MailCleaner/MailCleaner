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
    SRCDIR="/usr/mailcleaner"
fi
VARDIR=`grep 'VARDIR' $CONFFILE | cut -d ' ' -f3`
if [ "$VARDIR" = "" ]; then
    VARDIR="/var/mailcleaner"
fi
ISMASTER=$(grep 'ISMASTER' $CONFFILE | cut -d ' ' -f3)

LOGFILE=$VARDIR/log/mailcleaner/downloadDatas.log
FILTER_FILENAME="files.filter"
MAXSLEEPTIME=120
MINSLEEPTIME=0
SSH_CMD='ssh -o PasswordAuthentication=no -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -o LogLevel=error -i /root/.ssh/id_rsa'

function log {
    echo "["`date "+%Y/%m/%d %H:%M:%S"`"] $1" >> $LOGFILE
}

function downloadDatas {
    # $1 = local folder
    # $2 = remote folder
    # $3 = randomize or not
    # $4 = owner:group or null
    # $5 = ignored files list, fmt: \|file1\|file2\|etc
    # $6 = if noexit, then no exit
    # $7 = folder to copy to after download

    if ${3} ; then
        sleep_time=$((${RANDOM} * $((${MAXSLEEPTIME} - ${MINSLEEPTIME})) / 32767 + ${MINSLEEPTIME}))
        log "${2} - Sleeping for ${sleep_time} seconds..."
        sleep ${sleep_time}
    fi

    for file in $(echo ${5} | sed "s/\\\|/ /g" | column); do
        excluded_opt="${excluded_opt} --exclude=\"/${2}/${file}\""
    done

    if [ "${4}" != "null" ]; then
        ownership_opt="--chown=$4:$4"
    fi

    local_folder=${1}
    if [ ! -d ${local_folder} ]; then
        mkdir -p $local_folder
    fi

    filter="${local_folder}/${FILTER_FILENAME}"
    if [ -f "${filter}" ]; then
        old_filter=/tmp/${FILTER_FILENAME}.old
        cp ${filter} ${old_filter}
    fi

    log "Synchronizing ${local_folder}..."
    while [ ! $has_tried_cvs ]; do
        if [[ $ISMASTER != "Y" && ! $has_tried_master ]]; then
            user=root
            server=$(echo "SELECT hostname from master;" | ${SRCDIR}/bin/mc_mysql -s mc_config | grep -v -e "hostname")
            remote_folder=${local_folder}
            has_tried_master=true
        else
            user=mcscp
            server=cvs.mailcleaner.net
            remote_folder=/${2}/
            has_tried_cvs=true
        fi
        rsync_result=$(rsync \
            --archive \
            --compress \
            --itemize-changes \
            --out-format="[%t] %i %f" \
            --rsh="${SSH_CMD}" \
            ${ownership_opt} \
            --filter=": /${2}/${FILTER_FILENAME}" \
            ${excluded_opt} \
            ${user}@${server}:${remote_folder} ${local_folder} 2> /dev/null)
        if [ $? == 0 ]; then
            if [[ ! -z ${rsync_result} ]]; then
                echo "${rsync_result}" >> $LOGFILE
            fi
            break
        fi
    done

    # Check for removed files (without removing custom files)
    if [ -f "${old_filter}" ] && [ -f "${filter}" ]; then
        for f in $(cat ${old_filter} | grep -v -e "\*" | cut -d " " -f2); do
            filepath=$(echo ${local_folder}/${f} | sed "s%/\+%/%g")
            is_in_filter=$(grep -e "$f" ${filter})
            if [ ! "${is_in_filter}" ]; then
                rm -f $filepath &>> $LOGFILE
                log "$2 - Removed file $filepath"
            fi
        done
    fi

    # If one of the files of the filter was modifed during synchronization, replace it
    # Also copy all if destination folder is created
    if [ ${7} ]; then
        copy_folder=${7}
        if [ ! -d "${copy_folder}" ]; then
            mkdir -p "${copy_folder}"
            folder_created=true
        fi
        for f in $(cat ${filter} | grep -v -e "\*" | cut -d " " -f2); do
            if [ -n "$(echo "${rsync_result}" | grep -e "${f:1}")" ] || [ ${folder_created} ]; then
                filepath=$(echo ${local_folder}/${f} | sed "s%/\+%/%g")
                destination_path=$(echo ${copy_folder}/${f} | sed "s%/\+%/%g")
                cp $filepath $destination_path
            fi
        done
    fi

    # Exit if the calling script didnt specify not to exit
    if [ "$6" != "noexit" ]; then
        exit 0
    fi
}
