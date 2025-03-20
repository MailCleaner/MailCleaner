#!/bin/bash
# Récupération du nom du script
script_name=$(basename $0)
script_name_no_ext=${script_name%.*}
# Timestamp => fichier unique et temps d'exécution
timestamp=$(date +%s)
# Fichier PID et pour écrire le résultat
PID_FILE="/var/mailcleaner/run/watchdog/$script_name_no_ext.pid"
OUT_FILE="/var/mailcleaner/spool/watchdog/${script_name_no_ext}_$timestamp.out"

# Fonction de gestion de la sortie du script
# A appeler également en cas de succès
# Efface le fichier PID et renseigne le temps d'éxécution et le return code dans le fichier de sortie
my_own_exit() {
    if [ -f $PID_FILE ]; then
        rm $PID_FILE
    fi

    END=$(date +%s)
    ELAPSED=$(($END - $timestamp))

    echo "EXEC : $ELAPSED" >>$OUT_FILE
    echo "RC : $1" >>$OUT_FILE
    exit $1
}

#### MAIN
#### Lorsque le module a trouvé une erreur, il est censé sortir avec my_own_exit "#ERREUR" (avec #ERREUR : chiffre : retour de la commande)
#if [[ $(grep mailcleaner-internal /root/.ssh/authorized_keys | wc -l) > 2 ]]
RET=$(grep "internal" /root/.ssh/authorized_keys | wc -l)
if (("$RET" > "1")); then
    echo "Internal keys found several times in /root/.ssh/authorized_keys" >$OUT_FILE
    my_own_exit "1"
fi

if (("$RET" <= "0")); then
    echo "Internal keys not found in /root/.ssh/authorized_keys" >$OUT_FILE
    my_own_exit "2"
fi

if [ ! -f /root/.ssh/id_rsa_internal ]; then
    echo "/root/.ssh/id_rsa_internal not found" >$OUT_FILE
    my_own_exit "3"
fi

if [ ! -f /root/.ssh/id_rsa_internal.pub ]; then
    echo "/root/.ssh/id_rsa_internal.pub not found" >$OUT_FILE
    my_own_exit "4"
fi

# CONSERVER !
my_own_exit "0"
