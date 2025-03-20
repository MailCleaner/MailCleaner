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

# If file doesn't exist, things are fine
STATUSFILE=/var/mailcleaner/spool/resync/fail_count
if [ -e $STATUSFILE ]; then
    COUNT="$(cat $STATUSFILE)"
    # If running for longer than an hour or exited without cleaning
    if [[ "$COUNT" -ge 6 ]]; then
        echo "DBs are out of sync and automatic resync has failed for 1 whole day (6 attempts). No longer attempting automatic correction. Recent configuration changes will not have been be applied. Try manually running /usr/mailcleaner/bin/resync_db.sh" >>$OUT_FILE
        my_own_exit "2"
    # If it specifically failed an update
    else
        echo "DBs are out of sync and automatic resync has failed $COUNT time(s). A total of 6 automatic attempts to resync will be made. Recent configuration changes will not have been be applied. Try manually running /usr/mailcleaner/bin/resync_db.sh" >>$OUT_FILE
        my_own_exit "1"
    fi
fi

my_own_exit "0"
