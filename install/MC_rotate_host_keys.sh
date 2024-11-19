#!/bin/bash

GEN=0
	
if [[ "$(sha256sum /etc/ssh/ssh_host_dsa_key | cut -d ' ' -f 1)" == "322ccaf54b5169334405c54c1e00edebfa0ca8b67c53603d3af523ae978c81f4" ]]; then
	GEN=1
	echo "Disabling default DSA key"
	mv /etc/ssh/ssh_host_dsa_key /etc/ssh/.ssh_host_dsa_key.old
	mv /etc/ssh/ssh_host_dsa_key.pub /etc/ssh/.ssh_host_dsa_key.pub.old
fi
if [[ "$(sha256sum /etc/ssh/ssh_host_rsa_key | cut -d ' ' -f 1)" == "cf9a7e0cffbc7235b288da3ead2b71733945fe6c773e496f85a450781ef4cf33" ]]; then
	GEN=1
	echo "Disabling default RSA key"
	mv /etc/ssh/ssh_host_rsa_key /etc/ssh/.ssh_host_rsa_key.old
	mv /etc/ssh/ssh_host_rsa_key.pub /etc/ssh/.ssh_host_rsa_key.pub.old
fi
if [[ "$(sha256sum /etc/ssh/ssh_host_ed25519_key | cut -d ' ' -f 1)" == "da41b256dc70344f06bdb6d74245688a941633b5d312aca10895c4f997f35884" ]]; then
	GEN=1
	echo "Disabling default ED25519 key"
	mv /etc/ssh/ssh_host_ed25519_key /etc/ssh/.ssh_host_ed25519_key.old
	mv /etc/ssh/ssh_host_ed25519_key.pub /etc/ssh/.ssh_host_ed25519_key.pub.old
fi

if [[ $GEN != 0 ]]; then
	echo "Generating new host keys..."
	/usr/bin/ssh-keygen -A
	echo "You may need to remove the old host keys from the .ssh/known_hosts file for any client which has previously connected to this machine."
fi
