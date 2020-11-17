#!/usr/bin/perl
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

use strict;
if ($0 =~ m/(\S*)\/\S+.pl$/) {
  my $path = $1."/../lib";
  unshift (@INC, $path);
}
require ReadConfig;
require DB;

output("Starting spam syncronisation");
my $TMPDIR = "/var/tmp/spam_sync";
if ( -d $TMPDIR) {
  `rm -rf $TMPDIR/*`;
} else {
  mkdir $TMPDIR or die("could not create temoprary directory $TMPDIR");
}


my $conf = ReadConfig::getInstance();
if ($conf->getOption('ISMASTER') !~ /^[y|Y]$/) {
  print "NOTAMASTER";
  exit 0;
}

my $mconfig = DB::connect('master', 'mc_config', 0);
my $slavesrequest = "SELECT id,hostname,port,password FROM slave";
my @slavesarray = $mconfig->getListOfHash($slavesrequest);
$mconfig->disconnect();

my $synced = 0;
foreach my $s_h (@slavesarray) {

  my $pid = fork;
  sleep 2;
  if ($pid) {
  my $sid = $s_h->{'id'};
  output("($sid) Syncing with: ".$s_h->{'hostname'}.":".$s_h->{'port'}."...");
  my %conn = ('host' => $s_h->{'hostname'}, 'port' => $s_h->{'port'}, 'password' => $s_h->{'password'}, 'database' => 'mc_spool');
  my $slavedb = DB::connect('custom', \%conn, 0);

  ## get slave date
  my $datequery = "SELECT CURDATE() as date, CURTIME() as time";
  my %dateh = $slavedb->getHashRow($datequery);
  my $date = '';
  my $time = '';
  if (%dateh) {
   $date = $dateh{'date'};
   $time = $dateh{'time'};
  }

  foreach my $l ('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','misc', 'num') {
    my $dumpcmd = "/opt/mysql5/bin/mysqldump --insert-ignore -t --skip-opt -h".$s_h->{'hostname'}." -P".$s_h->{'port'}." -umailcleaner -p".$s_h->{'password'}." mc_spool spam_$l -w \"in_master='0' and ( date_in < '$date' or ( date_in = '$date' and time_in < '$time') )\"";
    output("($sid) - exporting spam_$l ...");
    my $res = `$dumpcmd > $TMPDIR/spam_$l-$sid.sql`;
    if ( ! $res eq '' ) {
       print "Something went wrong while exporting spams on table: spam_$l on host ".$s_h->{'hostname'}.":\n";
       print "$res\n";
       next;
    }
    #print "  slave $sid - done!\n";

    output("($sid) - reimporting spam_$l ...");
    my $exportcmd = $conf->getOption('SRCDIR')."/bin/mc_mysql -m mc_spool < $TMPDIR/spam_$l-$sid.sql";
    $res = `$exportcmd`;
    if ( ! $res eq '' ) {
       print "Something went wrong while reimporting spams on table: spam_$l from host ".$s_h->{'hostname'}.":\n";
       print "$res\n";
       next;
    }
    #print "  slave $sid - done!\n"; 


    my $updatequery = "UPDATE spam_$l SET in_master='1' WHERE in_master='0' AND ( date_in < '$date' OR ( date_in = '$date' AND time_in < '$time') )";
    my $nbres = $slavedb->{dbh}->do($updatequery);
    if (!$nbres || $nbres < 0 || $nbres !~ /^\d+$/ ) {
      $nbres = 0;
    }
    output("($sid) sync status updated for spam_$l ($nbres records).");
  }

  $slavedb->disconnect();
  }
  wait;
  exit if $pid;
  die "Couldn't fork: $!" unless defined($pid);
}
exit 0;

sub output {
 my $str = shift;
 my $ldate = `date '+%Y-%m-%d %H:%M:%S'`;
 chomp($ldate);

 print $ldate." ".$str."\n";
}
