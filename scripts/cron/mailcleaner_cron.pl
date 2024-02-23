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

use strict;
use DBI;
use LWP::UserAgent;
use Getopt::Std;
require '/usr/mailcleaner/lib/lib_utils.pl';

my $cron_occurence=15;  # in minutes..
my $itsmidnight=0;
my $itstime=0;
my $itsweekday=0;
my $itsmonthday=0;
my $minute = `date +%M`;
my @wait = ();

my %config = readConfig("/etc/mailcleaner.conf");
my $lockfile = 'mailcleaner_cron.lock';

# Anti-breakdown for MailCleaner services
if (defined($config{'REGISTERED'}) && $config{'REGISTERED'} == "1") {
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

############################################
# Restart Fail2Ban if inaccessible/stopped #
############################################
unless ( -e "$config{'VARDIR'}/run/fail2ban.disabled" ) {
  my $timout = 5;
  my $failed = 0;
  my $cmd = "/usr/bin/fail2ban-client status 2>/dev/null";
  eval {
    local $SIG{ALRM} = sub { die "timeout\n" };
    alarm $timout;
    system($cmd);
    $failed=1 if ($?);
    alarm 0;
  };
  if ($@) {
    print("fail2ban not responding, restarting...\n");
    system("$config{'SRCDIR'}/etc/init.d/fail2ban restart");
  } elsif ($failed) {
    print("fail2ban not running, restarting fail2ban...\n");
    system("$config{'SRCDIR'}/etc/init.d/fail2ban restart");
  }
}

###########################
# We need the DB to be okay to make sure we are dropping
# the right informations in configuration files
###########################
# check for lock
my $resync_lock = 'resync_db.lock';
my $rc = create_lockfile($resync_lock, undef, time+15*60, 'resync_db');
if ($rc != 0) {

  #######################
  # check and resync DB #
  #######################
  system("$config{'SRCDIR'}/bin/resync_db.sh -C");
  remove_lockfile($resync_lock, undef);
}

###########################################################################
## connect to db to fetch start time and days for daily/weekly/monthly jobs
###########################################################################
# we may have done only one query for the time instead of repeating it
# but in future we may think of different times for daily/weekly or monthly jobs

my $slave_dbh = DBI->connect("DBI:mysql:database=mc_config;mysql_socket=$config{'VARDIR'}/run/mysql_slave/mysqld.sock",
                                        "mailcleaner","$config{'MYMAILCLEANERPWD'}", {RaiseError => 0, PrintError => 1} );
if (!$slave_dbh) {
  printf ("ERROR: no slave database found on this system ! \n");
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


#############################################
# Skip minute/hourly tasks if lockfile exists
#############################################
my $skip = 0;
$rc = create_lockfile($lockfile, undef, time+15*60, 'mailcleaner_cron');
if ($rc==0) {
  print STDERR "Recent Cron already running. Continuing with high-priority tasks only.\n";
  $skip = 1;
}

unless ($skip) {

  ########################################
  # check and remove ClamAv temp folders #
  ########################################
  system("$config{'SRCDIR'}/scripts/cron/clean_clamav_tmp.sh");

  ######################
  ## update anti-viruses
  ######################
  if (my $pid_av = fork) {
    push(@wait,$pid_av);
  } elsif (defined $pid_av && $mcDataServicesAvailable) {
    print "updating anti-viruses...\n";
    system($config{'SRCDIR'}."/scripts/cron/update_antivirus.sh");
    print "done updating anti-viruses.\n";
    #system($config{'SRCDIR'}."/etc/init.d/mailscanner restart >/dev/null 2>&1");
    exit;
  }

  ####################################################
  # check inodes occupation and old counts if needed #
  ####################################################
  system("$config{'SRCDIR'}/scripts/cron/inodes_check.sh");

  ########################################
  ########################################
  # process $cron_occurence minutes jobs #
  ########################################
  ########################################


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
          push(@wait,$pid_restart);
        } elsif (defined $pid_restart) {
          system($config{'SRCDIR'}."/etc/init.d/$key start");
          exit;
        }
      }
    }
  }

  my $cmd = "grep 'Pre Filters' ".$config{'SRCDIR'}."/etc/mailscanner/MailScanner.conf | grep 'Commtouch'";
  my $hascommtouch=`$cmd`;
  if ($hascommtouch ne '') {
    ## check commtouch
    $cmd = "grep 'Commtouch ' ".$config{'VARDIR'}."/log/mailscanner/infolog | grep -v ': Message' | tail -n1 | grep 'timed out'";
    my $istimeout=`$cmd`;
    if ($istimeout ne '') {
      print "Commtouch module seems timing out.. restarting...\n";
      system($config{'SRCDIR'}."/etc/init.d/commtouch restart");
      print "done.\n";
    }
  }

  ###########################
  ## check internal SSH keys and install if available and not valid
  ###########################
  if (my $pid_keys = fork) {
      push(@wait,$pid_keys);
  } elsif (defined $pid_keys) {
      if (system("$config{'SRCDIR'}/bin/internal_access --validate") != 0) {
          system("$config{'SRCDIR'}/bin/internal_access --install")
      }
      exit;
  }

  ###########################
  ## check for services availability
  ###########################
  if (defined($config{'REGISTERED'}) && $config{'REGISTERED'} == "1" && $mcDataServicesAvailable ) {
    if ( my $pid_checkservices = fork) {
      push(@wait,$pid_checkservices);
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
      push(@wait,$pid_updates);
    } elsif (defined $pid_updates) {

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
      push(@wait,$pid_rules);
    } elsif (defined $pid_rules) {
      #print "doing rules updates...";
      system($config{'SRCDIR'}."/bin/fetch_clamspam.sh ".$randomize_option);
      system($config{'SRCDIR'}."/bin/fetch_spamc_rules.sh ".$randomize_option);
      system($config{'SRCDIR'}."/bin/fetch_spamc_modules_conf.sh ".$randomize_option);
      system($config{'SRCDIR'}."/bin/fetch_newsl_rules.sh ".$randomize_option);
      system($config{'SRCDIR'}."/bin/fetch_watchdog_modules.sh ".$randomize_option);
      system($config{'SRCDIR'}."/bin/fetch_watchdog_config.sh ".$randomize_option);
      system($config{'SRCDIR'}."/bin/fetch_administrator.sh ".$randomize_option);

      #print "done.\n";
      exit;
      
    }
  }

  #######################
  #######################
  # process hourly jobs #
  #######################
  #######################

  if ($minute >=0 && $minute < $cron_occurence) {

    ##############
    ## Bayesian
    ##############
    if (defined($config{'REGISTERED'}) && $config{'REGISTERED'} == "1") {
      if (my $pid_learn = fork) {
        push(@wait,$pid_learn);
      } elsif (defined $pid_learn && $mcDataServicesAvailable) {
        #print "doing auto-learn...";
        system($config{'SRCDIR'}."/bin/CDN_fetch_bayes.sh");
        #print "done.\n";
        exit;
      }
    }

    ###############
    ## resync spams
    ###############
    print "syncing spams...\n";
    if (my $pid_syncspam = fork) {
      push(@wait,$pid_syncspam);
    } elsif (defined $pid_syncspam) {
      system($config{'SRCDIR'}."/bin/resync_spams.pl >> ".$config{'VARDIR'}."/log/mailcleaner/spam_sync.log");
      print "done syncing spams.\n";
      exit;
    }

    ####################
    ## import DMARC logs
    ####################
    print "importing dmarc...\n";
    if (my $pid_importdmarc = fork) {
      push(@wait,$pid_importdmarc);
    } elsif (defined $pid_importdmarc) {
      system($config{'SRCDIR'}."/scripts/cron/dmarc_import.sh");
      print "done importing dmarc.\n";
      exit;
    }

  }

  print "Cleaning MailScanner tmp files...\n";
  system($config{'SRCDIR'}."/bin/ms_tmp-cleaner -o 10");

  ###################
  # end hourly jobs #
  ###################
}

######################
######################
# process daily jobs #
######################
######################

if ($itsmidnight) {

  ###############
  ## log rotation
  ###############
  ## first do the log rotation and NOT fork as it shut down mysql_slave which is used by others scripts
  print "rotating logs...\n";
  my $rc = create_lockfile('rotate.lock', '/tmp/', time+4*60*60, 'resync_db');
  if ($rc!=0) {
    system($config{'SRCDIR'}."/scripts/cron/rotate_logs.sh");
    print "done rotating logs.\n";
    remove_lockfile('rotate.lock', '/tmp/');
  }

  ##########################
  ## db.root update
  ##########################
  if (my $pid_rootcert = fork) {
    push(@wait,$pid_rootcert);
  } elsif (defined $pid_rootcert) {
    print "Updating db.root...\n";
    system("wget -q --no-check-certificate https://www.internic.net/domain/named.root -O /etc/bind/db.root && rndc reload >/dev/null 2>&1");
    print "db.root updated.\n";
    exit;
  }


  ##########################
  ## spam quarantine cleanup
  ##########################
  if (my $pid_clsp = fork) {
    push(@wait,$pid_clsp);
  } elsif (defined $pid_clsp) {
    print "cleaning spam quarantine...\n";
    system($config{'SRCDIR'}."/scripts/cron/clean_spam_quarantine.pl >/dev/null 2>&1");
    print "done cleaning spam quarantine.\n";
    exit;
  }

  ###########################
  ## virus quarantine cleanup
  ###########################
  if (my $pid_clvi = fork) {
    push(@wait,$pid_clvi);
  } elsif (defined $pid_clvi) {
    print "cleaning virus quarantine...\n";
    system($config{'SRCDIR'}."/scripts/cron/clean_virus_quarantine.pl >/dev/null 2>&1");
    print "done cleaning virus quarantine.\n";
    exit;
  }

  ###########################
  ## kaspersky cleanup
  ###########################
  if (my $pid_clkav = fork) {
    push(@wait,$pid_clkav);
  } elsif (defined $pid_clkav) {
    print "cleaning Kaspersky temporary files...\n";
    system($config{'SRCDIR'}."/scripts/cron/clean_kav_tmp.sh >/dev/null 2>&1");
    print "done cleaning kaspersky temporary files.\n";
    exit;
  }

  ###########################
  ## network antispam updates
  ###########################
  if (my $pid_asdis = fork) {
    push(@wait,$pid_asdis);
  } elsif (defined $pid_asdis) {
    print "discovering antispam...\n";
    system($config{'SRCDIR'}."/scripts/cron/antispam_discovers.sh >/dev/null 2>&1");
    print "done discovering antispam.\n";
    exit;
  }

  ########################
  ## expire bayes database
  ########################
  if (my $pid_bayes = fork) {
    push(@wait,$pid_bayes);
  } elsif (defined $pid_bayes) {
    print "expiring bayes databases...\n";
    system($config{'SRCDIR'}."/scripts/cron/expire_bayes.sh");
    print "done expiring bayes databases.\n";
    exit;
  }

  ################################
  ## generate monthly/yearly stats
  ################################
  if (my $pid_stats = fork) {
    push(@wait,$pid_stats);
  } elsif (defined $pid_stats) {
    print "updating monthly/yearly graphs...\n";
    system($config{'SRCDIR'}."/bin/collect_rrd_stats.pl daily");
    print "done updating monthly/yearly graphs.\n";
    exit;
  }

  if ( defined($config{'REGISTERED'}) && $config{'REGISTERED'} && $mcDataServicesAvailable == "1" ){
    if (my $pid_pushstats = fork) {
      push(@wait,$pid_pushstats);
    } elsif (defined $pid_pushstats) {
      print "pushing stats...\n";
      system($config{'SRCDIR'}."/bin/push_stats.sh ".$randomize_option);
      system($config{'SRCDIR'}."/bin/push_config.sh ".$randomize_option);
      print "done pushing stats.\n";
      exit;
    }
  }

  ##################
  ## clean up spools
  ##################
  print "cleaning spools...\n";
  if (my $pid_cleanspool = fork) {
    push(@wait,$pid_cleanspool);
  } elsif (defined $pid_cleanspool) {
    system($config{'SRCDIR'}."/scripts/cron/clean_spool.sh");
    print "done cleaning spools.\n";
    exit;
  }

  ##################################
  ## generate and send DMARC reports
  ##################################
  print "generating and sending DMARC reports...\n";
  if (my $pid_dmarcreports = fork) {
    push(@wait,$pid_dmarcreports);
  } elsif (defined $pid_dmarcreports) {
    system($config{'SRCDIR'}."/scripts/cron/dmarc_reports.sh");
    print "done generating and sending DMARC reports...\n";
    exit;
  }

  ##################################
  ## get the autoconf
  ##################################
  print "getting the last conf for autoconf...\n";
  if (defined($config{'REGISTERED'}) && $config{'REGISTERED'} == "1" && defined($config{'ISMASTER'}) && $config{'ISMASTER'} eq "Y") {
	if ( -e $config{'VARDIR'}."/spool/mailcleaner/mc-autoconf"  && $mcDataServicesAvailable) {
	        system($config{'SRCDIR'}."/bin/fetch_autoconf.sh &>> /dev/null");
		system($config{'SRCDIR'}."/etc/autoconf/prepare_sqlconf.sh &>> /dev/null");
	}
  }

  #######################################
  ## check for RBLs, ClamAV, binary updates
  #######################################
  if (defined($config{'REGISTERED'}) && $config{'REGISTERED'} == "1" && $mcDataServicesAvailable) {
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

  ##################################
  ## send anon
  ##################################
  print "sending anon ...\n";
  if (defined($config{'REGISTERED'}) && $config{'REGISTERED'} == "2") {
        system($config{'SRCDIR'}."/bin/send_anon.sh &>> /dev/null");
  }

}

if ($itstime) {

  ##################################
  ## send watchdog alert
  ##################################
  if (my $pid_swatch = fork) {
    push(@wait,$pid_swatch);
  } elsif (defined $pid_swatch) {
    print "sending watchdog report to support address, if applicable...\n";
    my $date = `date '+%Y%m%d'`;
    chomp($date);
    system("echo 'Sending watchdog alerts:' >> ".$config{'VARDIR'}."/log/mailcleaner/watchdogs.log");
    system($config{'SRCDIR'}."/bin/send_watchdogs.pl >> ".$config{'VARDIR'}."/log/mailcleaner/watchdogs.log");
    print "done watchdog report.\n";
    exit;
  }

  ######################
  ## send daily sumaries
  ######################
  if (my $pid_ssum = fork) {
    push(@wait,$pid_ssum);
  } elsif (defined $pid_ssum) {
    print "sending daily summaries...\n";
    my $date = `date '+%Y%m%d'`;
    chomp($date);
    system("echo 'Sending daily summaries:' >> ".$config{'VARDIR'}."/log/mailcleaner/summaries.log");
    system($config{'SRCDIR'}."/bin/send_summary.pl -a 3 1 >> ".$config{'VARDIR'}."/log/mailcleaner/summaries.log");
    print "done daily summaries.\n";
    exit;
  }
}


#######################
#######################
# process weekly jobs #
#######################
#######################

if ($itsweekday) {

  #######################
  ## send weekly sumaries
  #######################
  if (my $pid_ssumw = fork) {
    push(@wait,$pid_ssumw);
  } elsif (defined $pid_ssumw) {
    print "sending weekly summaries...\n";
    my $date = `date '+%Y%m%d'`;
    chomp($date);
    system("echo 'Sending weekly summaries:' >> ".$config{'VARDIR'}."/log/mailcleaner/summaries.log");
    system($config{'SRCDIR'}."/bin/send_summary.pl -a 2 7 >> ".$config{'VARDIR'}."/log/mailcleaner/summaries.log");
    print "done sending weekly summaries.\n";
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
    push(@wait,$pid_ssumm);
  } elsif (defined $pid_ssumm) {
    print "sending monthly summaries...\n";
    my $date = `date '+%Y%m%d'`;
    chomp($date);
    system("echo 'Sending monthly summaries:' >> ".$config{'VARDIR'}."/log/mailcleaner/summaries.log");
    system($config{'SRCDIR'}."/bin/send_summary.pl -a 1 31 >> ".$config{'VARDIR'}."/log/mailcleaner/summaries.log");
    print "done sending monthly summaries.\n";
    exit;
  }
}

######################
######################
# MC restart         #
######################
######################
if ( -e $config{'VARDIR'}."/run/mailcleaner.rn") {
	system($config{'SRCDIR'}."/etc/init.d/mailcleaner restart &>> /dev/null");
	system("rm -rf ".$config{'VARDIR'}."/run/mailcleaner.rn  &>> /dev/null");
}

##########################
# Wait for all forked pids
##########################
require Proc::ProcessTable;
my $pt = Proc::ProcessTable->new();
my @remaining = @wait;
while (scalar(@remaining)) {
  sleep(1);
  @wait = @remaining;
  @remaining = ();
  foreach my $p (@{$pt->table}) {
    if (scalar(grep(/^$p->{'pid'}$/, @wait))) {
      unless ($p->{'state'} eq 'defunct') {
        push(@remaining, $p->{'pid'});
      }
    }
    if (scalar(@remaining) == scalar(@wait)) {
      last;
    }
  }
}

  #####################################
  # end $cron_occurence  minutes jobs #
  #####################################

unless ($skip) {
  remove_lockfile($lockfile,undef);
}
system($config{'SRCDIR'}."/scripts/cron/service_checks.pl");

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
