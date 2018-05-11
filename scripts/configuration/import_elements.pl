#!/usr/bin/perl

use strict;
if ($0 =~ m/(\S*)\/\S+.pl$/) {
  my $path = $1."/../../lib";
  unshift (@INC, $path);
}
require DB;
require ReadConfig;
require ElementMapper;

my $info = 1;
my $warning = 1;
my $error = 1;

my $db = DB::connect('master', 'mc_config');
my $conf = ReadConfig::getInstance();

my $importfile = shift;
if (! -f $importfile ) {
  error("Import file not found !");
  exit 1;
}
my $what = shift;
if (!defined($what)) {
  error("No element type given");
  exit 1;
}
my $flags = shift;
my $nottodeletefile = shift;
my $dontdelete = 1;
if (defined($nottodeletefile)) {
  $dontdelete = 0;
}

### ElementMapper factory called here
my $mapper = ElementMapper::getElementMapper($what);
if (!defined($mapper)) {
  error("Element type \"$what\" not supported");
  exit 1; 
}

if (! open (IMPORTFILE, $importfile)) { 
  warning("Could not open import file: $importfile"); 
  exit 0; 
}

my %elements = ();
while (<IMPORTFILE>) {
  if (/^__DEFAULTS__ (.*)/) {
    # set new defaults
    $mapper->setNewDefault($1);
    next;
  }
  my $el_name = $_;
  my $el_params = '';
  if (/(.*)\s*:\s*(.*)/) {
    $el_name = $1;
    $el_params = $2;
  }
  $el_name =~ s/\s//g;
  info("will process element $el_name");
  $mapper->processElement($el_name, $flags, $el_params);
  $elements{$el_name} = 1;
}
close IMPORTFILE;

#my $domainsnottodelete = $conf->getOption('SRCDIR')."/etc/exim/domain_not_to_delete.cf";
my $domainsnottodelete = $nottodeletefile;
my %nodelete = ();
if ( open (NODELETEFILE, $domainsnottodelete)) {
  while (<NODELETEFILE>) {
   my $el = $_;
   chomp($el);
   $nodelete{$el} = 1;
   print "preventing delete for: $el\n";
  }
  close NODELETEFILE;
}

if (! $dontdelete) {
  my @existing_elements = $mapper->getExistingElements();
  foreach my $el (@existing_elements) {
    #print "testing relevance of: $el\n";
    next if (defined($nodelete{$el}));
    if (!defined($elements{$el}) || ! $elements{$el}) {
      $mapper->deleteElement($el);
    }
  }
}


sub warning {
 my $text = shift;
 if ($warning) {
   print $text."\n";
 }
}

sub error {
 my $text = shift;
 if ($error) {
   print $text."\n";
 }
}

sub info {
 my $text = shift;
 if ($info) {
   print $text."\n";
 }
}
