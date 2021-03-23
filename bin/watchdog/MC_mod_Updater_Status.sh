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

# If file doesn't exist, things are fine
STATUSFILE=/var/mailcleaner/spool/mailcleaner/updater4mc.status
if [ -e $STATUSFILE ]; then
    STATUS="`cat $STATUSFILE`"
    # If running for longer than an hour or exited without cleaning
    if [[ $STATUS == "Running" ]]; then
        if [ "$(expr $(stat -c %Y $STATUSFILE) + 3600)" -lt "$(date +%s)" ]; then
            echo "Updater4MC still running after 1 hour or exited without completing" >> $OUT_FILE
            my_own_exit "1"
        fi
    # If it specifically failed an update
    elif grep -q "^Failed " <<< `echo "$STATUS"`; then
        my_own_exit "2"
    # Misc error code to accomadate future errors
    elif grep -q "Not a valid MailCleaner Installation: no conf file" <<< `echo "$STATUS"`; then
        my_own_exit "3"
    else
        echo "Updater4MC encountered error: $STATUS" >> $OUT_FILE
        my_own_exit "255"
    fi
fi

my_own_exit "0"
