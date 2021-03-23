#!/bin/bash
# Récupération du nom du script
script_name=$(basename $0);
script_name_no_ext=${script_name%.*}
# Timestamp => fichier unique et temps d'exécution
timestamp=`date +%s`
# Fichier PID et pour écrire le résultat
PID_FILE="/var/mailcleaner/run/watchdog/$script_name_no_ext.pid"
OUT_FILE="/var/mailcleaner/spool/watchdog/${script_name_no_ext}_$timestamp.out"
rc=0

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

if [ -f $OUT_FILE ]; then
	rm $OUT_FILE
fi

queue1=`/opt/exim4/bin/exiqgrep -C /usr/mailcleaner/etc/exim/exim_stage1.conf -c | cut -d' ' -f1`
queue2=`/opt/exim4/bin/exiqgrep -C /usr/mailcleaner/etc/exim/exim_stage2.conf -c | cut -d' ' -f1`
queue4=`/opt/exim4/bin/exiqgrep -C /usr/mailcleaner/etc/exim/exim_stage4.conf -c | cut -d' ' -f1`

if [[ $queue1 -gt 300 ]]; then
	echo "More than 300 mails in queue1" >> $OUT_FILE
	rc=1
fi

if [[ $queue2 -gt 200 ]]; then
	echo "More than 200 mails in queue2" >> $OUT_FILE
	rc=1
fi

if [[ $queue4 -gt 800 ]]; then
	echo "More than 800 mails in queue4" >> $OUT_FILE
	rc=1
else
	if [[ $queue4 -gt 300 ]]; then
		if [[ $queue2 -gt 50 ]]; then
			echo "More than 300 mails in queue4 and 50 mails in queue2" >> $OUT_FILE
			rc=1
		fi
	else
		if [[ $queue4 -gt 100 ]]; then
			if [[ $queue2 -gt 100 ]]; then
				echo "More than 100 mails in queue4 and 100 mails in queue2" >> $OUT_FILE
				rc=1
			fi
		fi
	fi
fi

# CONSERVER !
my_own_exit "$rc"
