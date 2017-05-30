#!/usr/bin/perl -w -I../lib/
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
#   This script will dump the whitelist and warnlists for the system/domain/user
#
#   Usage:
#           dump_wwlists.pl [domain|user] 

use strict;
if ($0 =~ m/(\S*)\/dump_wwlist\.pl$/) {
  my $path = $1."/../lib";
  unshift (@INC, $path);
}
require ReadConfig;
require DB;

my $conf = ReadConfig::getInstance();
my $op = $conf->getOption('SRCDIR');
my $uid = getpwnam( 'mailcleaner' );
my $gid = getgrnam( 'mailcleaner' );

my $what = shift;
if (!defined($what)) {
  $what = "";
}
my $to = "";
my $filepath = $conf->getOption('VARDIR')."/spool/mailcleaner/prefs/";
if ($what =~ /^\@([a-zA-Z0-9\.\_\-]+)$/) {
  $to = $what;
  $filepath .= $1."/_global/";
} elsif ($what =~ /^([a-zA-Z0-9\.\_\-]+)\@([a-zA-Z0-9\.\_\-]+)/) {
  $to = $what;
  $filepath .= $2."/".$1."@".$2."/";
} else {
  $filepath .= "_global/";
}

my $slave_db = DB::connect('slave', 'mc_config');

dumpWWFiles($to, $filepath);

$slave_db->disconnect();
print "DUMPSUCCESSFUL";
exit 0;

#####################################
## dumpWWFiles

sub dumpWWFiles {
  my $to = shift;
  my $filepath = shift;
  
  my @types = ('warn', 'white');
  
  foreach my $type (@types) {
    ## get list
    my @list = $slave_db->getList("SELECT sender FROM wwlists WHERE 
                                               status=1 AND type='".$type."' 
                                               AND recipient='".$to."'");
                                               
    # first remove file if exists
    my $file = $filepath."/".$type.".list";
    if ( -f $file) {
       unlink $file;
    }

    # exit if list empty
    if (!@list) {
       next;
    }
  
    # create directory if needed
    createDirs($filepath);
   
    # and write the file down
    if ( !open(WWFILE, ">$file") ) {
      return 0;
    }
  
    foreach my $entry (@list) {
      print WWFILE "$entry\n";
    }
 
    close WWFILE;
    chown 'mailcleaner', $file;
  }
  return 1;
}

#####################################
## createDir

sub createDirs() {
  my $path = shift;
  
  my $cmd = "mkdir -p $path";
  my $res = `$cmd`;
  chown chown $uid, $gid, $path;
}
