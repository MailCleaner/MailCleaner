#!/usr/bin/perl -w
use strict;
use File::Basename;

# Récupération du nom du script
my $script_name         = basename($0);
my $script_name_no_ext  = $script_name;
$script_name_no_ext     =~ s/\.[^.]*$//;

# Timestamp => fichier unique et temps d'exécution
my $timestamp           = time();

# Fichier PID et pour écrire le résultat 
my $PID_FILE   = '/var/mailcleaner/run/watchdog/' . $script_name_no_ext . '.pid';
my $OUT_FILE   = '/var/mailcleaner/spool/watchdog/' .$script_name_no_ext. '_' .$timestamp. '.out';

open my $file, '>', $OUT_FILE;

# Fonction de gestion de la sortie du script
# A appeler également en cas de succès
# Efface le fichier PID et renseigne le temps d'éxécution et le return code dans le fichier de sortie
sub my_own_exit {
    my ($exit_code) = @_;
    $exit_code = 0  if ( ! defined ($exit_code) );

    if ( -e $PID_FILE ) {
        unlink $PID_FILE;
    }

    my $ELAPSED = time() - $timestamp;
    print $file "EXEC : $ELAPSED\n";
    print $file "RC : $exit_code\n";

    close $file;

    exit($exit_code);
}


#### MAIN
#### Lorsque le module a trouvé une erreur, il est censé sortir avec my_own_exit "#ERREUR" (avec #ERREUR : chiffre : retour de la commande)





# A CONSERVER !
my_own_exit(0);
