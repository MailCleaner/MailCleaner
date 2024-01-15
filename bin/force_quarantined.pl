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
#
#   This script will release a message put on the MailScanner quarantine.
#   This is for the antivirus/dangerous content filters.
#
#   Usage:
#           force_quarantined.pl date/msg_id
#   where date is the date when the msg was blocked (format: YYYYMMDD)
#   and msg id is the id of the message blocked
#   The whole string (date/msg_id) will be included in the warning text file
#   attached to the original messagein place of the quarantined content.

use strict;
use File::Copy;

my %config = readConfig("/etc/mailcleaner.conf");

my $quardir = shift;
my $forced_postfix = '-F'.int(rand(100));;

if (! $quardir || (! ($quardir =~ /\d{8}\/([a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{6,11}-[a-z,A-Z,0-9]{2,4})$/) ) ) {
	bad_usage();
}

my $dir = $config{VARDIR}."/spool/mailscanner/quarantine/".$quardir;
my $id = $1;
chomp $dir;

my $uid = getpwnam( 'mailcleaner' );
my $gid = getgrnam( 'mailcleaner' );

if (! -d $dir ) {
	print  "NOTFOUND\n";
	exit;
}

if (!open HFILE, $dir."/".$id."-H") {
  die("CANNOTFINDHEADERFILE");
}
if (!open DHFILE, '>'.$config{VARDIR}."/spool/exim_stage4/input/".$id."-H") {
  die("CANNOTOPENDESTHEADERFILE");
}
my $id_header = '';
my $hsize;
my $hname;
my $hlpart;
my $hrpart;
my $header;
my $is_multiline_id = 0;
while (<HFILE>) {
   if (/^(\d+)I (\S+):\s*<([^@]+)@([^>]+)>/m) {
     # Do this if the Message ID is on a single line
     $hsize = $1;
     $hname = $2;
     $hlpart = $3;
     $hrpart = $4;

     $header = $hname.": <".$hlpart.$forced_postfix."@".$hrpart.">";
     $id_header = sprintf('%.3d', length($header)+1)."I ".$header;
     print DHFILE $id_header."\n";
   } elsif (/^(\d+)I (\S+):\s*$/) {
     # Do this if the Message ID is on two lines
     $is_multiline_id = 1;
     $hsize = $1;
     $hname = $2;
   } else {
     if ($is_multiline_id && /^\s+<([^@]+)@([^>]+)>/){
        # Do this if the Message ID is on two lines
        $is_multiline_id = 0;
        $hlpart = $1;
        $hrpart = $2;
        $header = $hname.": <".$hlpart.$forced_postfix."@".$hrpart.">";
        my $id_header = sprintf('%.3d', length($header)+1)."I ".$header;
        print DHFILE $id_header."\n";
     } else {
        print DHFILE $_;
     }
   }
}
close HFILE;

if (!copy($dir."/".$id."-D", $config{VARDIR}."/spool/exim_stage4/input/")) {
  die "NOTFORCED: failed to copy file from: ";
}
my @exts = ('H', 'D', 'J', 'T');
foreach my $ext ( @exts ) {
  my $spoolfile = $config{VARDIR}."/spool/exim_stage4/input/".$id."-".$ext;
  if (-f $spoolfile) {
    chown $uid, $gid, $spoolfile;
  }
}
sleep 2;
my $cmd = "/opt/exim4/bin/exim -C ".$config{SRCDIR}."/etc/exim/exim_stage4.conf -M ".$id." 2>&1";
my $res = `$cmd`;
if ($res =~ /^$/) {
	mark_forced($id);
	print "FORCED\n";
	exit;
}

print "NOTFORCED: $res\n";

exit;

######################################

sub bad_usage
{
	print("Error:  bad usage.  force_quarantined.pl date/msgid\n");
	exit 1;
}

##########################################
sub mark_forced
{
	use DBI;
	my $id = shift;
	my $dbh = DBI->connect("DBI:mysql:database=mc_stats;host=localhost;mysql_socket=$config{VARDIR}/run/mysql_slave/mysqld.sock",
		"mailcleaner", "$config{MYMAILCLEANERPWD}", {RaiseError => 0, PrintError => 0}) || return;

	my $query = "UPDATE maillog SET content_forced='1' WHERE id='$id'";
	my $sth = $dbh->prepare($query);
	$sth->execute() or return;

	$dbh->disconnect();
}

##########################################
sub readConfig
{       # Reads configuration file given as argument.
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
