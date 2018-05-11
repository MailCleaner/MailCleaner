
package MailScanner::TrustedSources;

use strict 'vars';
use strict 'refs';
no  strict 'subs'; # Allow bare words for parameter %'s
#use English; # Needed for $PERL_VERSION to work in all versions of Perl

use IO;
use POSIX qw(:signal_h); # For Solaris 9 SIG bug workaround
use Net::IP;
use MCDnsLists;

my $MODULE = "TrustedSources";
my %conf;

sub initialise {
  MailScanner::Log::InfoLog("$MODULE module initializing...");

  my $confdir = MailScanner::Config::Value('prefilterconfigurations');
  my $configfile = $confdir."/$MODULE.cf";
  my %domainsToSPF_;
  %TrustedSources::conf = (
     header => "X-$MODULE",
     putHamHeader => 0,
     putSpamHeader => 1,
     putDetailedHeader => 1,
     scoreHeader => "X-$MODULE-score",
     maxSize => 0,
     timeOut => 100,
     useAllTrusted => 1,
     useAuthServers => 1,
     useSPFOnLocal => 1,
     useSPFOnGlobal => 0,
     authServers => "",
     authString => "",
     localDomainSFile => "",
     builtInDomainsFile => "",
     whiterbls => '',
     rwlhits => 1,
     debug => 0,
  );
  @TrustedSources::domainsToSPF_ = ();
  %TrustedSources::localDomains_;

  if (open (CONFIG, $configfile)) {
    while (<CONFIG>)  	{
      if (/^(\S+)\s*\=\s*(.*)$/) {
       $TrustedSources::conf{$1} = $2;
      }
    }
    close CONFIG;
  } else {
    MailScanner::Log::WarnLog("$MODULE configuration file ($configfile) could not be found !");
  }
  my @a = ();
  foreach my $d (split(' ', $TrustedSources::conf{domainsToSPF})) {
  	$d =~ s/\.?\*/\.\*/g;
  	push @a, $d;
  	if ($TrustedSources::conf{debug}) {
  	  MailScanner::Log::InfoLog("$MODULE added SPF domain: $d");
  	}
  }
  @TrustedSources::domainsToSPF_ = @a;
  
  if ($TrustedSources::conf{useSPFOnLocal} && -f $TrustedSources::conf{localDomainSFile}) {
  	if (open (LOCALDOMAINS, $TrustedSources::conf{localDomainSFile})) {
      while(<LOCALDOMAINS>) {
      	if (m/\s*([-_.a-zA-Z]+)/) {
      	  my $dom = lc($1);
      	  $TrustedSources::localDomains_{$dom} = 1;
      	  if ($TrustedSources::conf{debug}) {
  	        MailScanner::Log::InfoLog("$MODULE added local domain: $dom");
  	      }
      	}
      }
      close LOCALDOMAINS;
   	}
  }
  
  if (-f $TrustedSources::conf{builtInDomainsFile}) {
  	if (open (BUILTIN, $TrustedSources::conf{builtInDomainsFile})) {
  	  while (<BUILTIN>) {
  	  	next if (/^\s*#/);
  	  	next if (/^\s*$/);
  	  	my $d = $_;
  	  	$d =~ s/\.?\*/\.\*/g;
  	  	chomp($d);
  	  	push @TrustedSources::domainsToSPF_, $d;
  	  	if ($TrustedSources::conf{debug}) {
  	      MailScanner::Log::InfoLog("$MODULE added builtin domain: $d");
  	    }
  	  }
  	}
  	close BUILTIN
  }

  ## then populate trusted and authenticated servers list
  my @trusted_ips = split / /, MailScanner::Config::Value('trustedips');
  my @auth_ips = split / /, $TrustedSources::conf{'authServers'};
  use Net::CIDR::Lite;
  $TrustedSources::tcidr = Net::CIDR::Lite->new();
  $TrustedSources::tcidripv6 = Net::CIDR::Lite->new();
  $TrustedSources::acidr = Net::CIDR::Lite->new();
  $TrustedSources::acidripv6 = Net::CIDR::Lite->new();
  $TrustedSources::tcidr->add_any('127.0.0.1');
  foreach my $tip (@trusted_ips) {
    if ($tip =~ m/:/) {
        eval { $TrustedSources::tcidripv6->add_any($tip); };
    } else {
        eval { $TrustedSources::tcidr->add_any($tip); };
    }
    if ($TrustedSources::conf{debug}) {
      MailScanner::Log::InfoLog("$MODULE added trusted ip/net: $tip");
    }
  }
  $TrustedSources::acidr->add_any('127.0.0.2');
  foreach my $tip (@auth_ips) {
    if ($tip =~ m/:/) {
       eval { $TrustedSources::acidripv6->add_any($tip); };
    } else {
       eval { $TrustedSources::acidr->add_any($tip); };
    }
    if ($TrustedSources::conf{debug}) {
      MailScanner::Log::InfoLog("$MODULE adding auth server: $tip");
    }
  }
  
  $TrustedSources::conf{whiterbls} .= ' '.$TrustedSources::conf{spflists};
  
  $TrustedSources::dnslists = new MCDnsLists(\&MailScanner::Log::WarnLog, $TrustedSources::conf{debug});
  $TrustedSources::dnslists->loadRBLs( $TrustedSources::conf{rblsDefsPath}, $TrustedSources::conf{whiterbls}, 'IPRWL SPFLIST', 
                                '', '', 
                                '', $MODULE);
}

sub Checks {
  my $this = shift;
  my $message = shift; 
  
  my $ham = 1;
  my $reason = "";
  
  ## first fetch received server list
  my $h_id = 0;
  my %full_received;
  my %ip_received;
  my $twolines = 0;
  foreach my $hl ($global::MS->{mta}->OriginalMsgHeaders($message)) {
    #print STDERR "Got line: $hl\n";
    #if ($hl =~ m/^received:\s+from\s+(?:\S+\s+)?\(?(?:\S+\s+)?\(?\[?(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\]?\)?/i ) {
    if ($hl =~ m/^received:\s+from[^\[]+\[(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}|[a-f0-9:]{5,72})\]/i ) {
      $h_id++;
      $full_received{$h_id} = $hl;
      $ip_received{$h_id} = $1;
      $twolines = 0;
      #print STDERR "Got IP 1 :".$ip_received{$h_id}."\n";
      next;
    }
    # we need to search for received fields that have IP address on another line
    if ($hl =~ m/^received:\s+from/i) {
       $twolines = 1;
    }
    #if ($twolines && $hl =~ m/\(?\[?(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\]?\)?/i) {
    if ($twolines && $hl =~ m/\(?\[?(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}|[a-f0-9:]{5,72})\]?\)?/i) {
      my $potential_ip = $1;
      # avoid time expression that could be mistaken as ipv6
      if ($potential_ip =~ m/^\d{1,2}\:\d{1,2}\:\d{1,2}$/) {
          next;
      }
      # avoid non valid IPs
      if ($potential_ip !~ m/[\.\:]/) {
          next;
      }
      $h_id++;
      $full_received{$h_id} = $hl;
      $ip_received{$h_id} = $potential_ip;

      #print STDERR "Got IP 2 :".$ip_received{$h_id}."\n";
      
      $twolines = 0;
      next;
    }
    #if (!defined($ip_received{$h_id}) && $hl =~ m/\(?\[?(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\]?\)?/i) {
    #  $ip_received{$h_id} = $1;
    #}
    
    if ($hl =~ m/^\s+/ && $h_id>0) {
      $full_received{$h_id} .= $hl;
      next;
    }
    last if ($hl =~ m/^[^rR ]\w+: / );
  }

  my $usealltrusted = $TrustedSources::conf{'useAllTrusted'};
  if ($h_id < 1) {
    if ($usealltrusted) {
        MailScanner::Log::InfoLog("$MODULE result is not spam (no received headers, trusted path) for ".$message->{id});
        $message->{prefilterreport} .= ", $MODULE (no received headers, trusted path)";
        return 0;
    }
    MailScanner::Log::InfoLog("$MODULE result is unknown (no received headers !) for ".$message->{id});
    $message->{prefilterreport} .= ", $MODULE (no received headers !)";
    return 1;
  }

  foreach my $h (sort keys %full_received) {
  	$full_received{$h} =~ s/\s+/ /g;
  }
 
  my $useauthservers = $TrustedSources::conf{'useAuthServers'};
  my $usespf = $TrustedSources::conf{'useSPF'};

  my $self_auth_server = 0;
  ## first check if we already are an authentfied relay 
  if ($full_received{1} =~ m/stage1 with [es]?smtps?a/) {
    $self_auth_server = 1;
    if ($TrustedSources::conf{debug}) {
      MailScanner::Log::InfoLog("$MODULE message authenticated by local SMTP (from: ".$ip_received{1}.")");
    }   
  }

  ## then find out first untrusted server or authenticated server, whichever comes first
  my $auth_server = 0;
  my $first_untrusted = 0;
  my $authsearchvalue = $TrustedSources::conf{'authString'};
  foreach my $h (sort keys %full_received) {

    #print STDERR "Will test IP :".$ip_received{$h}."\n";
    if ($self_auth_server > 0) {
      last;
    }
    $full_received{$h} =~ s/\s+/ /g;

    if ($TrustedSources::conf{debug}) {
      MailScanner::Log::InfoLog("$MODULE testing received IP: ".$ip_received{$h});
    }
  	
    if ( ( $ip_received{$h} =~ /:/ && $TrustedSources::acidripv6->find($ip_received{$h}) ) ||
         ( $ip_received{$h} !~ /:/ && $TrustedSources::acidr->find($ip_received{$h}) ) ) {
      $auth_server = $h;
      if ($TrustedSources::conf{debug}) {
  	    MailScanner::Log::InfoLog("$MODULE authenticated server at: ".$ip_received{$h});
  	  }
      next;
    }
   
    if ( ( $ip_received{$h} =~ /:/ && ! $TrustedSources::tcidripv6->find($ip_received{$h}) ) ||
         ( $ip_received{$h} !~ /:/ && ! $TrustedSources::tcidr->find($ip_received{$h}) ) ) { 
      $first_untrusted = $h;
      if ($TrustedSources::conf{debug}) {
  	    MailScanner::Log::InfoLog("$MODULE untrusted server at: ".$ip_received{$h});
  	  }
      last;
    }
  }

  if ($self_auth_server > 0) {
    my $string = "message authenticated by SMTP from [".$ip_received{1}."]";
    if ($TrustedSources::conf{debug}) {
        MailScanner::Log::InfoLog("$MODULE $string");
    }
    MailScanner::Log::InfoLog("$MODULE result is ham ($string) for ".$message->{id});
    if ($TrustedSources::conf{'putHamHeader'}) {
      $global::MS->{mta}->AddHeaderToOriginal($message, $TrustedSources::conf{'header'}, "is ham ($string)");
    }
    $message->{prefilterreport} .= ", $MODULE ($string)";
    return 0;
  }

  my $authenticated = 0;
  if ($useauthservers && ($auth_server > 0) && defined($full_received{$auth_server+1})) {
    if ( $authsearchvalue eq "" || $full_received{$auth_server+1} =~ m/$authsearchvalue/) {

      my $string = "authenticated server found at [".$ip_received{$auth_server}."] from [".$ip_received{$auth_server+1}."]";
      if ($TrustedSources::conf{debug}) {
        MailScanner::Log::InfoLog("$MODULE $string");
      }
      MailScanner::Log::InfoLog("$MODULE result is ham ($string) for ".$message->{id});
      if ($TrustedSources::conf{'putHamHeader'}) {
        $global::MS->{mta}->AddHeaderToOriginal($message, $TrustedSources::conf{'header'}, "is ham ($string)");
      }
      $message->{prefilterreport} .= ", $MODULE ($string)";
      return 0;
    }
  }

  if ($usealltrusted && ($first_untrusted <1) ) {
        MailScanner::Log::InfoLog("$MODULE result is ham (all trusted path) for ".$message->{id});
    if ($TrustedSources::conf{'putHamHeader'}) {
      $global::MS->{mta}->AddHeaderToOriginal($message, $TrustedSources::conf{'header'}, "is ham (all trusted path)");
    }
    $message->{prefilterreport} .= ", $MODULE (all trusted path)";
    return 0;
  }
  
  if ((! $message->{from} eq "") && $this->wantSPF($message) && ( $first_untrusted > 0)) {
    use Mail::SPF;
    my $spf_from = $this->validatedFrom($message);
    if ($TrustedSources::conf{debug}) {
          MailScanner::Log::InfoLog("$MODULE will do SPF check for: ".$spf_from);
    }
    my $returnspf = 1;
eval {
    my $spf_server  = Mail::SPF::Server->new(max_dns_interactive_terms => 20);
    my $request     = Mail::SPF::Request->new(
                             scope => 'mfrom',
                             identity => $spf_from,
                             ip_address => $ip_received{$first_untrusted}
                       );

    my $result      = $spf_server->process($request);
    if ($TrustedSources::conf{debug}) {
  	    MailScanner::Log::InfoLog("$MODULE SPF result for ".$ip_received{$first_untrusted}. " and ".$spf_from.": [".$result->code."] ".$result->local_explanation);
  	}
    if ($result->code eq "pass" && $result->local_explanation !~ m/mechanism \'all\' matched/) {
  	  my $string = "SPF record matches ".$message->{from}." [".$ip_received{$first_untrusted}."]";
  	  MailScanner::Log::InfoLog("$MODULE result is ham ($string) for ".$message->{id});
          if ($TrustedSources::conf{'putHamHeader'}) {
             $global::MS->{mta}->AddHeaderToOriginal($message, $TrustedSources::conf{'header'}, "is ham ($string)");
          }
          $message->{prefilterreport} .= ", $MODULE ($string)";
          $returnspf = 0;   
     }
    };
    if (!$returnspf) {
     return 0;
    }
  }
  
  ## check DNS white lists
  my $continue = 1;
  my $wholeheader = '';
  my $dnshitcount = 0;
  my ($data, $hitcount, $header) = $TrustedSources::dnslists->check_dns($message->{clientip}, 'IPRWL', "$MODULE (".$message->{id}.")", $TrustedSources::conf{rwlhits});
  $dnshitcount = $hitcount;
  if ($TrustedSources::conf{rwlhits} && $dnshitcount >= $TrustedSources::conf{rwlhits}) {
  	my $string = "sender IP address is whitelisted by ".$header;
    MailScanner::Log::InfoLog("$MODULE result is ham ($string) for ".$message->{id});
  	if ($TrustedSources::conf{'putHamHeader'}) {
             $global::MS->{mta}->AddHeaderToOriginal($message, $TrustedSources::conf{'header'}, "is ham ($string)");
    }
  	$message->{prefilterreport} .= " $MODULE (".$header.")";
    return 0;
  }
  
  return 1;
}

sub dispose {
  MailScanner::Log::InfoLog("$MODULE module disposing...");
}

sub wantSPF {
  my $this = shift;
  my $message = shift;
  
  my $from = $this->validatedFrom($message);
  
  if ($TrustedSources::conf{useSPFOnGlobal}) {
  	return 1;
  }
  if ($from !~ m/\S+\@(\S+)/) {
  	return 0;
  }
  my $domain = $1;
  $domain = lc($domain);
  
  my ($data, $hitcount, $header) = $TrustedSources::dnslists->check_dns($domain, 'SPFLIST', "$MODULE (".$message->{id}.")", 1);
  if ($hitcount) {
  	return 1;
  }
   
  foreach my $d (@TrustedSources::domainsToSPF_) {
  	if ($domain =~ m/^$d$/i) {
  	  return 1;
  	}
  }
  if (defined($TrustedSources::localDomains_{$domain}) && $TrustedSources::localDomains_{$domain} > 0) {
 	return 1;
  }
  return 0;
}

sub validatedFrom {
	my $this = shift;
	my $message = shift;
	
	my $from = $message->{from};
	my $res = $from;
	if ($from =~ /^SRS\d=[^=@]+\=[^=@]+=([^=@]+)=([^=@]+)\@/i) {   
        $res = $2.'@'.$1;
        if ($TrustedSources::conf{debug}) {
          MailScanner::Log::InfoLog("$MODULE (".$message->{id}.") SRS encoded sender decoded to: ".$res. " (from ".$from.")");
        }
	}
	return $res;
}
1;
