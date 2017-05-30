#!/bin/bash

clear

IFFILE=/etc/network/interfaces
RESOLVFILE=/etc/resolv.conf
NETFILE=/etc/networks

echo -n "Please enter search domain name (e.g., youdomain.com): "
read DOMAIN
echo -n "Please enter ip address (e.g., 192.168.1.101): "
read IPADD
echo -n "Please enter netmask (e.g., 255.255.255.0): "
read NETMASK
echo -n "Please enter network base address (e.g., 192.168.1.0): "
read NET
echo -n "Please enter broadcast address (e.g., 192.168.1.255): "
read BROADCAST
echo -n "Please enter gateway address (e.g., 192.168.1.1): "
read GATEWAY
echo -n "Please enter primary dns server (e.g., 192.168.1.1): "
read DNS1
echo -n "Please enter secondary dns server (e.g., 192.168.1.2): "
read DNS2

echo "search $DOMAIN" > $RESOLVFILE
echo "nameserver $DNS1" >> $RESOLVFILE
echo "nameserver $DNS2" >> $RESOLVFILE

echo "localnet $NET" > $NETFILE

echo "auto lo" > $IFFILE
echo "iface lo inet loopback" >> $IFFILE
echo "" >> $IFFILE
echo "auto eth0" >> $IFFILE
echo "iface eth0 inet static" >> $IFFILE
echo "address $IPADD" >> $IFFILE
echo "netmask $NETMASK" >> $IFFILE
echo "broadcast $BROADCAST" >> $IFFILE
echo "gateway $GATEWAY" >> $IFFILE

ifdown eth0
ifup eth0

echo "done !"
