#!/bin/bash

sed -i "s/^MYMAILCLEANERPWD.*$/MYMAILCLEANERPWD = ${@}/g" /etc/mailcleaner.conf
