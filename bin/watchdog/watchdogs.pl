#!/usr/bin/perl -w
use strict;

use File::Basename;
use POSIX;

my $script_name                     = basename($0);
$script_name                        =~ s/\.[^.]*$//;
my $mode                            = $ARGV[0] || '';

use constant WATCHDOG_BIN           => '/usr/mailcleaner/bin/watchdog/';
use constant WATCHDOG_CFG           => '/usr/mailcleaner/etc/watchdog/';
use constant WATCHDOG_TMP           => '/var/mailcleaner/spool/watchdog/';
use constant WATCHDOG_PID_FOLDER	=> '/var/mailcleaner/run/watchdog/';

my $time = time();
my $WATCHDOG_OUTFILE		        = WATCHDOG_TMP . $script_name. '___' .$mode. '_' .$time. '.out';

# Liste erreur
# 1	=> ne peut pas cd dans le dossier WATCHDOG_BIN
# 2	=> n'a pas pu lancer une sous tache
# 3	=> n'a pas pu forker

##################################################################################################################################################
# SUBS
######
# Définition de valeurs par défaut pour tous les paramètres configurables
sub valeur_par_defaut {
	my ($current_process) = @_;

	$current_process->{TIMEOUT}         = 0;
	$current_process->{EXEC_MODE}       = 'Parralel';
	$current_process->{TAGS}            = 'All';
	$current_process->{ERROR_LEVEL}     = 0;
	$current_process->{DELAY}           = 0;
	$current_process->{NICE}            = 10;
}

###
# Retourne 0 si ne peut pas ouvrir le fichier (1, @contenu_du_fichier) sinon
sub Slurp_file {
    my ($file) = @_;
    my @contains = ();

    ###
    # lecture totale du fichier avant de l'analyser
    if ( ! open(FILE, '<', $file) ) {
        return (0, @contains);
    }

    @contains = <FILE>;
    close(FILE);
    chomp(@contains);

    return(1, @contains)
}

###
# Charge la configuration depuis le fichier $fichier_params.
# Chaque ligne du type cle=valeur ajoute ce couple cle/valeur au hash %$current_process
sub chargement_params {
	my ($fichier_params, $current_process) = @_;

	my @return;
	my $cle;
	my $valeur;

	# On assigne des valeurs par défaut pour tous les paramètres configurables et on les surchargera si besoin
	valeur_par_defaut($current_process);

    ###
   	# lecture totale du fichier de configuration avant de l'analyser
    my ($exists, @contains) = Slurp_file($fichier_params);
    if ( $exists == 0 ) {
        $current_process->{NOFILECONF} = 1;

        return;
    }


    ###
    # Recherche des cles/valeurs pour les assigner au hash
    foreach (@contains) {
        chomp();
    	next if ( /^\s*#/ ) ;
	    next if ( /^\s*$/ ) ;

   		($cle, $valeur) = split('=', $_);
    	chomp($cle);chomp($valeur);

###
# Mecanisme d'inclusion de fichier de configuration externe
# ATTENTION aux references circulaires
#if ( $cle eq "INCLUDE" ) {  _chargement_params($valeur, $current_process) }
		###
		# partie ds cles generiques
		if ( $cle eq 'TIMEOUT' )			{ $current_process->{TIMEOUT}		= $valeur }
		if ( $cle eq 'EXEC_MODE' )			{ $current_process->{EXEC_MODE}		= $valeur }
		if ( $cle eq 'TAGS' )				{ $current_process->{TAGS}			= $valeur }
		if ( $cle eq 'ERROR_LEVEL' )		{ $current_process->{ERROR_LEVEL}	= $valeur }
		if ( $cle eq 'DELAY' )				{ $current_process->{DELAY}			= $valeur }
		if ( $cle eq 'NICE' )				{ $current_process->{NICE}			= $valeur }
	}
}

###
# Rajoute une ligne au fichier $MYOUTFILE
sub MC_log {
    my $MYOUTFILE;

    # Fichier de sortie pour Watchdog
    open($MYOUTFILE, '>>', $WATCHDOG_OUTFILE);

    my ($data) = @_;
    print $MYOUTFILE $data ."\n";

    close $MYOUTFILE;
}


##################################################################################################################################################
# MAIN
######
my @processes	    	= ();
my @processes_seq   	= ();
my @processes_par   	= ();
my @launched_process    = ();
my @ignore_process	    = qw/watchdogs.pl watchdog_report.sh/;

# Création du répertoire d'écriture si besoin
my @old = ();
if( ! -d WATCHDOG_TMP  ) {
    mkdir(WATCHDOG_TMP, 0755);
} else {
    @old = glob(WATCHDOG_TMP."*");
}

#####
# Lancement des watchdog-tools

# récupérer le liste des fichiers du répertoire $watchdog_tools
chdir(WATCHDOG_BIN) or exit(1);
my @files	= glob('MC_mod_*');
push(@files,glob('EE_mod_*'));
push(@files,glob('CUSTOM_mod_*'));
@files		= sort { $a cmp $b } @files;

# Pour chaque fichier
foreach my $file (@files) {
	my %current_process	= ();
	# Supprimer l'extension du nom de fichier
	$current_process{file}                  = $file;
	$current_process{file_no_extension}     = $file;
	$current_process{file_no_extension}     =~ s/\.[^\.]*$//;
	# Supprimer le fichier ancien
	my @remaining = ();
	foreach (@old) {
		if ($_ =~ m/$current_process{file_no_extension}/) {
			unlink($_);
		} else {
			push(@remaining,$_);
		}
	}
	@old = @remaining;
	if (-e WATCHDOG_CFG.$current_process{file_no_extension}.'.disabled') {
		print STDERR "Ignoring $current_process{file_no_extension} because it is disabled by '" . WATCHDOG_CFG.$current_process{file_no_extension}.'.disabled' . "\n";
		next;
	}
	$current_process{pid_file}	        	=  WATCHDOG_PID_FOLDER.$current_process{file_no_extension}.'.pid';
	$current_process{configuration_file}	=  WATCHDOG_CFG.$current_process{file_no_extension}.'.conf';
	$current_process{TIMEOUT}		        =  5;
	$current_process{EXEC_MODE}	        	=  'Sequence';

	#####
	# Récupération de la configuration
	chargement_params($current_process{configuration_file}, \%current_process);

	if ($current_process{EXEC_MODE} eq 'Parralel')	{ push(@processes_par, \%current_process); }
	else                    						{ push(@processes_seq, \%current_process); }
}

if (scalar(@old)) {
	foreach (@old) {
		if ((-M "$_") > 1) {
			unlink($_);
		} elsif ($_ =~ m/watchdogs___(All|oneday|dix)_(\d+).out/) {
			unlink($_) unless ($2 eq $time);
		}
	}
}

# Un seul tableau de tous les process dans lequel tous les process à lancer en // sont au début
push(@processes, @processes_par, @processes_seq);

#####
# Lancement du process en // ou en sequentiel
foreach my $current_process (@processes) {
    next if grep( /^$current_process->{file}$/, @ignore_process );
    next if ( $current_process->{file} =~ /~$/ );
    next if ( ! -f $current_process->{file} );
    # Lancement du processus en fonction du mode
    next unless ( ($mode eq 'All') || ($current_process->{TAGS} =~ m/$mode/) );

	my $pid =  'Not_a_pid';

	# vérifier si déjà lancé
	if ( -f $current_process->{pid_file}) {
        MC_log("MODULE : $current_process->{file}\nRETURN CODE : N/A\nRAPPORT : Pid file found. Skipped");

		next;
	}

	# Lancement du process
	if ( -x $current_process->{file} ) {

		# Construction de la commande
		my $commande = '';
		if ($current_process->{TIMEOUT} != 0) {
			$commande = "timeout $current_process->{TIMEOUT} ";
		}
		$commande .= "nice --$current_process->{NICE} ". WATCHDOG_BIN.$current_process->{file} . ' ' .$mode.  ' >> /dev/null 2>&1';


		# Execution //
		if ($current_process->{EXEC_MODE} eq 'Parralel') {
			# récupérer le pid
			$pid = fork();
			exit(3) unless defined($pid);
			if (!$pid) {  # child
				exec $commande;
				exit(2);
			}

			$current_process->{pid} = $pid;
			# créer le fichier process.pid
			open(OUTFILE, '>', $current_process->{pid_file});
			print OUTFILE $pid;
			close(OUTFILE);
		# Execution séquentielle
		} else {
			system(split(/ /, $commande));
			$current_process->{pid} = 'NA';
			$current_process->{return_code}	= $?>>8;
			unlink $current_process->{pid_file};
		}

		# Liste des fichiers à récupérer
		push(@launched_process, $current_process);
	}
}

exit(0);
