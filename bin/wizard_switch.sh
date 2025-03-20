#!/bin/bash
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
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
#   This script will enable/disable access to the wizard
#
#   Usage:
#           wizard_switch.sh [-c <true|false>] [-b] [-s]
#

usage()
{
  cat << EOF
usage: $0 options

This script will enable/disable access to the wizard

OPTIONS:
  -c set true|false, enable or disable access to the wizard
  -b in batch mode, for automated process
  -s return the current status of wizard (When used with others options, -s has priority over others options)
EOF
}

# Get standard path
CONFFILE=/etc/mailcleaner.conf

SRCDIR=`grep 'SRCDIR' $CONFFILE | cut -d ' ' -f3`
if [ "$SRCDIR" = "" ]; then
  SRCDIR="/opt/mailcleaner"
fi
VARDIR=`grep 'VARDIR' $CONFFILE | cut -d ' ' -f3`
if [ "$VARDIR" = "" ]; then
  VARDIR="/opt/mailcleaner"
fi

getWizardStatus()
{
        req=$(echo "SELECT count(*) FROM external_access WHERE service='configurator' AND port='4242' AND protocol='TCP' \G" | $SRCDIR/bin/mc_mysql -m mc_config | grep -v ". row" | cut -d ':' -f2)
    [ "$req" -ge "1" ] && req=1
        return $req
}

getStatus()
{
    getWizardStatus
    status=$?
    exit $status
}

flagStatus=0
while getopts "sbc:" OPTION
do
  case $OPTION in
    c)
       enable=${OPTARG}
       ;;
    b)
       batch=true
       ;;
    s)
       flagStatus=1
       ;;
    ?)
       usage
       exit
       ;;
  esac
done

if [ ! -z "$enable" ] && [ "$flagStatus" -eq "1" ]; then
    [ "$batch" != "true" ] && echo "You can't set and get a status at same time." 
    usage
    exit 0
fi

[ "$flagStatus" -eq "1" ] && getStatus

updateWizardStatus()
{
    getWizardStatus
    [ "$1" -eq "$?" ] && return 0 # Nothing to do, desired status and current status are the same
    enableWizard="INSERT INTO external_access VALUES(NULL, 'configurator', '4242', 'TCP', '0.0.0.0/0', NULL);"
    disableWizard="DELETE FROM external_access WHERE service='configurator' AND port='4242' AND protocol='TCP';"
    if [ "$1" -eq "1" ]; then
        echo $enableWizard | $SRCDIR/bin/mc_mysql -m mc_config
    else
        echo $disableWizard | $SRCDIR/bin/mc_mysql -m mc_config
    fi
    return 1 # external_access changed
}

# 1 enable wizard
# 0 disable wizard
action=0

# Live mode
# Reverse the current status of wizard access
if [ -z "$enable" ]; then
    getWizardStatus
    [ "$?" == "0" ] && action=1
elif [ ! -z "$enable" ]; then
    [ "$enable" == "true" ] && action=1
fi

status=$([ "$action" == "1" ] && echo "Enabling" || echo "Disabling")
[ -z "$batch" ] && echo "$status the wizard access..."

updateWizardStatus $action
changed=$?
[ "$changed" == "1" ] && $SRCDIR/etc/init.d/firewall restart &>> /dev/null # Restart only on changes

getWizardStatus
wizardStatus=$?

if [ -z "$batch" ]; then
    status=$([ "$wizardStatus" == "1" ] && echo "open" || echo "close")
    echo "Firewall restarted"
    echo "Wizard access status: $status"
fi

exit $wizardStatus
