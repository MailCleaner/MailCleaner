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
#   This script will dump the exim configuration file from the configuration
#   setting found in the database.
#
#   Usage:
#           dump_greylistd_config.pl
#

use strict;

if ($0 =~ m/(\S*)\/dump_greylistd_config\.pl$/) {
  my $path = $1."/../lib";
  unshift (@INC, $path);
}
require ReadConfig;
require DB;
my $conf = ReadConfig::getInstance();

my $lasterror;
my $DEBUG = 0;

my %greylist_conf = get_greylist_config() or fatal_error("NOGREYLISTDONFIGURATIONFOUND", "no greylistd configuration found");

my $uid = getpwnam( 'mailcleaner' );
my $gid = getgrnam( 'mailcleaner' );

dump_greylistd_file(\%greylist_conf) or fatal_error("CANNOTDUMPGREYLISTDFILE", $lasterror);

dump_domain_to_avoid($greylist_conf{'__AVOID_DOMAINS_'});

my $domainsfile = $conf->getOption('VARDIR')."/spool/tmp/mailcleaner/domains_to_greylist.list";
if ( ! -f $domainsfile) {
  my $res=`touch $domainsfile`;
  chown $uid, $gid, $domainsfile;
}

print "DUMPSUCCESSFUL";

#############################
sub get_greylist_config
{
  my $slave_db = DB::connect('slave', 'mc_config');

  my %configs = $slave_db->getHashRow("SELECT retry_min, retry_max, expire, avoid_domains 
                                                                         FROM greylistd_config");
  my %ret;
  
  $ret{'__RETRYMIN__'} = $configs{'retry_min'};
  $ret{'__RETRYMAX__'} = $configs{'retry_max'};
  $ret{'__EXPIRE__'} = $configs{'expire'};
  $ret{'__AVOID_DOMAINS_'} = $configs{'avoid_domains'};
  
  return %ret;
}

#############################
sub dump_domain_to_avoid
{
   my $domains = shift;
   my @domains_to_avoid;
   if (! $domains eq "") {
     @domains_to_avoid = split /\s*[\,\:\;]\s*/, $domains;
   }
   
   my $file = $conf->getOption('VARDIR')."/spool/tmp/mailcleaner/domains_to_avoid_greylist.list";
   if ( !open(DOMAINTOAVOID, ">$file") ) {
		$lasterror = "Cannot open template file: $file";
		return 0;
	}
   foreach my $adomain (@domains_to_avoid) {
     print DOMAINTOAVOID $adomain."\n";
   }
   close DOMAINTOAVOID;
   return 1;
}

#############################
sub dump_greylistd_file
{
	my $href = shift;
	my %greylist_conf = %$href;
	my $srcpath = $conf->getOption('SRCDIR');
	my $varpath = $conf->getOption('VARDIR');
	
	my $template_file = $srcpath."/etc/greylistd/greylistd.conf_template";
	my $target_file = $srcpath."/etc/greylistd/greylistd.conf";

	if ( !open(TEMPLATE, $template_file) ) {
		$lasterror = "Cannot open template file: $template_file";
		return 0;
	}
	if ( !open(TARGET, ">$target_file") ) {
                $lasterror = "Cannot open target file: $target_file";
		close $template_file;
                return 0;
        }

	while(<TEMPLATE>) {
		my $line = $_;

		$line =~ s/__VARDIR__/$varpath/g;
		$line =~ s/__SRCDIR__/$srcpath/g;

        foreach my $key (keys %greylist_conf) {
			$line =~ s/$key/$greylist_conf{$key}/g;
		}
		
		print TARGET $line;
	}

	close TEMPLATE;
	close TARGET;

        chown $uid, $gid, $target_file;	
	return 1;
}


#############################
sub fatal_error
{
	my $msg = shift;
	my $full = shift;

	print $msg;
	if ($DEBUG) {
		print "\n Full information: $full \n";
	}
	exit(0);
}

#############################
sub print_usage
{
	print "Bad usage: dump_exim_config.pl [stage-id]\n\twhere stage-id is an integer between 0 and 4 (0 or null for all).\n";
	exit(0);
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
