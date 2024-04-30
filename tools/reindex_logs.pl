#!/usr/bin/env perl

#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2024 John Mertz <git@john.me.tz>
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
#   This script will generate outbound relaying summaries to assist in the
#   diagnosing of outbound blacklisting or other unexpected relaying behaviour
#
#   See Usage menu below or by running with the --help option

use strict;
use warnings;
use Time::Piece;
use Time::Seconds qw( ONE_DAY );
use Module::Load::Conditional qw( check_install );
use File::Copy qw( mv );
use File::Path qw( rmtree );

check_install( 'module' => 'PerlIO::gzip', 'version' => 0.18) || die "Reindexing requires the library PerlIO::gzip. Please install with:\n  apt-get install libperlio-gzip-perl\n\n";

our $VAR = "/var/mailcleaner";

sub usage() {
	print("$0 [--fast|--backup] <services>

--fast -f	
	Use a fast(er) algorithm to reindex logs at the cost of a lot of memory being used. 
	This will load all available logs of a specific type (eg. exim_stage1/mainlog*)
	into memory, uncompressed, then print them out all at once. This is much faster than the
	default which reads one file at a time, from oldest to newest and prints immediately to the
	correct output file, but which needs to re-check all available input files once for each
	output file.

--backup -b
	Original log directories will be kept at $VAR/log.bk

Provide a list of services you'd like to re-index logs for. All services will be processed by default.\n");
	exit();
}

my $services = {
	'apache' => {
		'access.log' => [ '%d/%b/%Y' ],
		'access_configurator.log' => [ '%d/%b/%Y' ],
		'access_soap.log' => [ '%d/%b/%Y' ],
		'error.log' => [ '%a %d %b' ],
		'error_configurator.log' => [ '%a %d %b' ],
		'error_soap.log' => [ '%a %d %b' ],
		'mc_auth.log' => [ '%d/%b/%Y' ],
	},
	'clamav' => {
		'clamd.log' => [ '%Y-%m-%d', '%a %b %d' ],
		'clamspamd.log' => [ '%Y-%m-%d', '%a %b %d' ],
	},
	'exim_stage1' => {
		'mainlog' => [ '%Y-%m-%d' ],
		'paniclog' => [ '%Y-%m-%d' ],
		'rejectlog' => [ '%Y-%m-%d' ],
	},
	'exim_stage2' => {
		'mainlog' => [ '%Y-%m-%d' ],
		'paniclog' => [ '%Y-%m-%d' ],
		'rejectlog' => [ '%Y-%m-%d' ],
	},
	'exim_stage4' => {
		'mainlog' => [ '%Y-%m-%d' ],
		'paniclog' => [ '%Y-%m-%d' ],
		'rejectlog' => [ '%Y-%m-%d' ],
	},
	'fail2ban' => {
		'mc-fail2ban.log' => [ '%Y-%m-%d' ],
	},
	'mailcleaner' => {
		'downloadDatas.log' => [ '%Y/%m/%d' ],
		'PrefTDaemon.log' => [ '%Y-%m-%d' ],
		'SpamHandler.log' => [ '%Y-%m-%d' ],
		'spam_sync.log' => [ '%Y-%m-%d' ],
		'StatsDaemon.log' => [ '%Y-%m-%d' ],
		'summaries.log' => [ '%Y-%m-%d' ],
		'update.log' => [ '%Y-%m-%d' ],
	},
	'mailscanner' => {
		'infolog' => [ '%b %d' ],
		'newsld.log' => [ '%Y-%m-%d', '%a %b %d \d\d:\d\d:\d\d %Y' ],
		'spamd.log' => [ '%Y-%m-%d', '%a %b %d \d\d:\d\d:\d\d %Y' ],
		'warnlog' => [ '%b %d' ],
	},
	'mysql_master' => {
		'mysql.log' => [ '%y%m%d' ],
	},
	'mysql_slave' => {
		'mysql.log' => [ '%y%m%d' ],
	}
};

my ($fast, $backup, @services_to_index, @error) = ( 0, 0 );
foreach my $arg (@ARGV) {
	if ($arg eq '--fast' || $arg eq '-f') {
		print("
WARNING: Fast algorithm can potentially use a lot of memory. It loads all logs of a specific type
(eg. exim_stage1/mainlog) into memory, uncompressed, all at once.
This could cause performance problems or crashes elsewhere on the system.\n\n");
		$fast = 1;
	} elsif ($arg eq '--backup' || $arg eq '-b') {
		$backup = 1;
	} elsif ($arg eq '--help' || $arg eq '-h') {
		usage();
	} elsif (defined($services->{$arg}) && !scalar(grep(@services_to_index, $arg))) {
		push(@services_to_index, $arg);
	} else {
		push(@error, $arg);
	}
}
die("Invalid option(s) or service(s): ".join(', ', @error)."\n") if (scalar(@error));

@services_to_index = keys(%{$services}) unless scalar(@services_to_index);

my %filemap;
my @rev_order;

my $t = Time::Piece->localtime();
#$t = $t->truncate(to => 'day');
for (my $i = 0; $i <= 365; $i++) {
	my $suffix;
	if ($i == 0) {
		$suffix  = '';
	} elsif ($i == 1) {
		$suffix = '.0';
	} else {
		$suffix = '.'.($i-1).'.gz';
	}
	my ($a, $b, $d, $m, $Y);
	$filemap{$suffix} = { '%a' => $t->wdayname(), '%b' => $t->monname(), '%d' => sprintf('%02d', $t->mday()), '%m' => sprintf('%02d', $t->mon()), '%Y' => $t->year() };
	unshift(@rev_order, $suffix);
	$t -= ONE_DAY;
	#$t = $t->truncate(to => 'day');
}

my $tmp_dir = '/var/tmp/reindex_logs';
my %logs;
mkdir(${tmp_dir}) unless (-d ${tmp_dir});
foreach my $service (@services_to_index) {
	print "Indexing ${service}...\n";
	mkdir("${tmp_dir}/${service}") unless (-d "${tmp_dir}/${service}");
	foreach my $file (keys %{$services->{$service}}) {
		my $keep = 0;
		foreach my $output (@rev_order) {
			my @search_dates = ( $output );
			@search_dates = @rev_order if ($fast);
			unlink("${tmp_dir}/$service/$file$output") if ( -e "${tmp_dir}/$service/$file$output" );
			if ($fast) {
				print "Processing all ${service}/${file} files at once...\n";
			} else {
				print("Indexing $service/$file$output ($filemap{$output}{'%a'}, $filemap{$output}{'%b'} $filemap{$output}{'%d'}, $filemap{$output}{'%Y'})");
			}
			my $oh;
			if ($fast) {
				%{$logs{"${tmp_dir}/${service}/${file}"}} = map { $_ => [] } keys(%filemap);
			} else {
				my $out_method = '>>';
				$out_method .= ':gzip' if ($output =~ m/\.gz$/);
				
				die("Failed to open ${tmp_dir}/${service}/${file}${output}\n") unless (open($oh, $out_method, "${tmp_dir}/${service}/${file}${output}"));
			}
			foreach my $input (@rev_order) {
				next unless (-e "${VAR}/log/${service}/${file}${input}");


				my $in_method = '<';
				$in_method .= ':gzip' if ($input =~ m/\.gz$/);
				if (open(my $ih, $in_method, "${VAR}/log/${service}/${file}${input}")) {
					while (my $line = <$ih>) {
						foreach my $date (@search_dates) {
							my @searches = @{$services->{$service}->{$file}};
							for (my $i = 0; $i < scalar(@searches); $i++) {
								foreach my $key (keys(%{$filemap{$output}})) {
									$searches[$i] =~ s/$key/$filemap{$date}->{$key}/;
								}
							}
							my $search = "(".join('|', @searches).")";
							next unless ($line =~ m/$search/);
							if ($fast) {
								push(@{$logs{"${tmp_dir}/${service}/${file}"}->{$date}}, $line);
							} else {
								print $oh $line;
							}
						}
					}
					close($ih);
				}
			}
			if ($fast) {
				foreach my $fast_output (@rev_order) {
					next unless (scalar(@{$logs{"${tmp_dir}/${service}/${file}"}->{$fast_output}}) || $keep || !$fast_output);
					$keep = 1;
					my $out_method = '>>';
					$out_method .= ':gzip' if ($fast_output =~ m/\.gz$/);
					die("Failed to open ${tmp_dir}/${service}/${file}${fast_output}\n") unless (open($oh, $out_method, "${tmp_dir}/${service}/${file}${fast_output}"));
					foreach my $line (@{$logs{"${tmp_dir}/${service}/${file}"}->{$fast_output}}) {
						print $oh $line;
					}
					close($oh);
				}
				delete($logs{"${tmp_dir}/${service}/${file}"});
				last();
			} else {
				close($oh);
			}
			my $size = (stat("${tmp_dir}/${service}/${file}${output}"))[7];
			if (!$keep && ( ($output =~ m/\.0$/ && !$size) || ($output =~ m/\.gz$/ && $size == 20)) ) {
				print(" - empty, deleting\n");
				unlink("${tmp_dir}/${service}/${file}${output}");
			} else {
				print(" - OK\n");
				# Only delete empty files older than the first non-empty file
				$keep = 1;
			}
		}
	}
	rmtree("${VAR}/log.bk/${service}") if (-d "${VAR}/log.bk");
	mkdir("${VAR}/log.bk");
	rmtree("${VAR}/log.bk/${service}") if ( -d "${VAR}/log.bk/${service}" );
	print("Moving old ${service} logs to ${VAR}/log.bk/${service}...\n");
	mv("${VAR}/log/${service}", "${VAR}/log.bk/${service}");
	print("Moving new ${service} logs to ${VAR}/log/${service}...\n");
	mv("${tmp_dir}/${service}", "${VAR}/log/${service}");
	unless ($backup) {
		print("Deleting ${VAR}/log.bk/${service}...\n");
		rmtree("${VAR}/log.bk/${service}");
	}
}

rmtree("${tmp_dir}");

=pod

TODO:
Speed fast mode up greatly:
	Within the same file, it should be impossible for earlier logs to come after later logs, so change the starting offset for the next line to begin with the date that hit for that line. Since it will do `next()` after asuccessful hit, it should perform number_of_lines+366 searches per file rather than up to number_of_lines*366.

Special cases not yet implemented:
	'freshclam.log' 
		starts with  '[%Y-%m-%d.* Starting ClamAV update...',
		ends with  '[%Y-%m-%d.* Done.',
	'mc_counts-cleaner.log'
		starts with '%a %b %d .* %Z %Y: Sleeping'
		ends with '%a %b %d .* %Z %Y: Cleaning terminated'
	'summaries.log'
		need to insert 'Send daily summaries:' at the start of each file '%Y-%m-%d',
	'updater4mc.log'
		starts with '%Y-%m-%d.* Launching Updater4MC'
		ends with '>> Logfile present here: /var/mailcleaner/log/mailcleaner/updater4mc.log'
=cut
