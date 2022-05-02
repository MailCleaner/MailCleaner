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

#err_report() {
#    echo "Error on line $1"
#}
#trap 'err_report $LINENO' ERR

#### MAIN
#### Lorsque le module a trouvé une erreur, il est censé sortir avec my_own_exit "#ERREUR" (avec #ERREUR : chiffre : retour de la commande)
DEADLINE=7
# First, check if Kaspersky is installed
if dpkg-query -s kaspersky-64-2.0 | grep "Status: install ok installed"; then
   if ! ls /opt/kaspersky-updater/bin/*.key >/dev/null 2>&1; then 
        echo "License key not found" > $OUT_FILE
        my_own_exit "1"
   fi
   if /opt/kaspersky-updater/bin/keepup2date8.sh --licinfo --simplelic | grep "0x00000000. Success"; then 
     expirationDate=`/opt/kaspersky-updater/bin/keepup2date8.sh --licinfo --simplelic | sed  -n '8p' | awk -F': ' '{print $2}'`
     expirationTime=`echo $expirationDate | sed -re 's/(.*)\/(.*)\/(.*)/\2\/\1\/\3/g' | xargs date +"%s" -d`
     currentTime=`date +"%s"`
     timeDiff=$(($expirationTime - $currentTime))
     daysDiff=$(($timeDiff/(3600*24)))
     if [ $daysDiff -le $DEADLINE ]; then
       echo "License expired in $daysDiff days ($expirationDate)" > $OUT_FILE
       my_own_exit "1"
     fi
   fi
fi


# A CONSERVER !
my_own_exit "0"
