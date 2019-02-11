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
#
#   This script will install MailCleaner options.
#   Usage:
#           install_options.sh
#
#   If you have any question regarding the installation, please take a look at:
#   https://support.mailcleaner.net/boards/3/topics/62-installation-of-mailcleaner-options
#
VARDIR=`grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "VARDIR" = "" ]; then
  VARDIR=/var/mailcleaner
fi
SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "SRCDIR" = "" ]; then
  SRCDIR=/var/mailcleaner
fi
MCVERSION=`cat /usr/mailcleaner/etc/mailcleaner/version.def | cut -c1-4`
LOGILFE=/tmp/mc_install_options.log

# Font properties
FONT_RESET=$(tput sgr0)
FONT_BOLD=$(tput bold)
FONT_RED=$(tput setaf 1)

function usage() {
	printf "Usage: install_options.sh [OPTION]... :\n"
	printf "Installer of MailCleaner filtering and analysis options\n"
	printf "\t--messagesniffer : install MessageSniffer\n"
	printf "\t--spamhaus : install SpamHaus\n"
	printf "\t--kaspersky : install Kaspersky\n"
	printf "\t-h : Help.\n\n"
  printf "Exit status:\n"
  printf "\t0  if OK,\n"
  printf "\t1  if minor problems (e.g., wrong licenses, error during install) \n"
  printf "${FONT_BOLD}Notes: ${FONT_RESET} for any issues, please contact our sales dept if you're a Community Edition or open a ticket on our online support: https://support.mailcleaner.net \n"
}

function messagesniffer() {
  if [ "$MCVERSION" -lt "2016" ]; then
    printf "You can't install MessageSniffer option in smaller version than 2016.xx \n"
    exit 1
  fi

  printf "Please provide MessageSniffer licenses informations. \n"
  read -p "License ID: "  license_id
  read -p "Auth Code: " auth_code
  # check if not empty etc
  if [[ -z "$license_id" ]] || [[ -z "$auth_code" ]]; then
    printf "License or Auth code is not valid. \nPlease try again !\n"
    exit 1
  fi

  if dpkg-query -s mc-messagesniffer | grep "Status: install ok installed"; then
    echo "MessageSniffer already installed. Please contact our support: https://support.mailcleaner.net"
  else
    apt-get update &>> $LOGILFE
    env PATH=$PATH:/usr/sbin:/sbin apt-get install --yes --force-yes mc-messagesniffer &>> $LOGILFE
    printf "Installing MessageSniffer ... \n"
    echo "UPDATE prefilter SET position=position+1 WHERE position > 1;" | ${SRCDIR}/bin/mc_mysql -m mc_config &>> $LOGILFE
    echo "INSERT INTO prefilter VALUES(NULL, 1, 'MessageSniffer', 1, 2, 0, 1, 'pos_decisive', 10, 2000000, 'X-MessageSniffer', 1, 1, 1);" | ${SRCDIR}/bin/mc_mysql -m mc_config &>> $LOGILFE
    echo "UPDATE MessageSniffer set licenseid='${license_id}', authentication='${auth_code}';" | ${SRCDIR}/bin/mc_mysql -m mc_config &>> $LOGILFE
    printf "Restarting MailScanner ... \n"
    
    printf "MessageSniffer has been correctly installed \n"
    printf "${FONT_BOLD}${FONT_RED}IMPORTANT: ${FONT_RESET}"
    printf "In order to enable MessageSniffer, please restart the filtering engine: \n"
    printf "\t ${SRCDIR}/etc/init.d/mailscanner restart \n"
  fi
  
}

function spamhaus() {
  if [ "$MCVERSION" -lt "2018" ]; then
    printf "You can't install SpamHaus option in smaller version than 2018.xx \n"
    exit 1
  fi

  printf "Please provide SpamHaus licenses informations. \n"
  read -p "Your SpamHaus token: "  token

  # check if not empty etc
  if [ -z ${token} ] || [ ${#token} -le 7 ]; then
    printf "Token is not valid! Please retry."
    exit 1
  fi

  printf "Installing SpamHaus RBL ... \n"

  # Create the RBL configuration file
  read -d '' RBL_CONTENT <<EOF
name=SPAMHAUSDQS
type=IPRBL
dnsname=${token}.zen.dq.spamhaus.net
sublist=127.0.0.\d+,SPAMHAUS,spamhaus.org list
EOF

  echo "${RBL_CONTENT}" > ${SRCDIR}/etc/rbls/SPAMHAUSDQS.cf

  # Override SpamAssassin default rules
  read -d '' RBL_SPAMC_OVERRIDE <<EOF
header __RCVD_IN_ZEN eval:check_rbl('zen','${token}.zen.dq.spamhaus.net.')
header RCVD_IN_XBL eval:check_rbl('zen-lastexternal', '${token}.zen.dq.spamhaus.net.', '127.0.0.[45678]')
header RCVD_IN_PBL eval:check_rbl('zen-lastexternal', '${token}.zen.dq.spamhaus.net.', '127.0.0.1[01]')

ifplugin Mail::SpamAssassin::Plugin::URIDNSBL
    uridnssub URIBL_SBL             ${token}.zen.dq.spamhaus.net. A 127.0.0.2
    uridnsbl  URIBL_SBL_A           ${token}.sbl.dq.spamhaus.net. A
endif # Mail::SpamAssassin::Plugin::URIDNSBL
EOF

  echo "${RBL_SPAMC_OVERRIDE}" > ${SRCDIR}/share/spamassassin/60_spamhaus_override.cf

  # Enable SpamHaus at PreRBLs level
  echo 'UPDATE PreRBLs set lists=concat(lists, " SPAMHAUSDQS");' | ${SRCDIR}/bin/mc_mysql -m mc_config &>> $LOGILFE
  printf "SpamHaus installed. \n"  
  printf "${FONT_BOLD}${FONT_RED}IMPORTANT: ${FONT_RESET}"
    printf "In order to enable SpamHaus, please restart the filtering engine: \n"
  printf "\t ${SRCDIR}/etc/init.d/mailscanner restart \n"  
  exit 0
}

function kaspersky() {
  if [ "$MCVERSION" -lt "2016" ]; then
    printf "You can't install Kaspersky option in smaller version than 2016.xx \n"
    exit 1
  fi

  printf "Please provide Kaspersky licenses informations. \n"
  read -p "The PATH to the Kaspersky license file (ended with .key): "  key_file

  # check if file exists
  if [[ ! -e ${key_file} ]]; then
    echo "The file ${key_file} doesnt exists" 
    exit 1
  fi

  if [[ ${key_file} != *.key ]]; then
    echo "The file ${key_file} is not a Kaspersky key file"
    exit 1
  fi

  printf "Installing Kaspersky ... \n"
  KASPERSKYSCANNER=/opt/kaspersky
  KASPERSKYUPDATER=/opt/kaspersky-updater

  # Install the new Kaspersky-64-2.0
  apt-get update &>> $LOGILFE
  env PATH=$PATH:/usr/sbin:/sbin apt-get install  --yes --force-yes kaspersky-64-2.0 &>> $LOGILFE

  # Update Kaspersky databases
  printf "Updating Kaspersky databases ... \n"
  $SRCDIR/etc/init.d/kaspersky stop &>> $LOGILFE
  cp -f ${key_file} $KASPERSKYSCANNER/bin/ &>> $LOGILFE
  cp -f ${key_file} $KASPERSKYUPDATER/bin/ &>> $LOGILFE
  ls $KASPERSKYUPDATER/bin/*.key &>> $LOGILFE
  
  if $KASPERSKYUPDATER/bin/keepup2date8.sh --licinfo --simplelic | grep "0x00000000. Success"; then
    $KASPERSKYUPDATER/bin/keepup2date8.sh --simplelic --download &>> $LOGILFE
    $SRCDIR/etc/init.d/kaspersky restart &>> $LOGILFE
    echo "EXEC: Kaspersky updated" &>> $LOGILFE
  else
    $KASPERSKYUPDATER/bin/keepup2date8.sh --licinfo --simplelic
    printf "Error during the update of Kaspersky databases. \n Notes for MailCleaner support: %s" 
  fi
  $KASPERSKYUPDATER/bin/keepup2date8.sh --licinfo --simplelic | sed  -n '8p' &>> $LOGILFE 
  printf "${FONT_BOLD}${FONT_RED}IMPORTANT: ${FONT_RESET}"
  printf "In order to enable Kaspersky, please restart the filtering engine: \n"
  printf "\t ${SRCDIR}/etc/init.d/mailscanner restart \n"
  exit 0
}

if [ $# -eq 0 ]
then
	usage
fi

OPTS=$( getopt -o h -l messagesniffer,spamhaus,kaspersky -- "$@" )
if [ $? != 0 ]
then
    exit 1
fi
 
eval set -- "$OPTS"

while true ; do
    case "$1" in
        -h) usage; exit 0;;
        --messagesniffer) messagesniffer; shift;;
	      --spamhaus) spamhaus; shift;;
	      --kaspersky) kaspersky; shift;;
        --) shift; break;;
    esac
done
 
exit 0
