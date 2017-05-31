
#!/bin/bash
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2017 Florian Billebault <florian.billebault@gmail.com>
#   Copyright (C) 2017 Mentor Reka <reka.mentor@gmail.com>

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
#   This script prepare MailCleaner structure and datas for a new releases or tests
#
#   Usage: prepare_mc_release -i 2017041501 -d '2017-10-04' -t '12:00:00' -r '2017.04 migrated to Jessie' -m true resellerID resellerPwd ClientID dbPassword
#
#   Options:
#   -h, Help: Usage
#   -i,	MC ID
#   -d,	Date
#   -t, Time
#   -r, Reason
#   -m, Mode Dev: true or false - delete logs or not and some others things
#


PROGNAME='prepare_mc_release'
VERSION='0.5'


VARDIR=`grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "$VARDIR" = "" ]; then
  VARDIR=/var/mailcleaner
fi
SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "$SRCDIR" = "" ]; then
  SRCDIR=/var/mailcleaner
fi

function cdel {
    if [ "$modeDev" != "false" ]; then
	echo "Dev Mode On: rm $@"
    else
	rm $@
    fi
}

usage() {
  cat <<- _EOF_
  Usage: $PROGNAME -i 2017041501 -d '2017-10-04' -t '12:00:00' -r '2017.04 migrated to Jessie' -m true resellerID resellerPwd ClientID dbPassword

  Options (Not really here...) :

  -h,   Help: Usage
  -i,	MC ID
  -d,	Date
  -t,   Time
  -r,   Reason
  -m,   Mode Dev: true OR false OR test - delete logs or not and some others things

_EOF_
}

modeDev=true

while getopts ":hd:i:t:r:m:" option
do
  case $option in
    h)
	usage
	exit 0
	;;
    d)
	patchDate=$OPTARG
	;;
    i)
	patchID=$OPTARG
	;;
    t)
	patchTime=$OPTARG
	;;
    r)
	reason=$OPTARG	
	;;
    m)
	modeDev=$OPTARG
	;;
    :)
	echo "L'option $OPTARG requiert un argument"
	exit 1
	;;
    ?)
	echo "$OPTARG : option invalide"
	exit 1
	;;
  esac
done

shift $((OPTIND-1))

if [ -z "$patchDate" ] || [ -z "$patchID" ] || [ -z "$patchTime" ] || [ -z "$reason" ]; then
    usage
    exit 1
fi

resellerID="$1"
resellerPwd="$2"
clientID="$3"
dbPassword="$4"

if [ -z "$resellerID" ] || [ -z "$resellerPwd" ] || [ -z "$clientID" ] || [ -z "$dbPassword" ]; then
    usage
    exit 1
fi

echo "Beginning..."
service cron stop

echo Updating date
ntpdate 0.debian.pool.ntp.org

echo Registrating MailCleaner
echo "Using values: $resellerID $resellerPwd $clientID"
${SRCDIR}/bin/register_mailcleaner.sh "$resellerID" "$resellerPwd" "$clientID" -b

echo Dump of ClamAV config and update of ClamAV antivirus files
${SRCDIR}/bin/dump_clamav_config.pl
/opt/clamav/bin/freshclam --user=clamav --config-file=/usr/mailcleaner/etc/clamav/freshclam.conf

STARTERSPATH="/root/starters"

echo Stop MailCleaner
${SRCDIR}/etc/init.d/mailcleaner stop

echo Remove know_hosts
rm -f ~/.ssh/known_hosts

echo Delete MC SRCDIR and recreate it from last version
cdel -rf "$SRCDIR"
if [ ! -d "$SRCDIR" ]; then
    cd /usr
    git clone git@github.com:MailCleaner/MailCleaner.git mailcleaner
fi

cd

${SRCDIR}/etc/init.d/mailcleaner start

cdel -rf $STARTERSPATH
[ ! -d "$STARTERSPATH" ] && mkdir $STARTERSPATH

. $SRCDIR/lib/updates/download_files.sh

randomize=false;

echo Update ClamAV starters files
[ ! -d "${STARTERSPATH}/clamd" ] && mkdir "${STARTERSPATH}/clamd"
cp -f "${VARDIR}/spool/clamav/"{bytecode.c[vl]d,daily.c[vl]d,main.c[vl]d} "${STARTERSPATH}/clamd/"
downloadDatas "${STARTERSPATH}/clamd/" "clamav3" $randomize "clamav" "\|main.cvd\|bytecode.cvd\|daily.cvd\|mirrors.dat\|others\|magic.mgc" noexit
cdel -f "${STARTERSPATH}/clamd/dbs.md5"

echo Update ClamSpam starters files
[ ! -d "$STARTERSPATH/clamspam" ] && mkdir "${STARTERSPATH}/clamspam"
downloadDatas "${STARTERSPATH}/clamspam/" "clamspam3" $randomize "clamav" "\|main.cvd\|bytecode.cvd\|daily.cvd\|mirrors.dat\|others\|magic.mgc" noexit
cdel -f "${STARTERSPATH}/clamspam/dbs.md5"

echo Update Nicebayes starters files
downloadDatas "${STARTERSPATH}/" "bayes_bogo" $randomize "mailcleaner" "\|clamd\|clamspam\|wordlist.db\|bayes_toks\|others\|magic.mgc" noexit
cdel -f "${STARTERSPATH}/dbs.md5"

echo Update Spamassassin bayesian starters files
downloadDatas "${STARTERSPATH}/" "bayes_packs" $randomize "mailcleaner" "\|clamd\|clamspam\|wordlist.db\|bayes_toks\|others\|magic.mgc" noexit
cdel -f "${STARTERSPATH}/dbs.md5"

echo Update magic.mgc starters files
downloadDatas "${STARTERSPATH}/" "magic" $randomize "" "\|clamd\|clamspam\|wordlist.db\|bayes_toks\|others\|magic.mgc" noexit
cdel -f "${STARTERSPATH}/dbs.md5"

echo Other Data download
[ ! -d "$STARTERSPATH/others" ] && mkdir "${STARTERSPATH}/others"
downloadDatas "${STARTERSPATH}/others/" "prepare_mc_release" $randomize "mailcleaner" "\|main.cvd\|bytecode.cvd\|daily.cvd\|mirrors.dat\|others\|magic.mgc" noexit

echo End of downloads
echo State of the starters dir:
ls -R $STARTERSPATH
sleep 5s

echo Update db.root file for bind
wget --user=ftp --password=ftp ftp://ftp.rs.internic.net/domain/db.cache -O /etc/bind/db.root

echo Delete or replace DevMode Tag in specified files
[ "$devMode" == "false" ] && sed -i "s/#\[DEVMODEDEL\]//g" "$SRCDIR/bin/unregister_mailcleaner.sh"
${SRCDIR}/bin/unregister_mailcleaner.sh --no-rsp -b

echo Stop MailCleaner
${SRCDIR}/etc/init.d/mailcleaner stop

echo Delete Configurator step files
cdel -f "${VARDIR}/run/configurator/"{adminpass,baseurl,dbpass,hostid,identify,rootpass}
[ ! -d "${VARDIR}/run/configurator" ] && mkdir "${VARDIR}/run/configurator"
chown mailcleaner:mailcleaner "${VARDIR}/run/configurator"
touch "${VARDIR}/run/configurator/welcome"

echo Enable Configurator redirections
cdel -f "${SRCDIR}/etc/apache/sites/configurator.conf.disabled"

echo Delete all useless dirs and files of /root
find /root -mindepth 1 -maxdepth 1 \( -path /root/.ssh -o -path /root/.profile -o -path /root/.pyzor -o -path /root/starters \) -prune -o -print | while read dirdata; do cdel -rf "$dirdata"; done

echo Enable installer.pl redirection
echo 'if ! [ -f "/var/mailcleaner/spool/mailcleaner/firstcmdlogin" ]; then /usr/mailcleaner/scripts/installer/installer.pl; touch "/var/mailcleaner/spool/mailcleaner/firstcmdlogin"; fi' > ~/.bashrc
rm -f /var/mailcleaner/spool/mailcleaner/firstcmdlogin

# Others data installation goes here ->
cp -Rf "${STARTERSPATH}/clamd/"* ${VARDIR}/spool/clamav/
cp -Rf "${STARTERSPATH}/clamspam/"* ${VARDIR}/spool/clamspam/
cp -f "${STARTERSPATH}/wordlist.db" ${VARDIR}/spool/bogofilter/database/
cp -f "${STARTERSPATH}/bayes_toks" ${VARDIR}/spool/spamassassin/
cp -f "${STARTERSPATH}/others/issue" /etc/issue
cp -f "${STARTERSPATH}/magic.mgc" /opt/file/share/misc/magic.mgc 

${SRCDIR}/etc/init.d/mailcleaner start

echo Create file for backup IF 192.168.1.42
echo -e 'auto eth0:0\nallow-hotplug eth0:0\niface eth0:0 inet static\n\taddress 192.168.1.42\n\tnetmask 255.255.255.0\n' > /etc/network/interfaces.d/configif.conf

echo Set port access for the configurator
echo "DELETE FROM external_access where service='configurator'" |${SRCDIR}/bin/mc_mysql -m mc_config
echo "INSERT INTO external_access values(NULL, 'configurator', '4242', 'TCP', '0.0.0.0/0', 'NULL')" |${SRCDIR}/bin/mc_mysql -m mc_config

echo Set default value in DB
echo "update domain_pref set allow_newsletters=0,prevent_spoof=1 where id=(select prefs from domain where name='__global__')\G" |${SRCDIR}/bin/mc_mysql -m mc_config

echo Insert Version in DB
echo "DELETE FROM update_patch WHERE id='${patchID}';" |${SRCDIR}/bin/mc_mysql -s mc_config
echo "INSERT INTO update_patch VALUES ('${patchID}', '${patchDate}', '${patchTime}', 'OK', '${reason}');" |${SRCDIR}/bin/mc_mysql -s mc_config

sleep 10s

echo Delete all possible MySQL bin logs
echo "FLUSH LOGS;" |/opt/mysql5/bin/mysql -uroot -p"$dbPassword" --socket=${VARDIR}/run/mysql_master/mysqld.sock
echo "PURGE BINARY LOGS BEFORE NOW();" |/opt/mysql5/bin/mysql -uroot -p"$dbPassword" --socket=${VARDIR}/run/mysql_master/mysqld.sock
sleep 5s
echo Delete old not aligned binary logs
ls -1 ${VARDIR}/log/mysql_master/mysql_bin.[0-9]* | sort ${VARDIR}/log/mysql_master/mysql_bin.index ${VARDIR}/log/mysql_master/mysql_bin.index - |uniq -u |xargs rm -f

echo "Delete messages in queues"
/opt/exim4/bin/exiqgrep -C ${SRCDIR}/etc/exim/exim_stage1.conf -i |xargs /opt/exim4/bin/exim -Mrm
/opt/exim4/bin/exiqgrep -C ${SRCDIR}/etc/exim/exim_stage2.conf -i |xargs /opt/exim4/bin/exim -Mrm
/opt/exim4/bin/exiqgrep -C ${SRCDIR}/etc/exim/exim_stage4.conf -i |xargs /opt/exim4/bin/exim -Mrm

echo "Delete Watchdog files"
cdel -f ${VARDIR}/spool/watchdog/watchdogs*
cdel -f ${VARDIR}/spool/watchdog/reports/*
cdel -f ${VARDIR}/spool/watchdog/reports.wrk/*


${SRCDIR}/etc/init.d/mailcleaner stop

# CleanUp in MC SRCDIR
# Things to do to add !

apt-get clean

echo Delete Others data files not useful anymore
cdel -rf "${STARTERSPATH}/others"

echo Delete MC logs
cdel -rf "${VARDIR}/log/"{apache,clamav,exim_stage1,exim_stage2,exim_stage4,mailcleaner,mailscanner,mysql_slave}"/*"
cdel -rf "${VARDIR}/log/mysql_master/*.log*"

echo Delete old system logs and Empty recent logs files
find /var/log/ -type f -name "*.*gz" | while read logdata; do cdel -f "$logdata"; done
[ "$devMode" == "false" ] && find /var/log/ -type f -exec truncate -s0 {} \;

echo Shrink VM
[ "$devMode" == "false" ] && time dd if=/dev/zero of=/tmp/spaceuseless
rm -f /tmp/spaceuseless
[ "$devMode" == "false" ] && fstrim -av

echo Delete History files
cdel -f "/root/"{.bash_history,.nano_history,.mysql_history}

service cron start
echo 'End of preparation, please exit with : history -c && unset $HIST_FILE && exit'
