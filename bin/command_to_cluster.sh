#! /bin/bash

for i in `/usr/mailcleaner/bin/slaves.pl`; do echo $i; ssh -q -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no root@$i "$1"; done

