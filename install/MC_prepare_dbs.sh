#!/bin/bash

if [ "$SRCDIR" = "" ]; then
        SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
        if [ "SRCDIR" = "" ]; then
                SRCDIR=/var/mailcleaner
        fi
fi
if [ "$VARDIR" = "" ]; then
	VARDIR=`grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
	if [ "VARDIR" = "" ]; then
  		VARDIR=/var/mailcleaner
	fi
fi
if [ "$CLIENTORG" = "" ]; then
	CLIENTORG=`grep 'CLIENTORG' /etc/mailcleaner.conf | cut -d ' ' -f3`
	MCHOSTNAME=`grep 'MCHOSTNAME' /etc/mailcleaner.conf | cut -d ' ' -f3`
	HOSTID=`grep 'HOSTID' /etc/mailcleaner.conf | cut -d ' ' -f3`
	CLIENTID=`grep 'CLIENTID' /etc/mailcleaner.conf | cut -d ' ' -f3`
	DEFAULTDOMAIN=`grep 'DEFAULTDOMAIN' /etc/mailcleaner.conf | cut -d ' ' -f3`
	CLIENTTECHMAIL=`grep 'CLIENTTECHMAIL' /etc/mailcleaner.conf | cut -d ' ' -f3`
	MYMAILCLEANERPWD=`grep 'MYMAILCLEANERPWD' /etc/mailcleaner.conf | cut -d ' ' -f3`
	ISMASTER=`grep 'ISMASTER' /etc/mailcleaner.conf | cut -d ' ' -f3`
fi
VARDIR_SANE=`echo $VARDIR | perl -pi -e 's/\//\\\\\//g'`

echo "-- removing previous mariadb databases and stopping mariadb"
$SRCDIR/etc/init.d/mariadb@slave stop 2>&1
$SRCDIR/etc/init.d/mariadb@master stop 2>&1
rm -rf $VARDIR/spool/mysql_master/*; rm -rf $VARDIR/spool/mysql_slave/* 2>&1
rm -rf $VARDIR/log/mysql_master/*; rm -rf $VARDIR/log/mysql_slave/* 2>&1
rm -rf $VARDIR/run/mysql_master/*; rm -rf $VARDIR/run/mysql_slave/* 2>&1

##
# first, ask for the mysql admin password

if [ "$MYMAILCLEANERPWD" = "" ]; then
  echo -n "enter mariadb mailcleaner password: "
  read -s MYMAILCLEANERPWD
  echo ""
fi

##
# next generate databases
#
# slave:

$SRCDIR/bin/dump_mysql_config.pl 2>&1

echo "-- generating slave database"
/usr/bin/mysql_install_db --datadir=${VARDIR}/spool/mysql_slave --basedir=/usr --defaults-file=$SRCDIR/etc/mysql/my_slave.cnf 2>&1
chown -R mysql:mysql ${VARDIR}/spool/mysql_slave 2>&1

#
# master

echo "-- generating master database"
/usr/bin/mysql_install_db --datadir=${VARDIR}/spool/mysql_master --basedir=/usr --defaults-file=$SRCDIR/etc/mysql/my_master.cnf 2>&1
chown -R mysql:mysql ${VARDIR}/spool/mysql_master 2>&1


##
# start db

cp $SRCDIR/etc/mysql/my_slave.cnf_template $SRCDIR/etc/mysql/my_slave.cnf
echo "-- starting mariadb"
$SRCDIR/etc/init.d/mariadb@master start 2>&1
$SRCDIR/etc/init.d/mariadb@slave start 2>&1
sleep 5

##
# delete default users and dbs and create mailcleaner dbs and users

echo "-- deleting default databases and users and creating mailcleaner dbs and user"
cat > /tmp/tmp_install.sql << EOF
USE mysql;
FLUSH PRIVILEGES;
ALTER USER 'root'@'localhost' IDENTIFIED BY '$MYMAILCLEANERPWD';
DELETE FROM user WHERE User='';
DELETE FROM db WHERE User='';
DROP DATABASE test;
DELETE FROM user WHERE Password='';
DROP DATABASE IF EXISTS mc_config;
DROP DATABASE IF EXISTS mc_spool;
DROP DATABASE IF EXISTS mc_stats;
CREATE DATABASE mc_config;
CREATE DATABASE mc_spool;
CREATE DATABASE mc_stats;
CREATE DATABASE dmarc_reporting;
DELETE FROM user WHERE User='mailcleaner';
DELETE FROM db WHERE User='mailcleaner';
CREATE USER 'mailcleaner'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD('$MYMAILCLEANERPWD');
GRANT ALL PRIVILEGES ON mc_config.* TO mailcleaner@"%" IDENTIFIED BY '$MYMAILCLEANERPWD' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON mc_spool.* TO mailcleaner@"%" IDENTIFIED BY '$MYMAILCLEANERPWD' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON mc_stats.* TO mailcleaner@"%" IDENTIFIED BY '$MYMAILCLEANERPWD' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON dmarc_reporting.* TO mailcleaner@"%" IDENTIFIED BY '$MYMAILCLEANERPWD' WITH GRANT OPTION;
GRANT REPLICATION SLAVE , REPLICATION CLIENT ON *.* TO  mailcleaner@"%";
USE mysql;
EOF

sleep 1

/usr/bin/mysql -uroot -S ${VARDIR}/run/mysql_slave/mysqld.sock < /tmp/tmp_install.sql 2>&1
/usr/bin/mysql -uroot -S ${VARDIR}/run/mysql_master/mysqld.sock < /tmp/tmp_install.sql 2>&1

rm /tmp/tmp_install.sql 2>&1

echo "-- creating mailcleaner tables"
for SOCKDIR in mysql_slave mysql_master; do
	for file in `ls $SRCDIR/install/dbs/spam/*.sql`; do
		/usr/bin/mysql -umailcleaner -p$MYMAILCLEANERPWD -S$VARDIR/run/$SOCKDIR/mysqld.sock mc_spool < $file
	done
	for file in `ls $SRCDIR/install/dbs/t_cf*`; do
		/usr/bin/mysql -umailcleaner -p$MYMAILCLEANERPWD -S$VARDIR/run/$SOCKDIR/mysqld.sock mc_config < $file
	done
	for file in `ls $SRCDIR/install/dbs/t_sp*`; do
		/usr/bin/mysql -umailcleaner -p$MYMAILCLEANERPWD -S$VARDIR/run/$SOCKDIR/mysqld.sock mc_config < $file
	done
        /usr/bin/mysql -umailcleaner -p$MYMAILCLEANERPWD -S$VARDIR/run/mysql_master/mysqld.sock dmarc_reporting < dbs/dmarc_reporting.sql
        /usr/bin/mysql -umailcleaner -p$MYMAILCLEANERPWD -S$VARDIR/run/mysql_slave/mysqld.sock mc_config < dbs/t_st_maillog.sql
done;

echo "-- forcing update and repair"
$SRCDIR/bin/check_db.pl --myrepair 2>&1 >/dev/null
$SRCDIR/bin/check_db.pl --update 2>&1 >/dev/null

echo "-- inserting config and default values"

## TO DO: check these values !! either coming from the superior installation script or from /etc/mailcleaner.conf

HOSTKEY=`cat /etc/ssh/ssh_host_rsa_key.pub`
if [ "$ISMASTER" = "Y" ]; then
        MASTERHOST=127.0.0.1
        MASTERKEY=`cat $VARDIR/.ssh/id_rsa.pub`
	MASTERPASSWD=$MYMAILCLEANERPWD
fi


[[ -z $CLIENTID ]] && CLIENTID=0
for SOCKDIR in mysql_slave mysql_master; do
        echo "INSERT INTO system_conf (organisation, company_name, hostid, clientid, default_domain, contact_email, summary_from, analyse_to, falseneg_to, falsepos_to, src_dir, var_dir) VALUES ('$CLIENTORG', '$MCHOSTNAME', '$HOSTID', $CLIENTID, '$DEFAULTDOMAIN', '$CLIENTTECHMAIL', '$CLIENTTECHMAIL', '$CLIENTTECHMAIL', '$CLIENTTECHMAIL', '$CLIENTTECHMAIL', '$SRCDIR', '$VARDIR');" | /usr/bin/mysql -umailcleaner -p$MYMAILCLEANERPWD -S$VARDIR/run/$SOCKDIR/mysqld.sock mc_config
	echo "INSERT INTO slave (id, hostname, password, ssh_pub_key) VALUES ('$HOSTID', '127.0.0.1', '$MYMAILCLEANERPWD', '$HOSTKEY');" | /usr/bin/mysql -umailcleaner -p$MYMAILCLEANERPWD -S$VARDIR/run/$SOCKDIR/mysqld.sock mc_config
	echo "INSERT INTO master (hostname, password, ssh_pub_key) VALUES ('$MASTERHOST', '$MASTERPASSWD', '$MASTERKEY');" | /usr/bin/mysql -umailcleaner -p$MYMAILCLEANERPWD -S$VARDIR/run/$SOCKDIR/mysqld.sock mc_config
	echo "INSERT INTO httpd_config (serveradmin, servername) VALUES('root', 'mailcleaner');" | /usr/bin/mysql -umailcleaner -p$MYMAILCLEANERPWD -S$VARDIR/run/$SOCKDIR/mysqld.sock mc_config
done;

sleep 1
$SRCDIR/etc/init.d/mariadb@slave stop
$SRCDIR/etc/init.d/mariadb@slave-nopass start
sleep 5
## MySQL redundency
echo "STOP SLAVE; CHANGE MASTER TO master_host='$MASTERHOST', master_user='mailcleaner', master_password='$MASTERPASSWD'; START SLAVE;" | /usr/bin/mysql -umailcleaner -p$MYMAILCLEANERPWD -S$VARDIR/run/mysql_slave/mysqld.sock mc_config
sleep 1
$SRCDIR/etc/init.d/mariadb@slave-nopass stop
$SRCDIR/etc/init.d/mariadb@slave start 
sleep 5

## creating web admin user
echo "INSERT INTO administrator (username, password, can_manage_users, can_manage_domains, can_configure, can_view_stats, can_manage_host, domains) VALUES('admin', ENCRYPT('$WEBADMINPWD'), 1, 1, 1, 1, 1, '*');" | /usr/bin/mysql -umailcleaner -p$MYMAILCLEANERPWD -S$VARDIR/run/mysql_master/mysqld.sock mc_config

[[ -z $ACTUALUPDATE ]] && ACTUALUPDATE=20250101
## inserting last version update 
echo "INSERT INTO update_patch VALUES('$ACTUALUPDATE', NOW(), NOW(), 'OK', 'Fresh install');" | /usr/bin/mysql -umailcleaner -p$MYMAILCLEANERPWD -S$VARDIR/run/mysql_slave/mysqld.sock mc_config

#$SRCDIR/etc/init.d/mysql_master stop
echo "-- DONE -- mailcleaner dbs are ready !"
