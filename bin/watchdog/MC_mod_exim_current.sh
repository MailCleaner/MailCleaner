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
EXIT=0
if [[ $(grep authresults /usr/mailcleaner/etc/exim/exim_stage1.conf_template|wc -l) = 0 ]]
then
    echo "/usr/mailcleaner/etc/exim/exim_stage1.conf_template is not up to date" >> $OUT_FILE
    EXIT=1
fi

if [[ $(dpkg -l |grep mc-exim | sed -e 's/.*4.96.*/4.96/') != '4.96' ]]
then
    echo "mc_exim is not in version 4.96" >> $OUT_FILE
    EXIT=1
fi

if [[ ${EXIT} = 1 ]]
then
    my_own_exit "1"
fi

# CONSERVER !
my_own_exit "0"
