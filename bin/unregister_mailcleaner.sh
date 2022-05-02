#!/bin/bash
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2017 Florian Billebault <florian.billebault@gmail.com>
#   Copyright (C) 2017 Mentor Reka <reka.mentor@gmail.com>
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
#   This script let you unregister Mailcleaner to the Mailcleaner.net Update Services
#
#   Usage:
#           unregister_mailcleaner.sh --no-rsp|resellerpassword [-b]

batch=0
if [ "$2" = "-b" ];then 
  batch=1
fi

CONFFILE=/etc/mailcleaner.conf

REGISTERED=`grep 'REGISTERED' $CONFFILE | cut -d ' ' -f3`
CLIENTID=`grep 'CLIENTID' $CONFFILE | cut -d ' ' -f3`
HOSTID=`grep 'HOSTID' $CONFFILE | cut -d ' ' -f3`
RESELLERID=`grep 'RESELLERID' $CONFFILE | cut -d ' ' -f3`
MCREPO=`grep 'MCREPO' $CONFFILE | cut -d ' ' -f3`

if [ "$REGISTERED" = "" ];then 
  exit 0
fi

SRCDIR=`grep 'SRCDIR' $CONFFILE | cut -d ' ' -f3`
if [ "$SRCDIR" = "" ]; then 
  SRCDIR="/opt/mailcleaner"
fi
VARDIR=`grep 'VARDIR' $CONFFILE | cut -d ' ' -f3`
if [ "$VARDIR" = "" ]; then
  VARDIR="/opt/mailcleaner"
fi

HTTPPROXY=`grep -e '^HTTPPROXY' $CONFFILE | cut -d ' ' -f3`
export http_proxy=$HTTPPROXY


function check_parameter {
        if [ "$1" = "" ]; then
                echo "Error: parameter not given.."
                let RETURN=0
        else
                let RETURN=1
        fi
}

#####
# get or ask values
if [ "$batch" = 0 ]; then
echo "*****************************************"
echo "** Welcome to Mailcleaner UNregistration **"
echo "*****************************************"
echo ""
fi

RESELLERPWD=$1
if [ "$RESELLERPWD" = "" ]; then
let RETURN=0;
while [ $RETURN -lt 1 ]; do
        echo -n "What is you reseller password: "
        read -s RESELLERPWD
        check_parameter $RESELLERPWD
done
elif [ "$RESELLERPWD" = "-b" ]; then
echo "Usage: unregister_mailcleaner.sh --no-rsp|resellerpassword [-b]"
exit 1
fi

if [ ! -d /root/.ssh ]; then
        mkdir /root/.ssh
fi
cd /tmp

if [ -f $CLIENTID.tar.gz ]; then
	rm $CLIENTID.tar.gz >/dev/null 2>&1
fi

if [ -f "/tmp/mc_unregister.error" ]; then
	rm /tmp/mc_unregister.error >/dev/null 2>&1
fi

#check_parameter $RESELLERID

if [ "$batch" = 0 ]; then
echo -n "invalidating keys..."
fi 

# Inform team about unregistration
URL="http://reselleradmin.mailcleaner.net/hosts/unregister.php"
if [ "$REGISTERED" = "1" ];then
        # EE Unregistration
        URL="$URL?cid=$CLIENTID&hid=$HOSTID&rid=$RESELLERID&p=$RESELLERPWD"
elif [ "$REGISTERED" = "2" ];then
        # CE Unregistration
        TMP_FILE=/tmp/mcce.tmp
        echo "select * from registration LIMIT 1 \G" | mc_mysql -m mc_community &> $TMP_FILE
        FIRST_NAME=`grep 'first_name' $TMP_FILE | cut -d ':' -f2`
        LAST_NAME=`grep 'last_name' $TMP_FILE | cut -d ':' -f2`
        EMAIL=`grep 'email' $TMP_FILE | cut -d ':' -f2`
        COMPANY=`grep 'company' $TMP_FILE | cut -d ':' -f2`
        ADDRESS=`grep 'address' $TMP_FILE | cut -d ':' -f2`
        POSTAL_CODE=`grep 'postal_code' $TMP_FILE | cut -d ':' -f2`
        CITY=`grep 'city' $TMP_FILE | cut -d ':' -f2`
        COUNTRY=`grep 'country' $TMP_FILE | cut -d ':' -f2`
        ACCEPT_NEWSLETTERS=`grep 'accept_newsletters' $TMP_FILE | cut -d ':' -f2`
        ACCEPT_RELEASES=`grep 'accept_releases' $TMP_FILE | cut -d ':' -f2`
        ACCEPT_SEND_STATISTICS=`grep 'accept_send_statistics' $TMP_FILE | cut -d ':' -f2`
        HTTP_PARAMS="?first_name=$FIRST_NAME&last_name=$LAST_NAME&email=$EMAIL&host_id=$HOSTID"
        HTTP_PARAMS="$HTTP_PARAMS&company=$COMPANY&address=$ADDRESS&postal_code=$POSTAL_CODE&city=$CITY"
        HTTP_PARAMS="$HTTP_PARAMS&country=$COUNTRY&accept_newsletters=$ACCEPT_NEWSLETTERS"
        HTTP_PARAMS="$HTTP_PARAMS&accept_releases=$ACCEPT_RELEASES&accept_send_statistics=$ACCEPT_SEND_STATISTICS"
        URL="$URL$HTTP_PARAMS"
        rm $TMP_FILE
	# Also local unregistration
	sql="DELETE FROM registration;"
	echo $sql|$SRCDIR/bin/mc_mysql -m mc_community
fi

wget -q "$URL" -O /tmp/mc_unregister.out >/dev/null 2>&1

if [ "$batch" = 0 ]; then
echo -n "Removing keys..."
fi

rm -f /root/.ssh/id_rsa >/dev/null 2>&1
rm -f /root/.ssh/id_rsa.pub >/dev/null 2>&1

function removeKey() {
        sed -i "/${1}/d" /root/.ssh/authorized_keys
	if [ "$batch" = 0 ]; then
		echo -n "MC support keys deleted"
	fi
}

# Remove employee keys
removeKey 'PwN+8SLCGmrKYBfiiMAdl601XpMZWBtPWWtp7iqYNdmmFnk+561fzquKBXktvyFMetlhd2PvJOwnhKe6lPWH3S1FwWBttP6pyMlnTiD'
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAACAQDVagDT2xUUg5FkZJVwmZGbZe3pWtZxFwGc9DLcka5HF'
removeKey 'AAAAB3NzaC1kc3MAAACBALQGMC8i4UXhV8FvU55Gyk9miPDahEp4O'
removeKey 'gT144M0VC9ZzTPoPEPeuB+mggfSzZlubY4pJDpv5wB4ZwihIYFXYiuf08DRmGFwNOpZUY6hDOVDVOIWniR8j4Lhsh1LXy7Tee'
removeKey 'jeN26lpG9Ijw028CbKkf4BgRoW+B8stCsy7KYZLZtYaK9dqwEpNlZCnmc8MC1mCB'
removeKey '5rnfCt33RAwefucps6Eq3ga4Ui2VixmJPcDhCFi8mux8GB6xDX1DXUHhx4GhrClyQWh9ioCvG3+iDSFS2iEehgPQQCiJG5sQXmVTkB95Oya7fmTYfGJQDCR7XYympJkl8zqFrg'
removeKey '7HTkU1S13bJpwXB2LqBPxUjo2v+MfZBOK+4FwzZ7QKh776RMyMINvNbbzdK4wbtBSfBo1Mi3rf+E0'
removeKey 'NzaC1yc2EAAAADAQABAAACAQDQqqaMbFJAH+HBRCREq5oIFa4YEGIEIARYeXVGMaIbV7tj9WN7yOVmDY9LO1YKIXmmLbZoaCHmmMA3z02tf2tJ5zUs'
removeKey 'myLjcr03BPaKcPa+yKlxn0oXe6yIJf6JL+zerMwEl6GSs3jj/R4EhNHa7NjTAbwCIipbum6imcBiVQeTAMFFdNcJW5+V85RJhVCJ7JAaUDegmacLf7w8c+0RTshMUUw9LirO'


if [ "$batch" = 0 ]; then
echo -n "done"
fi

if [ "$batch" = 0 ]; then
echo -n "Removing known hosts..."
fi
sed -i '/mailcleaner/d' /root/.ssh/known_hosts >/dev/null 2>&1
if [ "$batch" = 0 ]; then
echo -n "done"
fi

if [ "$batch" = 0 ]; then
echo -n "Removing authorized keys (File deleted, copy your own key again if needed)..."
fi
rm -f /root/.ssh/authorized_keys >/dev/null 2>&1
if [ "$batch" = 0 ]; then
echo -n "done"
fi

if [ "$batch" = 0 ]; then
echo -n "Restoring Community Edition content..."
fi

if [ "$REGISTERED" = "1" ];then
	rm -rf $SRCDIR/share/spamassassin/* >/dev/null 2>&1
	rm -rf $SRCDIR/share/newsld/siteconfig/* >/dev/null 2>&1
	rm -rf $SRCDIR/etc/exim/mc_binary/* >/dev/null 2>&1
	rm -rf $SRCDIR/etc/rbls/* >/dev/null 2>&1
	rm -rf $SRCDIR/bin/watchdog/EE_* >/dev/null 2>&1
	rm -rf $SRCDIR/etc/watchdog/EE_* >/dev/null 2>&1
	rm -rf $SRCDIR/bin/watchdog/dbs.md5 >/dev/null 2>&1
	rm -rf $SRCDIR/etc/watchdog/dbs.md5 >/dev/null 2>&1
	rm -rf $SRCDIR/updates/* >/dev/null 2>&1
	rm -f $VARDIR/spool/bogofilter/database/wordlist.db >/dev/null 2>&1
	rm -f $VARDIR/spool/spamassassin/bayes_* >/dev/null 2>&1
	rm -rf $VARDIR/spool/clamspam/* >/dev/null 2>&1
	rm -rf $VARDIR/spool/clamav/* >/dev/null 2>&1
	cp -f /root/starters/clamd/* $VARDIR/spool/clamav/ 
	cp -f /root/starters/wordlist.db $VARDIR/spool/bogofilter/database/wordlist.db >/dev/null 2>&1
	cp -f /root/starters/bayes_toks $VARDIR/spool/spamassassin/bayes_toks >/dev/null 2>&1
	# Restore CE missing files
	cd $SRCDIR && git ls-files -d | xargs git checkout --
fi

if [ "$batch" = 0 ]; then
echo -n "done"
fi

if [ "$batch" = 0 ]; then
echo -n "creating shell defaults..."
fi
echo "export PS1='\h:\w\$ '" > /root/.bashrc
echo "umask 022" >> /root/.bashrc
echo "export CVS_RSH=ssh" >> /root/.bashrc
echo "export CVSROOT=:ext:mccvs@cvs.mailcleaner.net:/var/lib/cvs" >> /root/.bashrc
if [ "$REGISTERED" = "1" ];then
        echo "export PROMPT_COMMAND='echo -ne \"\033]0;${USER}@${HOSTNAME} - $CLIENTID-$HOSTID \007\"'" >> /root/.bashrc
fi
echo "export PATH=$PATH:$SRCDIR/bin" >> /root/.bashrc
cp /root/.bashrc /root/.bash_profile

#export CVS_RSH=ssh
#export CVSROOT=:ext:mccvs@cvs.mailcleaner.net:/var/lib/cvs
if [ "$REGISTERED" = "1" ];then
	export PROMPT_COMMAND='echo -ne \"\033]0;${USER}@${HOSTNAME} - $CLIENTID-$HOSTID \007\"'
fi
export PATH=$PATH:$SRCDIR/bin
if [ "$batch" = 0 ]; then
echo "done"
fi

if [ "$batch" = 0 ]; then
echo -n "writing configuration file..."
fi
CONFFILE=/etc/mailcleaner.conf
sed -i '/CLIENTID/d' $CONFFILE >/dev/null 2>&1
sed -i '/RESELLERID/d' $CONFFILE >/dev/null 2>&1
sed -i '/REGISTERED/d' $CONFFILE >/dev/null 2>&1
if [ "$batch" = 0 ]; then
echo "done"
fi

rm -f $SRCDIR/www/guis/admin/public/templates/default/images/login_header.png
ln -s $SRCDIR/www/guis/admin/public/templates/default/images/login_header_ce.png $SRCDIR/www/guis/admin/public/templates/default/images/login_header.png
rm -f $SRCDIR/www/guis/admin/public/templates/default/images/logo_name.png
ln -s $SRCDIR/www/guis/admin/public/templates/default/images/logo_name_ce.png $SRCDIR/www/guis/admin/public/templates/default/images/logo_name.png
rm -f $SRCDIR/www/guis/admin/public/templates/default/images/status_panel.png
ln -s $SRCDIR/www/guis/admin/public/templates/default/images/status_panel_ce.png $SRCDIR/www/guis/admin/public/templates/default/images/status_panel.png
rm -f $SRCDIR/www/user/htdocs/templates/default/images/login_header.png
ln -s $SRCDIR/www/user/htdocs/templates/default/images/login_header_ce.png $SRCDIR/www/user/htdocs/templates/default/images/login_header.png
rm -f $SRCDIR/www/user/htdocs/templates/default/images/logo_name.png
ln -s $SRCDIR/www/user/htdocs/templates/default/images/logo_name_ce.png $SRCDIR/www/user/htdocs/templates/default/images/logo_name.png
rm -f $SRCDIR/templates/summary/default/en/summary_parts/banner.jpg
ln -s $SRCDIR/templates/summary/default/en/summary_parts/banner_ce.jpg $SRCDIR/templates/summary/default/en/summary_parts/banner.jpg

sed -ri 's/^(\s+).*__MAINHEADERBG__.*$/\1background-color: #5C6D99; \/\*__MAINHEADERBG__\*\//g' $SRCDIR/www/guis/admin/public/templates/default/css/main.css
sed -ri 's/^(\s+).*__MAINHEADERBG__.*$/\1background-color: #5C6D99; \/\*__MAINHEADERBG__\*\//g' $SRCDIR/www/guis/admin/public/templates/default/css/login.css
sed -ri 's/^(\s+).*__MAINHEADERBG__.*$/\1background-color: #5C6D99; \/\*__MAINHEADERBG__\*\//g' $SRCDIR/www/user/htdocs/templates/default/css/navigation.css
sed -ri 's/^(\s+).*__MAINHEADERBG__.*$/\1background-color: #5C6D99; \/\*__MAINHEADERBG__\*\//g' $SRCDIR/www/user/htdocs/templates/default/css/login.css

echo "delete from administrator where username='mailcleaner-support';" | $SRCDIR/bin/mc_mysql -m mc_config &> /dev/null
echo "delete from external_access where service='ssh' AND port='22' AND protocol='TCP' AND (allowed_ip='193.246.63.0/24' OR allowed_ip='195.176.194.0/24');" | $SRCDIR/bin/mc_mysql -m mc_config &> /dev/null

echo "Community Edition" > $SRCDIR/etc/edition.def

if [ "$batch" = 0 ]; then
echo "*****************"
echo "UNREGISTRATION SUCCESSFULL !"
echo "*****************"
else
  echo "SUCCESS"
fi
exit 0
