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

DEADLINE=14
# First, check if ESET EFS is installed
if dpkg-query -s efs | grep "Status: install ok installed"; then
   if /opt/eset/efs/sbin/lic -s | grep "Status: Activated"; then
     expirationDate=`/opt/eset/efs/sbin/lic -s | sed  -n '4p' | awk -F': ' '{print $2}'`
     if [ $expirationDate ]; then
       expirationTime=`echo $expirationDate | sed -re 's/(.*)-(.*)-(.*)/\2\/\3\/\1/g' | xargs date +"%s" -d`
       currentTime=`date +"%s"`
       timeDiff=$(($expirationTime - $currentTime))
       daysDiff=$(($timeDiff/(3600*24)))
       if [ $daysDiff -le $DEADLINE ]; then
         echo "License expires in $daysDiff days ($expirationDate)" > $OUT_FILE
         my_own_exit "1"
       fi
     else
       echo "License is active" > $OUT_FILE
       my_own_exit "0"
     fi
   else
     echo "License is expired" > $OUT_FILE
     my_own_exit "2"
   fi
else
   echo "ESET EFS not installed" > $OUT_FILE
   my_own_exit "0"
fi

# A CONSERVER !
my_own_exit "0"
