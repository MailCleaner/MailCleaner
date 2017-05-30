#!/usr/bin/perl

use strict;
if ($0 =~ m/(\S*)\/\S+.pl$/) {
  my $path = $1."/../lib";
  unshift (@INC, $path);
}
require ReadConfig;

my $conf = ReadConfig::getInstance();

my $logfile=$conf->getOption('VARDIR')."/log/exim_stage4/mainlog";

open(LOGFILE, $logfile) or die "cannot open log file: $logfile\n";

my %counts = ();
my %sums = ();
my %max = ();
my %min = ();
while (<LOGFILE>) {
  if (/\d+\.\d+s/) {
    my @fields = split / /,$_;
    foreach my $field (@fields) {
     if ($field =~ m/\((\d+\.\d+)s\/(\d+\.\d+)s\)/) {
       $sums{'global'} += $2;
       $counts{'global'}++;
       if ($2 > $max{'global'}) {
         $max{'global'} = $2;
       }
       if (!defined($min{'global'}) || $min{'global'} eq "" || $2 < $min{'global'}) {
         $min{'global'} = $2;
       }
     }

     if ($field =~ m/\((\w+):(\d+\.\d+)s\)/) {
       $sums{$1} += $2;
       $counts{$1}++;
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
printStat('global');
my $av = 0;
if (defined($counts{'global'}) && $counts{'global'} > 0) {
  $av = $sums{'global'}/$counts{'global'};
}
my $msgpersec = 'nan';
if ($av > 0 ) {
  $msgpersec = 1/$av;
}
print "   rate: ".(int($msgpersec*10000)/10000)." msgs/s\n";
print "-----------------------------------------------------------------------------------------------\n";
foreach my $var (keys %counts) {
  next if ($var eq 'global');
  printStat($var);
}

sub printStat {
  my $var = shift;

  my $av = 0;
  if (defined($counts{$var}) && $counts{$var} > 0) {
   $av = (int(($sums{$var}/$counts{$var})*10000)/10000);
  }
  print $var.": ".$counts{$var}." => ".$av."s (max:".$max{$var}."s, min:".$min{$var}."s)\n";
}
