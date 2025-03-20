#!/bin/bash

# adds or removes domain names or email_addresses from the whitelist for HTML Controls
# manage_wh_html_controls.sh add domain.tld to add it
# manage_wh_html_controls.sh del domain.tld to remove it
# manage_wh_html_controls.sh show

usage() {
	echo "Usage is $0 [add|del|show] [domain_name|email_address]"
	exit
}

if [ -z $1 ]; then
	usage
fi

if [ $1 == "add" ]; then
	echo "INSERT IGNORE INTO wwlists (sender, type) values ('$2', 'htmlcontrols');" | mc_mysql -m mc_config
	echo "$2 added"

	/usr/mailcleaner/bin/dump_html_controls_wl.pl
	exit
fi

if [ $1 == "del" ]; then
	echo "DELETE FROM wwlists WHERE sender='$2' AND type='htmlcontrols';" | mc_mysql -m mc_config
	echo "$2 removed"

	/usr/mailcleaner/bin/dump_html_controls_wl.pl
	exit
fi

if [ $1 == "show" ]; then
	echo "SELECT * FROM wwlists WHERE type='htmlcontrols';" | mc_mysql -m mc_config -t
	exit
fi

usage
