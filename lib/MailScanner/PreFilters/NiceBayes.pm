
package MailScanner::NiceBayes;

use strict 'vars';
use strict 'refs';
no  strict 'subs'; # Allow bare words for parameter %'s
#use English; # Needed for $PERL_VERSION to work in all versions of Perl

use IO;
use POSIX qw(:signal_h); # For Solaris 9 SIG bug workaround

my $MODULE = "NiceBayes";
my %conf;

sub initialise {
  MailScanner::Log::InfoLog("$MODULE module initializing...");

  my $confdir = MailScanner::Config::Value('prefilterconfigurations');
  my $configfile = $confdir."/$MODULE.cf";
  %NiceBayes::conf = (
     command => '/opt/bogofilter/bin/bogofilter -c __CONFIGFILE__ -v',
     configFile => '/opt/bogofilter/etc/bogofilter.cf',
     header => "X-$MODULE",
     putHamHeader => 0,
     putSpamHeader => 1,
     maxSize => 0,
     active => 0,
     timeOut => 10,
     avoidHeaders => '',
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
       $NiceBayes::conf{$1} = $2;
      }
    }
    close CONFIG;
  } else {
    MailScanner::Log::WarnLog("$MODULE configuration file ($configfile) could not be found !");
  }
  if ( -f $NiceBayes::conf{'configFile'} ) {
    my $cmd = "grep 'bogofilter_dir' ".$NiceBayes::conf{'configFile'}." | cut -d'=' -f2";
    my $database = `$cmd`;
    chomp($database);
    $database .= "/wordlist.db";
    if ( -f $database ) {
      #MailScanner::Log::InfoLog("$MODULE: bogfilter database found ($database)");
      $NiceBayes::conf{'active'} = 1;
    } else {
      MailScanner::Log::WarnLog("$MODULE bogofilter database not found (".$database.") ! Disabling $MODULE");
    }
  } else {
    MailScanner::Log::WarnLog("$MODULE bogofilter config file (".$NiceBayes::conf{'configFile'}." could not be found ! Disabling $MODULE");    
  }

  $NiceBayes::conf{'command'} =~ s/__CONFIGFILE__/$NiceBayes::conf{'configFile'}/g;

  if ($NiceBayes::conf{'pos_decisive'} && ($NiceBayes::conf{'decisive_field'} eq 'pos_decisive' || $NiceBayes::conf{'decisive_field'} eq 'both')) {
    $NiceBayes::conf{'pos_text'} = '+'.$NiceBayes::conf{'position'}.'+ ';
  } else {
    $NiceBayes::conf{'pos_text'} = '~'.$NiceBayes::conf{'position'}.'~ ';
  }
  if ($NiceBayes::conf{'neg_decisive'} && ($NiceBayes::conf{'decisive_field'} eq 'neg_decisive' || $NiceBayes::conf{'decisive_field'} eq 'both')) {
    $NiceBayes::conf{'neg_text'} = '-'.$NiceBayes::conf{'position'}.'- ';
  } else {
    $NiceBayes::conf{'neg_text'} = '~'.$NiceBayes::conf{'position'}.'~ ';
  }
}

sub Checks {
  my $this = shift;
  my $message = shift;

  ## check maximum message size
  my $maxsize = $NiceBayes::conf{'maxSize'};
  if ($maxsize > 0 && $message->{size} > $maxsize) {
     MailScanner::Log::InfoLog("Message %s is too big for NiceBayes checks (%d > %d bytes)",
                                $message->{id}, $message->{size}, $maxsize);
     $global::MS->{mta}->AddHeaderToOriginal($message, $NiceBayes::conf{'header'}, "too big (".$message->{size}." > $maxsize)");
     return 0;
  }

  if ($NiceBayes::conf{'active'} < 1) {
    MailScanner::Log::WarnLog("$MODULE has been disabled (no database ?)");
    $global::MS->{mta}->AddHeaderToOriginal($message, $NiceBayes::conf{'header'}, "disabled (no database ?)");
    return 0;
  }

  my $msgtext = "";
  my (@WholeMessage, $maxsize);
  my $toadd = 0;
  my @avoidheaders = split /,/, $NiceBayes::conf{'avoidHeaders'};
  foreach my $headerline (@{$message->{headers}}) {
      if ($headerline =~ m/^(\S+):/) {
          my $headermatch = $1;
          $toadd = 1;
          foreach my $avoidheader (@avoidheaders) {
              if ($headermatch =~ m/^$avoidheader/i) {
                  $toadd = 0;
                  last;
              }
          }
          if ($toadd) {
              $msgtext .= $headerline."\n";
          }
      } else {
        if ($toadd) {
            $msgtext .= $headerline."\n";
        }
      }
  }
  $message->{store}->ReadBody(\@WholeMessage, 0);

  $msgtext .= "\n";
  foreach my $line (@WholeMessage) {
    $msgtext .= $line;
  }

  my $tim = $NiceBayes::conf{'timeOut'};
  use Mail::SpamAssassin::Timeout;
  my $t = Mail::SpamAssassin::Timeout->new({ secs => $tim });
  my $is_prespam = 0;
  my $ret = -5;
  my $res = "";

  $t->run(sub {  
    use IPC::Run3;
    my $out;
    my $err;

    $msgtext .= "\n";
    $msgtext =~ s/=[0-9A-F]{2}//g;
    run3 $NiceBayes::conf{'command'}, \$msgtext, \$out, \$err;
    $res = $out;
  });
  if ($t->timed_out()) {
    MailScanner::Log::InfoLog("$MODULE timed out for ".$message->{id}."!");
    $global::MS->{mta}->AddHeaderToOriginal($message, $NiceBayes::conf{'header'}, 'timeout');
    return 0;
  }
  $ret = -1;
  my $score = 0;
  if ($res =~ /^X-Bogosity: (Ham|Spam|Unsure), tests=bogofilter, spamicity=([0-9.]+), version=([0-9.]+)$/) {
   $ret = 1;
   if ($1 eq "Spam") {
     $ret = 2;
   } 
   $score = int($2*10000) / 100;
  }

  if ($ret == 2) {
    MailScanner::Log::InfoLog("$MODULE (position ".$NiceBayes::conf{position}.": ".($NiceBayes::conf{pos_decisive})?'':'not '."decisive) result is spam ($score%) for ".$message->{id});
    if ($NiceBayes::conf{'putSpamHeader'}) {
      $global::MS->{mta}->AddHeaderToOriginal($message, $NiceBayes::conf{'header'}, $NiceBayes::conf{'pos_text'}."is spam ($score%)");
    }
    $message->{prefilterreport} .= ", NiceBayes (position=".$NiceBayes::conf{position}.", decisive=".(($NiceBayes::conf{pos_decisive})?'spam':'false').", $score\%)";
    return 1;
  }
  if ($ret < 0) {
    MailScanner::Log::InfoLog("$MODULE result is weird ($res ".$NiceBayes::conf{'command'}.") for ".$message->{id});
    return 0;
  }
  MailScanner::Log::InfoLog("$MODULE (position ".$NiceBayes::conf{position}.": ".($NiceBayes::conf{neg_decisive})?'':'not '."decisive) result is not spam ($score%) for ".$message->{id});
  if ($NiceBayes::conf{'putHamHeader'}) {
    $global::MS->{mta}->AddHeaderToOriginal($message, $NiceBayes::conf{'header'}, $NiceBayes::conf{'neg_text'}."is not spam ($score%)");
  }
  $message->{prefilterreport} .= ", NiceBayes (position=".$NiceBayes::conf{position}.", decisive=".(($NiceBayes::conf{neg_decisive})?'ham':'false').", $score\%)";
  return 0;
}

sub dispose {
  MailScanner::Log::InfoLog("$MODULE module disposing...");
}

1;
