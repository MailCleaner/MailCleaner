#!/usr/bin/perl -w
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
#   Copyright (C) 2021 John Mertz <git@john.me.tz>
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
#   This script will dump the firewall script
#
#   Usage:
#           dump_firewall.pl


use strict;
use DBI();
use Net::DNS;
if ($0 =~ m/(\S*)\/\S+.pl$/) {
  my $path = $1."/../lib";
  unshift (@INC, $path);
}
require GetDNS;

my $DEBUG = 1;

my %config = readConfig("/etc/mailcleaner.conf");
my $start_script = $config{'SRCDIR'}."/etc/firewall/start";
my $stop_script = $config{'SRCDIR'}."/etc/firewall/stop";
my %services = ( 'web' => ['80|443', 'TCP'],
                 'mysql' => ['3306:3307', 'TCP'],
		 'snmp' => ['161', 'UDP'],
		 'ssh' => ['22', 'TCP'],
		 'mail' => ['25', 'TCP'],
		 'soap' => ['5132', 'TCP']
		);
my $iptables = "/sbin/iptables";
my $ip6tables = "/sbin/ip6tables";

my $lasterror = "";
my $has_ipv6 = 0;

unlink($start_script);
unlink($stop_script);

my $dbh;
$dbh = DBI->connect("DBI:mysql:database=mc_config;host=localhost;mysql_socket=$config{VARDIR}/run/mysql_slave/mysqld.sock",
                        "mailcleaner", "$config{MYMAILCLEANERPWD}", {RaiseError => 0, PrintError => 0})
		 or fatal_error("CANNOTCONNECTDB", $dbh->errstr);

my %masters_slaves = get_masters_slaves();

my $dnsres = Net::DNS::Resolver->new;

# do we have ipv6 ?
my $res=`/sbin/ifconfig | /bin/grep 'Scope:Global'`;
if ($res ne '') {
  $has_ipv6 = 1;
}

my %rules;
get_default_rules();
get_external_rules();

do_start_script() or fatal_error("CANNOTDUMPMYSQLFILE", $lasterror);;
do_stop_script();

print "DUMPSUCCESSFUL";

############################
sub get_masters_slaves {
  my %hosts;

  my $sth = $dbh->prepare("SELECT hostname from master");
  $sth->execute() or fatal_error("CANNOTEXECUTEQUERY", $dbh->errstr);

  while (my $ref = $sth->fetchrow_hashref() ) {
     $hosts{$ref->{'hostname'}} = 1;
  }
  $sth->finish();

  $sth = $dbh->prepare("SELECT hostname from slave");
  $sth->execute() or fatal_error("CANNOTEXECUTEQUERY", $dbh->errstr);

  while (my $ref = $sth->fetchrow_hashref() ) {
    $hosts{$ref->{'hostname'}} = 1;
  }
  $sth->finish();
  return %hosts;
  
}

sub get_default_rules {
 #foreach my $service (keys %services) {
 #   $rules{"localhost $service"} = [ $services{$service}[0], $services{$service}[1], '127.0.0.1' ];
 #}

 foreach my $host (keys %masters_slaves) {
   next if ($host =~ /127\.0\.0\.1/ || $host =~ /^\:\:1$/);

   $rules{"host $host, service mysql"} = [ $services{'mysql'}[0], $services{'mysql'}[1], $host];
   $rules{"host $host, service snmp"} = [ $services{'snmp'}[0], $services{'snmp'}[1], $host];
   $rules{"host $host, service ssh"} = [ $services{'ssh'}[0], $services{'ssh'}[1], $host];
   $rules{"host $host, service soap"} = [ $services{'soap'}[0], $services{'soap'}[1], $host];
 }
 my @subs = getSubnets();
 foreach my $sub (@subs) {
   $rules{"host $sub, service ssh"} = [ $services{'ssh'}[0], $services{'ssh'}[1], $sub ];
 }
}

sub get_external_rules {
  my $sth = $dbh->prepare("SELECT service, port, protocol, allowed_ip FROM external_access");
  $sth->execute() or fatal_error("CANNOTEXECUTEQUERY", $dbh->errstr);

  while (my $ref = $sth->fetchrow_hashref() ) {
     #next if ($ref->{'allowed_ip'} !~ /^(\d+.){3}\d+\/?\d*$/);
     next if ($ref->{'port'} !~ /^\d+[\:\|]?\d*$/);
     next if ($ref->{'protocol'} !~ /^(TCP|UDP|ICMP)$/i);
     foreach my $ip (expand_host_string($ref->{'allowed_ip'})) {
       if ($ip =~ m/^[0-9\.\:]{3,40}\/?\d*$/) {
           $rules{"host ".$ip.", service ".$ref->{'service'}} = [ $ref->{'port'}, $ref->{'protocol'}, $ip];
       }
     }
  }
 
  ## check snmp UDP
  foreach my $rulename (keys %rules) {
     if ($rulename =~ m/([^,]+), service snmp/) {
        $rules{$1.", service snmp UDP"} = [ 161, 'UDP', $rules{$rulename}[2]];
     }
  }
 
  ## enable submission port
  foreach my $rulename (keys %rules) {
     if ($rulename =~ m/([^,]+), service mail/) {
     	$rules{$1.", service mail submission"} = [ 587, 'TCP', $rules{$rulename}[2]];
     }
  }
  ## do we need obsolete SMTP SSL port ?
  $sth = $dbh->prepare("SELECT tls_use_ssmtp_port FROM mta_config where stage=1");
  $sth->execute() or fatal_error("CANNOTEXECUTEQUERY", $dbh->errstr);
  while (my $ref = $sth->fetchrow_hashref() ) {
    if ($ref->{'tls_use_ssmtp_port'} > 0) {
    	foreach my $rulename (keys %rules) {
    		if ($rulename =~ m/([^,]+), service mail/) {
    			$rules{$1.", service smtps"} = [ 465, 'TCP', $rules{$rulename}[2] ];
       		}
    	}
    }
  }
}

sub do_start_script {
  if ( !open(START, ">$start_script") ) { 
     $lasterror = "Cannot open start script";
     return 0;
  }

  print START "#!/bin/sh\n";

  print START "/sbin/modprobe ip_tables\n";
  if ($has_ipv6) {
    print START "/sbin/modprobe ip6_tables\n";
  }

  print START "\n# policies\n";
  print START $iptables." -P INPUT DROP\n";
  print START $iptables." -P FORWARD DROP\n";
  if ($has_ipv6) {
    print START $ip6tables." -P INPUT DROP\n";
    print START $ip6tables." -P FORWARD DROP\n";
  }

  print START "\n# bad packets:\n";
  print START $iptables." -A INPUT -p tcp ! --syn -m state --state NEW -j DROP\n";
  if ($has_ipv6) {
    print START $ip6tables." -A INPUT -p tcp ! --syn -m state --state NEW -j DROP\n";
  }

  print START "# local interface\n";
  print START $iptables." -A INPUT -p ALL -i lo -j ACCEPT\n";
  if ($has_ipv6) {
    print START $ip6tables." -A INPUT -p ALL -i lo -j ACCEPT\n";
  }

  print START "# accept\n";
  print START $iptables." -A INPUT -p ALL -m state --state ESTABLISHED,RELATED -j ACCEPT\n";
  if ($has_ipv6) {
    print START $ip6tables." -A INPUT -p ALL -m state --state ESTABLISHED,RELATED -j ACCEPT\n";
  }

  print START $iptables." -A INPUT -p ICMP --icmp-type 8 -j ACCEPT\n";
  if ($has_ipv6) {
    print START $ip6tables." -A INPUT -p ipv6-icmp -j ACCEPT\n";
  }

  foreach my $description (sort keys %rules) {
    print START "\n# $description\n";
    my @ports = split '\|', $rules{$description}[0];
    foreach my $port (@ports) {
      my $host = $rules{$description}[2];
      if ($host =~ m/\:/) {
        if ($has_ipv6) {
          print START $ip6tables." -A INPUT -p ".$rules{$description}[1]." --dport ".$port." -s ".$host." -j ACCEPT\n";
        }
      } else {

        my $reply = $dnsres->query($host, "AAAA");
        if ($reply) {
            print START $ip6tables." -A INPUT -p ".$rules{$description}[1]." --dport ".$port." -s ".$host." -j ACCEPT\n";
        }
        print START $iptables." -A INPUT -p ".$rules{$description}[1]." --dport ".$port." -s ".$host." -j ACCEPT\n";
        if ($host eq '0.0.0.0/0' && $has_ipv6) {
          print START $ip6tables." -A INPUT -p ".$rules{$description}[1]." --dport ".$port." -j ACCEPT\n";
        }
      }
    }
  }
  
  my @blacklist_files = ('/usr/mailcleaner/etc/firewall/blacklist.txt', '/usr/mailcleaner/etc/firewall/blacklist_custom.txt');
  my $blacklist = 0;
  my $blacklist_script = '/usr/mailcleaner/etc/firewall/blacklist';
  unlink $blacklist_script;
  foreach my $blacklist_file (@blacklist_files) {
     if ( -e $blacklist_file ) {
       if ( open(BLACK_IP, '<', $blacklist_file) ) {
            open(BLACKLIST, '>>', $blacklist_script);
            if ( $blacklist == 0 ) {
               print BLACKLIST "#! /bin/sh\n\n";
               print BLACKLIST "$iptables -N BLACKLIST\n";
               print BLACKLIST "$iptables -A BLACKLIST -j RETURN\n";
               print BLACKLIST "$iptables -I INPUT 1 -j BLACKLIST\n\n";

            }
	    $blacklist = 1;
            foreach my $IP (<BLACK_IP>) {
                chomp($IP);
                print BLACKLIST "$iptables -I BLACKLIST 1 -s $IP -j DROP\n";
            }
            close BLACKLIST;
            close BLACK_IP;
       }
     }
  }
  if ( $blacklist == 1 ) {
    chmod 0755, $blacklist_script;
    print START "\n$blacklist_script\n";
  }
   
  close START;

  chmod 0755, $start_script;
}

sub do_stop_script {
   if ( !open(STOP, ">$stop_script") ) {
     $lasterror = "Cannot open stop script";
     return 0;
   }

   print STOP "#!/bin/sh\n";

   print STOP $iptables." -P INPUT ACCEPT\n";
   print STOP $iptables." -P FORWARD ACCEPT\n";
   print STOP $iptables." -P OUTPUT ACCEPT\n";
   if ($has_ipv6) {
     print STOP $ip6tables." -P INPUT ACCEPT\n";
     print STOP $ip6tables." -P FORWARD ACCEPT\n";
     print STOP $ip6tables." -P OUTPUT ACCEPT\n";
   }

   print STOP $iptables." -F\n";
   print STOP $iptables." -X\n";
   if ($has_ipv6) {
     print STOP $ip6tables." -F\n";
     print STOP $ip6tables." -X\n";
   }

   
   close STOP;
   chmod 0755, $stop_script;
}

sub getSubnets() {
  my $ifconfig = `/sbin/ifconfig`;
  my @subs = ();
  foreach my $line (split("\n", $ifconfig)) {
    if ($line =~ m/\s+inet\ addr:([0-9.]+)\s+Bcast:[0-9.]+\s+Mask:([0-9.]+)/) {
      my $ip = $1;
      my $mask = $2;
      if ($mask && $mask =~ m/\d/) {
        my $ipcalc = `/usr/bin/ipcalc $ip $mask`;
        foreach my $subline (split("\n", $ipcalc)) {
           if ($subline =~ m/Network:\s+([0-9.]+\/\d+)/) {
              push @subs, $1;
           }
        } 
      }
    }
  }
  return @subs;
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
                ($var, $value) = split(/\s*=\s*/, $_, 2);
                $config{$var} = $value;
        }
        close CONFIG;
        return %config;
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

sub expand_host_string
{
    my $string = shift;
    my $dns = GetDNS->new();
    return $dns->dumper($string);
}
