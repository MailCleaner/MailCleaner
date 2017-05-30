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
#   This script will dump the domains configuration
#
#   Usage:
#           dump_domains.pl 

use strict;
if ($0 =~ m/(\S*)\/dump_domains\.pl$/) {
  my $path = $1."/../lib";
  unshift (@INC, $path);
}
require ReadConfig;
require DB;
require Domain;
require SystemPref;
require ConfigTemplate;
use File::Copy;
use File::Touch;
use Time::HiRes qw(gettimeofday tv_interval);

my $domain = shift;

my $debug_time = 1;
my %time_in;
my $start_time = time();
my $previous_time = $start_time;

my $uid = getpwnam( 'mailcleaner' );
my $gid = getgrnam( 'mailcleaner' );

my $conf = ReadConfig::getInstance();
my $op = $conf->getOption('SRCDIR');

my $slave_db = DB::connect('slave', 'mc_config');

my @masters = $slave_db->getListOfHash("SELECT hostname from master");
my %exim_conf = $slave_db->getHashRow("SELECT * from mta_config WHERE stage=1");

$time_in{'gathering_config'} = time() - $previous_time;
$previous_time = time();

######### Dump domains list
my @domain_list = $slave_db->getListOfHash("SELECT * FROM domain d, domain_pref dp WHERE d.active='true' AND d.name != '__global__' AND d.prefs=dp.id");

if (!@domain_list) {
 die('file not dumped, no domain could be retrieved');
}
my %dest_hosts = parseDestinations(\@domain_list);

$time_in{'gathering_domains_config'} = time() - $previous_time;
$previous_time = time();

my $filepath = $conf->getOption('VARDIR')."/spool/tmp/mailcleaner";
my $posterspath = $conf->getOption('VARDIR')."/spool/mailcleaner/addresses";
if ( ! -d $filepath ) {
   if (! mkdir $filepath ) {
     print "NODESTINATIONFOLDERAVAILABLE $filepath\n";
     exit 1;
   }
   chown $uid, $gid, $filepath;
}
if ( !dumpDomainsFile(\%dest_hosts, $filepath)) {
  print "CANNOTDUMPFILE\n";
  $slave_db->disconnect();
  exit 1;
}

$time_in{'dumping_domains'} = time() - $previous_time;
print_time('dumping_domains');
$previous_time = time();

######### Dump system wide preferences
my $syspref = SystemPref::getInstance();
if ( ! $syspref->dumpPrefs()) {
  print "CANNOTDUMPSYSTEMPREF\n";
  $slave_db->disconnect();
  exit 1;
}

$time_in{'dumping_system_pref'} = time() - $previous_time;
print_time('dumping_system_pref');
$previous_time = time();

######### Dump all domain prefs
if (defined($domain) && $domain eq "-a") { 
  foreach my $d (@domain_list) {
    my $do = Domain::create($$d{name});
    $do->dumpPrefsFromRow($d);
    if ($$d{'addlistcallout'} eq 'true') {
        $do->dumpLocalAddresses($slave_db);
    }
  }
} elsif (defined($domain)) {
######### Dump a domain configuration
  my $domain = Domain::create($domain);
  if ($domain->getPref('addlistcallout') eq 'true') {
      $domain->dumpLocalAddresses($slave_db);
  }
  if (!$domain->dumpPrefs($slave_db)) {
    exit 1;
  } 
}
$time_in{'dumping_domains_pref'} = time() - $previous_time;
print_time('dumping_domains_pref');
$previous_time = time();

## dump archiving and copy to rules
my $dumpcmd = $conf->getOption('SRCDIR')."/bin/dump_archiving.pl";
my $dumpret = `$dumpcmd`;

$time_in{'dumping_archiving'} = time() - $previous_time;
print_time('dumping_archiving');
$previous_time = time();

$slave_db->disconnect();
print "DUMPSUCCESSFUL";
if ($debug_time) {
	
}
exit 0;

#####################################
## parseDestinations

sub parseDestinations {
  my $d_ref = shift;
  my @domain_list = @$d_ref;
  my %domain_dest;
 
  my $options = ""; 
  for my $domain (@domain_list) {
    $options = "";
    # get destination hosts and port
    my $port = 25;
    my $destinations = $domain->{'destination'};
    if ($domain->{'destination'} =~ m/(.*)\/(\d+)+$/ ) {
      $destinations = $1;
      $port = $2;
    }
    my $dest_str = $destinations;
   
    my @dest_hosts;  
    # parse for different hosts
    while ($dest_str =~ m/^:?([a-zA-Z0-9\.\-\_\/]+(::\d+)?)(.*)/) {
      push @dest_hosts, $1."::$port";
      $dest_str = "";
      if (defined($3)) {
        $dest_str = $3;
        next;
      }
    
      if (defined($2) && $2 !~ /::\d+$/) {
        $dest_str = $2;
      	next;
      }
      
    }

    # parse for custom options
    if ($dest_str =~ m/^\s*([^\/]+)$/) {
      $options = $1;   
      $options =~ s/^\s*//;
      $options =~ s/\s*$//;
    }
    my $name = $domain->{'name'};
    $domain_dest{$name} = { 
    	            id => $domain->{'id'},
    	            callout => $domain->{'callout'} ,
    	            altcallout => $domain->{'altcallout'} ,
    	            ldapcalloutserver => $domain->{'ldapcalloutserver'},
    	            ldapcalloutparam => $domain->{'ldapcalloutparam'},
    	            adcheck => $domain->{'adcheck'} , 
                    addlistcallout => $domain->{'addlistcallout'},
                    addlist_posters => $domain->{'addlist_posters'},              
                    extcallout => $domain->{'extcallout'} ,
                    extcallout_type => $domain->{'extcallout_type'} , 
                    extcallout_param => $domain->{'extcallout_param'} , 
    	            forward_by_mx => $domain->{'forward_by_mx'} , 
    	            greylist => $domain->{'greylist'} ,
    	            destinations =>  [ @dest_hosts ],
                    destination => $domain->{'destination'},
                    batv_check => $domain->{'batv_check'},
                    batv_secret => $domain->{'batv_secret'},
                    prevent_spoof => $domain->{'prevent_spoof'},
                    dkim_domain => $domain->{'dkim_domain'},
                    dkim_selector => $domain->{'dkim_selector'},
                    dkim_pkey => $domain->{'dkim_pkey'},
                    require_incoming_tls => $domain->{'require_incoming_tls'},
                    require_outgoing_tls => $domain->{'require_outgoing_tls'},
                    options => $options };
  }

  return %domain_dest;
}

#####################################
## dumpDomainsFile

sub dumpDomainsFile {
  my $d_ref = shift;
  my %domains = %$d_ref;
  my $filepath = shift;

  my $locktime = 20;
  my $lockfile = $filepath.'/dump.lock';
  my $lockedtime = 0;
  my $locked = 0;
  while( $lockedtime < $locktime ) {
    if (! -f $lockfile ) {
      if (!touch($lockfile)) {
        print "CANNOTTOUCHLOCK $lockfile\n";
        exit 1;
      }
      $locked = 1;
      last;
    }
    sleep(1);
    print "Locked, waiting...\n";
    $lockedtime++;
  }
  if (! $locked) {
    print "CANNOTGETLOCK $lockfile\n";
    exit 1;
  }
  
  if ( !open(DOMAINSFILE, ">/tmp/domains.list") ) {
    return 0;
  }
  
  if ( !open(CALLOUTFILE, ">$filepath/domains_to_callout.list")) {
    return 0;
  }
  if ( !open(ALTCALLOUTFILE, ">$filepath/domains_to_altcallout.list")) {
    return 0;
  }
  if ( !open(ADCHECKFILE, ">$filepath/domains_to_adcheck.list")) {
    return 0;
  }
  if ( !open(ADDLISTCALLOUTFILE, ">$filepath/domains_to_addlistcallout.list")) {
    return 0;
  }
  if ( !open(EXTCALLOUTFILE, ">$filepath/domains_to_extcallout.list")) {
    return 0;
  }
  if ( !open(MXEDFILE, ">$filepath/domains_to_mx.list")) {
    return 0;
  }
  if ( !open(GREYLISTFILE, ">$filepath/domains_to_greylist.list")) {
    return 0;
  }
  if ( !open(RELAYACCEPTEDDESTFILE, ">$filepath/relay_accepteddest.list")) {
    return 0;
  }
  if ( !open(BATVCHECKFILE, ">$filepath/domains_to_check_batv.list")) {
    return 0;
  }
  if ( !open(PREVENTSPOOFFILE, ">$filepath/domains_to_prevent_spoof.list")) {
    return 0;
  }
  if ( !open(REQUIREOUTGOINGTLS, ">$filepath/local_domains_require_outgoing_tls.list")) {
    return 0;
  }
  if ( !open(REQUIREINCOMINGTLS, ">$filepath/local_domains_require_incoming_tls.list")) {
    return 0;
  }
  
  if ( !open(DKIMFILE, ">$filepath/domains_to_dkim.list")) {
    return 0;
  }
  if ( !open(SNMPDOMAINSFILE, ">/tmp/snmpdomains.list") ) {
    return 0;
  }

  my @list;
  foreach my $domain_name ( keys %domains) {
  	next if ($domain_name =~ m/^\*$/);
    push @list, $domain_name;
  }
  my @sorted_list = sort @list;
  if ( defined($domains{'*'}) ) {
     push @sorted_list, '*';
  }
   	
  foreach my $domain_name (@sorted_list) {
    # add domain name
    my $line = $domain_name.":\t\t";
    
    # add each destination server
    foreach my $det ( @{ $domains{$domain_name}{destinations} }) {
      if ($domains{$domain_name}{options} =~ m/no_randomize/) {
        $line .= $det.":+:";
      } else {
        $line .= $det."\:";
      }
    }
    $line =~ s/\s*\:\s*$//;
    $line =~ s/\s*\:\+\:?\s*$//;
    
    # add destination port

    my $rule = $domain_name.":\t\t";
    my $dest = $domains{$domain_name}{destination};
    $dest =~ s/\//::/;
    $rule .= $dest;
    print DOMAINSFILE $rule."\n";
    
    print SNMPDOMAINSFILE $domains{$domain_name}{'id'}.":".$domain_name."\n";
    
    my $value = $domains{$domain_name}{callout};
    my $altvalue = $domains{$domain_name}{altcallout};
    if ( defined($altvalue)  && $altvalue !~ /^\s*$/ && ($value eq "true" || $value eq "1")) {
      $altvalue =~ s/:/::/g;
      print ALTCALLOUTFILE $domain_name.": ".$altvalue."\n";
    } else {
    
        if ($value eq "true" || $value eq "1") {
            print CALLOUTFILE $domain_name."\n";
        }
    }
    
    my $extcalloutvalue = $domains{$domain_name}{extcallout};
    if ($extcalloutvalue eq "true" || $extcalloutvalue eq "1") {
      print EXTCALLOUTFILE $domain_name."\n";
    }
    my $addlistcallout = $domains{$domain_name}{addlistcallout};
    my $postersfile = $posterspath."/".$domain_name.".posters";
    if ( $addlistcallout eq "true" || $addlistcallout eq "1" ) {
      print ADDLISTCALLOUTFILE $domain_name."\n";
      if (defined($domains{$domain_name}{addlist_posters})) {
        my @posters = split /[\s,;]/, $domains{$domain_name}{addlist_posters};
   
        if ( open(ADDLISTPOSTERS, ">$postersfile")) {
          foreach my $p (@posters) {
           print ADDLISTPOSTERS $p."\n";
          }
          foreach my $p (@masters) {
           print ADDLISTPOSTERS $p->{'hostname'}."\n";
          }
        }
        close ADDLISTPOSTERS;
        chown $uid, $gid, "$postersfile";
      }
    } else {
      if ( -f $postersfile ) {
        unlink $postersfile;
      }
    }
    $value = $domains{$domain_name}{adcheck};
    if ($value eq "true" || $value eq "1") {
      print ADCHECKFILE $domain_name."\n";
      
      if (! -d "$filepath/ldap_callouts") {
      	 mkdir "$filepath/ldap_callouts";
      }
      if ( open(LDAPCALLOUTDATA, ">$filepath/ldap_callouts/$domain_name")) {
      	if ($domains{$domain_name}{ldapcalloutserver}) {
         	print LDAPCALLOUTDATA "server: ".$domains{$domain_name}{ldapcalloutserver}."\n";
      	}
    	if ($domains{$domain_name}{ldapcalloutparam} && $domains{$domain_name}{ldapcalloutparam} =~ m/([^:]+):([^:]+):([^:]+)(?:\:([^:]*))?(?:\:([01]))?/) {
                my $user = $2;
                my $basedn = $1; 
                my $pass = $3;
                my $group = $4;
                my $usessl = $5;
                if (!$usessl && $group && $group =~ m/^(0|1)$/) {
                    $usessl = $1;
                    $group = '';
                }
                $pass =~ s/__C__/:/g;
    		print LDAPCALLOUTDATA "user: ".$user."\n";
    		print LDAPCALLOUTDATA "pass: ".$pass."\n";
    		print LDAPCALLOUTDATA "basedn: ".$basedn."\n";
            if ($usessl && $usessl eq '1') {
              print LDAPCALLOUTDATA "usessl: 1\n";
            }
            if ($group && $group ne '') {
              print LDAPCALLOUTDATA "group: ".$group;
            }
    	}
      	close LDAPCALLOUTDATA;
      }
    }
    $value = $domains{$domain_name}{forward_by_mx};
    if ($value eq "true" || $value eq "1") {
      print MXEDFILE $domain_name."\n";
    }
    $value = $domains{$domain_name}{greylist};
    if ($value eq "true" || $value eq "1") {
      print GREYLISTFILE $domain_name."\n";
    }
    $value = $domains{$domain_name}{batv_check};
    if ($value eq "true" || $value eq "1") {
      print BATVCHECKFILE $domain_name.": ";
      my $secret = $domains{$domain_name}{batv_secret};
      if (!defined($secret) || $secret eq '') {
      	$secret = 'no secret provided';
      }
      print BATVCHECKFILE "$secret\n";
    }
    $value = $domains{$domain_name}{prevent_spoof};
    if ($value eq "true" || $value eq "1") {
      print PREVENTSPOOFFILE $domain_name."\n";
    }
    $value = $domains{$domain_name}{require_outgoing_tls};
    if ($value eq "true" || $value eq "1") {
      print REQUIREOUTGOINGTLS $domain_name."\n";
    }
    $value = $domains{$domain_name}{require_incoming_tls};
    if ($value eq "true" || $value eq "1") {
      print REQUIREINCOMINGTLS $domain_name."\n";
    }
    
    my $dkim_pkey_file = $filepath."/dkim/".$domain_name.".pkey";
    if (-f $dkim_pkey_file) {
            unlink($dkim_pkey_file);
    }
    $value = $domains{$domain_name}{dkim_domain};
    if (defined($value) && $value ne "" && $value ne 'none') {
    	print DKIMFILE $domain_name.": ";
    
        if ($value eq 'default') {
            print DKIMFILE $exim_conf{'dkim_default_domain'}.";".$exim_conf{'dkim_default_selector'}."\n";
        } else {
        	print DKIMFILE $value.";";
        	if (defined($domains{$domain_name}{dkim_selector})) {
        		print DKIMFILE $domains{$domain_name}{dkim_selector}."\n";
        	} else {
        		print DKIMFILE "mailcleaner\n";
        	}
        	if (defined($domains{$domain_name}{dkim_pkey}) && $domains{$domain_name}{dkim_pkey} ne '') {
        		if (open DKIMPKEY, ">$dkim_pkey_file") {
        			print DKIMPKEY $domains{$domain_name}{dkim_pkey}."\n";
        			close DKIMPKEY;
        		}
        	}
        }
    }
  }
  
  close DOMAINSFILE;
  move("/tmp/domains.list","$filepath/domains.list");
  chown $uid, $gid, "$filepath/domains.list";
  close CALLOUTFILE;
  chown $uid, $gid, "$filepath/domains_to_callout.list";
  close ALTCALLOUTFILE;
  chown $uid, $gid, "$filepath/domains_to_altcallout.list";
  close ADCHECKFILE;
  chown $uid, $gid, "$filepath/domains_to_adcheck.list";
  close ADDLISTCALLOUTFILE;
  chown $uid, $gid, "$filepath/domains_to_addlistcallout.list";
  close EXTCALLOUTFILE; 
  chown $uid, $gid, "$filepath/domains_to_extcallout.list";
  close MXEDFILE;
  chown $uid, $gid, "$filepath/domains_to_mx.list";
  close GREYLISTFILE;
  chown $uid, $gid, "$filepath/domains_to_greylist.list";
  close RELAYACCEPTEDDESTFILE; 
  chown $uid, $gid, "$filepath/relay_accepteddest.list";
  close BATVCHECKFILE;
  chown $uid, $gid, "$filepath/domains_to_check_batv.list";
  close PREVENTSPOOFFILE;
  chown $uid, $gid, "$filepath/domains_to_prevent_spoof.list";
  close REQUIREINCOMINGTLS;
  chown $uid, $gid, "$filepath/local_domains_require_incoming_tls.list";
  close REQUIREOUTGOINGTLS;
  chown $uid, $gid, "$filepath/local_domains_require_outgoing_tls.list";
  close DKIMFILE;
  chown $uid, $gid, "$filepath/domains_to_dkim.list";
  close SNMPDOMAINSFILE;
  move("/tmp/snmpdomains.list","$filepath/snmpdomains.list");
  chown $uid, $gid, "$filepath/snmpdomains.list";

  if (-f $lockfile) {
    unlink($lockfile);
  }
  return 1;
}

sub print_time {
	my $what = shift;
	
	print "Time for: $what: ".( int( $time_in{$what} * 10000 ) / 10000 )."s.\n";
}
