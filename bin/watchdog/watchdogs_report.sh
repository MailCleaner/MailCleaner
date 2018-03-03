#!/bin/bash

CLIENTID=`grep 'CLIENTID' /etc/mailcleaner.conf | sed 's/ //g' | cut -d '=' -f2`
HOSTID=`grep 'HOSTID' /etc/mailcleaner.conf | sed 's/ //g' | cut -d '=' -f2`
TIME=`date +%s`

DIRBASE='/var/mailcleaner/spool/watchdog/'
REPORTSWRK=$DIRBASE'reports.wrk'
FILE=$DIRBASE"reports/report-$CLIENTID-$HOSTID-$TIME.tar.gz"

if [ -e '/var/tmp/mc_checks_data.ko' ]; then
        exit;
fi

# Nettoyage
find $DIRBASE -type f -mtime +5 -exec rm {} \; > /dev/null 2>&1

# Création du dossier temporaire, copie des fichiers et compression
if [ ! -d "$REPORTSWRK"  ]; then
    mkdir -p $REPORTSWRK >/dev/null 2>&1
else
    # Suppression des watchdogs plus agés que 15 jours
    find $REPORTSWRK -type f -mtime +5 -exec /bin/rm -f {} \; >/dev/null 2>&1
fi

# Création du dossier temporaire
if [ ! -d "$DIRBASE/reports"  ]; then
    mkdir -p $DIRBASE/reports >/dev/null 2>&1
fi

mv $DIRBASE/MC_mod*.out $REPORTSWRK/ >/dev/null 2>&1
cd $DIRBASE >/dev/null 2>&1
tar cvf - reports.wrk 2>/dev/null  | gzip -9 - > $FILE

scp -q -o PasswordAuthentication=no -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no $FILE mcscp@cvs.mailcleaner.net:/upload/watchdog-reports/ &> /dev/null
if [[ $? = 0  ]]
then
    rm -Rf $REPORTSWRK >/dev/null 2>&1
    rm $FILE >/dev/null 2>&1
else
    rm $FILE >/dev/null 2>&1
fi
