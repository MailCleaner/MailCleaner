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
#   This script will check for newly available updates and apply them
#

use strict;
use DBI;
use LWP::UserAgent;
use Getopt::Std;
use Proc::ProcessTable;

my $CVSHOST='cvs.mailcleaner.net';
my $REPORTHOST='reselleradmin.mailcleaner.net';

my %config = readConfig("/etc/mailcleaner.conf");

sub usage() {
  print STDERR << "EOF";
usage: $0 [-rh]

-r     : randomize start of script, for periodic calls
-h     : display usage
EOF
  exit;
}

my $minsleeptime=0;
my $maxsleeptime=120;

my $t = new Proc::ProcessTable;
foreach my $p ( @{ $t->table } ) {
  if (defined($p->cmndline) && defined($p->pid) && $p->cmndline =~ m/check_update\.pl/ && $p->pid != $$ ) {
    `echo "Already running" >> $config{'VARDIR'}/log/mailcleaner/update.log`;
    exit(1);
  }
}

my %options=();
getopts(":rh", \%options);

my $randomize = 0;
if (defined $options{r}) {
  my $delay = int(rand($maxsleeptime)) + $minsleeptime;
  my $date = `date "+%Y-%m-%d %H:%M:%S"`;
  chomp($date);
  `echo "[$date] sleeping for $delay seconds..." >> $config{'VARDIR'}/log/mailcleaner/update.log`;  
  sleep($delay);
}
if (defined $options{h}) {
  usage();
}

##########################
## get http proxy settings:
##########################
my $dbh = DBI->connect("DBI:mysql:database=mc_config;mysql_socket=$config{'VARDIR'}/run/mysql_slave/mysqld.sock",
                                         "mailcleaner","$config{'MYMAILCLEANERPWD'}", {RaiseError => 1, PrintError => 1} );
my $http_proxy = "";
if ($dbh) {
  my $proxy_sth =  $dbh->prepare("SELECT http_proxy FROM system_conf");
  if ($proxy_sth->execute()) {
    if (my $proxyline = $proxy_sth->fetchrow_hashref()) {
      my $proxy = $proxyline->{'http_proxy'};
      if (defined($proxy) && $proxy && $proxy =~ m/\S+/) {
        $config{'HTTPPROXY'} = $proxy;
      }
    }
    $proxy_sth->finish();
  }
}


##################################
## check for remote custom scripts
##################################
## exec personalized scripts for maintenance
my $exec_file  = $config{'VARDIR'}."/spool/mailcleaner/scripts/exec.sh";
my $scp = "scp -q -o PasswordAuthentication=no -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no mcscp\@$CVSHOST:/scripts/$config{'CLIENTID'}/exec.sh $exec_file >/dev/null 2>&1";
my $scp_res = `$scp`;
if (-f $exec_file) {
  chmod 0755, $exec_file;
  `$exec_file`;
  unlink $exec_file;	
}

#########################
## get last patch applied
#########################
my $date = `date "+%Y-%m-%d %H:%M:%S"`;
chomp($date);
`echo "[$date] looking for updates..." >> $config{'VARDIR'}/log/mailcleaner/update.log`;

my $lastpatch = "";
if ($dbh) {
  my $lastpatch_sth =  $dbh->prepare("SELECT id FROM update_patch ORDER BY id DESC LIMIT 1");
  if ($lastpatch_sth->execute()) {
    if (my $lastpatchline = $lastpatch_sth->fetchrow_hashref()) {
      $lastpatch = $lastpatchline->{'id'};
    }
  $lastpatch_sth->finish();
  }

  $dbh->disconnect();

  chomp($lastpatch);
  #print $lastpatch."\n";
}

if ( (! defined($lastpatch)) ||  $lastpatch !~ /\d{10}/ ) {
      $lastpatch = '9999999999';
}

###############
# report status
###############
my $cmdstats = $config{'SRCDIR'}."/bin/get_today_stats.pl -A";
my $stats = `$cmdstats`;
my $cmdspools = $config{'SRCDIR'}."/bin/get_status.pl -p";
my $spools = `$cmdspools`;
chomp($stats);
my $cmdbayes = $config{'SRCDIR'}."/bin/get_bayes_stats.pl";
my $bayescertainty = `$cmdbayes`;
my $nbcertainty = 0;
my $sabcertainty = 0;
if ($bayescertainty =~ m/^(\d+\.\d+)\|(\d+\.\d+)$/) {
  $nbcertainty = $1;
  $sabcertainty = $2;
} else {
  $nbcertainty = $bayescertainty;
}
chomp($nbcertainty);
chomp($sabcertainty);
chomp($spools);
my $probeuri = "/hosts/probe.php?cid=$config{'CLIENTID'}&hid=$config{'HOSTID'}&lp=$lastpatch&s=$stats&sp=$spools&nb=$nbcertainty&sab=$sabcertainty";
chomp($probeuri);
$probeuri = "http://$REPORTHOST".$probeuri;
call_uri($probeuri);


######################################
# now check for new patches throug CVS
######################################

chdir($config{'SRCDIR'}."/updates");
$ENV{'CVSROOT'}=":ext:mccvs\@$CVSHOST:/var/lib/cvs";
$ENV{'CVS_RSH'}="ssh";
# get patch through scp
system($config{'SRCDIR'}."/bin/fetch_updates.sh");

## get downloaded patches list
my @patches = ();
if (opendir(UPDIR, $config{'SRCDIR'}."/updates")) {
  while (my $update_file = readdir(UPDIR)) {
    if ($update_file =~ m/^\d{10}$/) {
      push(@patches, $update_file);
    }
  }
  close(UPDIR);
}

my @sorted_patches = sort(@patches);

## apply all patches that where not previously applied
my $patchcount=0;
my $onepatchfailed = 0;
foreach my $patch (@sorted_patches) {
  if ($patch gt $lastpatch) {
    #print "will apply patch: $patch ... ";
    my $patch_cmd = $config{'SRCDIR'}."/bin/apply_update.sh ".$patch;
    my $patch_res = `$patch_cmd`;
    $patch_res =~ s/\n/ /g;
    #print $patch_res."\n";

    my $patchphpfile = "patched.php";
    if ($patch_res =~ /OK/) {
      $patchcount++;
    } else {
      $patchphpfile = "patchfailed.php";
      $onepatchfailed = 1;
    }
    ###############
    # report status
    ###############
    my $patcheduri = "/hosts/".$patchphpfile."?cid=$config{'CLIENTID'}&hid=$config{'HOSTID'}&pid=$patch&s=$patch_res";
    chomp($patcheduri);
    $patcheduri = "http://$REPORTHOST".$patcheduri;
    call_uri($patcheduri);
  } else {
    #print "patch $patch already applied\n";
  }
}

my $result = "";
if ($patchcount > 0) {
  my $date = `date "+%Y-%m-%d %H:%M:%S"`;
  chomp($date);
  `echo "[$date] $patchcount patches applied" >> $config{'VARDIR'}/log/mailcleaner/update.log`;
  $result = "$patchcount PATCHESAPPLIED";
} else {
  my $date = `date "+%Y-%m-%d %H:%M:%S"`;
  chomp($date);
  if ( $onepatchfailed == 0 ) {
    `echo "[$date] system is up-to-date" >> $config{'VARDIR'}/log/mailcleaner/update.log`;
    $result = "UPTODATE";
  } else {
    `echo "[$date] update exited" >> $config{'VARDIR'}/log/mailcleaner/update.log`;
    $result = "ABORTED";
  }
}

print $result."\n";

########################################
# fetch integrator updated informations
#######################################
my $inturi = "/hosts/int_infos.php?cid=$config{'CLIENTID'}";
chomp($inturi);
$inturi = "http://$REPORTHOST".$inturi;
my $intinfos = call_uri($inturi);
my $cont = $intinfos->content();
if ($cont =~ /Name_T/) {
  `echo "$cont" > $config{'VARDIR'}/spool/mailcleaner/integrator.txt`;
}
exit 0;

####################################################################################
sub call_uri {
  my $uri = shift;

  my $ua = LWP::UserAgent->new;
  $ua->agent("Mailcleaner");

  if (defined($config{'HTTPPROXY'})) {
    $ENV{'http_proxy'} = $config{'HTTPPROXY'};
    #print "setting proxy as: ".$config{'HTTPPROXY'};
    $ua->env_proxy;
  }

  my $req = HTTP::Request->new(GET => $uri);
  my $res = $ua->request($req);
  return $res;
}

####################################################################################
sub readConfig {       # Reads configuration file given as argument.
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

######################################################################################
