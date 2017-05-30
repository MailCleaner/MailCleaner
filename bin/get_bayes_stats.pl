#!/usr/bin/perl -w

if ($0 =~ m/(\S*)\/get_bayes_stats\.pl$/) {
  my $path = $1."/../lib";
  unshift (@INC, $path);
}
print "0|0";
exit;

require ReadConfig;
use strict;

my $givenmode = shift;
my $mode = "";
if (defined($givenmode) && $givenmode =~ /^(-v|-h)$/) {
  $mode = 'h';
  if ($givenmode eq "-v") {
    $mode = "v"; 
  }
}

my $conf = ReadConfig::getInstance();

my $MSLOGFILE=$conf->getOption('VARDIR')."/log/mailscanner/infolog";

open LOGFILE, $MSLOGFILE or die("cannot open log file: $MSLOGFILE\n");

my $msgs = 0;
my $spam_sure = 0;
my $ham_sure = 0;
my $weird = 0;
my $unsure = 0;
my $hour = 0;
my %hourly_counts;
my $samsgs = 0;
my $saspams = 0;
my $sahams = 0;
my $saunsure = 0;
while (<LOGFILE>) {
  if (/(\d\d):\d\d:\d\d .* NiceBayes result (is not spam|is spam) \(([^)]+)\)/) { 
    $msgs++; 
    my $hour = $1;
    #my %hcount = ( 'msgs' => 0, 'spam' => 0, 'ham' => 0, 'weird' => 0, 'unsure' => 0);
    $hourly_counts{$hour}{'msgs'}++;

    my $percent = $3;
    if ($percent =~ m/^(100|99)(\.\d+)?%$/) {
    #if ($percent =~ m/^(100|9\d)(\.\d+)?%$/) {
      $spam_sure++;
      $hourly_counts{$hour}{'spam'}++;
      #print "found SPAM sure: $percent\n";
    } elsif ($percent =~ m/^0%$/) {
      $ham_sure++;
      $hourly_counts{$hour}{'ham'}++;
      #print "found HAM sure: $percent\n";
    } elsif ($percent eq "") {
      $hourly_counts{$hour}{'weird'}++;
      $weird++;
    } else {
      $hourly_counts{$hour}{'unsure'}++;
      $unsure++;
    }
  }

  if (/(\d\d):\d\d:\d\d .*(:?SpamAssassin|Spamc) \(.*\)/)    {
    $samsgs++;
    my $hour = $1;
    $hourly_counts{$hour}{'samsgs'}++;
    if (/BAYES_99/) {
      $saspams++;
      $hourly_counts{$hour}{'saspams'}++;
    } elsif (/BAYES_0/) {
      $sahams++;
      $hourly_counts{$hour}{'sahams'}++;
    } else {
      $saunsure++;
      $hourly_counts{$hour}{'saunsure'}++;
    }
  } 
}
close LOGFILE;

my $certainty = 0;
my $sacertainty = 0;
if ($msgs > 0) {
 $certainty = int( (100 / $msgs) * ($spam_sure + $ham_sure) * 100) / 100;
 $sacertainty = int( (100 / $samsgs) * ($saspams + $sahams) * 100) / 100;
}

if ($mode eq "v") {
  print "messages: $msgs\n";
  print "spam sure: $spam_sure\n";
  print "ham sure: $ham_sure\n";
  print "weird: $weird\n";
  print "unsure: $unsure\n";
  print "certainty: $certainty%\n";
  print "### SpamAssassin\n";
  print "SA messages: $samsgs\n";
  print "SA spam sure: $saspams\n";
  print "SA ham sure: $sahams\n";
  print "SA unsure: $saunsure\n";
  print "SA certainty: $sacertainty%\n";
  exit 0;
}

if ($mode eq "h") {
  foreach my $hour (sort keys %hourly_counts) {
    my $p = int( (100 / $hourly_counts{$hour}{'msgs'}) * ($hourly_counts{$hour}{'spam'} + $hourly_counts{$hour}{'ham'}) * 100) / 100;
    print "hour: $hour  => ".$hourly_counts{$hour}{'msgs'}."/".$hourly_counts{$hour}{'spam'}."/".$hourly_counts{$hour}{'ham'}." => $p%\n";
  }
  exit 0;
}

print "$certainty|$sacertainty\n";
exit 0;

