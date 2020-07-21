
package MailScanner::ClamSpam;

use strict 'vars';
use strict 'refs';
no  strict 'subs'; # Allow bare words for parameter %'s
#use English; # Needed for $PERL_VERSION to work in all versions of Perl

use IO;
use POSIX qw(:signal_h); # For Solaris 9 SIG bug workaround

my $MODULE = "ClamSpam";
my %conf;

sub initialise {
  MailScanner::Log::InfoLog("$MODULE module initializing...");

  my $confdir = MailScanner::Config::Value('prefilterconfigurations');
  my $configfile = $confdir."/$MODULE.cf";
  %ClamSpam::conf = (
     command => '/opt/clamav/bin/clamdscan --no-summary --config-file=__CONFIGFILE__ -',
     header => "X-$MODULE",
     putHamHeader => 0,
     putSpamHeader => 1,
     putDetailedHeader => 1,
     scoreHeader => "X-$MODULE-result",
     maxSize => 0,
     timeOut => 20,
     decisive_field => 'none',
     pos_text => '',
     pos_decisive => 0,
     position => 0
  );

  if (open (CONFIG, $configfile)) {
    while (<CONFIG>) {
      if (/^(\S+)\s*\=\s*(.*)$/) {
       $ClamSpam::conf{$1} = $2;
      }
    }
    close CONFIG;
  } else {
    MailScanner::Log::WarnLog("$MODULE configuration file ($configfile) could not be found !");
  }
  $ClamSpam::conf{'command'} =~ s/__CONFIGFILE__/$ClamSpam::conf{'configFile'}/g;
  $ClamSpam::conf{'command'} =~ s/__CLAM_DB__/$ClamSpam::conf{'clamdb'}/g;

  if ($ClamSpam::conf{'pos_decisive'} && ($ClamSpam::conf{'decisive_field'} eq 'pos_decisive' || $ClamSpam::conf{'decisive_field'} eq 'both')) {
    $ClamSpam::conf{'pos_text'} = '+'.$ClamSpam::conf{'position'}.'+ ';
  } else {
    $ClamSpam::conf{'pos_text'} = '~'.$ClamSpam::conf{'position'}.'~ ';
  }
}

sub Checks {
  my $this = shift;
  my $message = shift;

  ## check maximum message size
  my $maxsize = $ClamSpam::conf{'maxSize'};
  if ($maxsize > 0 && $message->{size} > $maxsize) {
    MailScanner::Log::InfoLog("Message %s is too big for ClamSpam checks (%d > %d bytes)",
                              $message->{id}, $message->{size}, $maxsize);
    $message->{prefilterreport} .= ", ClamSpam (too big)";
    $global::MS->{mta}->AddHeaderToOriginal($message, $ClamSpam::conf{'header'}, "too big (".$message->{size}." > $maxsize)");
    return 0;
  } 
  
  my (@WholeMessage, $maxsize);
  push(@WholeMessage, $global::MS->{mta}->OriginalMsgHeaders($message, "\n"));
  push(@WholeMessage, "\n");
  $message->{store}->ReadBody(\@WholeMessage, 0);
     
  my $msgtext = "";
  foreach my $line (@WholeMessage) {
    $msgtext .= $line;
  }      

  my $tim = $ClamSpam::conf{'timeOut'};
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
    run3 $ClamSpam::conf{'command'}, \$msgtext, \$out, \$err;
    $res = $out;
  });
  if ($t->timed_out()) {
    MailScanner::Log::InfoLog("$MODULE timed out for ".$message->{id}."!");
    $global::MS->{mta}->AddHeaderToOriginal($message, $ClamSpam::conf{'header'}, 'timeout');
    return 0;
  }
  ## not spam per default
  $ret = 1;
  my $spamfound = "";
## analyze result
 
  @lines = split "\n", $res; 
  foreach my $line (@lines) {
    if ($line =~ m/:\s*(\S+)\s+FOUND$/ ) {
      $spamfound .= ", $1";
      $ret = 2;
    }
  }
  $spamfound =~ s/^, //;

  if ($ret == 2) {
    MailScanner::Log::InfoLog("$MODULE (position ".$ClamSpam::conf{position}.": ".($ClamSpam::conf{pos_decisive})?'':'not '."decisive) result is spam ($spamfound) for ".$message->{id});
    if ($ClamSpam::conf{'putSpamHeader'}) {
      $global::MS->{mta}->AddHeaderToOriginal($message, $ClamSpam::conf{'header'}, $ClamSpam::conf{pos_text}."is spam ($spamfound)");
    }
    $message->{prefilterreport} .= ", ClamSpam (position=".$ClamSpam::conf{position}.", decisive=".(($ClamSpam::conf{pos_decisive})?'spam':'false').", $spamfound)";
    return 1;
  }
  return 0;
}

sub dispose {
  MailScanner::Log::InfoLog("$MODULE module disposing...");
}

1;
