#!/bin/bash
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
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


### first get the http proxy if exists
SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "$SRCDIR" = "" ]; then
  SRCDIR=/opt/mailcleaner
fi
VARDIR=`grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "$VARDIR" = "" ]; then
  VARDIR=/var/mailcleaner
fi
MYMAILCLEANERPWD=`grep 'MYMAILCLEANERPWD' /etc/mailcleaner.conf | cut -d ' ' -f3`
 	
HTTPPROXY=`grep -e '^HTTPPROXY' /etc/mailcleaner.conf | cut -d ' ' -f3`
export http_proxy=$HTTPPROXY

export PATH=$PATH:/usr/local/sbin:/usr/sbin:/sbin
export DEBIAN_FRONTEND=noninteractive
export DEBIAN_PRIORITY=critical

if [ "$LOGFILE" = "" ]; then
  export LOGFILE=/tmp/update.log
fi

################################################
# getUpdateList
#
#  param  integer  is critical or not
#         if yes and function failed, then will exit script
#  return 1 on success, 0 on failure
################################################
function getUpdateList {
 CRITICAL=$1
 export RETURN_VALUE=0
 if [ -f /tmp/aptitude.log ]; then
   rm /tmp/aptitude.log
 fi
 
 RES=`apt-get -y -o APT::Get::AllowUnauthenticated=true -o 'DPkg::Options::=--force-confold' update 2>/dev/null | tee /tmp/aptitude.log >> $LOGFILE`
 OUTRES=$?
 OUT=`grep -E 'Err ' /tmp/aptitude.log`
 if [ "$OUTRES" = "0" -a "$RES" = "" -a "$OUT" = "" ]; then
   export RETURN_VALUE=1
   return 0
 fi

 export ERRSTR="could not fetch packages list"
 if [ "$CRITICAL" = "1" ]; then
   echo $ERRSTR
   echo ABORTED
   rm /tmp/update_$PATCHNUM.lock 2>&1 >> $LOGFILE
   exit 1
 fi
 return 0
}

################################################
# downloadUpdates
#  
#  param  integer  is critical or not
#         if yes and function failed, then will exit script
#  return 1 on success, 0 on failure
################################################
function downloadUpdates {
 CRITICAL=$1
 export RETURN_VALUE=0
 if [ -f /tmp/aptitude.log ]; then
   rm /tmp/aptitude.log
 fi
 
 RES=`apt-get -dy -o APT::Get::AllowUnauthenticated=true safe-upgrade 2>/dev/null | tee /tmp/aptitude.log >> $LOGFILE`
 OUTRES=$? 
 OUT=`grep -E 'Err ' /tmp/aptitude.log`
 if [ "$OUTRES" = "0" -a "$RES" = "" -a "$OUT" = "" ]; then
  export RETURN_VALUE=1
  return 0
 fi
 
 export ERRSTR="could not download upgraded packages"
 if [ "$CRITICAL" = "1" ]; then
   echo $ERRSTR
   echo ABORTED
   rm /tmp/update_$PATCHNUM.lock 2>&1 >> $LOGFILE
   exit 1
 fi
 return 0
}


################################################
# downloadInstalls
#
# params  string  package list (many packages should be in a single quoted string)
# param   integer  is critical or not
#         if yes and function failed, then will exit script
# return 1 on success, 0 on failure
################################################
function downloadInstalls {
 export RETURN_VALUE=0
 PACKAGES=$1
 CRITICAL=$2
 if [ -f /tmp/aptitude.log ]; then
   rm /tmp/aptitude.log
 fi

 RES=`apt-get -dy -o APT::Get::AllowUnauthenticated=true install $PACKAGES 2>/dev/null | tee /tmp/aptitude.log >> $LOGFILE`
 OUTRES=$?
 OUT=`grep -E 'Err |Couldn' /tmp/aptitude.log`
 if [ "$OUTRES" = "0" -a "$RES" = "" -a "$OUT" = "" ]; then
  export RETURN_VALUE=1
  return 0
 fi
 
 export ERRSTR="could not download packages for installation"
 if [ "$CRITICAL" = "1" ]; then
   echo $ERRSTR
   echo ABORTED
   rm /tmp/update_$PATCHNUM.lock 2>&1 >> $LOGFILE
   exit 1
 fi
 return 0
}


################################################
# upgradeBinaries
#
# param  integer  is critical or not
#        if yes and function failed, then will exit script
# return 1 on success, 0 on failure
################################################
function upgradeBinaries {
 export RETURN_VALUE=0
 CRITICAL=$1
 if [ -f /tmp/aptitude.log ]; then
   rm /tmp/aptitude.log
 fi
 
 RES=`apt-get -y -o APT::Get::AllowUnauthenticated=true -o 'DPkg::Options::=--force-confold' safe-upgrade 2>/dev/null | tee /tmp/aptitude.log >> $LOGFILE`
 OUTRES=$?
 
 OUT=`grep -E 'Err |Couldn' /tmp/aptitude.log`
 if [ "$OUTRES" = "0" -a "$RES" = "" -a "$OUT" = "" ]; then
  export RETURN_VALUE=1
  return 0
 fi
 
 export ERRSTR=echo "could not upgraded packages"
 if [ "$CRITICAL" = "1" ]; then
   echo $ERRSTR
   echo ABORTED
   rm /tmp/update_$PATCHNUM.lock 2>&1 >> $LOGFILE
   exit 1
 fi
 return 0 
}

################################################
# distupgradeBinaries
#
# param  integer  is critical or not
#        if yes and function failed, then will exit script
# return 1 on success, 0 on failure
################################################
function distupgradeBinaries {
 export RETURN_VALUE=0
 CRITICAL=$1
 if [ -f /tmp/aptitude.log ]; then
   rm /tmp/aptitude.log
 fi
 RES=`apt-get -y -o APT::Get::AllowUnauthenticated=true -o 'DPkg::Options::=--force-confold' dist-upgrade 2>/dev/null | tee /tmp/aptitude.log >> $LOGFILE`
 OUTRES=$?
 
 OUT=`grep -E 'Err |Couldn' /tmp/aptitude.log`
 if [ "$OUTRES" = "0" -a "$RES" = "" -a "$OUT" = "" ]; then
  export RETURN_VALUE=1
  return 0
 fi
 
 export ERRSTR=echo "could not upgraded packages"
 if [ "$CRITICAL" = "1" ]; then
   echo $ERRSTR
   echo ABORTED
   rm /tmp/update_$PATCHNUM.lock 2>&1 >> $LOGFILE
   exit 1
 fi
 return 0 
}

################################################
# downloadDistupgradeBinaries
#
# param  integer  is critical or not
#        if yes and function failed, then will exit script
# return 1 on success, 0 on failure
################################################
function downloadDistupgradeBinaries {
 export RETURN_VALUE=0
 CRITICAL=$1
 if [ -f /tmp/aptitude.log ]; then
   rm /tmp/aptitude.log
 fi
 
 RES=`apt-get -dy -o APT::Get::AllowUnauthenticated=true dist-upgrade 2>/dev/null | tee /tmp/aptitude.log >> $LOGFILE`
 OUTRES=$?
 
 OUT=`grep -E 'Err |Couldn' /tmp/aptitude.log`
 if [ "$OUTRES" = "0" -a "$RES" = "" -a "$OUT" = "" ]; then
  export RETURN_VALUE=1
  return 0
 fi
 
 export ERRSTR=echo "could not upgraded packages"
 if [ "$CRITICAL" = "1" ]; then
   echo $ERRSTR
   echo ABORTED
   rm /tmp/update_$PATCHNUM.lock 2>&1 >> $LOGFILE
   exit 1
 fi
 return 0
}


################################################
# installBinaries
#
# param  integer  is critical or not
#        if yes and function failed, then will exit script
# params  string  package list
# return 1 on success, 0 on failure
################################################
function installBinaries {
 export RETURN_VALUE=0
 PACKAGES=$1
 CRITICAL=$2
 if [ -f /tmp/aptitude.log ]; then
   rm /tmp/aptitude.log
 fi

 RES=`apt-get -y -o APT::Get::AllowUnauthenticated=true -o 'DPkg::Options::=--force-confold' install $PACKAGES 2>/dev/null | tee /tmp/aptitude.log >> $LOGFILE`
 OUTRES=$?
 OUT=`grep -E 'Err |Couldn' /tmp/aptitude.log`
 if [ "$OUTRES" = "0" -a "$RES" = "" -a "$OUT" = "" ]; then
  export RETURN_VALUE=1
  return 0
 fi
 
 export ERRSTR="could not install packages"
 if [ "$CRITICAL" = "1" ]; then
   echo $ERRSTR
   echo ABORTED
   rm /tmp/update_$PATCHNUM.lock 2>&1 >> $LOGFILE
   exit 1
 fi
 return 0
}

################################################
# stabilizeBinaries
#
# param  integer  is critical or not
#        if yes and function failed, then will exit script
# return 1 on success, 0 on failure
################################################
function stabilizeBinaries {
  export RETURN_VALUE=0
  CRITICAL=$1
  if [ -f /tmp/aptitude.log ]; then
    rm /tmp/aptitude.log
  fi

  getUpdateList $CRITICAL  
  distupgradeBinaries $CRITICAL

  PACKAGESFILE='PACKAGES.lenny' 
  ISLENNY=`grep ' lenny ' /etc/apt/sources.list`
  if [ "$ISLENNY" = "" ]; then
     PACKAGESFILE='PACKAGES.squeeze'
  fi 
  for p in `cat $SRCDIR/install/$PACKAGESFILE`; do PACKS=$PACKS" $p"; done
  installBinaries "$PACKS" $CRITICAL
  
  return 0
}
