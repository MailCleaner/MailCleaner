#!/usr/bin/env bash

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
LOG_FILE="/var/mailcleaner/log/mailcleaner/updater4mc.log"
LOG_MOD=$(stat --format=%Y $LOG_FILE)
ABANDONED=$(grep "Abandoning update because Git tree " $LOG_FILE | tail -n 1)
# Le jour dernier
if [[ $LOG_MOD -gt $((timestamp - 86400)) ]]; then
    if [[ $ABANDONED != '' ]]; then
        echo "$ABANDONED" >$OUT_FILE
        echo "$ABANDONED"
        if grep -Fq "master" <<<"$ABANDONED"; then
            if grep -Fq "mailcleaner" <<<"$ABANDONED"; then
                my_own_exit "1" # Abandoning update because Git tree at '$SRCDIR' is not on 'master' branch
            else
                my_own_exit "2" # Abandoning update because Git tree at '$rpath' is not on 'master' branch
            fi
        else
            if grep -Fq "mailcleaner" <<<"$ABANDONED"; then
                my_own_exit "3" # Abandoning update because Git tree at '$SRCDIR' is blocking changes
            else
                my_own_exit "4" # Abandoning update because Git tree at '$rpath' is blocking changes
            fi
        fi
    fi
fi

# CONSERVER !
my_own_exit "0"
