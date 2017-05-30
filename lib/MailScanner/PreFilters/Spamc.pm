
package MailScanner::Spamc;

use strict 'vars';
use strict 'refs';
no  strict 'subs'; # Allow bare words for parameter %'s
#use English; # Needed for $PERL_VERSION to work in all versions of Perl

use IO;
use POSIX qw(:signal_h); # For Solaris 9 SIG bug workaround

my $MODULE = "Spamc";
my %conf;

sub initialise {
  MailScanner::Log::InfoLog("$MODULE module initializing...");

  my $confdir = MailScanner::Config::Value('prefilterconfigurations');
  my $configfile = $confdir."/$MODULE.cf";
  %Spamc::conf = (
     command => '/usr/local/bin/spamc -R --socket=__SPAMD_SOCKET__ -s __MAX_SIZE__',
     header => "X-$MODULE",
     putHamHeader => 0,
     putSpamHeader => 1,
     putDetailedHeader => 1,
     scoreHeader => "X-$MODULE-score",
     maxSize => 0,
     timeOut => 100
  );

  if (open (CONFIG, $configfile)) {
    while (<CONFIG>) {
      if (/^(\S+)\s*\=\s*(.*)$/) {
       $Spamc::conf{$1} = $2;
      }
    }
    close CONFIG;
  } else {
    MailScanner::Log::WarnLog("$MODULE configuration file ($configfile) could not be found !");
  }

  $Spamc::conf{'command'} =~ s/__CONFIGFILE__/$Spamc::conf{'configFile'}/g;
  $Spamc::conf{'command'} =~ s/__SPAMD_SOCKET__/$Spamc::conf{'spamdSocket'}/g;
  $Spamc::conf{'command'} =~ s/__MAX_SIZE__/$Spamc::conf{'maxSize'}/g;
}

sub Checks {
  my $this = shift;
  my $message = shift;

  ## check maximum message size
  my $maxsize = $Spamc::conf{'maxSize'};
  if ($maxsize > 0 && $message->{size} > $maxsize) {
    MailScanner::Log::InfoLog("Message %s is too big for Spamc checks (%d > %d bytes)",
                              $message->{id}, $message->{size}, $maxsize);
    $message->{prefilterreport} .= ", Spamc (too big)";
    $global::MS->{mta}->AddHeaderToOriginal($message, $Spamc::conf{'header'}, "too big (".$message->{size}." > $maxsize)");
    return 0;
  } 
      

  my @WholeMessage;
  push(@WholeMessage, $global::MS->{mta}->OriginalMsgHeaders($message, "\n"));
  if ($message->{infected}) {
      push(@WholeMessage, "X-MailCleaner-Internal-Scan: infected\n");
  }
  push(@WholeMessage, "\n");
  $message->{store}->ReadBody(\@WholeMessage, 0);

  my $msgtext = "";
  foreach my $line (@WholeMessage) {
    $msgtext .= $line;
  }

  my $tim = $Spamc::conf{'timeOut'};
  use Mail::SpamAssassin::Timeout;
  my $t = Mail::SpamAssassin::Timeout->new({ secs => $tim });
  my $is_prespam = 0;
  my $ret = -5;
  my $res = "";
  my @lines;

  $t->run(sub {  
     use IPC::Run3;
     my $out;
     my $err;

     $msgtext .= "\n";
     run3 $Spamc::conf{'command'}, \$msgtext, \$out, \$err;
     $res = $out;
  });
  if ($t->timed_out()) {
    MailScanner::Log::InfoLog("$MODULE timed out for ".$message->{id}."!");
    $global::MS->{mta}->AddHeaderToOriginal($message, $Spamc::conf{'header'}, 'timeout');
    return 0;
  }
  $ret = -1;
  my $score = 0;
  my $limit = 100;
  my %rules;

## analyze result
 
  @lines = split '\n', $res; 
  foreach my $line (@lines) {
    if ($line =~ m/^\s*(-?\d+(?:\.\d+)?)\/(\d+(?:\.\d+)?)\s*$/ ) {
      $score = $1;
      $limit = $2;
      if ($score >= $limit && $limit != 0) {
        $ret = 2;
      } else {
        $ret = 1;
      }
    }
    if ($line =~ /^\s*([- ]?\d+(?:\.\d+)?|[- ]?\d+)\s+([A-Za-z_0-9]+)\s+(.*)$/) {
      $rules{$2} = $1;
      $rules{$2} =~ s/\s//g;
    }
  }
  my $rulesum = "";
  foreach my $r (keys %rules) {
   $rulesum .= ", $r $rules{$r}";
  }
  $rulesum =~ s/^, //;

  if ($ret == 2) {
    MailScanner::Log::InfoLog("$MODULE result is spam ($score/$limit) for ".$message->{id});
    if ($Spamc::conf{'putSpamHeader'}) {
      $global::MS->{mta}->AddHeaderToOriginal($message, $Spamc::conf{'header'}, "is spam ($score/$limit)");
    }
    $message->{prefilterreport} .= ", Spamc (score=$score, required=$limit, $rulesum)";
    return 1;
  }
  if ($ret < 0) {
    MailScanner::Log::InfoLog("$MODULE result is weird ($lines[0]) for ".$message->{id});
    return 0;
  }
  MailScanner::Log::InfoLog("$MODULE result is not spam ($score/$limit) for ".$message->{id});
  if ($Spamc::conf{'putHamHeader'}) {
    $global::MS->{mta}->AddHeaderToOriginal($message, $Spamc::conf{'header'}, "is not spam (score=$score, required=$limit)");
  }
  $message->{prefilterreport} .= ", Spamc (score=$score, required=$limit, $rulesum)";
  return 0;
}

sub dispose {
  MailScanner::Log::InfoLog("$MODULE module disposing...");
}

1;
