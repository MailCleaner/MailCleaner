#!/usr/bin/perl

use strict;
if ($0 =~ m/(\S*)\/\S+.pl$/) {
  my $path = $1."/../lib";
  unshift (@INC, $path);
}
require ReadConfig;

my $conf = ReadConfig::getInstance();

my $logfile=$conf->getOption('VARDIR')."/log/mailscanner/infolog";

open(LOGFILE, $logfile) or die "cannot open log file: $logfile\n";

my %counts = ();
my %sums = ();
my %max = ();
my %min = ();
my %hourly_counts = ();
my %hourly_sums = ();
while (<LOGFILE>) {
  if (/\d+\.\d+s/) {
    my $hour = 0;
    if (/\w+\s+\d+\s+(\d+):\d+:\d+/) {
      $hour = $1;
    } 
    my @fields = split / /,$_;
    foreach my $field (@fields) {
     if ($field =~ m/\((\w+):(\d+\.?\d*)s\)/) {
       $sums{$1} += $2;
       $hourly_sums{$hour}{$1} += $2;
       $counts{$1}++;
       $hourly_counts{$hour}{$1}++;
       if ($2 > $max{$1}) {
         $max{$1} = $2;
       }
       if (!defined($min{$1}) || $min{$1} eq "" || $2 < $min{$1}) {
         $min{$1} = $2;
       }
     }
    }
  }
}
close LOGFILE;

print "-----------------------------------------------------------------------------------------------\n";
printStat('Prefilters');
my $av = 0;
if (defined($counts{'Prefilters'}) && $counts{'Prefilters'} > 0) {
  $av = $sums{'Prefilters'}/$counts{'Prefilters'};
}
my $msgpersec = 'nan';
if ($av > 0 ) {
  $msgpersec = 1/$av;
}
print "   rate: ".(int($msgpersec*10000)/10000)." msgs/s\n";
print "-----------------------------------------------------------------------------------------------\n";
foreach my $var (sort hashValueDescendingNum(keys %counts)) {
  next if ($var eq 'Prefilters');
  printStat($var);
}
print "-----------------------------------------------------------------------------------------------\n";
print "Hourly stats: \n";
my @h = sort keys %hourly_counts;
foreach my $hour (@h) {
  printHourly($hour, 'Prefilters'); 
}

sub printStat {
  my $var = shift;

  my $av = 0;
  if (defined($counts{$var}) && $counts{$var} > 0) {
   $av = (int(($sums{$var}/$counts{$var})*10000)/10000);
  }
  my $percent = 0;
  if ($counts{'SpamCacheCheck'} > 0) {
    $percent = (int( (100/$counts{'SpamCacheCheck'} * $counts{$var}) * 100) / 100);
  }
  print $var.": ".$counts{$var}." ($percent%) => ".$av."s (max:".$max{$var}."s, min:".$min{$var}."s)\n";
}

sub printHourly {
  my $h = shift;
  my $var = shift;

  if (defined($hourly_counts{$h}{$var}) && $hourly_counts{$h}{$var} > 0) {
   $av = (int(($hourly_sums{$h}{$var}/$hourly_counts{$h}{$var})*10000)/10000);
  }
  print $h.": ".$hourly_counts{$h}{$var}." => ".$av."s \n"; 
}

sub hashValueAscendingNum {
   $counts{$a} <=> $counts{$b};
}

sub hashValueDescendingNum {
   $counts{$b} <=> $counts{$a};
}
