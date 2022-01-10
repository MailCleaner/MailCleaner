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
BLOCK25AUTH=`echo SELECT "block_25_auth FROM mta_config WHERE stage = 1" | mc_mysql -m mc_config | tail -n 1`
# Le jour dernier
if [[ $BLOCK25AUTH -eq 0 ]]; then
    LOG_FILE="/var/mailcleaner/log/exim_stage1/mainlog"
    NB_AUTH=`grep -e "Accepting authenticated session from .* on port 25" $LOG_FILE | wc -l`
    if [[ $NB_AUTH -ne 0 ]]; then
        echo "Port 25 authentication is in use ($NB_AUTH occurences)" > $OUT_FILE
        my_own_exit "1"
    else
        echo "Port 25 authentication is open (not used today)" > $OUT_FILE
        my_own_exit "2"
    fi
else
    echo "Port 25 authentication is blocked" > $OUT_FILE
    my_own_exit "0"
fi

# CONSERVER !
my_own_exit "0"
