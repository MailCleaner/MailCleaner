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

export ACTUALUPDATE="20250328"
export MCVERSION="Community Edition 2025"
 
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

ssh-keygen -q -t ed_25519 -f $VARDIR/.ssh/id_rsa -N ""
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
### creating databases

echo -n " - Creating databases...                               "
MYMAILCLEANERPWD="MCPassw0rd"
echo "MYMAILCLEANERPWD = $MYMAILCLEANERPWD" >> $CONFFILE
export MYMAILCLEANERPWD
$SRCDIR/install/MC_prepare_dbs.sh  2>&1 >> $LOGFILE

## recreate my_slave.cnf
#$SRCDIR/bin/dump_mysql_config.pl 2>&1 >> $LOGFILE
$SRCDIR/etc/init.d/mysql_slave restart 2>&1 >> $LOGFILE
echo "[done]"
sleep 5

## create starter status file
cat > $VARDIR/run/mailcleaner.status <<EOF
Disk : OK
Swap: 0
Raid: OK
Spools: 0
Load: 0.00
EOF
cp $VARDIR/run/mailcleaner.status $VARDIR/run/mailcleaner.127.0.0.1.status

###############################################
### starting and installing mailcleaner service
echo -n " - Starting services...                                "
$SRCDIR/etc/init.d/mailcleaner start 2>&1 >/dev/null
echo "[done]"
