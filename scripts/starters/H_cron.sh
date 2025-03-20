#!/bin/bash

DELAY=2

export PATH=$PATH:/sbin:/usr/sbin

/etc/init.d/cron stop 2>&1 >/dev/null
sleep $DELAY
echo -n "SUCCESSFULL"
