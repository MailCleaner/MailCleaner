#!/bin/bash
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004-2014 Olivier Diserens <olivier@diserens.ch>
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
#   This is a custom log rotate script for mailcleaner logs
#

DAYSTOKEEP=366

SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "SRCDIR" = "" ]; then
  SRCDIR=/opt/mailcleaner
fi
VARDIR=`grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "VARDIR" = "" ]; then
  VARDIR=/var/mailcleaner
fi

MYMAILCLEANERPWD=`grep 'MYMAILCLEANERPWD' /etc/mailcleaner.conf | cut -d ' ' -f3`


###################
## exim rotating ##
###################
if [ -x /usr/bin/savelog ]; then
	for stage in 1 2 4; do
		for i in mainlog rejectlog paniclog; do
    			if [ -s $VARDIR/log/exim_stage$stage/$i ]; then
				savelog -p -c $DAYSTOKEEP $VARDIR/log/exim_stage$stage/$i >/dev/null
    			fi;
  		done
	done	

	if [ -x /opt/exim4/bin/exim_tidydb ]; then
			/opt/exim4/bin/exim_tidydb $VARDIR/spool/exim_stage1 retry >/dev/null
			/opt/exim4/bin/exim_tidydb $VARDIR/spool/exim_stage1 wait-local_smtp >/dev/null

			/opt/exim4/bin/exim_tidydb $VARDIR/spool/exim_stage4 retry >/dev/null
			/opt/exim4/bin/exim_tidydb $VARDIR/spool/exim_stage4 wait-remote_smtp >/dev/null
	fi
	
	$SRCDIR/etc/init.d/preftdaemon stop
	for i in PrefTDaemon.log ; do
    	if [ -s $VARDIR/log/mailcleaner/$i ]; then
           savelog -p -c $DAYSTOKEEP $VARDIR/log/mailcleaner/$i >/dev/null
        fi;
	done
	$SRCDIR/etc/init.d/preftdaemon start
fi

##########################
## mailscanner rotating ##
##########################

if [ -x /usr/bin/savelog ]; then
  for i in mainlog errorlog infolog warnlog spamd.log newsld.log; do
    if [ -s $VARDIR/log/mailscanner/$i ]; then
      savelog -p -c $DAYSTOKEEP $VARDIR/log/mailscanner/$i >/dev/null
    fi
  done
fi
for i in `seq 1 10`; do
  rm -rf /tmp/.spamassassin$1* >/dev/null 2>&1
done
if [ -d $VARDIR/spool/tmp ]; then
  for i in `seq 1 10`; do
    rm -rf $VARDIR/spool/tmp/.spamassassin$1* >/dev/null 2>&1
  done
fi

$SRCDIR/etc/init.d/mailscanner stop
	
if [ -f /etc/init.d/sysklogd ]; then
  /etc/init.d/sysklogd restart
fi
if [ -f /etc/init.d/rsyslog ]; then
  /etc/init.d/rsyslog restart
fi
$SRCDIR/etc/init.d/exim_stage1 stop
$SRCDIR/etc/init.d/statsdaemon stop
savelog -p -c $DAYSTOKEEP $VARDIR/log/mailcleaner/StatsDaemon.log > /dev/null
$SRCDIR/etc/init.d/statsdaemon start
$SRCDIR/etc/init.d/exim_stage1 start
$SRCDIR/etc/init.d/mailscanner start
sleep 2
chown -R mailcleaner:mailcleaner $VARDIR/log/mailscanner/
####################
## mysql rotating ##
####################

if [ -x /usr/bin/savelog ]; then
  for i in mysql.log; do
    if [ -s $VARDIR/log/mysql_slave/$i ]; then
      savelog -p -c $DAYSTOKEEP $VARDIR/log/mysql_slave/$i >/dev/null
    fi
  done
fi

$SRCDIR/etc/init.d/exim_stage1 stop
$SRCDIR/etc/init.d/exim_stage4 stop

/opt/mysql5/bin/mysqladmin -S $VARDIR/run/mysql_slave/mysqld.sock -umailcleaner -p$MYMAILCLEANERPWD flush-logs
$SRCDIR/etc/init.d/mysql_slave restart

$SRCDIR/etc/init.d/exim_stage1 start
$SRCDIR/etc/init.d/exim_stage4 start

if [ -x /usr/bin/savelog ]; then
  for i in mysql.log; do
    if [ -s $VARDIR/log/mysql_master/$i ]; then
      savelog -p -c $DAYSTOKEEP $VARDIR/log/mysql_master/$i >/dev/null
    fi
  done
fi

/opt/mysql5/bin/mysqladmin -S $VARDIR/run/mysql_master/mysqld.sock -umailcleaner -p$MYMAILCLEANERPWD flush-logs
$SRCDIR/etc/init.d/mysql_master restart


####################
## razor rotating ##
####################

if [ -x /usr/bin/savelog ]; then
  for i in razor-agent.log; do
	if [ -s $VARDIR/.razor/$i ]; then
		savelog -p -c $DAYSTOKEEP $VARDIR/.razor/$i >/dev/null
    fi
  done
fi

###########################
## MessageSniffer rotate ##
###########################

if [ -d $VARDIR/log/messagesniffer ]; then
  find $VARDIR/log/messagesniffer/*[0-9].log.xml -mtime +7 -exec rm {} \;
fi

#####################
## apache rotating ##
#####################

$SRCDIR/etc/init.d/apache stop
if [ -x /usr/bin/savelog ]; then
  for i in access.log error.log ssl.log mc_auth.log access_soap.log error_soap.log; do
	if [ -e $VARDIR/log/apache/$i ]; then
		savelog -p -c $DAYSTOKEEP $VARDIR/log/apache/$i >/dev/null
    fi
  done
fi

#result=`$SRCDIR/scripts/starters/H_apache.sh`
sleep 5
$SRCDIR/etc/init.d/apache start

#if [ "$result" = "SUCCESSFULL" ]; then
#	result2=`$SRCDIR/scripts/starters/S_apache.sh`
#	if [ "$result2" = "SUCCESSFULL" ]; then
#		echo "OK"
#	else
#		echo "FAILED1"
#	fi
#else
#	echo "FAILED2"
#fi	
touch $VARDIR/log/apache/mc_auth.log
chown mailcleaner:mailcleaner $VARDIR/log/apache/mc_auth.log

# clean statistics graphs
rm $VARDIR/www/stats/* >/dev/null 2>&1

##########################
## mailcleaner rotating ##
##########################

if [ -x /usr/bin/savelog ]; then
  for i in update.log update2.log autolearn.log rules.log spam_sync.log mc_counts-cleaner.log downloadDatas.log summaries.log updater4mc.log; do
	if [ -e $VARDIR/log/mailcleaner/$i ]; then
		savelog -p -c $DAYSTOKEEP $VARDIR/log/mailcleaner/$i >/dev/null
	fi
  done
fi

########################
## kaspersky rotating ##
########################
if [ -x /usr/bin/savelog ]; then
  for i in kaspersky_updater.log kaspersky_stats.log; do
        if [ -e $VARDIR/log/kaspersky/$i ]; then
                savelog -p -c $DAYSTOKEEP $VARDIR/log/kaspersky/$i >/dev/null
        fi
  done
fi

#####################
## clamav rotating ##
#####################

if [ -x /usr/bin/savelog ]; then
  for i in clamav.log freshclam.log clamd.log clamspamd.log; do
	if [ -e $VARDIR/log/clamav/$i ]; then
		savelog -u clamav -g clamav -c $DAYSTOKEEP $VARDIR/log/clamav/$i >/dev/null
        fi
  done
fi
$SRCDIR/etc/init.d/clamd restart 
$SRCDIR/etc/init.d/clamspamd restart

$SRCDIR/etc/init.d/spamhandler stop
for i in SpamHandler.log ; do
   if [ -s $VARDIR/log/mailcleaner/$i ]; then
      savelog -p -c $DAYSTOKEEP $VARDIR/log/mailcleaner/$i >/dev/null
   fi;
done
$SRCDIR/etc/init.d/spamhandler start

################0#########
## third parties tools ##
#########################
if [ -e /opt/commtouch/etc/init.d/ctasd_initd ] && [ -f /opt/commtouch/etc/ctasd.conf ]; then
   /opt/commtouch/etc/init.d/ctasd_initd stop
   sleep 5
   /opt/commtouch/etc/init.d/ctasd_initd start
fi
if [ -e /opt/commtouch/etc/init.d/ctipd.init_d ] && [ -f /opt/commtouch/etc/ctipd.conf ]; then
   /opt/commtouch/etc/init.d/ctipd.init_d stop 
   sleep 5
   /opt/commtouch/etc/init.d/ctipd.init_d start
fi

################0##
## Resync checks ##
###################
if [ -s $VARDIR/log/mailcleaner/resync/resync.log ]; then
    savelog -p -c $DAYSTOKEEP $VARDIR/log/mailcleaner/resync/resync.log >/dev/null;
fi
