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
#   This script will dump the clamav configuration file with the configuration
#   settings found in the database.
#
#   Usage:
#           dump_clamav_config.pl


use strict;
use DBI();

my $DEBUG = 1;

my $lasterror;

my %config = readConfig("/etc/mailcleaner.conf");

dump_file("clamav.conf");
dump_file("freshclam.conf");
dump_file("clamd.conf");
dump_file("clamspamd.conf");

# recreate links
my $cmd = "rm /opt/clamav/etc/*.conf >/dev/null 2>&1";
`$cmd`;
$cmd = "ln -s $config{'SRCDIR'}/etc/clamav/*.conf /opt/clamav/etc/ >/dev/null 2>&1";
`$cmd`;

$cmd = "rm /opt/clamav/share/clamav/* >/dev/null 2>&1";
`$cmd`;
$cmd = "ln -s $config{'VARDIR'}/spool/clamav/* /opt/clamav/share/clamav/ >/dev/null 2>&1";
`$cmd`;
$cmd = "chown clamav:clamav -R $config{'VARDIR'}/spool/clamav $config{'VARDIR'}/log/clamav/ >/dev/null 2>&1";
`$cmd`;

if (-e "$config{'VARDIR'}/spool/mailcleaner/clamav-unofficial-sigs") {
	if (-e "$config{'VARDIR'}/spool/clamav/unofficial-sigs") {
		my @src = glob("$config{'VARDIR'}/spool/clamav/unofficial-sigs/*");
		foreach my $s (@src) {
			my $d = $s;
			$d =~ s/unofficial-sigs\///;
 			unless (-e $d) {
				symlink($s, $d);
			}
		}
	} else {
		print "$config{'VARDIR'}/spool/clamav/unofficial-sigs does not exist. Run $config{'SRCDIR'}/scripts/cron/update_antivirus.sh then try again\n";
	}
} else {
	my @dest = glob("$config{'VARDIR'}/spool/clamav/*");
	foreach my $d (@dest) {
		my $s = $d;
		$s =~ s/clamav/clamav\/unofficial-sigs/;
		if (-l $d && $s eq readlink($d)) {
			unlink($d);
		}
	}
}

print "DUMPSUCCESSFUL\n";

#############################
sub dump_file
{
	my $file = shift;

	my $template_file = "$config{'SRCDIR'}/etc/clamav/".$file."_template";
	my $target_file = "$config{'SRCDIR'}/etc/clamav/".$file;

	if ( !open(TEMPLATE, $template_file) ) {
		$lasterror = "Cannot open template file: $template_file";
		return 0;
	}
	if ( !open(TARGET, ">$target_file") ) {
                $lasterror = "Cannot open target file: $target_file";
		close $template_file;
                return 0;
        }

	my $proxy_server = "";
	my $proxy_port = "";
	if (defined($config{'HTTPPROXY'})) {
		if ($config{'HTTPPROXY'} =~ m/http\:\/\/(\S+)\:(\d+)/) {
			$proxy_server = $1;
			$proxy_port = $2;
		} 
	}

	while(<TEMPLATE>) {
		my $line = $_;

		$line =~ s/__VARDIR__/$config{'VARDIR'}/g;
		$line =~ s/__SRCDIR__/$config{'SRCDIR'}/g;
		if ($proxy_server =~ m/\S+/) {
			$line =~ s/\#HTTPProxyServer __HTTPPROXY__/HTTPProxyServer $proxy_server/g;
			$line =~ s/\#HTTPProxyPort __HTTPPROXYPORT__/HTTPProxyPort $proxy_port/g;
		}

		print TARGET $line;
	}

	if (($file eq "clamd.conf") && ( -e "/var/mailcleaner/spool/mailcleaner/mc-experimental-macros")) {
            print TARGET "OLE2BlockMacros yes";
        }

	close TEMPLATE;
	close TARGET;
	
	return 1;
}

#############################
sub readConfig
{
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
