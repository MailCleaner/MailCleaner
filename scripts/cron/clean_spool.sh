#!/bin/sh

#!/bin/bash

SRCDIR=$(grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3)
if [ "$SRCDIR" = "" ]; then
	SRCDIR=/usr/mailcleaner
fi
VARDIR=$(grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3)
if [ "$VARDIR" = "" ]; then
	VARDIR=/var/mailcleaner
fi

## clean exim garbage
for exim in stage1 stage2 stage4; do
	cd $VARDIR/spool/exim_$exim/input/
	for dir in $(find . -type d); do
		if [ "$dir" != '.' ]; then cd $dir; fi

		#echo -n "cleaning: "
		#pwd

		for i in $(ls *-D 2>/dev/null); do
			j=$(echo $i | cut -d'-' -f-3)
			if [ ! -f $j-H ]; then rm $i >/dev/null 2>&1; fi
		done
		for i in $(ls *-H 2>/dev/null); do
			j=$(echo $i | cut -d'-' -f-3)
			if [ ! -f $j-D ]; then rm $i >/dev/null 2>&1; fi
		done
		for i in $(ls *-J 2>/dev/null); do
			j=$(echo $i | cut -d'-' -f-3)
			if [ ! -f $j-H ]; then rm $i >/dev/null 2>&1; fi
		done
		for i in $(ls *-K 2>/dev/null); do
			j=$(echo $i | cut -d'-' -f-3)
			if [ ! -f $j-H ]; then rm $i >/dev/null 2>&1; fi
		done
		for i in $(ls *-T 2>/dev/null); do
			j=$(echo $i | cut -d'-' -f-3)
			if [ ! -f $j-H ]; then rm $i >/dev/null 2>&1; fi
		done

		if [ "$dir" != '.' ]; then cd ..; fi
	done
done

## clean spamstore
cd $VARDIR/spool/exim_stage4/spamstore/
for f in $(ls *.env 2>/dev/null | cut -d'.' -f-1); do
	if [ ! -f $f.msg ]; then
		rm $f.env
	fi
done
rm *.tmp 2>/dev/null

## clean tmp dir
if [ -d $VARDIR/spool/tmp ]; then
	cd $VARDIR/spool/tmp
	rm -rf clamav-* .spamassassin* >/dev/null 2>&1
fi
if [ -d $VARDIR/spool/tmp/mailscanner/spamassassin ]; then
	cd $VARDIR/spool/tmp/mailscanner/spamassassin
	rm -rf MailScanner.* >/dev/null 2>&1
fi
find $VARDIR/spool/exim_stage1/scan -type f -mtime +30 -delete
find $VARDIR/spool/exim_stage1/scan -type d -empty -delete
cd $VARDIR/spool/tmp
#find . -ctime +4 -and -type f -exec rm {} \; >/dev/null 2>&1
