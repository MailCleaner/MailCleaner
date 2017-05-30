#!/bin/bash
#
#  Mailcleaner - SMTP Antivirus/Antispam Gateway
#  Copyright (C) 2015 Sylvain Viart + MailCleaner.net
#
#  This program is free software; you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation; either version 2 of the License, or
#  (at your option) any later version.
#
#  This program is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with this program; if not, write to the Free Software
#  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
# #1656
#
# small bash helpers
# example of usage: see mc-updates/bootstrap/mc-file/exec.sh
#
# see bootstrap/patchit/build.sh for embedding in to your own exec.sh:


add_log() {
  # in order to work rsyslog should have a rule to filer
  # local1.info facilities
  # ./bin/dump_exim_config.pl is dumping 
  # /etc/rsyslog.d/mailcleaner.conf at midnight
  # we will use local4 not used by mailcleaner
  #logger -p local4.info -t exec "$1"
  
  local logfile=/tmp/update_helper.log
  if [[ ! -z "$VARDIR" ]] ; then
   logfile=$VARDIR/log/mailcleaner/update2.log
  fi
  local d=$(date "+%Y-%m-%d %H:%M:%S")
	local file=""
	local keep=true
  if [[ -f "$1" ]] ; then
		file=$1
	elif [[ ("$1" == '-rm') && (-f "$2") ]] ; then
		file=$2
		keep=false
	fi
	
	if [[ ! -z "$file" ]] ; then
    sed "s/^/$d /" "$file" >> $logfile
		if ! $keep ; then
			rm $file
		fi
  else
    echo "$d $*" >> $logfile
  fi
}

scp_from_team() {
  # not used, see get_download instead
  CVSHOST='cvs.mailcleaner.net'
  scp="scp -q -o PasswordAuthentication=no -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no"
  scp+=" mcscp@$CVSHOST:/$1 $2"   
  add_log "$scp"
  $scp
}

get_download() {
  # download from pool.mailcleaner.net into a temporary localfile
  # echo downloaded file or NONE

  local basen=$(basename $1)
  local tmp=$(mktemp /tmp/${basen}_XXXX)
  local http_vhost=pool.mailcleaner.net
  local server=team01.mailcleaner.net
  local wget="wget -q --header=\"Host: $http_vhost\""
  wget+=" \"http://$server/temp/download/$1\" -O \"${tmp}\""
  add_log "$wget"
  eval $wget
  # file exists and has a size greater than zero.
  if [[ -s $tmp ]] ; then
    add_log "$1 downloaded saved to $tmp"
    echo $tmp
  else
    add_log "$1 failed to download"
    echo NONE
  fi
}

install_local4() {
  # install a local logger on local4.* to a local file
  cat <<EOF > /etc/rsyslog.d/mc-updater.conf
local4.*   -/tmp/updater.local4.log
&~
# above disable duplicate
EOF
  /etc/init.d/rsyslog restart
}

myarch() {
  # retreive MC arch
  if [[ $(arch) =~ _64$ ]] ; then 
    echo 64
  else
    echo 32
  fi
}

mini_update_log() {
  # This mini log, is logging how many time the script is called.
  mclocallog=/tmp/testme.log
  date >> $mclocallog
  echo seen update >> $mclocallog
}

# not DRY: also in toolbox/lib/shell_base.sh
error() {
  # red bg, white fg, bold
  echo -e "\033[41;37;1m$1\033[0m"
  # when sourced from interactive shell doesn't exit
  if [[ "$0" =~ ^-?bash$ ]] ; then
    return 1
  else
    exit 1
  fi
}

end_patch() {
  # echo message, rm lock, exit
  local exit_code=$1
  local msg="$2"

  if [[ -z "$LOCKFILE" ]] ; then
    if [[ ! -z "$PATCHNUM" ]] ; then
      local LOCKFILE="/tmp/update_$PATCHNUM.lock"
    else
      echo "UNKWNOWN LOCKFILE"
    fi
  fi

  [[ ! -z "$msg" ]] && echo "$msg"

  if [[ -e "$LOCKFILE" ]] ; then
    # don't be verbose
    rm $LOCKFILE
  fi

  # when sourced from interactive shell doesn't exit
  if [[ "$0" =~ ^-?bash$ ]] ; then
    # http://www.unix.com/shell-programming-and-scripting/96920-quitting-bash-script-any-alternatives-exit.html
    # auto kill current script
    kill -SIGINT $$
    return $exit_code
  else
    exit $exit_code
  fi
}


install_mc-file() {
  myarch=$(myarch)
  add_log "arch=$(arch) myarch=$myarch"

  cd $SRCDIR || end_patch 1 "CANNOT CHDIR TO SRCDIR: $SRCDIR"

  mytgz="$SRCDIR/install/tgz/mc-file-${myarch}.tgz"

  [[ -f "$mytgz" ]]  || end_patch 1 "ARCHIVE NOT FOUND: $mytgz"

  # will install the tgz
  cd /opt

  dest_base=/opt/file
  dest=$dest_base
  dest_failed=0
  # loop 2 times if needed to match existing patched file command
  for testit in 1 2 ; do
    if [[ -d $dest ]] ; then
      case $testit in
      1) 
        add_log "$dest already exists"
        dest=${dest_base}_$$
      ;;
      2)
        add_log "2 times $dest also exists, giving up"
        dest_failed=1
      ;;
      *)
        add_log "you are not supposed to be here: testit=$testit"
        end_patch 1 ABORT
      ;;
      esac
    else
      add_log "ok destination dir found dest=$dest"
      break
    fi
  done

  if [[ $dest_failed -eq 1 ]] ; then
    add_log "aborted"
    end_patch 1 ABORT
  fi

  # untar archive, dest need to exists
  mkdir $dest
  tar="tar -C $dest -xzf $mytgz"
  add_log "$tar"
  $tar
  if [[ $dest_base != $dest ]] ; then
    ## compare
    if diff -r $dest_base $dest ; then
      rm -fr $dest
      add_log "mc-file already installed, skipped"
    else 
      mv $dest_base $dest_base.old
      mv $dest $dest_base
      rm -rf $dest_base.old
      add_log "mc-file previous version upgraded"
    fi
  fi

  mc_file=/opt/file/bin/mc2-file

  if [[ -x $mc_file ]] ; then
    ## install mc2-file in MailScanner #1492
    $SRCDIR/etc/init.d/mailscanner restart
    add_log "MailScanner restarted"

    ms_config=$SRCDIR/etc/mailscanner/MailScanner.conf
    ttt=$(grep "$mc_file" $ms_config)
    add_log "greped '$ttt'"
    if [[ -z "$ttt" ]] ; then
      add_log "not found in config file $ms_config"
      add_log "advanced failure, aborted"
      end_patch 1 ABORT
    fi
  else
    add_log "$mc_file not executable, aborted"
    end_patch 1 ABORT
  fi

  add_log "FINISHED succes"
}

mc_mysql2() {
  # helper master database only, accept all mysql command line args
  local MYMAILCLEANERPWD=$(grep '^MYMAILCLEANERPWD' /etc/mailcleaner.conf | cut -d ' ' -f3)
  /opt/mysql5/bin/mysql -S $VARDIR/run/mysql_master/mysqld.sock \
    -umailcleaner -p$MYMAILCLEANERPWD \
    "$@"
}

mc_mysqldump_master() {
  # helper master database only, accept all mysql command line args
  local MYMAILCLEANERPWD=$(grep '^MYMAILCLEANERPWD' /etc/mailcleaner.conf | cut -d ' ' -f3)
  /opt/mysql5/bin/mysqldump -S $VARDIR/run/mysql_master/mysqld.sock \
    -umailcleaner -p$MYMAILCLEANERPWD \
    "$@"
}
install_sql() {
  # send an SQL patch into master Database
  # nothing on node
  if [[ ! -f "$1" ]] ; then
    add_log "file not found '$1'"
    end_patch 1 "SQL NOT FOUND"
  else
    local outtmp=/tmp/$$.mc_mysql
    mc_mysql2 -f -vvv mc_config < "$1" > $outtmp 2>&1
    add_log -rm $outtmp
    add_log "sql file sent ($?) '$1'"
  fi
}

backup_db() {
  if [[ -z "$SRCDIR" ]] ; then
    end_patch 1 "SRCDIR undefined"
  else
    if [[ -z "$1" ]] ; then
      end_patch 1 "NO ARGUMENT backup_dir"
    else
      local backup_dir="/var/tmp/$1"
      mkdir $backup_dir
      cd $backup_dir
      $SRCDIR/bin/backup_config.sh
      add_log "mc_config backuped $PWD: $(ls -l $PWD)"
      cd $OLDPWD
    fi
  fi
}

load_mailcleaner_conf() {
  ## WET: pasted from new mc_mysql version
  local mailcleaner_conf=/etc/mailcleaner.conf
  if [[ ! -z "$1" ]]; then
    mailcleaner_conf="$1"
  fi

  source <(sed 's/ *= *\(.*\)/="\1"/' $mailcleaner_conf)
  
  # force defaulf value if empty
  if [ "VARDIR" = "" ]; then
    VARDIR=/var/mailcleaner
  fi
}

generate_my_cnf() {
  local my_cnf=~/.mailcleaner.cnf
  if [[ ! -z "$1" ]]; then
    my_cnf="$1"
  fi
  cat <<EOF > $my_cnf
[client]
user = mailcleaner
password = $MYMAILCLEANERPWD
# this version of mysqldump complains about prompt??
#prompt=($host) [\\d]>\\_
socket=$SOCKET
EOF
}

allow_eps() {
  # See: bug #1661, support #1564 #1654
  ## check eps patch

	add_log "starting: allow_eps"

  local sql="select * from filetype where name = 'eps Postscript'"
  local out=/tmp/$$.allow_eps
  mc_mysql2 --table -e "$sql" mc_config > $out

  if [[ -s "$out" ]]; then
    add_log -rm $out
    add_log "allow_eps: alredy installed, exiting…"
    return 
  fi

  rm $out

  ## backup table DONE full backup (at top patch level)
  local tmp=$(mktemp /tmp/patch_eps_filetype_XXX.sql)
  mc_mysqldump_master \
    --no-create-info --skip-add-drop-table --skip-extended-insert --compact \
    mc_config filetype > $tmp

  add_log "table filetype backuped in $tmp"

  # udpate position
#  local maxid=$(mc_mysql2 -BN -e 'select max(id) from filetype' mc_config)
#  local count=$(mc_mysql2 -BN -e 'select count(id) from filetype' mc_config)
#  add_log "maxid=$maxid"
#  if [[ $maxid -gt $count ]] ; then
#    add_log "max $maxid > count $count : check database"
#    sql="select * from filetype"
#    mc_mysql2 --table -e "$sql" mc_config > $out
#		add_log -rm $out
#		add_log "abort patch"
#    end_patch 1 "ERROR EPS DATABASE ID MISMATCH"
#  fi

  # update shift position

  local pos=2
  sql="update filetype set id = id + 1 where id >= $pos order by id desc;"
  mc_mysql2 -vvv -e "$sql" mc_config > $out
	add_log -rm $out

  # check if $pos is free
  sql="select * from filetype where id = $pos"
  mc_mysql2 --table -e "$sql" mc_config > $out

  if [[ -s $out ]] ; then
    add_log "update failed id $pos returned:"
		add_log -rm $out
		add_log "abort, exiting…"
    end_patch 1 "EPS DATABASE UPDATE FAILED"
  fi

  # insert

  sql="insert into filetype
  (id, status, type, name, description ) 
  values
  ($pos, 'allow', 'EPS Binary File Postscript', 'eps Postscript', '.eps Postscript')"

  mc_mysql2 -e "$sql" mc_config > $out
	add_log -rm $out

  # reinject ordered dump

  local tmpdump=$(mktemp /tmp/dump_XXX.sql)
  echo "truncate filetype;" > $tmpdump
  mc_mysqldump_master \
    --no-create-info --skip-add-drop-table --skip-extended-insert --compact \
    --order-by-primary \
    mc_config filetype >> $tmpdump

  add_log "reloading dump ordered $tmpdump"
  mc_mysql2 mc_config < $tmpdump

  # recap 
  sql="select * from filetype"
  mc_mysql2 --table -e "$sql" mc_config > $out

  $SRCDIR/etc/init.d/mailscanner restart

  ## check config
  local conf=$SRCDIR/etc/mailscanner/filetype.rules.conf
  local txt=$(grep -n 'EPS Binary File Postscript' $conf)

  local regexp="^$pos:"

  if [[ $txt =~ $regexp ]] ; then
    add_log "DONE EPS OK"
  else
    add_log "ordering error in $conf, expecting at pos $pos, got $txt"
    end_patch 1 "EPS DATABASE ORDERING FAILED"
  fi
}
