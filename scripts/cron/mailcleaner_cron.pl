#!/usr/bin/perl -w
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004-2014 Olivier Diserens <olivier@diserens.ch>
#   Copyright (C) 2015-2017 Florian Billebault <florian.billebault@gmail.com>
#   Copyright (C) 2015-2017 Mentor Reka <reka.mentor@gmail.com>
#
#   This program is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; either version 2 of the License, or
#   (at your option) any later version.
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
#
#   This is the main system cron. To be run every 15 minutes
#
#   WARNING : When forking processes, DO NOT FORGET TO EXIT THE PROCESS

use strict;
use DBI;
use LWP::UserAgent;
use Getopt::Std;

my $cron_occurence=15;  # in minutes..
my $itsmidnight=0;
my $itstime=0;
my $itsweekday=0;
my $itsmonthday=0;

my %config = readConfig("/etc/mailcleaner.conf");
my $lockfile = '/var/mailcleaner/spool/tmp/mailcleaner_cron.lock';

require "$config{'SRCDIR'}/lib/lib_utils.pl";
our $logmode = 1;
our $logfile = $config{"VARDIR"} . "/log/mailcleaner/mailcleaner_cron-" . DateTime->now->ymd . ".log";

_log("Starting cron run");

# Anti-breakdown for MailCleaner services
if (defined($config{'REGISTERED'}) && $config{'REGISTERED'} == "1") {
    _log("Launching anti-breakdown");
	system($config{'SRCDIR'}."/scripts/cron/anti-breakdown.pl &>> /dev/null");
}
my $mcDataServicesAvailable = 1;
$mcDataServicesAvailable = 0 if ( -e '/var/tmp/mc_checks_data.ko' );

sub usage() {
  print STDERR << "EOF";
usage: $0 [-Rh]

-R     : do NOT randomize start of script
-h     : display usage
EOF
  exit;
}

my %options=();
getopts(":Rh", \%options);
my $randomize_option=" -r";
if (defined $options{R}) {
  $randomize_option="";
}
if (defined $options{h}) {
  usage();
}


########################################
########################################
# process $cron_occurence minutes jobs #
########################################
########################################

_log("Launching 15 minutes cron");
#################################################################################
## check for important processes actually not running and restart them if needed
#################################################################################

my $res = `$config{'SRCDIR'}/bin/get_status.pl -s`;
my %proc =();
my $tmp;
%proc = ( 
        'exim_stage1' => 0,
        'exim_stage2' => 0,
        'exim_stage4' => 0,
        'apache' => 0,
        'mailscanner' => 0,
        'mysql_master' => 0,
        'mysql_slave' => 0,
        'snmpd' => 0,
        'greylistd' => 0,
        'cron' => 0,
        'preftdaemon' => 0,
        'spamd' => 0,
        'clamd' => 0,
        'clamspamd' => 0,
        'spamhandler' => 0,
        );
($tmp, $proc{'exim_stage1'}, $proc{'exim_stage2'}, $proc{'exim_stage4'}, $proc{'apache'}, $proc{'mailscanner'}, $proc{'mysql_master'}, $proc{'mysql_slave'}, $proc{'snmpd'},$proc{'greylistd'},$proc{'cron'},$proc{'preftdaemon'},$proc{'spamd'},$proc{'clamd'},$proc{'clamspamd'},$proc{'spamhandler'}) = split('\|', $res);

foreach my $key (keys %proc) {
  if ($proc{$key} < 1) {
    if (! -e "/tmp/rotate.lock") {
      print "Process: $key NOT RUNNING !\n trying to start it...\n";
      if ( my $pid_restart = fork) {
      } elsif (defined $pid_restart) {
       system($config{'SRCDIR'}."/etc/init.d/$key start");
       exit;
      }
    }
  }
}

my $cmd = "grep 'Pre Filters' $config{'SRCDIR'}/etc/mailscanner/MailScanner.conf | grep 'Commtouch'";
my $hascommtouch=`$cmd`;
if ($hascommtouch ne '') {
  # check commtouch
  $cmd = "grep 'Commtouch ' ".$config{'VARDIR'}."/log/mailscanner/infolog | grep -v ': Message' | tail -n1 | grep 'timed out'";
  my $istimeout=`$cmd`;
  if ($istimeout ne '') {
    _log("Commtouch module seems timing out.. restarting...");
    system($config{'SRCDIR'}."/etc/init.d/commtouch restart");
    _log("Done");
  }
}

###########################
## check internal SSH keys and install if available and not valid
###########################
if (my $pid_keys = fork) {
} elsif (defined $pid_keys) {
    _log("Checking Internal SSH keys");
    if (system("$config{'SRCDIR'}/bin/internal_access --validate") != 0) {
        _log("Reinstalling Internal keys");
        system("$config{'SRCDIR'}/bin/internal_access --install");
    }
    exit;
}

###########################
## check for services availability
###########################
if (defined($config{'REGISTERED'}) && $config{'REGISTERED'} == "1" && $mcDataServicesAvailable ) {
    _log("Checking services availability");
  if ( my $pid_checkservices = fork) { 
  } elsif (defined $pid_checkservices) {
    system($config{'SRCDIR'}."/bin/check_services.pl ".$randomize_option." > /dev/null");
    exit
  }
}

###########################
## check for system updates
###########################
if (defined($config{'REGISTERED'}) && $config{'REGISTERED'} == "1" && $mcDataServicesAvailable) {
## just purging any dead cvs
##`killall -KILL cvs >/dev/null 2>&1`;

if ( my $pid_updates = fork) {
} elsif (defined $pid_updates) {
    _log("Checking for updates");

  #print "doing system updates...";
  system($config{'SRCDIR'}."/bin/check_update.pl ".$randomize_option);
  #print "done.\n";
  exit;
}
}

#######################################
## check for  updates
#######################################
if (defined($config{'REGISTERED'}) && $config{'REGISTERED'} == "1" && $mcDataServicesAvailable) {
if (my $pid_rules = fork) {
} elsif (defined $pid_rules) {
  _log("Fetching data");
  #print "doing rules updates...";
  system($config{'SRCDIR'}."/bin/fetch_clamspam.sh ".$randomize_option);
  system($config{'SRCDIR'}."/bin/fetch_spamc_rules.sh ".$randomize_option);
  system($config{'SRCDIR'}."/bin/fetch_newsl_rules.sh ".$randomize_option);
  system($config{'SRCDIR'}."/bin/fetch_watchdog_modules.sh ".$randomize_option);
  system($config{'SRCDIR'}."/bin/fetch_watchdog_config.sh ".$randomize_option);
  #print "done.\n";
  exit;
}
}

#######################################
## check for RBLs, ClamAV, binary updates
#######################################
if (defined($config{'REGISTERED'}) && $config{'REGISTERED'} == "1" && $mcDataServicesAvailable) {
   _log("Fetching more data");
   system($config{'SRCDIR'}."/bin/fetch_rbls.sh ".$randomize_option);
   system($config{'SRCDIR'}."/bin/fetch_clamav.sh ".$randomize_option);
   system($config{'SRCDIR'}."/bin/fetch_binary.sh ".$randomize_option);
}


#######################################
## check for MailCleaner binary
#######################################
if (defined($config{'REGISTERED'}) && $config{'REGISTERED'} == "1" && -e $config{'VARDIR'}."/run/mailcleaner.binary") {
   system("cat ".$config{'VARDIR'}."/run/mailcleaner.binary | xargs ".$config{'SRCDIR'}."/etc/exim/mc_binary/mailcleaner-binary &>> /dev/null");
   system("rm -rf ".$config{'VARDIR'}."/run/mailcleaner.binary &>> /dev/null");
   system("touch ".$config{'VARDIR'}."/run/mailcleaner.rn  &>> /dev/null");
}



#####################################
# end $cron_occurence  minutes jobs #
#####################################

#######################
#######################
# process hourly jobs #
#######################
#######################

my $minute = `date +%M`;
if ($minute >=0 && $minute < $cron_occurence or 1) {
  _log("Starting hourly jobs");
    
  ######################
  ## update anti-viruses
  ######################
  if (my $pid_av = fork) {
  } elsif (defined $pid_av && $mcDataServicesAvailable) {  
    _log("updating anti-viruses...");
    system($config{'SRCDIR'}."/scripts/cron/update_antivirus.sh");
    _log("done updating anti-viruses.");
    #system($config{'SRCDIR'}."/etc/init.d/mailscanner restart >/dev/null 2>&1");
    exit;
  }

  ##############
  ## learn bayes
  ##############
  if (defined($config{'REGISTERED'}) && $config{'REGISTERED'} == "1") {
    if (my $pid_learn = fork) {
    } elsif (defined $pid_learn && $mcDataServicesAvailable) {
      _log("Autolearning bayes...");
      system($config{'SRCDIR'}."/bin/fetch_autolearn.sh ".$randomize_option);
      _log("Done autolearning bayes");
      exit;
    }
  }

  ###############
  ## resync spams
  ###############
  if (my $pid_syncspam = fork) {
  } elsif (defined $pid_syncspam) {
    _log("Syncing spams");
    system($config{'SRCDIR'}."/bin/resync_spams.pl >> ".$config{'VARDIR'}."/log/mailcleaner/spam_sync.log");
    _log("Done syncing spams");
    exit;
  }

  ####################
  ## import DMARC logs
  ####################
  if (my $pid_importdmarc = fork) {
  } elsif (defined $pid_importdmarc) {
    _log("Importing DMARC");
    system($config{'SRCDIR'}."/scripts/cron/dmarc_import.sh &>> /dev/null");
    _log("Done importing dmarc");
    exit;
  }

  _log("Finished hourly jobs");
}

###################
# end hourly jobs #
###################

###########################################################################
## connect to db to fetch start time and days for daily/weekly/monthly jobs
###########################################################################
# we may have done only one query for the time instead of repeating it
# but in future we may think of different times for daily/weekly or monthly jobs

my $slave_dbh = DBI->connect("DBI:mysql:database=mc_config;mysql_socket=$config{'VARDIR'}/run/mysql_slave/mysqld.sock",
                                        "mailcleaner","$config{'MYMAILCLEANERPWD'}", {RaiseError => 0, PrintError => 1} );
if (!$slave_dbh) {
  _log("ERROR: no slave database found on this system, restarting...");
  ## try to properly kill all databases
  my $cmd = $config{'SRCDIR'}."/etc/init.d/mysql_slave stop";
  `$cmd`;
  $cmd = $config{'SRCDIR'}."/etc/init.d/mysql_master stop";
  `$cmd`;
  sleep 5;
  ## kill them hard
  $cmd = "killall -KILL mysqld mysqld_safe";
  `$cmd`;
  sleep 2;
  ## and restart them
  $cmd = $config{'SRCDIR'}."/etc/init.d/mysql_master start";
  `$cmd`;
  sleep 2;
  $cmd = $config{'SRCDIR'}."/etc/init.d/mysql_slave start";
  `$cmd`;
  exit 1;
}

my $sth =  $slave_dbh->prepare("SELECT hostid FROM system_conf WHERE (0=HOUR(NOW())) AND ((MINUTE(NOW()) >= 0) AND (MINUTE(NOW()) < 0+$cron_occurence))");
$sth->execute() or die ("error in SELECT");
if ($sth->rows > 0) {
    $itsmidnight=1;
}
$sth->finish();

$sth =  $slave_dbh->prepare("SELECT hostid FROM system_conf WHERE (HOUR(cron_time)=HOUR(NOW())) AND ((MINUTE(NOW()) >= MINUTE(cron_time)) AND (MINUTE(NOW()) < MINUTE(cron_time)+$cron_occurence))");
$sth->execute() or die ("error in SELECT");
if ($sth->rows > 0) {
	$itstime=1;
}
$sth->finish();

$sth =  $slave_dbh->prepare("SELECT hostid FROM system_conf WHERE (DAYOFWEEK(NOW())=cron_weekday AND HOUR(cron_time)=HOUR(NOW())) AND ((MINUTE(NOW()) >= MINUTE(cron_time)) AND (MINUTE(NOW()) < MINUTE(cron_time)+$cron_occurence))");
$sth->execute() or die ("error in SELECT");
if ($sth->rows > 0) {
  $itsweekday=1;
}
$sth->finish();

$sth =  $slave_dbh->prepare("SELECT hostid FROM system_conf WHERE (DAYOFMONTH(NOW())=cron_monthday AND HOUR(cron_time)=HOUR(NOW())) AND ((MINUTE(NOW()) >= MINUTE(cron_time)) AND (MINUTE(NOW()) < MINUTE(cron_time)+$cron_occurence))");
$sth->execute() or die ("error in SELECT");
if ($sth->rows > 0) {
  $itsmonthday=1;
}
$sth->finish();	
if (defined $slave_dbh) {
  $slave_dbh->disconnect();
}

######################
######################
# process daily jobs #
######################
######################

if ($itsmidnight or 1) {

  ###############
  ## log rotation
  ###############
  ## first do the log rotation and NOT fork as it shut down mysql_slave which is used by others scripts
  _log("Rotating logs...");
  system("touch /tmp/rotate.lock");
  system($config{'SRCDIR'}."/scripts/cron/rotate_logs.sh &>> /dev/null");
  system("rm /tmp/rotate.lock");
  _log("Done rotating logs");

  ##########################
  ## db.root update
  ##########################
  if (my $pid_clsp = fork) {
  } elsif (defined $pid_clsp) {
    _log("Updating db.root...");
    system("wget -q --no-check-certificate https://www.internic.net/domain/named.root -O /etc/bind/db.root && rndc reload >/dev/null 2>&1");
    _log("db.root updated");
    exit;
  }


  ##########################
  ## spam quarantine cleanup
  ##########################
  if (my $pid_clsp = fork) {
  } elsif (defined $pid_clsp) {
    _log("Cleaning spam quarantine...");
    system($config{'SRCDIR'}."/scripts/cron/clean_spam_quarantine.pl >/dev/null 2>&1");
    _log("Done cleaning spam quarantine");
    exit;
  }

  ###########################
  ## virus quarantine cleanup
  ###########################
  if (my $pid_clvi = fork) {
  } elsif (defined $pid_clvi) {
    _log("Cleaning virus quarantine...");
    system($config{'SRCDIR'}."/scripts/cron/clean_virus_quarantine.pl >/dev/null 2>&1");
    _log("Done cleaning virus quarantine");
    exit;
  }

  ###########################
  ## network antispam updates
  ###########################
  if (my $pid_asdis = fork) {
  } elsif (defined $pid_asdis) {
    _log("Discovering antispam...");
    system($config{'SRCDIR'}."/scripts/cron/antispam_discovers.sh >/dev/null 2>&1");
    _log("Done discovering antispam");
    exit;
  }

  ########################
  ## expire bayes database
  ########################
  if (my $pid_bayes = fork) {
  } elsif (defined $pid_bayes) {
    _log("Expiring bayes databases...");
    system($config{'SRCDIR'}."/scripts/cron/expire_bayes.sh");
    _log("Done expiring bayes databases");
    exit;
  }
  
  ################################
  ## generate monthly/yearly stats
  ################################
  if (my $pid_bayes = fork) {
  } elsif (defined $pid_bayes) {
    _log("Updating monthly/yearly graphs...");
    system($config{'SRCDIR'}."/bin/collect_rrd_stats.pl daily");
    _log("Done updating monthly/yearly graphs");
    exit;
  }

  if ( defined($config{'REGISTERED'}) && $config{'REGISTERED'} && $mcDataServicesAvailable == "1" ){
      if (my $pid_pushstats = fork) {
      } elsif (defined $pid_pushstats) {
          _log("Pushing stats...");
          system($config{'SRCDIR'}."/bin/push_stats.sh ".$randomize_option);
          system($config{'SRCDIR'}."/bin/push_config.sh ".$randomize_option);
          _log("Done pushing stats");
          exit;
      }
  }

  ##################
  ## clean up spools
  ##################

  if (my $pid_cleanspool = fork) {
  } elsif (defined $pid_cleanspool) {
    _log("Cleaning spools...");
    system($config{'SRCDIR'}."/scripts/cron/clean_spool.sh");
    _log("Done cleaning spools");
    exit;
  }

  ##################################
  ## generate and send DMARC reports
  ##################################

  if (my $pid_dmarcreports = fork) {
  } elsif (defined $pid_dmarcreports) {
    _log("Generating and sending DMARC reports...");
    system($config{'SRCDIR'}."/scripts/cron/dmarc_reports.sh");
    _log("Done generating and sending DMARC reports");
    exit;
  }

  ##################################
  ## get the autoconf
  ##################################
  _log("Getting the last configuration for autoconf...");
  if (defined($config{'REGISTERED'}) && $config{'REGISTERED'} == "1" && defined($config{'ISMASTER'}) && $config{'ISMASTER'} eq "Y") {
	if ( -e $config{'VARDIR'}."/spool/mailcleaner/mc-autoconf"  && $mcDataServicesAvailable) {
	        system($config{'SRCDIR'}."/bin/fetch_autoconf.sh &>> /dev/null");
		system($config{'SRCDIR'}."/etc/autoconf/prepare_sqlconf.sh &>> /dev/null");
	}
  }
  _log("Done getting the last configuration for autoconf...");

  ##################################
  ## send anon
  ##################################
  _log("Sending anonymous data...");
  if (defined($config{'REGISTERED'}) && $config{'REGISTERED'} == "2") {
        system($config{'SRCDIR'}."/bin/send_anon.sh &>> /dev/null");
  }


}

if ($itstime or 1) {
  ######################
  ## send daily sumaries
  ######################
  if (my $pid_ssum = fork) {	
  } elsif (defined $pid_ssum) {
    _log("Sending daily summaries...");
    my $date = `date '+%Y%m%d'`;
    chomp($date);
    system("echo 'Sending daily summaries:' >> ".$config{'VARDIR'}."/log/mailcleaner/summaries_$date.log");
    system($config{'SRCDIR'}."/bin/send_summary.pl -a 3 1 >> ".$config{'VARDIR'}."/log/mailcleaner/summaries_$date.log");
    _log("Done daily summaries");
    exit;
  }
}


#######################
#######################
# process weekly jobs #
#######################
#######################

if ($itsweekday or 1) {

  #######################
  ## send weekly sumaries
  #######################
  if (my $pid_ssumw = fork) {
  } elsif (defined $pid_ssumw) {
    _log("sending weekly summaries...");
    my $date = `date '+%Y%m%d'`;
    chomp($date);
    system("echo 'Sending weekly summaries:' >> ".$config{'VARDIR'}."/log/mailcleaner/summaries_$date.log");
    system($config{'SRCDIR'}."/bin/send_summary.pl -a 2 7 >> ".$config{'VARDIR'}."/log/mailcleaner/summaries_$date.log");
    _log("done sending weekly summaries.");
    exit;
  }

}

########################
########################
# process monthly jobs #
########################
########################

if ($itsmonthday) {

  #######################
  ## send monthly sumaries
  #######################
  if (my $pid_ssumm = fork) {
  } elsif (defined $pid_ssumm) {
    _log("Sending monthly summaries...");
    my $date = `date '+%Y%m%d'`;
    chomp($date);
    system("echo 'Sending monthly summaries:' >> ".$config{'VARDIR'}."/log/mailcleaner/summaries_$date.log");
    system($config{'SRCDIR'}."/bin/send_summary.pl -a 1 31 >> ".$config{'VARDIR'}."/log/mailcleaner/summaries_$date.log");
    _log("Done sending monthly summaries");
    exit;
  }
}

######################
######################
# MC restart         #
######################
######################
if ( -e $config{'VARDIR'}."/run/mailcleaner.rn") {
    _log("Restarting Mailcleaner...");
    system($config{'SRCDIR'}."/etc/init.d/mailcleaner restart &>> /dev/null");
	system("rm -rf ".$config{'VARDIR'}."/run/mailcleaner.rn  &>> /dev/null");
}

####################################################################################

sub readConfig {       # Reads configuration file given as argument.
  my $configfile = shift;
  my %config;
  my ($var, $value);

  open CONFIG, $configfile or die "Cannot open $configfile: $!\n";
  while (<CONFIG>) {
    chomp;                  # no newline
    s/#.*$//;                # no comments
    s/^\*.*$//;             # no comments
    s/;.*$//;                # no comments
    s/^\s+//;               # no leading white
    s/\s+$//;               # no trailing white
    next unless length;     # anything left?
    my ($var, $value) = split(/\s*=\s*/, $_, 2);
    $config{$var} = $value;
  }
  close CONFIG;
  return %config;
}
