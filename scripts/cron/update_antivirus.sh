#!/bin/bash

SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "$SRCDIR" = "" ]; then
  SRCDIR=/opt/mailcleaner
fi
VARDIR=`grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "$VARDIR" = "" ]; then
  VARDIR=/opt/mailcleaner
fi

MYMAILCLEANERPWD=`grep 'MYMAILCLEANERPWD' /etc/mailcleaner.conf | cut -d ' ' -f3`
HTTPPROXY=`grep -e '^HTTPPROXY' /etc/mailcleaner.conf | cut -d ' ' -f3`
export http_proxy=$HTTPPROXY

. $SRCDIR/lib/lib_utils.sh
FILE_NAME=$(basename -- "$0")
FILE_NAME="${FILE_NAME%.*}"
ret=$(createLockFile "$FILE_NAME")
if [[ "$ret" -eq "1" ]]; then
        exit 0
fi

if [ ! -f $VARDIR/log/clamav/freshclam.log ]; then
  /bin/touch $VARDIR/log/clamav/freshclam.log
fi
/bin/chown clamav:clamav $VARDIR/log/clamav/freshclam.log

#/opt/MailScanner/bin/update_virus_scanners

if [ -e $VARDIR/run/clamd.disabled ] && [ -e $VARDIR/run/clamspamd.disabled ]; then
  echo "Abandoning update because both services are disabled" >> $VARDIR/log/clamav/freshclam.log
  exit 0
fi

CLAMDPID=`pgrep -f clamd.conf`
#if [ "$CLAMDPID" = "" ]; then
#	exit;
#fi

echo "["`date "+%Y-%m-%d %H:%M:%S"`"] Starting ClamAV update..." >> $VARDIR/log/clamav/freshclam.log
/opt/clamav/bin/freshclam --user=clamav --config-file=$SRCDIR/etc/clamav/freshclam.conf --daemon-notify=$SRCDIR/etc/clamav/clamd.conf >> $VARDIR/log/clamav/freshclam.log 2>&1

RET=$?

if [ $RET -le 1 ]; then
  echo "OK"
else
  if [[ $RET -eq 52 || $RET -eq 58 || $RET -eq 59 || $RET -eq 62 ]] ; then
      echo "Network error, not able to download data now,retrying later..."
      echo "["`date "+%Y-%m-%d %H:%M:%S"`"] Network error, not able to download data now,retrying later..." >> $VARDIR/log/clamav/freshclam.log
  else
      echo "Error.. trying from scratch..."
      echo "["`date "+%Y-%m-%d %H:%M:%S"`"] Error.. trying from scratch..." >> $VARDIR/log/clamav/freshclam.log
      echo -n " Purging current data... "
      echo "["`date "+%Y-%m-%d %H:%M:%S"`"] Purging current data... " >> $VARDIR/log/clamav/freshclam.log
      rm -rf $VARDIR/spool/clamav/* &> /dev/null
      echo "done"
      echo -n " Retrying download... "
      echo "["`date "+%Y-%m-%d %H:%M:%S"`"] Retrying download... " >> $VARDIR/log/clamav/freshclam.log
      /opt/clamav/bin/freshclam --user=clamav --config-file=$SRCDIR/etc/clamav/freshclam.conf --daemon-notify=$SRCDIR/etc/clamav/clamd.conf --quiet
  
      RET2=$?
      if [ $RET2 -le 1 ]; then
          echo "OK"
          echo "["`date "+%Y-%m-%d %H:%M:%S"`"] OK" >> $VARDIR/log/clamav/freshclam.log
      else
          echo "NOTOK $RET2"
          echo "["`date "+%Y-%m-%d %H:%M:%S"`"] NOTOK $RET2" >> $VARDIR/log/clamav/freshclam.log
      fi
   fi
fi

if [ -e $VARDIR/spool/mailcleaner/clamav-unofficial-sigs ]; then
   if [ -e $VARDIR/spool/clamav/unofficial-sigs ]; then
      echo "Updating Unofficial Signatures..." >> $VARDIR/log/clamav/freshclam.log
      $SRCDIR/scripts/cron/clamav-unofficial-sigs.sh --update >> $VARDIR/log/clamav/freshclam.log
   else
      echo "Installing Unofficial Signatures..." >> $VARDIR/log/clamav/freshclam.log
      mkdir $VARDIR/spool/clamav/unofficial-sigs
      /bin/chown clamav:clamav -R $VARDIR/spool/clamav/unofficial-sigs
      $SRCDIR/scripts/cron/clamav-unofficial-sigs.sh --force >> $VARDIR/log/clamav/freshclam.log
   fi
fi
      
echo "["`date "+%Y-%m-%d %H:%M:%S"`"] Done." >> $VARDIR/log/clamav/freshclam.log
removeLockFile "$FILE_NAME"
