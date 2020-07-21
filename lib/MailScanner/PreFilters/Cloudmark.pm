
package MailScanner::Cloudmark;

use strict 'vars';
use strict 'refs';
no  strict 'subs'; # Allow bare words for parameter %'s
#use English; # Needed for $PERL_VERSION to work in all versions of Perl

use IO;
use POSIX qw(:signal_h); # For Solaris 9 SIG bug workaround
use Cloudmark::CMAE::Client qw( :errors );

my $MODULE = "Cloudmark";
my %conf;

sub initialise {
  MailScanner::Log::InfoLog("$MODULE module initializing...");

  my $confdir = MailScanner::Config::Value('prefilterconfigurations');
  my $configfile = $confdir."/$MODULE.cf";
  %Cloudmark::conf = (
     header => "X-$MODULE",
     putHamHeader => 0,
     putSpamHeader => 1,
     maxSize => 0,
     active => 1,
     timeOut => 10,
     server_host => 'localhost',
     server_port => 2703,
     threshold => 0,
     show_categories => 'yes',
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
       $Cloudmark::conf{$1} = $2;
      }
    }
    close CONFIG;
  } else {
    MailScanner::Log::WarnLog("$MODULE configuration file ($configfile) could not be found !");
  }

  if ($Cloudmark::conf{'pos_decisive'} && ($Cloudmark::conf{'decisive_field'} eq 'pos_decisive' || $Cloudmark::conf{'decisive_field'} eq 'both')) {
    $Cloudmark::conf{'pos_text'} = '+'.$Cloudmark::conf{'position'}.'+ ';
  } else {
    $Cloudmark::conf{'pos_text'} = '~'.$Cloudmark::conf{'position'}.'~ ';
  }
  if ($Cloudmark::conf{'neg_decisive'} && ($Cloudmark::conf{'decisive_field'} eq 'neg_decisive' || $Cloudmark::conf{'decisive_field'} eq 'both')) {
    $Cloudmark::conf{'neg_text'} = '-'.$Cloudmark::conf{'position'}.'- ';
  } else {
    $Cloudmark::conf{'neg_text'} = '~'.$Cloudmark::conf{'position'}.'~ ';
  }
}

sub Checks {
  my $this = shift;
  my $message = shift;

  ## check maximum message size
  my $maxsize = $Cloudmark::conf{'maxSize'};
  if ($maxsize > 0 && $message->{size} > $maxsize) {
     MailScanner::Log::InfoLog("Message %s is too big for Cloudmark checks (%d > %d bytes)",
                                $message->{id}, $message->{size}, $maxsize);
     $global::MS->{mta}->AddHeaderToOriginal($message, $Cloudmark::conf{'header'}, "too big (".$message->{size}." > $maxsize)");
     return 0;
  }

  if ($Cloudmark::conf{'active'} < 1) {
    MailScanner::Log::WarnLog("$MODULE has been disabled");
    $global::MS->{mta}->AddHeaderToOriginal($message, $Cloudmark::conf{'header'}, "disabled");
    return 0;
  }

### check against Cloudmark
   my ($client, $err) = Cloudmark::CMAE::Client->new (
            host    => $Cloudmark::conf{'server_host'},
            timeout => $Cloudmark::conf{'timeOut'},
            port    => $Cloudmark::conf{'server_port'},
            );

   if ($err)  {
    	MailScanner::Log::InfoLog("$MODULE server could not be reached for ".$message->{id}."!");
        $global::MS->{mta}->AddHeaderToOriginal($message, $Cloudmark::conf{'header'}, 'server could not be reached for');
        return 0;
   }
  
   my (@WholeMessage, $maxsize);
   push(@WholeMessage, $global::MS->{mta}->OriginalMsgHeaders($message, "\n"));
   push(@WholeMessage, "\n");
   $message->{store}->ReadBody(\@WholeMessage, 0);
   my $msg = "";
    foreach my $line (@WholeMessage) {
      $msg .= $line;
   }
    
   my $score;
   my $category;
   my $sub_category;
   my $rescan;
   my $analysis;

   $err = $client->score(rfc822 =>  $msg,
            out_score => \$score,
            out_category => \$category,
            out_sub_category => \$sub_category,
            out_rescan => \$rescan,
            out_analysis => \$analysis);
    
    if ($err) {
        MailScanner::Log::InfoLog("$MODULE scoring failed for ".$message->{id}."!");
        $global::MS->{mta}->AddHeaderToOriginal($message, $Cloudmark::conf{'header'}, 'scoring failed');
        return 0;
    }
    
    my $header = "$analysis";
    my $result_str = "";
    
    if ($Cloudmark::conf{'show_categories'} eq 'yes') {
        my $out_cat;
        my $out_subcat;

        $err = $client->describe_category(category => $category, 
                sub_category => $sub_category, 
                out_category_desc => \$out_cat,
                out_sub_category_desc => \$out_subcat);

        if ($err) {
            MailScanner::Log::InfoLog("$MODULE Can't extract category/subcat names for ".$message->{id}."!");
        }
        else 
        {
            # replace all punctuation and whitespace with underscores
            $out_subcat =~ s/[[:punct:]\s]/_/g;
            
            $result_str = ", xcat=$out_cat/$out_subcat";
        }
    }
    
    $global::MS->{mta}->AddHeaderToOriginal($message, $Cloudmark::conf{'header'}."-cmaetag", $header);
    
    if ($score > $Cloudmark::conf{'threshold'}) {
        MailScanner::Log::InfoLog("$MODULE (position ".$Cloudmark::conf{position}.": ".($Cloudmark::conf{pos_decisive})?'':'not '."decisive) result is spam (".$score.$result_str.") for ".$message->{id});
        if ($Cloudmark::conf{'putSpamHeader'}) {
          $global::MS->{mta}->AddHeaderToOriginal($message, $Cloudmark::conf{'header'}, "is spam (".$score.$result_str.")");
        }
        $message->{prefilterreport} .= ", Cloudmark (position=".$Cloudmark::conf{position}.", decisive=".(($Cloudmark::conf{pos_decisive})?'spam':'false').", ".$score.$result_str.")";
        return 1;
    }
    else {
        MailScanner::Log::InfoLog("$MODULE (position ".$Cloudmark::conf{position}.": ".($Cloudmark::conf{neg_decisive})?'':'not '."decisive) result is not spam (".$score.$result_str.") for ".$message->{id});
        if ($Cloudmark::conf{'putHamHeader'}) {
           $global::MS->{mta}->AddHeaderToOriginal($message, $Cloudmark::conf{'header'}, $Cloudmark::conf{'neg_text'}."is not spam (".$score.$result_str.")");
        }
        $message->{prefilterreport} .= ", Cloudmark (position=".$Cloudmark::conf{position}.", decisive=".(($Cloudmark::conf{neg_decisive})?'ham':'false').", ".$score.$result_str.")";
        return 0;
    }
    
  return 0;    
  
}

sub dispose {
  MailScanner::Log::InfoLog("$MODULE module disposing...");
}

1;
