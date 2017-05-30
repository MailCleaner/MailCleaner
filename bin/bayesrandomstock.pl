#!/usr/bin/perl -w
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
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
#   This script will compare the actual slave or master database with
#   the up-to-date database from Mailcleaner Update Services
#
#   Usage:
#           check_db.pl [-s|-m] [--dbs=database] [--update|--mycheck|--myrepair]

use strict;
if ($0 =~ m/(\S*)\/\S+.pl$/) {
  my $path = $1."/../lib";
  unshift (@INC, $path);
}
require ReadConfig;
require SystemPref;
require PrefDaemon;

my $conf = ReadConfig::getInstance();
my $gaction = shift;
my $action = "";
if (defined($gaction) && $gaction eq "-s") {
  $action = "send";
}

my $sysconf = SystemPref::getInstance();
if (!$sysconf->getPref('do_stockme')) {
  exit 0;
}

my $VARDIR=$conf->getOption('VARDIR');
my $CENTERPATH=$VARDIR."/spool/learningcenter";
my $SPAMDIR=$CENTERPATH."/stockspam/";
my $HAMDIR=$CENTERPATH."/stockham";
my $STOCKDIR=$CENTERPATH."/stockrandom";

my $maxpertenperhour = 30;

my @whats = ('spam', 'ham');
foreach my $WHAT (@whats) {
  my $whatcount = 0;
  opendir(HSDIR, $CENTERPATH."/stock$WHAT") or die("Cannot open $WHAT dir\n");
  while (my $day = readdir(HSDIR)) {
    next if $day !~ /^\d+/;
    next if ! -d $CENTERPATH."/stock$WHAT/".$day;
 
    my @tens = (0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0 , 9 => 0 ); 
    print "opening: ".$CENTERPATH."/stock$WHAT/".$day."\n"; 
    opendir(LIST, $CENTERPATH."/stock$WHAT/".$day) or next;
    while (my $file = readdir(LIST)) {
      next if ! open(FILE, $CENTERPATH."/stock$WHAT/".$day."/".$file);
      my $sascore = 0;
      my $nbscore = 0;
      while (<FILE>) {
        last if ($nbscore != 0 && $sascore != 0);
        if (/score=(-?\d+)/) {
           $sascore =  $1;
        }
        if (/NiceBayes: is not spam \(([^)%.]+)/) {
           $nbscore = $1;
        }
      }

      if ($WHAT eq 'spam' && $sascore > 15 && ($nbscore < 99 && $nbscore > 39)) {
         my $ten = int($nbscore / 10);
         if ($tens[$ten] < $maxpertenperhour) {
           $tens[$ten]++;
           print "SPAM $file ($nbscore <=> $sascore, $ten|".$tens[$ten]."/$maxpertenperhour)...";
           if (rename($CENTERPATH."/stock$WHAT/".$day."/".$file, $STOCKDIR."/".$WHAT."/cur/$file")) {
             print "moved\n";
           } else {
             print "NOT MOVED! $!\n";
           }
           $whatcount++;
         }
      }
      if ($WHAT eq 'ham' && $sascore < -1 && ($nbscore > 0 && $nbscore < 60)) {
        my $ten = int($nbscore / 10);
        if ($tens[$ten] < $maxpertenperhour) {
          $tens[$ten]++;
          print "HAM $file ($nbscore <=> $sascore, $ten|".$tens[$ten]."/$maxpertenperhour)...";
          if (rename($CENTERPATH."/stock$WHAT/".$day."/".$file, $STOCKDIR."/".$WHAT."/cur/$file")) {
             print "moved\n";
          } else {
             print "NOT MOVED! $!\n";
          }
          $whatcount++;
        }
      }
    } 
  }
  
  # delete old stocks
  if (opendir(QDIR, $CENTERPATH."/stock$WHAT/")) {
    while(my $entry = readdir(QDIR)) { 
      next if $entry !~ /^\d+/;
      $entry = $CENTERPATH."/stock$WHAT/$entry";
      system("rm -rf $entry") if -d $entry &&
                                 -M $entry > $sysconf->getPref('stockme_nbdays');
    }
    closedir(QDIR);
  }
  
  if ($action eq "send") {
    # create bayes tar
    my $date=`date +%Y%m%d`;
    chomp($date);
    my $tarfile = "$STOCKDIR/$WHAT-".$conf->getOption('CLIENTID')."-".$conf->getOption('HOSTID')."_$date.tar.gz";
    my $command = "tar -C $STOCKDIR/$WHAT/ -cvzf $tarfile cur";
    system("$command");

    my $CVSHOST='cvs.mailcleaner.net';
    $command = "scp -q -o PasswordAuthentication=no -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no $tarfile mcscp\@$CVSHOST:/upload/stocks/ >/dev/null 2>&1";
    system($command);
  }

  print "finished with $WHAT: $whatcount messages taken\n"; 
}
