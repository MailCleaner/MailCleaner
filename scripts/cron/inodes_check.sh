#! /bin/bash

INODES=$(df -i /var | grep '/var' | awk '{ print $5 }' | sed -e 's/%//')
DAYS=360
DONE=0

# Checks if inodes > 75%
while [[ $INODES -gt 70 ]]; do
	# Remove old search logs
	if [[ $DONE -eq 0 ]]; then
		find /var/mailcleaner/run/mailcleaner/log_search -type f -mtime +30 -delete
		DONE=1
	fi
	# If we already dropped files older than 120 days, inform the administrator
	if [[ $DAYS -eq 60 ]]; then
		EMAIL=$(echo "select contact_email from system_conf" | mc_mysql -s mc_config | grep -v 'contact_email')
		# Only if he gave an email address !
		if [[ ! -z $EMAIL ]]; then
			swaks -f ' ' -t $EMAIL --header "Subject: Please open a support ticket at https://support.mailcleaner.net for an inodes issue"
		fi
		exit
	fi

	# Deleting old count files
	find /var/mailcleaner/spool/mailcleaner/counts -type f -mtime +$DAYS -delete
	DAYS=$((DAYS - 60))
done
