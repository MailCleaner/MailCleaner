#!/bin/bash

usage() {
	echo "Usage: $0 public_chain private_key [-R]"
	echo "	-R  Don't restart services"
	exit;
}

SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`

RESTART=1
if [ ! $1 ] || [ ! $2 ]; then
	echo "Missing argument"
	usage
elif [[ ! -r $1 ]]; then
	echo "Cannot read $1"
	usage
elif [[ ! -r $2 ]]; then
	echo "Cannot read $2"
	usage
fi
if [ $3 ]; then
	if [[ $3 -eq '-R' ]]; then
		echo "Not restarting"
		RESTART=0
	else
		echo "Invaild option '$3'"
		usage
	fi
fi

CERT=`cat $1 | grep -m 1 -B 1000 'END CERTIFICATE'`
COUNT=`cat $1 | grep -c 'END CERTIFICATE'`
let "COUNT--"

if [ $COUNT ]; then
	CHAIN=`tac $1 | grep -m $COUNT -B 1000 'BEGIN CERTIFICATE' | tac`
else
	CHAIN=''
fi

cat << EOF | ${SRCDIR}/bin/mc_mysql -m mc_config
UPDATE mta_config set tls_certificate_data = '`cat $1`';
EOF

cat << EOF | ${SRCDIR}/bin/mc_mysql -m mc_config
UPDATE mta_config set tls_certificate_key = '`cat $2`';
EOF

if [[ $RESTART == 1 ]]; then
	for i in 4 2 1; do ${SRCDIR}/etc/init.d/exim_stage$i restart; done
fi

cat << EOF | ${SRCDIR}/bin/mc_mysql -m mc_config
UPDATE httpd_config set tls_certificate_data = '`echo -e "$CERT"`';
EOF

cat << EOF | ${SRCDIR}/bin/mc_mysql -m mc_config
UPDATE httpd_config set tls_certificate_chain = '`echo -e "$CHAIN"`';
EOF

cat << EOF | ${SRCDIR}/bin/mc_mysql -m mc_config
UPDATE httpd_config set tls_certificate_key = '`cat $2`';
EOF

if [[ $RESTART == 1 ]]; then
	${SRCDIR}/etc/init.d/apache restart
fi
