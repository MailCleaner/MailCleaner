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
LOGFILE=/tmp/mc_install_options.log

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
	printf "\t--eset : install ESET EFS\n"
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
    apt-get update &>> $LOGFILE
    env PATH=$PATH:/usr/sbin:/sbin apt-get install --yes --force-yes mc-messagesniffer &>> $LOGFILE
    printf "Installing MessageSniffer ... \n"
    echo "UPDATE prefilter SET position=position+1 WHERE position > 1;" | ${SRCDIR}/bin/mc_mysql -m mc_config &>> $LOGFILE
    echo "INSERT INTO prefilter VALUES(NULL, 1, 'MessageSniffer', 1, 2, 0, 1, 'pos_decisive', 10, 2000000, 'X-MessageSniffer', 1, 1, 1);" | ${SRCDIR}/bin/mc_mysql -m mc_config &>> $LOGFILE
    echo "UPDATE MessageSniffer set licenseid='${license_id}', authentication='${auth_code}';" | ${SRCDIR}/bin/mc_mysql -m mc_config &>> $LOGFILE
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

  while [[ "$shpackage" != "1" && "$shpackage" != "2" && "$shpackage" != "3" && "$shpackage" != "0" ]]; do
    printf "Do you want to install \n   1 - the ZEN package\n   2 - the Content package\n   3 - Both of them\n   0 - Exit\nWARNING : if you already have a package and want to add a new one afterwards, make sure the token are the same and answer 3\n"
    read -p "Package number: "  shpackage
  done
  if [[ "$shpackage" -eq 0 ]]; then
	  echo "No modifications made\n"
	  exit;
  fi

  printf "Please provide SpamHaus licenses informations. \n"
  read -p "Your SpamHaus token: "  token

  # check if not empty etc
  if [ -z ${token} ] || [ ${#token} -le 7 ]; then
    printf "Token is not valid! Please retry."
    exit 1
  fi

  printf "Installing SpamHaus RBLs... \n"



### ZEN package
  if [[ "$shpackage" -eq "1" ||  "$shpackage" -eq "3" ]]; then
    if [ -e "${SRCDIR}/etc/rbls/SPAMHAUSSBL.cf" ]; then
      rm -f "${SRCDIR}/etc/rbls/SPAMHAUSSBL.cf"
    fi

    if [ -e "${SRCDIR}/etc/rbls/SPAMHAUSDQS.cf" ]; then
      rm -f "${SRCDIR}/etc/rbls/SPAMHAUSDQS.cf"
    fi

    # Create the RBL configuration file
    read -d '' RBL_CONTENT <<EOF
name=SPAMHAUSZEN
type=IPRBL
dnsname=${token}.zen.dq.spamhaus.net
sublist=127.0.0.\d+,SPAMHAUS,spamhaus.org list
EOF

    echo "${RBL_CONTENT}" > ${SRCDIR}/etc/rbls/SPAMHAUSZEN.cf
  fi 




### Content package
  if [[ "$shpackage" -eq "2" ||  "$shpackage" -eq "3" ]]; then
    # Create the RBL configuration file
    read -d '' RBL_CONTENT <<EOF
name=SPAMHAUSDBL
type=URIRBL
dnsname=${token}.dbl.dq.spamhaus.net
sublist=127.0.1.\d+,SPAMHAUSDBL,Spamhaus domain blocklist
callonip=1
ishbl=0
EOF

    echo "${RBL_CONTENT}" > ${SRCDIR}/etc/rbls/SPAMHAUSDBL.cf




    # Create the RBL configuration file
    read -d '' RBL_CONTENT <<EOF
name=SPAMHAUSHBL
type=URIRBL
dnsname=${token}.hbl.dq.spamhaus.net
sublist=127.0.0.\d+,SPAMHAUSHBL,Spamhaus Hash blocklist
callonip=0
ishbl=1
EOF

    echo "${RBL_CONTENT}" > ${SRCDIR}/etc/rbls/SPAMHAUSHBL.cf




    # Create the RBL configuration file
    read -d '' RBL_CONTENT <<EOF
name=SPAMHAUSZRD
type=URIRBL
dnsname=${token}.zrd.dq.spamhaus.net
sublist=127.0.0.\d+,SPAMHAUSZRD,Spamhaus Zero Reputation list
EOF

    echo "${RBL_CONTENT}" > ${SRCDIR}/etc/rbls/SPAMHAUSZRD.cf
  fi




  # Override SpamAssassin default rules
  if [[ "$shpackage" -eq "1" || "$shpackage" -eq "2" ||  "$shpackage" -eq "3" ]]; then
### All packages
    if [ -e "${SRCDIR}/share/spamassassin/60_spamhaus_override.cf" ]; then
      rm -f "${SRCDIR}/share/spamassassin/60_spamhaus_override.cf"
    fi
  fi
### Zen package
  if [[ "$shpackage" -eq "1" ||  "$shpackage" -eq "3" ]]; then
    read -d '' RBL_SPAMC_OVERRIDE <<EOF
header __RCVD_IN_ZEN eval:check_rbl('zen','${token}.zen.dq.spamhaus.net.')
header RCVD_IN_SBL eval:check_rbl('zen-lastexternal', '${token}.zen.dq.spamhaus.net.', '127.0.0.2')
score  RCVD_IN_SBL 2.0
header RCVD_IN_CSS eval:check_rbl('zen-lastexternal', '${token}.zen.dq.spamhaus.net.', '127.0.0.3')
score  RCVD_IN_CSS 2.0
header RCVD_IN_XBL eval:check_rbl('zen-lastexternal', '${token}.zen.dq.spamhaus.net.', '127.0.0.[45678]')
score  RCVD_IN_XBL 2.0
header RCVD_IN_PBL eval:check_rbl('zen-lastexternal', '${token}.zen.dq.spamhaus.net.', '127.0.0.1[01]')
score  RCVD_IN_PBL 2.0

ifplugin Mail::SpamAssassin::Plugin::URIDNSBL
    uridnssub URIBL_SBL             ${token}.zen.dq.spamhaus.net. A 127.0.0.2
    score     URIBL_SBL 2.5
    uridnsbl  URIBL_SBL_A           ${token}.sbl.dq.spamhaus.net. A
endif # Mail::SpamAssassin::Plugin::URIDNSBL
EOF

    echo "${RBL_SPAMC_OVERRIDE}" >> ${SRCDIR}/share/spamassassin/60_spamhaus_override.cf
  fi

### Content package
  if [[ "$shpackage" -eq "2" ||  "$shpackage" -eq "3" ]]; then

    read -d '' RBL_SPAMC_OVERRIDE <<EOF
ifplugin Mail::SpamAssassin::Plugin::URIDNSBL
    urirhssub URIBL_DBL_SPAM        ${token}.dbl.dq.spamhaus.net. A 127.0.1.2
    score     URIBL_DBL_SPAM 2.5
    urirhssub URIBL_DBL_REDIR       ${token}.dbl.dq.spamhaus.net. A 127.0.1.3
    score     URIBL_DBL_REDIR 2.5
    urirhssub URIBL_DBL_PHISH       ${token}.dbl.dq.spamhaus.net. A 127.0.1.4
    score     URIBL_DBL_PHISH 2.5
    urirhssub URIBL_DBL_MALWARE     ${token}.dbl.dq.spamhaus.net. A 127.0.1.5
    score     URIBL_DBL_MALWARE 2.5
    urirhssub URIBL_DBL_BOTNETCC    ${token}.dbl.dq.spamhaus.net. A 127.0.1.6
    score     URIBL_DBL_MALWARE 2.5
    urirhssub URIBL_DBL_ABUSE_SPAM  ${token}.dbl.dq.spamhaus.net. A 127.0.1.102
    score     URIBL_DBL_ABUSE_SPAM 2.5
    urirhssub URIBL_DBL_ABUSE_REDIR ${token}.dbl.dq.spamhaus.net. A 127.0.1.103
    score     URIBL_DBL_ABUSE_REDIR 2.5
    urirhssub URIBL_DBL_ABUSE_PHISH ${token}.dbl.dq.spamhaus.net. A 127.0.1.104
    score     URIBL_DBL_ABUSE_PHISH 2.5
    urirhssub URIBL_DBL_ABUSE_MALW  ${token}.dbl.dq.spamhaus.net. A 127.0.1.105
    score     URIBL_DBL_ABUSE_MALW 2.5
    urirhssub URIBL_DBL_ABUSE_BOTCC ${token}.dbl.dq.spamhaus.net. A 127.0.1.106
    score     URIBL_DBL_ABUSE_BOTCC 2.5
    urirhssub URIBL_DBL_ERROR       ${token}.dbl.dq.spamhaus.net. A 127.0.1.255
    score     URIBL_DBL_ERROR 2.5

    if can(Mail::SpamAssassin::Plugin::URIDNSBL::has_tflags_domains_only)
        urirhsbl URIBL_ZRD ${token}.zrd.dq.spamhaus.net. A
        body     URIBL_ZRD eval:check_uridnsbl('URIBL_ZRD')
        describe URIBL_ZRD Contains a URL listed in the Spamhaus ZRD blocklist
        tflags   URIBL_ZRD net domains_only
        score    URIBL_ZRD 2.5
    endif # if can
endif # Mail::SpamAssassin::Plugin::URIDNSBL
EOF

    echo "${RBL_SPAMC_OVERRIDE}" >> ${SRCDIR}/share/spamassassin/60_spamhaus_override.cf
  fi


  # Enable SpamHaus at PreRBLs level
### ZEN
  if [[ "$shpackage" -eq "1" ||  "$shpackage" -eq "3" ]]; then
    echo 'UPDATE PreRBLs set lists=concat(lists, " SPAMHAUSZEN");' | ${SRCDIR}/bin/mc_mysql -m mc_config &>> $LOGFILE
    echo 'UPDATE antispam set sa_rbls=concat(sa_rbls, " SPAMHAUSZEN");' | ${SRCDIR}/bin/mc_mysql -m mc_config &>> $LOGFILE
  fi
### Content
  if [[ "$shpackage" -eq "1" ||  "$shpackage" -eq "3" ]]; then
    echo 'UPDATE UriRBLs set rbls=concat(lists, " SPAMHAUSDBL SPAMHAUSHBL SPAMHAUSZRD");' | ${SRCDIR}/bin/mc_mysql -m mc_config &>> $LOGFILE
    echo 'UPDATE antispam set sa_rblsconcat(sa_rbls, " SPAMHAUSDBL SPAMHAUSHBL SPAMHAUSZRD");' | ${SRCDIR}/bin/mc_mysql -m mc_config &>> $LOGFILE
  fi

  printf "SpamHaus installed. \n"  
  printf "${FONT_BOLD}${FONT_RED}IMPORTANT: ${FONT_RESET}"
  printf "In order to enable SpamHaus, please restart the filtering engine: \n"
  printf "\t ${SRCDIR}/etc/init.d/mailscanner restart \n"  
  exit 0
}

function eset() {
  REINSTALL=$1
  if [[ $REINSTALL == '--reinstall' ]]; then
    printf "Reinstalling ESET ..."
    REINSTALL=1
  elif [[ $REINSTALL != '--' ]]; then
    printf "Invalid argument $1\n"
    exit 1
  fi

  if [ "$MCVERSION" -lt "2016" ]; then
    printf "You can't install ESET option in smaller version than 2016.xx \n"
    exit 1
  fi

  printf "\n"
  read -p "Please provide ESET license: "  key

  if perl -e "exit(1) unless '$key' =~ m/^([0-9a-zA-Z]{4}-){4}[0-9a-zA-Z]{4}$/;"; then
    key=`perl -e "print uc('"$key"');"`
  elif perl -e "exit(1) unless '$key' =~ m/^([0-9a-zA-Z]){20}$/;"; then
    key=`perl -e 'my $x = "'$key'"; my ($a, $b, $c, $d, $e) = $x =~ m/([0-9a-zA-Z]{4})/g; print uc("$a-$b-$c-$d-$e");'`
  else
    printf "Invalid key format '$key'. Should be XXXX-XXXX-XXXX-XXXX-XXXX\n"
    exit 1;
  fi

  if [[ ! -d /opt/eset && ! $REINSTALL == 1 ]]; then
    printf "Installing ESET ... \n"
    env PATH=$PATH:/usr/sbin:/sbin apt-get update
    env PATH=$PATH:/usr/sbin:/sbin apt-get dist-upgrade --yes --force-yes
    env PATH=$PATH:/usr/sbin:/sbin apt-get autoremove --yes --force-yes
    env PATH=$PATH:/usr/sbin:/sbin apt-get autoclean --yes --force-yes
    cd /tmp
    wget https://download.eset.com/com/eset/apps/business/efs/linux/latest/efs.x86_64.bin
    if [[ ! -e efs.x86_64.bin ]]; then
      echo "Failed to download 'https://download.eset.com/com/eset/apps/business/efs/linux/latest/efs.x86_64.bin'" | tee &>> $LOGFILE
      exit 1
    fi
    chmod +x efs.x86_64.bin
    env PATH=$PATH:/usr/sbin:/sbin ./efs.x86_64.bin -y -f -g
  
    if [[ ! -d /opt/eset ]]; then
      printf "Failed to install to /opt/eset\n"
      exit 1
    fi
    printf "Cleaning up ... \n"
    rm efs.x86_64.bin
    rm efs-*.deb
  fi

  printf "Enabling ESET ... \n"
  /opt/eset/efs/sbin/lic -k $key

  SUCCESS=`/opt/eset/efs/sbin/lic --status | grep 'Status:' | cut -d' ' -f2`
  if [[ $SUCCESS != "Activated" ]]; then
    printf "License activation failed. Run again with correct License\n"
    exit
  fi

  list=`echo "SELECT allowed_ip FROM external_access WHERE service = 'web';" | mc_mysql -m mc_config | sed 's/\s*allowed_ip\s*//'`
  for ip in $list; do
    echo "INSERT external_access(service,port,protocol,allowed_ip) SELECT * FROM (SELECT 'esetweb', '9443', 'TCP', '$ip') AS new WHERE NOT EXISTS (SELECT id FROM external_access WHERE service = 'esetweb' AND allowed_ip = '$ip') LIMIT 1;" | /usr/mailcleaner/bin/mc_mysql -m mc_config
  done
  /usr/mailcleaner/etc/init.d/firewall restart
  
  echo "UPDATE scanner set active = 1 WHERE name = 'esetsefs';" | /usr/mailcleaner/bin/mc_mysql -m mc_config

  res="`/opt/eset/efs/sbin/setgui -gre`"
  SUCCESS=`echo $res | grep 'GUI is enabled'`
  if [[ $SUCCESS == '' ]]; then
    printf "Failed to enable GUI\n"
  fi
  URL=`echo $res | sed -r 's/.*URL: ([^ ]+).*/\1/'`
  USER=`echo -e $res | sed -r 's/.*Username: ([^ ]+).*/\1/'`
  PASS=`echo -e $res | sed -r 's/.*Password: ([^ ]+).*/\1/'`
  printf "${FONT_BOLD}${FONT_RED}IMPORTANT: ${FONT_RESET}"
  printf "In order to configure ESET, log in with:\n\n\tURL:  $URL\n\tUser: $USER\n\tPass: $PASS\n\nthen restart the filtering engine: \n\n\t ${SRCDIR}/etc/init.d/mailscanner restart \n\n"
  
  exit 0
}
function kaspersky() {
  if [ "$MCVERSION" -lt "2016" ]; then
    printf "You can't install Kaspersky option in smaller version than 2016.xx \n"
    exit 1
  fi

  printf "Please provide Kaspersky licenses informations. \n"
  read -p "The PATH to the Kaspersky license file (ended with .key or .KEY): "  key_file

  # check if file exists
  if [[ ! -e ${key_file} ]]; then
    echo "The file ${key_file} doesnt exists" 
    exit 1
  fi

  if [[ $(echo $key_file | sed 's/.KEY/\L&/g') != *.key ]]; then
    echo "The file ${key_file} is not a Kaspersky key file"
    exit 1
  fi

  printf "Installing Kaspersky ... \n"
  KASPERSKYSCANNER=/opt/kaspersky
  KASPERSKYUPDATER=/opt/kaspersky-updater

  # Install the new Kaspersky-64-2.0
  apt-get update &>> $LOGFILE
  env PATH=$PATH:/usr/sbin:/sbin apt-get install  --yes --force-yes kaspersky-64-2.0 &>> $LOGFILE

  # Update Kaspersky databases
  printf "Updating Kaspersky databases ... \n"
  $SRCDIR/etc/init.d/kaspersky stop &>> $LOGFILE
  rm -f $KASPERSKYSCANNER/bin/*.key &>> $LOGFILE
  rm -f $KASPERSKYUPDATER/bin/*.key &>> $LOGFILE
  cp -f ${key_file} $KASPERSKYSCANNER/bin/$(basename $key_file | sed 's/.KEY/\L&/g') &>> $LOGFILE
  cp -f ${key_file} $KASPERSKYUPDATER/bin/$(basename $key_file | sed 's/.KEY/\L&/g') &>> $LOGFILE
  ls $KASPERSKYUPDATER/bin/*.key &>> $LOGFILE
  
  if $KASPERSKYUPDATER/bin/keepup2date8.sh --licinfo --simplelic | grep "0x00000000. Success"; then
    $KASPERSKYUPDATER/bin/keepup2date8.sh --simplelic --download &>> $LOGFILE
    $SRCDIR/etc/init.d/kaspersky restart &>> $LOGFILE
    echo "EXEC: Kaspersky updated" &>> $LOGFILE
  else
    $KASPERSKYUPDATER/bin/keepup2date8.sh --licinfo --simplelic
    printf "Error during the update of Kaspersky databases. \n Notes for MailCleaner support: %s" 
  fi
  $KASPERSKYUPDATER/bin/keepup2date8.sh --licinfo --simplelic | sed  -n '8p' &>> $LOGFILE 
  printf "${FONT_BOLD}${FONT_RED}IMPORTANT: ${FONT_RESET}"
  printf "In order to enable Kaspersky, please restart the filtering engine: \n"
  printf "\t ${SRCDIR}/etc/init.d/mailscanner restart \n"
  exit 0
}

if [ $# -eq 0 ]
then
	usage
fi

OPTS=$( getopt -o h -l messagesniffer,spamhaus,kaspersky,eset,reinstall -- "$@" )
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
	      --eset) eset $2; shift;;
        --) shift; break;;
    esac
done
 
exit 0
