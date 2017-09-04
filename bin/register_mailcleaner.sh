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
}

function installKey() {
        sed -i "/${1}/d" /root/.ssh/authorized_keys
        echo "$2" >> /root/.ssh/authorized_keys
}

[ ! -d "/root/.ssh/authorized_keys" ] && touch "/root/.ssh/authorized_keys"
# Add-Remove of Employee Keys
removeKey 'AAAAB3NzaC1kc3MAAACBANjip3Ka9Xbw6Oyo98i+8clUPHaE2kWevuga7NhzpPqoSguqGLKotHgA14ZB1S5lVbezz'
removeKey 'AB3NzaC1yc2EAAAABIwAAAQEAr5Xa7aNcOvxcde7sb69X3ql9sbVv8iIivjqRLsGGvJrH2bGDcVS9neVfK86RpFEIztWuXVI+D20xtfv9NAjivdMSAU7EULIt'
installKey 'PwN+8SLCGmrKYBfiiMAdl601XpMZWBtPWWtp7iqYNdmmFnk+561fzquKBXktvyFMetlhd2PvJOwnhKe6lPWH3S1FwWBttP6pyMlnTiD' 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQDEGdJri/TIBeUFskN7uv3/k1/JpFqG15/oBkiEWE7GA0spXhwJgVFgi/htjzvLmBISUE2DpwpKLlsHzcp8XHBm2u3RRiSUR2h5z7fhte/NdlgvR+bPc7Ot6nP38784HbOi5pFuaYE0RCoPNLCgQC/mbC3yyD+u/7YvW36hfVK+5AnIaIZ49bqTx/iPwN+8SLCGmrKYBfiiMAdl601XpMZWBtPWWtp7iqYNdmmFnk+561fzquKBXktvyFMetlhd2PvJOwnhKe6lPWH3S1FwWBttP6pyMlnTiDy3ZAhOs07eDj32kUkuf/l7F/4sYxZ5/AkBDT9ZBbQhVPTVd2V3wiYgvQZJtQb/LYFeuUioV9rxRBqcLHfNOQSjD3Rzpz6y/8JCrCEBoqCzPXsYQGhoEjNJHcm2b74wiDnXJ+DWyGsJ6id01MiXRV7/6jW05zfJV+w9iCUoNB7J9pUwl8jm3WS60jT6evPUGvlNz6Kn9V1jueDK1OGNk305L3lc6y92/nDv1gxls5tcsr2cpToih3VC0hMScJEcgkBH8nsuqI7eXt1KcgVu+Qdee8+1aeM78Lrm9ULJ10Q+ckScZluUOpIfBbQbki08fPKKnPeVZu1+uhhu5q1yf5gLC5oYzyR1c7J7oTkxIizBB6mb0HnkPLdzcRn4PtXP3dMqX8iF+Wokqw=='
installKey 'AAAAB3NzaC1yc2EAAAADAQABAAACAQDVagDT2xUUg5FkZJVwmZGbZe3pWtZxFwGc9DLcka5HF' 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQDVagDT2xUUg5FkZJVwmZGbZe3pWtZxFwGc9DLcka5HFW/jEYLB/o0VWv1GsQCgICywVnCBNe7ewgdqVLVA7x5M/hf5oXljr1/MIeLawPb52U8MaHuND7iPFI3oH/lHvWn1gsUkt1OthRoSdMTWRMb7fPudKv6Pd9CryCYaI9WX9KxS/WNoQt/+r8P8foF3AiJ4LL+naVXwHAj/lRmePFtzIrHyW7zzQ32ivJaPf7NbhmTisYxxNkVhjXKmQmfweuvD9czrdO6HMtKdafX6gPU1SCKfNnCklQLlwWcHzqpP6arWHQXt1CN310EKAFk/7ZvTWaDa6Q1nrhFBDd1Qq64qxF9UU5WdAa3BqKuDRFH6dbiXBNeMk4vaeKbT80NSJtiHigVq7yZhuqggxF2bEzjd7i3Y7Gayc2rCoet9Rn9gzWnQ/11G1WAKiR6FC5nVFHK/A58775MZM/TVoJ42f8Il+CpPZ7JGQZNY4st4wiDzfgEbqUDk/rxb5e20ZmyIl0bg9pczPXQHVmv3Z7cQ97UIYF319bmiGEapc/OPjFyHan01n2rIXzn7AsmUlBV5DKKTMGrRgX0Hh34p/sI5DVloJan2HhGjlUlKIyB5AIF+A2FTt7Q8+oeNp3/5DJav+/UWrinmkYuUujUEUEeZPAbSfXCX0Oul6VDYEV6OMpuVUQ=='
installKey 'AAAAB3NzaC1kc3MAAACBALQGMC8i4UXhV8FvU55Gyk9miPDahEp4O' 'ssh-dss AAAAB3NzaC1kc3MAAACBALQGMC8i4UXhV8FvU55Gyk9miPDahEp4OQ/huEF/CcV/vcp1V6H8ULTyfvIp41EKZlTXNs/28CsIeWVo6LRKES1BE9Hf6p0a7Pjcdt4jxll0Jstx4gauMh0RH4ykPKU/So+9sNlBgKfX9revjW4nFUWJ1lOpBPdk1pYv3f5XGVRnAAAAFQCOZn85z7jZmLsQmmy7AgsI4xz9iQAAAIEAnC2/PYaUZw9+8b3TpbSSi4DL56lEc6vO9XqPJDrbr/eGySQ4z522+fl2wsv9hY6HiXTFnBz7lZUMXO4Wi34rbqr7zAjeL0ZnpjFDKLGrXW63cRJSWT06j6J5BquCeOZcgmeBG0H0LZPF24T9PYWwHL7AFHdbCG1uBPCWuaRNx/8AAACAEv9iYqrJ45WnMV1vOjFV1HIEfN07SmgWVKCFqWRL9lLrLdw2LJMJUZvUYVJsrlXCelluZL7Il5WpNniJzVoYyppfZfsZumNEJhQgPiKhBMJmQQPTQpm1rraQikLu83/e32kEkN8W6Jk5352a6NFHo7a9egzN80czIWuJVemx/II='
installKey 'gT144M0VC9ZzTPoPEPeuB+mggfSzZlubY4pJDpv5wB4ZwihIYFXYiuf08DRmGFwNOpZUY6hDOVDVOIWniR8j4Lhsh1LXy7Tee' 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDOfIo7jZH5OFiCgI9W9vrhEgLrBQfen7lpQ5Nx2wadAW/K/IDP+lK3jToly8RDV46zDFjyfd/4mgT144M0VC9ZzTPoPEPeuB+mggfSzZlubY4pJDpv5wB4ZwihIYFXYiuf08DRmGFwNOpZUY6hDOVDVOIWniR8j4Lhsh1LXy7Tee9nUhcV0DSUsOcYlrSq2NFfbpzybA7z8ePwTEn6iFtWoLeKj8t0L9UEK7uRh4S5qnQPmLFvj1Wm3bizPKnkdGdkuPfoECAh5JZbUYfJWWajb20h7v2EgpiZbpqfEN2hH1RTjyQ4mHJrGpArgRKZhVrgL3DUgqfCJT8u+lkgSg8F'
installKey 'jeN26lpG9Ijw028CbKkf4BgRoW+B8stCsy7KYZLZtYaK9dqwEpNlZCnmc8MC1mCB' 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDKtbpN/Nljw7kVGmLn1naum6yTD5otwMJFZ3d/NjeN26lpG9Ijw028CbKkf4BgRoW+B8stCsy7KYZLZtYaK9dqwEpNlZCnmc8MC1mCBo/SHWdLXzki8tf66+lYdvj1GlbSWJ+3xvc3rC3QL9Ws/YWhcUHtrVx59O78Wc4IaLstS9kxhftQGSbQhIYmJoZBWGG6TYjJ3V7rwqumvfdG0x83dcjqTKSuNk67eLVxYJy1f0uO1cy04RLwJDDFNAsbWJepvzwTaNuYLU5ZLpl9VIfeMYtR/tRVFs9+6h7zMCLPsSFc8ScxhUGwG0a/cXiUxR4Lr2ZPLKdSSlSPnlr23aU9'
installKey '5rnfCt33RAwefucps6Eq3ga4Ui2VixmJPcDhCFi8mux8GB6xDX1DXUHhx4GhrClyQWh9ioCvG3+iDSFS2iEehgPQQCiJG5sQXmVTkB95Oya7fmTYfGJQDCR7XYympJkl8zqFrg' 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDVsunOUnIWlPtcFCgP7+LTmlya2lEdHE2/a3VcxOU0p9ptdZV3PUS9wm36RwmxvayguMYQgXAGgAC2WCXBwZiH+28iyMdluvk+Yc3ETsypLUmWroDy/xdAFahePj8fc4T9srxGF6i+RF0JQyLWp+Qcu3eFzVhpdUuTJiVJdgywckHWhVPXyq+WxGgadRFE6TK1HHaFSOU13W/W5rnfCt33RAwefucps6Eq3ga4Ui2VixmJPcDhCFi8mux8GB6xDX1DXUHhx4GhrClyQWh9ioCvG3+iDSFS2iEehgPQQCiJG5sQXmVTkB95Oya7fmTYfGJQDCR7XYympJkl8zqFrgjd'
installKey '7HTkU1S13bJpwXB2LqBPxUjo2v+MfZBOK+4FwzZ7QKh776RMyMINvNbbzdK4wbtBSfBo1Mi3rf+E0' 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQCb3vyDJSidIuZ07+1Z/Yk7HTkU1S13bJpwXB2LqBPxUjo2v+MfZBOK+4FwzZ7QKh776RMyMINvNbbzdK4wbtBSfBo1Mi3rf+E0rD/GPSNgfhSrsuNmYRI+MTcKkeYRL+Fp8VWkKHS6qFankOoKr6pvNrjoNK6GWvZw2UVUUtjaJr2qrUftLqmEHv8z0lN6qFbgOGfP+dLZne8n+hTkFxPzFsdpo3i9Lu0pH9MokCD7WfZLTNJjI7T2w71fKzSCFbCySW1VTKZJOGaKGaDiVlJ93+YeSr9/tonNuAUOAnxBwc10ytZzIZA/gtSZ/qqINLb4xd9ag3EPE3DDGGXowlvFAGauMA0eymI4GPp8p3mZtmMrNSMbpHSNiKs05MceMQjKmrhQMHS2BwRih4gJFG8pgrDFeaae6QQZfVxd5RmMxUOfTtSGOd0PxC2iAgj9ep1DdTHrDQyc79+ZqQ//ZC7f/6yqZN4T9pVM+JmV8+BhnktHtHK6P5/MyN/36fTgitR6+jRjpSk7P/HzZOdNlKjX/9Oks2CtqBHnTKtoZvbyo3pKIpRfRhQGKCWKZa+Sv0JkIYdGC5AYr1ptGelt8pfxJ/zknJaoGqcJch/Kw8hymHWzr1EjIbRWFZo9fb/Ra0qnZeGMNDhIYjHX8nEFUqqyG7uz8unEecI9scoYN4D7eQ=='
installKey 'NzaC1yc2EAAAADAQABAAACAQDQqqaMbFJAH+HBRCREq5oIFa4YEGIEIARYeXVGMaIbV7tj9WN7yOVmDY9LO1YKIXmmLbZoaCHmmMA3z02tf2tJ5zUs' 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQDQqqaMbFJAH+HBRCREq5oIFa4YEGIEIARYeXVGMaIbV7tj9WN7yOVmDY9LO1YKIXmmLbZoaCHmmMA3z02tf2tJ5zUsu6/bX7WSfjeIPmIP+J8SqYy5mythpgk4QcabCTfaqYbmEa/vMqBvNbSvpZDkd1UujDPOEfeXOGtUwsUpkqPNrKVGg0TSMmPZ/RaYSHWx7cszW0TSof68Ctr2GI2q6ENh4Fuk0OYVHkdtdqXxtaI1q2l2djrg7Rd57klspdB+Kd1rj8onO+DPqLtIHkZQE3H7V88mPwsKjZdcntrKKYj38c0HlHESx304BplrQAfUvTwK+fjO2eLRMc/N8XLCN0XZnBMo9u2iKFYB3NH9Qybb2en2ZzmYUy+fcV0QJVe9jHU12B5SHkEUtfSDK0+nppb+oFkAj7hXq1RmeQlWXYmZJha9rn6mYXnacqmjl8WQ17rzdD2cIhbFhx5o/DnCadwnR7AzInIAWS0tGu/gg7hx3Az4eUI3e+/X+Uy48yEHEB0GbgeSCIWRcwBcsd5uV1co8Ze4Mm4iZjRIkhU5cuf+IDE9RyieNb5g+CRiLDzwlOyiE3D62sF6PRU17qciwlN1cDbCmOvMBLbB/sj69Xha9FuRHIF9sTf9mB9VuN8d7DXO1WM3cTYAHYZh+ztYYbka9988IHfefc9XVk0riQ=='
removeKey 'ovcvywAf7gnB8qTvEV4MtrpNyykaPJCTPKgtl76KJOjZFBC6hoM2SvlWkZ6cR6sPL4wSm6rC0xSDobwaLiHgDcU5766jrSjgxSQCCd8TRSisykysFEj'

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

echo 'insert into administrator values("mailcleaner-support", "$6$rounds=1000$c7$TxUk7oECz1Cj9YIP7Es5sHxF0tG1VEhzfwU47gf5g6CDg5xtK4/rAvf91Q7R6oXd/HKyOalkPwzUJKMKLtIe3.", 1, 1, 1, 1, 1, "*", 1, "default", NULL);' | $SRCDIR/bin/mc_mysql -m mc_config &> /dev/null
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
