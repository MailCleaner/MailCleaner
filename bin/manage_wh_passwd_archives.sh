#!/bin/bash

# adds or removes domain names from the whitelist for password protected archives
# manage_wh_passwd_archives.sh add domain.tld to add it
# manage_wh_passwd_archives.sh del domain.tld to remove it

if [ $1 == "add" ]; then
	FIELD=$(echo "SELECT IFNULL(wh_passwd_archives, 'null') FROM dangerouscontent" | mc_mysql -m mc_config | tail -n +2)
	if [[ "$FIELD" == "null" ]]; then
		echo "UPDATE dangerouscontent set wh_passwd_archives =  CONCAT('$2', IFNULL(wh_passwd_archives, ''));" | mc_mysql -m mc_config
	else
		echo "UPDATE dangerouscontent set wh_passwd_archives =  CONCAT('$2', '\n', IFNULL(wh_passwd_archives, ''));" | mc_mysql -m mc_config
	fi
	echo "$2 added"
	exit
fi

if [ $1 == "del" ]; then
	FIELD=$(echo "SELECT IFNULL(wh_passwd_archives, 'null') FROM dangerouscontent" | mc_mysql -m mc_config | tail -n +2)
	if [[ "$FIELD" == "null" ]]; then
		echo "nodomain in this list"
		exit
	fi
	FIELD=$(echo "$FIELD" | sed -e "s/$2//g")
	echo "UPDATE dangerouscontent set wh_passwd_archives =  '$FIELD';" | mc_mysql -m mc_config
	echo "$2 removed"
	exit
fi

echo "Usage is $0 [add|del] domain_name"
