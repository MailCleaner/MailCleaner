#! /bin/bash

FILE=$1

if [ ! -f $FILE ];
then
	echo "File $FILE not found!"
	echo ""
	echo "Please provide a file with the format"
	echo "sender recipient type"
	echo "if the recipient should be a whole domain, you need to include the '@' sign on it for example @mailcleaner.net"
	echo "if the rule is for all domains please use --- as domain name"
	echo "type can be either white or black"
	exit 0
fi

ISMASTER=`grep 'ISMASTER' /etc/mailcleaner.conf | cut -d ' ' -f3`
if [ "$ISMASTER" = "Y" ] || [ "$ISMASTER" = "y" ]; then
	sed -i 's/^\s*//' $FILE
	sed -i 's/ /", "/g' $FILE
	sed -i 's/^/insert ignore into wwlists (sender, recipient, type, comments) values ("/' $FILE
	sed -i 's/$/", "inserting bulk rules - MC script");/g' $FILE
	sed -i 's///g' $FILE
	sed -i 's/"---"/""/' $FILE

	sleep 1
	mc_mysql -m mc_config < $FILE
else
	echo "Please run this script on your master host"
fi
