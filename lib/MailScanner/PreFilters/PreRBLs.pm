
package MailScanner::PreRBLs;

use strict 'vars';
use strict 'refs';
no  strict 'subs'; # Allow bare words for parameter %'s
#use English; # Needed for $PERL_VERSION to work in all versions of Perl

use IO;
use POSIX qw(:signal_h); # For Solaris 9 SIG bug workaround
use Net::IP;
use Net::CIDR::Lite;
use MCDnsLists;

my $MODULE = "PreRBLs";
my %conf;
my %domainsHostnamesMapFile;

sub initialise {
  MailScanner::Log::InfoLog("$MODULE module initializing...");

  my $confdir = MailScanner::Config::Value('prefilterconfigurations');
  my $configfile = $confdir."/$MODULE.cf";
  %PreRBLs::conf = (
     header => "X-$MODULE",
     putHamHeader => 0,
     putSpamHeader => 1,
     timeOut => 30,
     rbls => '',
     maxrbltimeouts => 3,
     listedtobespam => 1,
     rblsDefsPath => '.',
     whitelistDomainsFile => 'whitelisted_domains.txt',
     TLDsFiles => 'two-level-tlds.txt tlds.txt',
     localDomainsFile => 'domains.list',
     domainsHostnamesMapFile => 'domains_hostnames_map.txt',
     spamhits => 0,
     bsspamhits => 1,
     avoidgoodspf => 0,
     avoidhosts => '',
     debug => 0,
     decisive_field => 'none',
     pos_text => '',
     neg_text => '',
     pos_decisive => 0,
     neg_decisive => 0,
     position => 0
  );

  if (open (CONFIG, $configfile)) {
    while (<CONFIG>) {
      if (/^(\S+)\s*\=\s*(.*)$/) {
       $PreRBLs::conf{$1} = $2;
      }
    }
    close CONFIG;
  } else {
    MailScanner::Log::WarnLog("$MODULE configuration file ($configfile) could not be found !");
  }

  $PreRBLs::dnslists = new MCDnsLists(\&MailScanner::Log::WarnLog, $PreRBLs::conf{debug});
  $PreRBLs::dnslists->loadRBLs( $PreRBLs::conf{rblsDefsPath}, $PreRBLs::conf{rbls}, 'IPRBL DNSRBL BSRBL', 
                                $PreRBLs::conf{whitelistDomainsFile}, $PreRBLs::conf{TLDsFiles}, 
                                $PreRBLs::conf{localDomainsFile}, $MODULE);

  if (-f $PreRBLs::conf{domainsHostnamesMapFile}) {
    if (open (MAPFILE, $PreRBLs::conf{domainsHostnamesMapFile})) {
      while (<MAPFILE>) {
        if (/^(\S+),(.*)$/) {
          $domainsHostnamesMapFile{$1} = $2;
          MailScanner::Log::InfoLog("$MODULE loading domain hostname mapping on $1 to $2");
        }
      }
      close MAPFILE;
    }
  }

  if ($PreRBLs::conf{'pos_decisive'} && ($PreRBLs::conf{'decisive_field'} eq 'pos_decisive' || $PreRBLs::conf{'decisive_field'} eq 'both')) {
    $PreRBLs::conf{'pos_text'} = 'position : '.$PreRBLs::conf{'position'}.', spam decisive';
  } else {
    $PreRBLs::conf{'pos_text'} = 'position : '.$PreRBLs::conf{'position'}.', not decisive';
  }
  if ($PreRBLs::conf{'neg_decisive'} && ($PreRBLs::conf{'decisive_field'} eq 'neg_decisive' || $PreRBLs::conf{'decisive_field'} eq 'both')) {
    $PreRBLs::conf{'neg_text'} = 'position : '.$PreRBLs::conf{'position'}.', ham decisive';
  } else {
    $PreRBLs::conf{'neg_text'} = 'position : '.$PreRBLs::conf{'position'}.', not decisive';
  }
}

sub Checks {
  my $this = shift;
  my $message = shift;

  my $RBLsaysspam = 0;

  my $senderdomain = $message->{fromdomain};
  my $senderip = $message->{clientip};
  
  my $continue = 1;
  my $wholeheader = '';
  my $dnshitcount = 0;
  my $senderhostname = '';
 
  ## try to find sender hostname 
  ## find out any previous SPF control
  foreach my $hl ($global::MS->{mta}->OriginalMsgHeaders($message)) {
    if ($senderhostname eq '' && $hl =~ m/^Received: from (\S+) \(\[$senderip\]/) {
        $senderhostname = $1;
        MailScanner::Log::InfoLog("$MODULE found sender hostname: $senderhostname for $senderip on message ".$message->{id});
    }
    if ($hl =~ m/^X-MailCleaner-SPF: (.*)/) {
    	if ($1 eq 'pass' && $PreRBLs::conf{avoidgoodspf}) {
       	MailScanner::Log::InfoLog("$MODULE not checking against: $senderdomain because of good SPF record for ".$message->{id});
       	$continue = 0;
      } 
      last; ## we can here because X-MailCleaner-SPF will always be after the Received fields.
    }
  }	

  my $checkip = 1;
  my ($data, $hitcount, $header);
  ## first check IP
  if ($continue) {
    if ($senderdomain ne '' && ! $PreRBLs::dnslists->isValidDomain($senderdomain, 1, 'PreRBLs domain validator')) {
        my $hostnameregex = $domainsHostnamesMapFile{$senderdomain};
        if ($hostnameregex && 
            $hostnameregex ne '' &&
            $senderhostname =~ m/$hostnameregex/  
           ) {
            MailScanner::Log::InfoLog("$MODULE not checking IPRBL on ".$message->{clientip}." because domain ".$senderdomain." is whitelisted and sender host ".$senderhostname." is from authorized domain for message ".$message->{id});
            $checkip = 0;
        }
    } 
    ## check if in avoided hosts
    foreach my $avoidhost (split(/,/, $PreRBLs::conf{avoidhosts})) {
      if ($avoidhost =~ m/^[\d\.\:\/]+$/) {
        if ($PreRBLs::conf{debug}) {
          MailScanner::Log::InfoLog("$MODULE should avoid control on IP ".$avoidhost." for message ".$message->{id});
        }
        my $acidr = Net::CIDR::Lite->new();
        eval { $acidr->add_any($avoidhost); };
        if ($acidr->find($message->{clientip})) {
          MailScanner::Log::InfoLog("$MODULE not checking IPRBL on ".$message->{clientip}." because IP is whitelisted for message ".$message->{id});
          $checkip = 0;
        }
      }
      if ($avoidhost =~ m/^[a-zA-Z\.\-\_\d\*]+$/) {
        $avoidhost =~ s/([^\\])\./\1\\\./g;
        $avoidhost =~ s/^\./\\\./g;
        $avoidhost =~ s/([^\\])\*/\1\.\*/g;
        $avoidhost =~ s/^\*/.\*/g;
        if ($PreRBLs::conf{debug}) {
          MailScanner::Log::InfoLog("$MODULE should avoid control on hostname ".$avoidhost." for message ".$message->{id});
        }
        if ($senderhostname =~ m/$avoidhost$/) {
          MailScanner::Log::InfoLog("$MODULE not checking IPRBL on ".$message->{clientip}." because hostname $senderhostname is whitelisted for message ".$message->{id});
          $checkip = 0;
        }
      }
    }
    if ($checkip) {
      ($data, $hitcount, $header) = $PreRBLs::dnslists->check_dns($message->{clientip}, 'IPRBL', "$MODULE (".$message->{id}.")", $PreRBLs::conf{spamhits});
      $dnshitcount = $hitcount;
      $wholeheader .= ','.$header;
      if ($PreRBLs::conf{spamhits} && $dnshitcount >= $PreRBLs::conf{spamhits}) {
  	  $continue = 0;
  	  $message->{isspam} = 1;
  	  $message->{isrblspam} = 1;
      }
    }
  }
  
  ## second check sender domain
  if ($continue && $PreRBLs::dnslists->isValidDomain($senderdomain, 1, 'PreRBLs domain validator')) {
    ($data, $hitcount, $header) = $PreRBLs::dnslists->check_dns($senderdomain, 'DNSRBL', "$MODULE (".$message->{id}.")", $PreRBLs::conf{spamhits});
    $dnshitcount += $hitcount;
    $wholeheader .= ','.$header;
    if ($PreRBLs::conf{spamhits} && $dnshitcount >= $PreRBLs::conf{spamhits}) {
      $continue = 0;
      $message->{isspam} = 1;
      $message->{isrblspam} = 1;
    }
  } elsif ($continue && $PreRBLs::conf{debug}) {
      MailScanner::Log::InfoLog("$MODULE not checking DNSBL against: $senderdomain (whitelisted) for ".$message->{id});
  }
  ## third check backscaterrer
  my $bsdnshitcount = 0;
  if ($continue && $message->{from} eq '' && $checkip) {
    ($data, $hitcount, $header) = $PreRBLs::dnslists->check_dns($message->{clientip}, 'BSRBL', "$MODULE (".$message->{id}.")", $PreRBLs::conf{spamhits}, $PreRBLs::conf{bsspamhits});
    $bsdnshitcount = $hitcount;
    $wholeheader .= ','.$header;
    if ($PreRBLs::conf{bsspamhits} && $bsdnshitcount >= $PreRBLs::conf{bsspamhits}) {
      $continue = 0;
      $message->{isspam} = 1;
      $message->{isrblspam} = 1;
    }
  }
  
  $wholeheader =~ s/^,+//;
  $wholeheader =~ s/,+$//;
  $wholeheader =~ s/,,+/,/;
  
  if ($message->{isspam}) {
    MailScanner::Log::InfoLog("$MODULE result is spam ($wholeheader) for ".$message->{id});
    if ($PreRBLs::conf{'putSpamHeader'}) {
      $global::MS->{mta}->AddHeaderToOriginal($message, $PreRBLs::conf{'header'}, "is spam ($wholeheader) ".$PreRBLs::conf{'pos_text'});
    }

    $message->{prefilterreport} .= ", PreRBLs ($wholeheader, ".$PreRBLs::conf{'pos_text'}.")";
    return 1;
  }
  if ($wholeheader ne '') {
    MailScanner::Log::InfoLog("$MODULE result is not spam ($wholeheader) for ".$message->{id});
    if ($PreRBLs::conf{'putSpamHeader'}) {
       $global::MS->{mta}->AddHeaderToOriginal($message, $PreRBLs::conf{'header'}, "is not spam ($wholeheader) ".$PreRBLs::conf{'neg_text'});
    }
  }

  return 0;
}

sub dispose {
  MailScanner::Log::InfoLog("$MODULE module disposing...");
}

1;
