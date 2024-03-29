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
NB_PROCESS=`ps fauxw |grep fail2ban-server |grep -v 'grep '|grep -v 'MC_mod_'|wc -l`
if [[ $NB_PROCESS -eq 0 ]]; then
    echo "fail2ban-server is not running" > $OUT_FILE
    my_own_exit "1"
elif [[ $NB_PROCESS -ne 1 ]]; then
    echo "fail2ban-server has too many processes ($NB_PROCESS)" > $OUT_FILE
    my_own_exit "2"
fi

# CONSERVER !
my_own_exit "0"
