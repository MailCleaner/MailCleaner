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
#   This script let you register Mailcleaner to the Mailcleaner.net Update Services
#   You will be asked for a reseller ID and password.
#
#   Usage:
#           register_mailcleaner.sh

batch=0
if [ "$4" = "-b" ];then 
  batch=1
fi

CONFFILE=/etc/mailcleaner.conf

HOSTID=`grep 'HOSTID' $CONFFILE | cut -d ' ' -f3`
if [ "$HOSTID" = "" ]; then
  HOSTID=1
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
echo "** Welcome to Mailcleaner registration **"
echo "*****************************************"
echo ""
fi

##############################
## reseller informations
##############################
RESELLERID=$1
if [ "$RESELLERID" = "" ]; then
let RETURN=0;
while [ $RETURN -lt 1 ]; do
        echo -n "What is you reseller id: "
        read RESELLERID
        check_parameter $RESELLERID
done
fi

RESELLERPWD=$2
if [ "$RESELLERPWD" = "" ]; then
let RETURN=0;
while [ $RETURN -lt 1 ]; do
        echo -n "What is you reseller password: "
        read -s RESELLERPWD
        check_parameter $RESELLERPWD
done
fi

################################
## client information
################################
CLIENTID=$3
if [ "$CLIENTID" = "" ];then
let RETURN=0;
echo ""
while [ $RETURN -lt 1 ]; do
        echo -n "What is this client id: "
        read CLIENTID
        check_parameter $CLIENTID
done
fi

if [ ! -d /root/.ssh ]; then
        mkdir /root/.ssh
fi
cd /tmp

if [ -f $CLIENTID.tar.gz ]; then
	rm $CLIENTID.tar.gz >/dev/null 2>&1
fi

if [ -f "/tmp/mc_register.error" ]; then
	rm /tmp/mc_register.error >/dev/null 2>&1
fi

if [ "$batch" = 0 ]; then
echo -n "fetching keys..."
fi 
wget -q http://reselleradmin.mailcleaner.net/hosts/register.php?cid="$CLIENTID"\&hid="$HOSTID"\&rid="$RESELLERID"\&p="$RESELLERPWD" -O /tmp/mc_register.out >/dev/null 2>&1

TYPE=`file -b /tmp/mc_register.out | cut -d' ' -f1`
if [ ! "$TYPE" = "gzip" ]; then
   mv /tmp/mc_register.out /tmp/mc_register.error
else
   mv /tmp/mc_register.out $CLIENTID-$HOSTID.tar.gz
fi
if [ -f "/tmp/mc_register.error" ]; then
  ERROR=`cat /tmp/mc_register.error`
  if [ "$ERROR" != "" ]; then
  if [ "$batch" = 0 ]; then
  echo ""
  echo "*** ERROR while registrating to Mailcleaner service ***"
  fi
  KNOWNERROR=0
  if [ "$ERROR" = "BADLOGIN" ]; then
    if [ "$batch" = 0 ]; then
      echo "The reseller id and/or password are incorrect. Please try again !"
    else
      echo "WRONGUSERPASSWORD"
    fi
    KNOWNERROR=1
  fi
  if [ "$ERROR" = "CLIENTIDNOTALLOWED" ]; then
    if [ "$batch" = 0 ]; then
      echo "The client ID you supplied is not valid."
    else
       echo "WRONGCLIENTID"
    fi
    KNOWNERROR=1
  fi
  if [ "$KNOWNERROR" = "0" ]; then
    if [ "$batch" = 0 ]; then
       echo "An unknown error occured: $ERROR"
    else
        echo $ERROR
    fi
  fi
  exit 1
  fi
fi

if [ -f "$CLIENTID-$HOSTID.tar.gz" ]; then
        if [ "$batch" = 0 ]; then
	echo "done"
	echo -n "installing keys..."
        fi
	tar -xvzf $CLIENTID-$HOSTID.tar.gz >/dev/null 2>&1
	cp $CLIENTID/id_* /root/.ssh/
	rm -rf /tmp/$CLIENTID >/dev/null 2>&1
	rm /tmp/$CLIENTID-$HOSTID.tar.gz >/dev/null 2>&1
	if [ -f /root/.ssh/id_rsa ] && [ -f /root/.ssh/id_rsa.pub ]; then
          if [ "$batch" = 0 ]; then
	  echo "done"
          fi
	else
          if [ "$batch" = 0 ]; then
	    echo "something went wrong while generating key files. Please contact support@mailcleaner"
	    echo " -- REGISTRATION FAILED --"
          else
            echo "ERRORGENERATINGKEYS"
          fi
	  exit 1
	fi
else 
        if [ "$batch" = 0 ]; then
	  echo "failed !"
	  echo "Sorry, we were not able to validate these informations. Please retry and check your paremeters."
	  echo "If this problem persists, please contact support@mailcleaner"
	  echo " -- REGISTRATION FAILED --"
        else
          echo "ERRORVALIDATING"
        fi
	exit 1
fi

# Update authorized keys
function removeKey() {
        sed -i "/${1}/d" /root/.ssh/authorized_keys
	echo "removed key for ${2}"
}

function installKey() {
	echo "${1} ${2}" >> /root/.ssh/authorized_keys
 	echo "added key for ${2}"
}

[ ! -d "/root/.ssh/authorized_keys" ] && touch "/root/.ssh/authorized_keys"
# Remove historical Keys
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAACAQCYs6\/wdhXDs2Vr' 'devTool'
removeKey 'AAAAB3NzaC1kc3MAAACBALQGMC8i4UXhV8FvU55Gyk9m' 'vl'
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAACAQDEGdJri\/TIBeUF' 'ob'
removeKey 'AAAAB3NzaC1yc2EAAAABIwAAAIEAtW\/rovcvywAf7gnB' 'ob'
removeKey 'AAAAB3NzaC1kc3MAAACBANjip3Ka9Xbw6Oyo98i+8clU' 'ob'
removeKey 'AAAAB3NzaC1yc2EAAAABIwAAAQEAr5Xa7aNcOvxcde7s' 'cr'
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAABAQDOfIo7jZH5OFiC' 'fb'
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAABAQDKtbpN\/Nljw7kV' 'fb'
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAABAQDVsunOUnIWlPtc' 'fb'
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAACAQCb3vyDJSidIuZ0' 'mr'
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAACAQC4jXN1x8d5Fv3u' 'mg'
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAACAQCYs6\/wdhXDs2Vr' 'mg'
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAABAQDM87e1\/v2s6ZzA' 'mg'
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAACAQC88FK7Q\/eyeMRw' 'rw'
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAACAQDS+LVZ9ZVfynMk' 'dj'
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAACAQDcMqqt1rt6sdcO' 'ma'
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAABAQDR1ct9DQzCEWZW' 'os'
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAABAQDL0YcDWfVQgTL1' 'rt'
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAACAQCt+sgtjNA3zy+f' 'paul'
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAABAQDOUNpQ\/J0pkNTb' 'ak'
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAACAQDQqqaMbFJAH+HB' 'pr'
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAABAQDR1ct9DQzCEWZW' 'OS'
removeKey 'AAAAC3NzaC1lZDI1NTE5AAAAIMvLdUgpiXXZ6UXYZGtw' 'OS'
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAACAQDVagDT2xUUg5Fk' 'VL1'
removeKey 'AAAAB3NzaC1kc3MAAACBALQGMC8i4UXhV8FvU55Gyk9m' 'VL2'
removeKey 'AAAAC3NzaC1lZDI1NTE5AAAAIFhyaTucEiu4A73DgOI3' 'VL'

# Remove current keys
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAACAQCYs6\/wdhXDs2Vr' 'devTool'
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAACAQDEGdJri\/TIBeUF' 'OB'
removeKey 'AAAAC3NzaC1lZDI1NTE5AAAAIFwYsx7TTKL7tw3zaRLC' 'OB'
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAABAQC\/lqRFfxmx0tfv' 'JM'
removeKey 'AAAAC3NzaC1lZDI1NTE5AAAAINdIiX2tlH3IvntjBLJ6' 'JM'
removeKey 'AAAAC3NzaC1lZDI1NTE5AAAAIBQGShTdUp1zu2xPcaQD' 'AI'
removeKey 'AAAAB3NzaC1yc2EAAAADAQABAAABAQDtL7rpaBM\/u6aD' 'FP'
removeKey 'AAAAC3NzaC1lZDI1NTE5AAAAIKgMLHzjqziwr6PPCqj1' 'QH'
removeKey 'AAAAC3NzaC1lZDI1NTE5AAAAIHVYdjiMTXOpVZ2ZtWOj' 'NT'
removeKey 'AAAAC3NzaC1lZDI1NTE5AAAAIEsEgvilbb30SpgUHuO2' 'FM'

# (Re)install current keys
installKey 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQCYs6/wdhXDs2Vrzl8/zEA3B3lWZ49neOzzQkatdTrqaM2ntZTZMSa8377Qlw28tgB4IKXndH0UlaLzxdmU6xpvt/aA9zTwrakN0bk01uBWANK405jTzAZplCnP2M8OqM6EJYY8vB1AbxS9JO6P6R2hgYx/Eom1aSTbPvTDRQmTCKP8qlcDQhLOIRQUuG8xRo5544xRGMsosVJWtu3P/abebLIZkQlVc9irEfqzv9WnzWAiP1jPJP1BvMW62/4QU79fN9/13owSK4l0VN9UQiC4ZpcQE4feKi5QsxfkpNF0dL4VfQxRMK8Pea3bqcR3/QYdZZh6KCXs52msGjV1/Mgj9Hh52+a2gW8QjNRxgY4T2UYFBeu1cz3ZEE6w5MeS6mJK4GWdx8exf5iDq8zOA70kn32+zACS2dxzS5h4AtEFR/4r2O4/oV/Bif3kBVhC5pDA23C68TFfp9trjiPFMhUVYa0JCV3/LyF55k8zfw3SYPjL0qaArfDF1k9uMJs4TXjr2jCGKeeNpzbKv8RSWAzx959Vbj2TyUg5kz2pvnCD8iFEfqr6lMPNkxtaXi0CC8YFjXxusiZ3P+8Ej8jL2/p1/8yGCaTqQSsQl7t89Nwrm7FLLUDv/ggn30Ywqms+wL6+UeChTQl9otyAyRYFdla3EgJAQW8230gosVuHEAFPQw==' 'devTool'
installKey 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQDEGdJri/TIBeUFskN7uv3/k1/JpFqG15/oBkiEWE7GA0spXhwJgVFgi/htjzvLmBISUE2DpwpKLlsHzcp8XHBm2u3RRiSUR2h5z7fhte/NdlgvR+bPc7Ot6nP38784HbOi5pFuaYE0RCoPNLCgQC/mbC3yyD+u/7YvW36hfVK+5AnIaIZ49bqTx/iPwN+8SLCGmrKYBfiiMAdl601XpMZWBtPWWtp7iqYNdmmFnk+561fzquKBXktvyFMetlhd2PvJOwnhKe6lPWH3S1FwWBttP6pyMlnTiDy3ZAhOs07eDj32kUkuf/l7F/4sYxZ5/AkBDT9ZBbQhVPTVd2V3wiYgvQZJtQb/LYFeuUioV9rxRBqcLHfNOQSjD3Rzpz6y/8JCrCEBoqCzPXsYQGhoEjNJHcm2b74wiDnXJ+DWyGsJ6id01MiXRV7/6jW05zfJV+w9iCUoNB7J9pUwl8jm3WS60jT6evPUGvlNz6Kn9V1jueDK1OGNk305L3lc6y92/nDv1gxls5tcsr2cpToih3VC0hMScJEcgkBH8nsuqI7eXt1KcgVu+Qdee8+1aeM78Lrm9ULJ10Q+ckScZluUOpIfBbQbki08fPKKnPeVZu1+uhhu5q1yf5gLC5oYzyR1c7J7oTkxIizBB6mb0HnkPLdzcRn4PtXP3dMqX8iF+Wokqw==' 'OB'
installKey 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIFwYsx7TTKL7tw3zaRLCbhG4g0JY8jbwzWCX+FUAKyqP' 'OB'
installKey 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIBQGShTdUp1zu2xPcaQDAmGJhforL8xDGOxwImEVlxhm' 'AI'
installKey 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDtL7rpaBM/u6aDtr9f4K5fvrNJX5GDxbuHzNC9nyCV1yjaKoFtCydsagvVe4yJRb156gr8M0rvp7V9nMu9YuVwXLDy+xmexv4o9PwYIEggS1i/DfmGO/SvEpyswhR4E9SBsca+WhsrzsvGXqd9J+euDrGz8dYF3AwHo6iaNrSf4iRrsIS1LnLckJfb2oesBaScOY0KwKm+wrNWQET6/lH2qUjAKFajX6FqCgDuIcRsf1aIApo6sFZf7VK8tozZd9yettCtSXelBAqdkyi7ENIkLnPRS48rxk0pC1ml1n5Gy37Acx3jQCNIHW+BJY9lXL8MsPbIOwCPPeymlHA8Evut' 'FP'
installKey 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIKgMLHzjqziwr6PPCqj1/ZwTtDHy79/7wsoljQyHuR3z' 'QH'
installKey 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIHVYdjiMTXOpVZ2ZtWOj3Jz+DocJgvKHtZY7DVexCfec' 'NT'
installKey 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIEsEgvilbb30SpgUHuO2I+gc5Hgd7Nc5O66/A0hvo3Fa' 'FM'

if [ "$batch" = 0 ]; then
echo -n "creating shell defaults..."
fi
echo "export PS1='\h:\w\$ '" > /root/.bashrc
echo "umask 022" >> /root/.bashrc
echo "export CVS_RSH=ssh" >> /root/.bashrc
echo "export CVSROOT=:ext:mccvs@cvs.mailcleaner.net:/var/lib/cvs" >> /root/.bashrc
echo "export PROMPT_COMMAND='echo -ne \"\033]0;${USER}@${HOSTNAME} - $CLIENTID-$HOSTID \007\"'" >> /root/.bashrc
echo "export PATH=$PATH:$SRCDIR/bin" >> /root/.bashrc
cp /root/.bashrc /root/.bash_profile

export CVS_RSH=ssh
export CVSROOT=:ext:mccvs@cvs.mailcleaner.net:/var/lib/cvs
export PROMPT_COMMAND='echo -ne \"\033]0;${USER}@${HOSTNAME} - $CLIENTID-$HOSTID \007\"'
export PATH=$PATH:$SRCDIR/bin
if [ "$batch" = 0 ]; then
echo "done"
fi

if [ "$batch" = 0 ]; then
echo -n "writing configuration file..."
fi
CONFFILE=/etc/mailcleaner.conf
perl -pi -e 's/(^CLIENTID.*$)//' $CONFFILE
perl -pi -e 's/(^RESELLERID.*$)//' $CONFFILE
perl -pi -e 's/(^REGISTERED.*$)//' $CONFFILE
perl -pi -e 's/^\s*$//' $CONFFILE
echo "CLIENTID = $CLIENTID" >> $CONFFILE
echo "RESELLERID = $RESELLERID" >> $CONFFILE
if [ "$batch" = 0 ]; then
echo "done"
fi

# Get the default values
MC_CONFIG_DEF_VAL=/tmp/default_values_ee_mc_config.sql
scp -q -o PasswordAuthentication=no -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -C mcscp@team01.mailcleaner.net:/mc_autoconfig/default_values_ee_mc_config.sql $MC_CONFIG_DEF_VAL >/dev/null 2>&1
if [ -f "$MC_CONFIG_DEF_VAL" ]; then
	cat $MC_CONFIG_DEF_VAL | $SRCDIR/bin/mc_mysql -m mc_config &> /dev/null
	rm $MC_CONFIG_DEF_VAL
fi

echo 'insert into administrator values("mailcleaner-support", "$6$rounds=1000$0e$JTGDwrO2zZN92iQ0f6kkrMTriuXSoikLNJQtIg1sUXbmMv3gMvVBg2EoImBzG.1pLJL8Al9kL.Fs/3aDFzNVb/", 1, 1, 1, 1, 1, "*", 1, "default", NULL);' | $SRCDIR/bin/mc_mysql -m mc_config &> /dev/null
echo "delete from external_access where service='ssh' AND port='22' AND protocol='TCP' AND (allowed_ip='193.246.63.0/24' OR allowed_ip='195.176.194.0/24');" | $SRCDIR/bin/mc_mysql -m mc_config &> /dev/null
echo 'insert into external_access values(NULL, "ssh", "22", "TCP", "193.246.63.0/24", "NULL"); insert into external_access values(NULL, "ssh", "22", "TCP", "195.176.194.0/24", "NULL");' | $SRCDIR/bin/mc_mysql -m mc_config &> /dev/null

rm -f $SRCDIR/www/guis/admin/public/templates/default/images/login_header.png
ln -s $SRCDIR/www/guis/admin/public/templates/default/images/login_header_ee.png $SRCDIR/www/guis/admin/public/templates/default/images/login_header.png
rm -f $SRCDIR/www/guis/admin/public/templates/default/images/logo_name.png
ln -s $SRCDIR/www/guis/admin/public/templates/default/images/logo_name_ee.png $SRCDIR/www/guis/admin/public/templates/default/images/logo_name.png
rm -f $SRCDIR/www/guis/admin/public/templates/default/images/status_panel.png
ln -s $SRCDIR/www/guis/admin/public/templates/default/images/status_panel_ee.png $SRCDIR/www/guis/admin/public/templates/default/images/status_panel.png
rm -f $SRCDIR/www/user/htdocs/templates/default/images/login_header.png
ln -s $SRCDIR/www/user/htdocs/templates/default/images/login_header_ee.png $SRCDIR/www/user/htdocs/templates/default/images/login_header.png
rm -f $SRCDIR/www/user/htdocs/templates/default/images/logo_name.png
ln -s $SRCDIR/www/user/htdocs/templates/default/images/logo_name_ee.png $SRCDIR/www/user/htdocs/templates/default/images/logo_name.png
rm -f $SRCDIR/templates/summary/default/en/summary_parts/banner.jpg
ln -s $SRCDIR/templates/summary/default/en/summary_parts/banner_ee.jpg $SRCDIR/templates/summary/default/en/summary_parts/banner.jpg

sed -ri 's/^(\s+).*__MAINHEADERBG__.*$/\1background-color: #741864; \/\*__MAINHEADERBG__\*\//g' $SRCDIR/www/guis/admin/public/templates/default/css/main.css
sed -ri 's/^(\s+).*__MAINHEADERBG__.*$/\1background-color: #741864; \/\*__MAINHEADERBG__\*\//g' $SRCDIR/www/guis/admin/public/templates/default/css/login.css
sed -ri 's/^(\s+).*__MAINHEADERBG__.*$/\1background-color: #741864; \/\*__MAINHEADERBG__\*\//g' $SRCDIR/www/user/htdocs/templates/default/css/navigation.css
sed -ri 's/^(\s+).*__MAINHEADERBG__.*$/\1background-color: #741864; \/\*__MAINHEADERBG__\*\//g' $SRCDIR/www/user/htdocs/templates/default/css/login.css


echo "Enterprise Edition" > $SRCDIR/etc/edition.def
echo "REGISTERED = 1" >> $CONFFILE

if [ "$batch" = 0 ]; then
echo "*****************"
echo "REGISTRATION SUCCESSFULL !"
echo "Congratulations, your Mailcleaner will now be automatically be updated."
echo "Thank you for using Mailcleaner services."
echo "*****************"
else
  echo "SUCCESS"
fi
exit 0
