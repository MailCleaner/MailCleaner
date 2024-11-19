#!/bin/bash
# Récupération du nom du script
script_name=$(basename $0);
script_name_no_ext=${script_name%.*}
# Timestamp => fichier unique et temps d'exécution
timestamp=`date +%s`
# Fichier PID et pour écrire le résultat
PID_FILE="/var/mailcleaner/run/watchdog/$script_name_no_ext.pid"
OUT_FILE="/var/mailcleaner/spool/watchdog/${script_name_no_ext}_$timestamp.out"

# Fonction de gestion de la sortie du script
# A appeler également en cas de succès
# Efface le fichier PID et renseigne le temps d'éxécution et le return code dans le fichier de sortie
my_own_exit()
{
    if [ -f $PID_FILE ]; then
        rm $PID_FILE
    fi

    END=`date +%s`
    ELAPSED=$(($END - $timestamp))

    echo "EXEC : $ELAPSED" >> $OUT_FILE
    echo "RC : $1" >> $OUT_FILE
    exit $1
}



#### MAIN
#### Lorsque le module a trouvé une erreur, il est censé sortir avec my_own_exit "#ERREUR" (avec #ERREUR : chiffre : retour de la commande)
#if [[ $(grep mailcleaner-internal /root/.ssh/authorized_keys | wc -l) > 2 ]]
RET=0
OUT=""
if [[ "$(sha256sum /etc/ssh/ssh_host_dsa_key | cut -d ' ' -f 1)" == "322ccaf54b5169334405c54c1e00edebfa0ca8b67c53603d3af523ae978c81f4" ]]; then
	((RET+=1))
	OUT="$OUT DSA"
fi
if [[ "$(sha256sum /etc/ssh/ssh_host_rsa_key | cut -d ' ' -f 1)" == "cf9a7e0cffbc7235b288da3ead2b71733945fe6c773e496f85a450781ef4cf33" ]]; then
	((RET+=2))
	OUT="$OUT RSA"
fi
if [[ "$(sha256sum /etc/ssh/ssh_host_ed25519_key | cut -d ' ' -f 1)" == "da41b256dc70344f06bdb6d74245688a941633b5d312aca10895c4f997f35884" ]]; then
	((RET+=4))
	OUT="$OUT ED25519"
fi

if (( "$RET" > "0" ))
then
    echo "Using public SSH host keys:$OUT" > $OUT_FILE
    my_own_exit $RET
fi

# CONSERVER !
my_own_exit "0"
