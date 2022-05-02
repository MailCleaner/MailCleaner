#!/bin/bash

find /var/mailcleaner/run/watchdog/ -type f -mmin +2 -delete
exit 0
