#!/usr/bin/perl -w

use strict;
use File::Copy;
use File::Path;
if ($0 =~ m/(\S*)\/\S+.pl$/) {
  my $path = $1."/../lib";
  unshift (@INC, $path);
}
require MCDnsLists;
require GetDNS;
require DB;

our %tables = (
        'trusted_ips'                   => 'antispam',
        'html_wl_ips'                   => 'antispam',
        'relay_from_hosts'              => 'exim_stage1',
        'no_ratelimit_hosts'            => 'exim_stage1',
        'host_reject'                   => 'exim_stage1',
        'hosts_require_tls'             => 'exim_stage1',
        'rbls_ignore_hosts'             => 'exim_stage1',
        'spf_dmarc_ignore_hosts'        => 'exim_stage1'
);

my $file = shift || usage("Missing argument");

my @ips;
if ($file =~ m/^--/) {
	$file =~ s/^--//;
	if (defined($tables{$file})) {
		@ips = get_from_db($tables{$file},$file);
	} else {
		usage("unknown column '$file'");
	}
} else {
	open(my $fh, '<', $file) || usage("Failed to open $file");
	my $raw;
	while (<$fh>) {
		$raw .= $_;
	}
	close($fh);
	if ($raw eq '') {
		usage("$file is empty");
	}
	@ips = ( expand_host_string($raw, ('dumper'=>'exemption_test_script')) );
}

foreach (@ips) {
	print "$_\n";
}

sub get_from_db
{
	my ($table, $column) = @_;
	my $stage;
	if ($table =~ m/^exim_stage(\d)$/) {
		$stage = $1;
		$table = "mta_config WHERE stage = '$1'";
	}
	my $db = DB::connect('slave', 'mc_config');

	my %row = $db->getHashRow("SELECT $column FROM $table");
	return ( expand_host_string($row{$column}, ('dumper'=>"exemption_test_script/$column")) );
}

sub expand_host_string
{
    my $string = shift;
    my %args = @_;
    my $dns = GetDNS->new();
    return $dns->dumper($string,%args);
}

sub usage
{
	my $error;
	if ($error = shift) {
		print "\nERROR: $error\n";
	}
	print "\nUsage: $0 <filepath>\n\n";
	print "File should contain valid hostlist contents.\n\n";
	print "Alternatively you can fetch one of the hostlists from the DB with:\n";
	foreach my $column (keys(%tables)) {
		printf("  --%-24s  (%s)\n", $column, $tables{$column});
	}
	die("\n");
}
