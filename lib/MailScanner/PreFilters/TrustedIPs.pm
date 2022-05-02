
package MailScanner::TrustedIPs;

use strict 'vars';
use strict 'refs';
no  strict 'subs'; # Allow bare words for parameter %'s

use IO;
use POSIX qw(:signal_h); # For Solaris 9 SIG bug workaround

my $MODULE = "TrustedIPs";
my %conf;

sub initialise {
  MailScanner::Log::InfoLog("$MODULE module initializing...");

  my $confdir = MailScanner::Config::Value('prefilterconfigurations');
  my $configfile = $confdir."/$MODULE.cf";
  %TrustedIPs::conf = (
     header => "X-$MODULE",
     putHamHeader => 0,
     putDetailedHeader => 1,
     scoreHeader => "X-$MODULE-score",
     maxSize => 0,
     timeOut => 100,
     debug => 0,
     decisive_field => 'neg_decisive',
     neg_text => '',
     neg_decisive => 0,
     position => 0
  );

  if (open (CONFIG, $configfile)) {
    while (<CONFIG>)  	{
      if (/^(\S+)\s*\=\s*(.*)$/) {
       $TrustedIPs::conf{$1} = $2;
      }
    }
    close CONFIG;
  } else {
    MailScanner::Log::WarnLog("$MODULE configuration file ($configfile) could not be found !");
  }
  
  $TrustedIPs::conf{'neg_text'} = 'position : '.$TrustedIPs::conf{'position'}.', ham decisive';
}

sub Checks {
  my $this = shift;
  my $message = shift; 
  
  foreach my $hl ($global::MS->{mta}->OriginalMsgHeaders($message)) {
    if ($hl =~ m/^X-MailCleaner-TrustedIPs: Ok/i) {
      my $string = 'sending IP is in Trusted IPs';
      if ($TrustedIPs::conf{debug}) {
          MailScanner::Log::InfoLog("$MODULE result is ham ($string) for ".$message->{id});
      }
      if ($TrustedIPs::conf{'putHamHeader'}) {
        $global::MS->{mta}->AddHeaderToOriginal($message, $TrustedIPs::conf{'header'}, "is ham ($string) ".'position : '.$TrustedIPs::conf{'position'}.', ham decisive');
      }
    ;
      $message->{prefilterreport} .= ", $MODULE ($string, ".'position : '.$TrustedIPs::conf{'position'}.', ham decisive'.")";

      return 0;
    }

    if ($hl =~ m/^X-MailCleaner-White-IP-DOM: WhIPDom/i) {
      my $string = 'sending IP is whitelisted for this domain';
      if ($TrustedIPs::conf{debug}) {
          MailScanner::Log::InfoLog("$MODULE result is ham ($string) for ".$message->{id});
      }
      if ($TrustedIPs::conf{'putHamHeader'}) {
        $global::MS->{mta}->AddHeaderToOriginal($message, $TrustedIPs::conf{'header'}, "is ham ($string) ".'position : '.$TrustedIPs::conf{'position'}.', ham decisive');
      }
      $message->{prefilterreport} .= ", $MODULE ($string, ".$TrustedIPs::conf{'position'}.', ham decisive'.")";

      return 0;
    }

  }

  return 1;
}

sub dispose {
  MailScanner::Log::InfoLog("$MODULE module disposing...");
}

1;
