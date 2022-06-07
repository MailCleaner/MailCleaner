#!/usr/bin/perl

# Configure source path and enable library
our $SRCDIR;
BEGIN {
	$SRCDIR = '/usr/mailcleaner';
}
use lib "$SRCDIR/lib";
require DB;

# Necessary configuration items
use strict;
use warnings;

my $dryrun = 0;
if (defined($ARGV[0])) {
	if ($ARGV[0] eq '-d') {
		$dryrun = 1;
	} else {
		usage();
	}
}

my $db = DB::connect('master', 'mc_config');
my @rows = $db->getListOfHash("SELECT id, recipient, sender, type FROM wwlists");
die "Failed to fetch wwlists\n" unless @rows;

my %uniq;
my @dup;
foreach my $rule (@rows) {
	if (defined($uniq{$rule->{'sender'}}->{$rule->{'recipient'}}->{$rule->{'type'}})) {
		if ($dryrun) {
			print "$rule->{'id'} is a duplicate of $uniq{$rule->{'sender'}}->{$rule->{'recipient'}}->{$rule->{'type'}} (sender: '$rule->{'sender'}', recipient: '$rule->{'recipient'}', type: '$rule->{'type'}')\n";
		}
		push(@dup, $rule->{'id'});
	} else {
		$uniq{$rule->{'sender'}}->{$rule->{'recipient'}}->{$rule->{'type'}} = $rule->{'id'};
	}
}

my @failed;
unless ($dryrun) {
	foreach (@dup) {
		unless ($db->execute("DELETE FROM wwlists WHERE id='$_';")) {
			print STDERR $?;
			push(@failed, $_);
		}
	}
	if (scalar(@failed)) {
		foreach (@failed) {
			print "Failed to delete $_\n";
		}
	}
	print "Deleted " . (scalar(@dup)-scalar(@failed)) . " of " . scalar(@dup) . " duplicates.\n";
}


sub usage
{
	print "usage: $0 [-d]

  -d	dryrun
	Simply print all of the duplicate rules, but don't delete them.\n";
	exit(0);
}
