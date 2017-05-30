#!/bin/bash

if [ "$LOGFILE" = "" ]; then
	LOGFILE=/tmp/mailcleaner.log
fi
if [ "$CONFFILE" = "" ]; then
	CONFFILE=/etc/mailcleaner.conf
fi
if [ "$VARDIR" = "" ]; then
	VARDIR=`grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
	if [ "VARDIR" = "" ]; then
  		VARDIR=/var/mailcleaner
	fi
fi

export ACTUALUPDATE="2014120101"
export MCVERSION="Enterprise Edition 2014"
 
###############################################
### creating mailcleaner, mysql and clamav user
if [ "`grep 'mailcleaner' /etc/passwd`" = "" ]; then
  groupadd mailcleaner 2>&1 >> $LOGFILE
  useradd -d $VARDIR -s /bin/bash -g mailcleaner mailcleaner 2>&1 >> $LOGFILE
fi
if [ "`grep 'mysql' /etc/passwd`" = "" ]; then
  groupadd mysql 2>&1 >> $LOGFILE
  useradd -d /var/lib/mysql -s /bin/false -g mysql mysql 2>&1 >> $LOGFILE
fi
if [ "`grep 'clamav' /etc/passwd`" = "" ]; then
  groupadd clamav 2>&1 >> $LOGFILE
  useradd -g clamav -s /bin/false -c "Clam AntiVirus" clamav 2>&1 >> $LOGFILE
fi

###############################################
### check or create spool dirs
#echo ""
echo -n " - Checking/creating spool directories...              "
./MC_create_vars.sh  2>&1 >> $LOGFILE
echo "[done]"

###############################################
## generate ssh keys
if [ ! -d $VARDIR/.ssh ]; then
  mkdir $VARDIR/.ssh
fi

ssh-keygen -q -t rsa -f $VARDIR/.ssh/id_rsa -N ""
chown -R mailcleaner:mailcleaner $VARDIR/.ssh

if [ "$ISMASTER" = "Y" ]; then
	MASTERHOST=127.0.0.1
	MASTERKEY=`cat $VARDIR/.ssh/id_rsa.pub`
fi

##############################################
## setting ssh as default for rsh
update-alternatives --set rsh /usr/bin/ssh 2>&1 >> $LOGFILE

###############################################
## stopping and desactivating standard services

#update-rc.d -f inetd remove 2>&1 >> $LOGFILE
#update-rc.d -f portmap remove 2>&1 >> $LOGFILE
#update-rc.d -f ntpd remove 2>&1 >> $LOGFILE
if [ -f /etc/init.d/inetd ]; then
  /etc/init.d/inetd stop 2>&1 >> $LOGFILE
fi
if [ -x /etc/init.d/exim ]; then
  update-rc.d -f exim remove 2>&1 > /dev/null
  /etc/init.d/exim stop 2>&1 >> $LOGFILE
fi
if [ -x /etc/init.d/exim4 ]; then
  /etc/init.d/exim4 stop 2>&1 >> $LOGFILE
  update-rc.d -f exim4 remove 2>&1 > /dev/null
fi

## reactivate internal mail system
if [ -d /etc/exim ]; then
 cp $SRCDIR/install/src/exim.conf /etc/exim/
 rm /var/spool/mail 2>&1 >> $LOGFILE
 ln -s /var/spool/mail /var/mail
fi

###############################################
### building libraries

echo -n " - Installing libraries...                             "
./install_libs.sh 2>&1 >> $LOGFILE
echo "[done]"

###############################################
### building mysql

echo -n " - Installing database system...                       "
./install_mysql.sh  2>&1 >> $LOGFILE
echo "[done]"

###############################################
### building and install perl libraries

echo -n " - Building libraries...                               "
./install_perl_libs.sh 2>&1 >> $LOGFILE
echo "[done]"

###############################################
### creating databases

echo -n " - Creating databases...                               "
MYMAILCLEANERPWD=`pwgen -1`
echo "MYMAILCLEANERPWD = $MYMAILCLEANERPWD" >> $CONFFILE
export MYMAILCLEANERPWD
./MC_prepare_dbs.sh  2>&1 >> $LOGFILE

## recreate my_slave.cnf
#$SRCDIR/bin/dump_mysql_config.pl 2>&1 >> $LOGFILE
$SRCDIR/etc/init.d/mysql_slave restart 2>&1 >> $LOGFILE
echo "[done]"
sleep 5

###############################################
### building exim

echo -n " - Installing MTA...                                   "
./install_exim.sh  2>&1 >> $LOGFILE

$SRCDIR/bin/dump_exim_config.pl 2>&1 >> $LOGFILE
echo "[done]"

###############################################
### building anti-spams

echo -n " - Building Antispam tools...                          "
./install_dcc.sh 2>&1 >> $LOGFILE
./install_razor.sh 2>&1 >> $LOGFILE
./install_pyzor.sh 2>&1 >> $LOGFILE
./install_sa.sh 2>&1 >> $LOGFILE
cd $SRCDIR/install 2>&1 >> $LOGFILE
echo "[done]";

###############################################
### building MailScanner

cd $SRCDIR/install 
echo -n " - Installing engine...                                "
./install_mailscanner.sh 2>&1 >> $LOGFILE
ln -s $SRCDIR/etc/mailscanner/spam.assassin.prefs.conf $SRCDIR/share/spamassassin/mailscanner.cf 2>&1 > /dev/null

# creating syslog entries
# with rsyslog, created in dump_exim_conf
if [ ! -f /etc/init.d/rsyslog ]; then
  LOGLINE=`grep 'mailscanner/infolog' /etc/syslog.conf`
  if [ "$LOGLINE" = "" ]; then
        echo "local0.info     -$VARDIR/log/mailscanner/infolog" >> /etc/syslog.conf
        echo "local0.warn     -$VARDIR/log/mailscanner/warnlog" >> /etc/syslog.conf
        echo "local0.err      $VARDIR/log/mailscanner/errorlog" >> /etc/syslog.conf

        /etc/init.d/sysklogd restart 2>&1 >> $LOGFILE
  fi

  # prevent syslog to rotate mailscanner log files
  perl -pi -e 's/`syslogd-listfiles`/`syslogd-listfiles -s mailscanner`/' /etc/cron.daily/sysklogd 2>&1 >> $LOGFILE
  perl -pi -e 's/`syslogd-listfiles --weekly`/`syslogd-listfiles --weekly -s mailscanner`/' /etc/cron.weekly/sysklogd 2>&1 >> $LOGFILE
fi
cd $SRCDIR/install

$SRCDIR/bin/dump_mailscanner_config.pl 2>&1 >> $LOGFILE

###############################################
### install starter baysian packs
STPACKDIR=/root/starters
STPACKFILE=$STPACKDIR.tar.lzma
if [ -f $STPACKFILE ]; then
  export MYPWD=`pwd` 
  cd /root
  tar --lzma -xvf $STPACKFILE 2>&1 >> $LOGFILE
  cd $MYPWD
fi
if [ -d $STPACKDIR ]; then
  cp $STPACKDIR/wordlist.db $VARDIR/spool/bogofilter/database/ 2>&1 >> $LOGFILE
  chown -R mailcleaner:mailcleaner $VARDIR/spool/bogofilter/ 2>&1 >> $LOGFILE
  cp $STPACKDIR/bayes_toks $VARDIR/spool/spamassassin/ 2>&1 >> $LOGFILE
  chown -R mailcleaner:mailcleaner $VARDIR/spool/spamassassin/ 2>&1 >> $LOGFILE
  cp -a $STPACKDIR/clamspam/* $VARDIR/spool/clamspam/ 2>&1 >> $LOGFILE
  chown -R clamav:clamav $VARDIR/spool/clamspam 2>&1 >> $LOGFILE

  cp -a $STPACKDIR/clamd/* $VARDIR/spool/clamav/ 2>&1 >> $LOGFILE
  chown -R clamav:clamav $VARDIR/spool/clamav 2>&1 >> $LOGFILE
fi
echo "[done]"

###############################################
### building anti-viruses

echo -n " - Installing AntiVirus software...                    "
./install_clamav.sh 2>&1 >> $LOGFILE

$SRCDIR/bin/dump_clamav_config.pl 2>&1 >> $LOGFILE
echo "[done]"

###############################################
### building and install setuid wrapper
#cd src/wrapper
#./install.sh 2>&1 >> $LOGFILE
#cd ../..

###############################################
### building and install snmp

$SRCDIR/bin/dump_snmpd_config.pl 2>&1 >> $LOGFILE

###############################################
### building and install apache and php
echo -n " - Installing web interface...                         "

./install_apache.sh 2>&1 >> $LOGFILE

$SRCDIR/bin/dump_apache_config.pl 2>&1 >> $LOGFILE
echo "[done]"

###############################################
### installing ssh keys

#$SRCDIR/bin/dump_ssh_keys.pl 2>&1 >> $LOGFILE

###############################################
### correcting some rights

chown -R mailcleaner $SRCDIR/etc 2>&1 > /dev/null

## remove locate database auto update
if [ -f /etc/cron.daily/find ]; then
  rm /etc/cron.daily/find
fi

## create starter status file
cat > $VARDIR/run/mailcleaner.status <<EOF
Disk : OK
Swap: 0
Raid: OK
Spools: 0
Load: 0.00
EOF
cp $VARDIR/run/mailcleaner.status $VARDIR/run/mailcleaner.127.0.0.1.status

## import default certificate
CERTFILE=$SRCDIR/etc/apache/certs/default.pem
KF=`grep -n 'BEGIN RSA PRIVATE KEY' $CERTFILE | cut -d':' -f1`
KT=`grep -n 'END RSA PRIVATE KEY' $CERTFILE | cut -d':' -f1`
CF=`grep -n 'BEGIN CERTIFICATE' $CERTFILE | cut -d':' -f1`
CT=`grep -n 'END CERTIFICATE' $CERTFILE | cut -d':' -f1`
KEY=`sed -n "${KF},${KT}p;${KT}q" $CERTFILE`
CERT=`sed -n "${CF},${CT}p;${CT}q" $CERTFILE`
QUERY="USE mc_config; UPDATE httpd_config SET tls_certificate_data='${CERT}', tls_certificate_key='${KEY}';"
echo "$QUERY" | $SRCDIR/bin/mc_mysql -m 2>&1 >> $LOGFILE

echo "update mta_config set smtp_banner='\$smtp_active_hostname ESMTP MailCleaner ($MCVERSION) \$tod_full';" | $SRCDIR/bin/mc_mysql -m mc_config 2>&1 >> $LOGFILE

###############################################
### installing mailcleaner cron job

echo -n " - Installing scheduled jobs...                        "
echo "0,15,30,45 * * * *  $SRCDIR/scripts/cron/mailcleaner_cron.pl > /dev/null" >> /var/spool/cron/crontabs/root
echo "0-59/5 * * * * $SRCDIR/bin/collect_rrd_stats.pl > /dev/null" >> /var/spool/cron/crontabs/root
crontab /var/spool/cron/crontabs/root 2>&1 >> $LOGFILE
/etc/init.d/cron restart 2>&1 >> $LOGFILE

echo "[done]"
###############################################
### starting and installing mailcleaner service
echo -n " - Starting services...                                "
if [ ! -d $SRCDIR/etc/firewall ]; then
  mkdir $SRCDIR/etc/firewall 2>&1 >> $LOGFILE
fi
$SRCDIR/bin/dump_firewall.pl 2>&1 >> $LOGFILE
ln -s $SRCDIR/etc/init.d/mailcleaner /etc/init.d/ 2>&1 > /dev/null
/etc/init.d/mailcleaner stop  2>&1 >> $LOGFILE
update-rc.d mailcleaner defaults 2>&1 >> $LOGFILE
/etc/init.d/mailcleaner start 2>&1 >> $LOGFILE
sleep 5
$SRCDIR/etc/init.d/apache restart 2>&1 >> $LOGFILE
$SRCDIR/bin/collect_rrd_stats.pl 2>&1 >> $LOGFILE
echo "[done]"
