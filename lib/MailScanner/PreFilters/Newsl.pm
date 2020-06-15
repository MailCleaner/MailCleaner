#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2016 Florian Billebault <florian.billebault@gmail.com>
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
#   Newsl prefilter module for MailScanner (Custom version for MailCleaner)
#

package MailScanner::Newsl;

use strict 'vars';
use strict 'refs';
no  strict 'subs'; # Allow bare words for parameter %'s
#use English; # Needed for $PERL_VERSION to work in all versions of Perl

use IO;
use POSIX qw(:signal_h); # For Solaris 9 SIG bug workaround

my $MODULE = "Newsl";
my %conf;

sub initialise {
  MailScanner::Log::InfoLog("$MODULE module initializing...");

  my $confdir = MailScanner::Config::Value('prefilterconfigurations');
  my $configfile = $confdir."/$MODULE.cf";
  %Newsl::conf = (
     command => '/usr/local/bin/spamc -R --socket=__NEWSLD_SOCKET__ -s __MAX_SIZE__',
     header => "X-$MODULE",
     putHamHeader => 1,
     putSpamHeader => 1,
     putDetailedHeader => 1,
     scoreHeader => "X-$MODULE-Score",
     maxSize => 0,
     timeOut => 100,
     decisive_field => 'none',
     pos_decisive => 0,
     neg_decisive => 0,
     position => 0
  );

  if (open (CONFIG, $configfile)) {
    while (<CONFIG>) {
      if (/^(\S+)\s*\=\s*(.*)$/) {
       $Newsl::conf{$1} = $2;
      }
    }
    close CONFIG;
  } else {
    MailScanner::Log::WarnLog("$MODULE configuration file ($configfile) could not be found !");
  }

  $Newsl::conf{'command'} =~ s/__CONFIGFILE__/$Newsl::conf{'configFile'}/g;
  $Newsl::conf{'command'} =~ s/__NEWSLD_SOCKET__/$Newsl::conf{'spamdSocket'}/g;
  $Newsl::conf{'command'} =~ s/__MAX_SIZE__/$Newsl::conf{'maxSize'}/g;

  # Unless something significant changes, the Newsletter module should NEVER be decisive. It is hard-coded with position 0, so it would override all other modules. There is a separate step to check for newsletters.
  if ($Newsl::conf{'pos_decisive'} && ($Newsl::conf{'decisive_field'} eq 'pos_decisive' || $Newsl::conf{'decisive_field'} eq 'both')) {
    $Newsl::conf{'pos_decisive'} = '+'.$Newsl::conf{'position'}.'+ ';
  } else {
    $Newsl::conf{'pos_decisive'} = '~'.$Newsl::conf{'position'}.'~ ';
  }
  if ($Newsl::conf{'neg_decisive'} && ($Newsl::conf{'decisive_field'} eq 'neg_decisive' || $Newsl::conf{'decisive_field'} eq 'both')) {
    $Newsl::conf{'neg_decisive'} = '-'.$Newsl::conf{'position'}.'- ';
  } else {
    $Newsl::conf{'neg_decisive'} = '~'.$Newsl::conf{'position'}.'~ ';
  }

}

sub Checks {
  my $this = shift;
  my $message = shift;

  ## check maximum message size
  my $maxsize = $Newsl::conf{'maxSize'};
  if ($maxsize > 0 && $message->{size} > $maxsize) {
       MailScanner::Log::InfoLog("Message %s is too big for Newsl checks (%d > %d bytes)",
   				$message->{id}, $message->{size}, $maxsize);
       $message->{prefilterreport} .= ", Newsl (too big)";
       MailScanner::Log::InfoLog("$MODULE module checking 2.....");
       $global::MS->{mta}->AddHeaderToOriginal($message, $Newsl::conf{'header'}, "too big (".$message->{size}." > $maxsize)");
       MailScanner::Log::InfoLog("$MODULE module checking 3.....");
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

  my $tim = $Newsl::conf{'timeOut'};
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
     run3 $Newsl::conf{'command'}, \$msgtext, \$out, \$err;
     $res = $out;
  });
  if ($t->timed_out()) {
    MailScanner::Log::InfoLog("$MODULE timed out for ".$message->{id}."!");
    $global::MS->{mta}->AddHeaderToOriginal($message, $Newsl::conf{'header'}, 'timeout');
    return 0;
  }
  $ret = -1;
  my $score = 0;
  my $limit = 100;
  my %rules;
  my $rulesum = "NONE";

## analyze result
 
  @lines = split '\n', $res; 
  foreach my $line (@lines) {
    if ($line =~ m/^(.*)\/(.*)$/ ) {
      $score = $1;
      $limit = $2;
      if ($score >= $limit && $limit != 0) {
        $ret = 2;
      } else {
        $ret = 1;
      }
    }
    if ($line =~ m/^(.*=.*)$/ ) {
	$rulesum = $1;
    }
  }

  if ($ret == 2) {
    MailScanner::Log::InfoLog("$MODULE ".$Newsl::conf{pos_decisive}."result is newsletter ($score/$limit) for ".$message->{id});
    if ($Newsl::conf{'putHamHeader'}) {
      $global::MS->{mta}->AddHeaderToOriginal($message, $Newsl::conf{'header'}, $Newsl::conf{pos_decisive}."is newsletter ($score/$limit)");
    }
    $message->{prefilterreport} .= ", Newsl (".$Newsl::conf{pos_decisive}."score=".$score.", required=".$limit.", ".$rulesum.")";
    return -1; # Set to 1 to put in spam quarantine, -1 to newsletter
  }
  if ($ret < 0) {
      MailScanner::Log::InfoLog("$MODULE result is weird ($lines[0]) for ".$message->{id});
      return 0;
  }
  MailScanner::Log::InfoLog("$MODULE ".$Newsl::conf{neg_decisive}."result is not newsletter ($score/$limit) for ".$message->{id});
  if ($Newsl::conf{'putSpamHeader'}) {
    $global::MS->{mta}->AddHeaderToOriginal($message, $Newsl::conf{'header'}, $Newsl::conf{neg_decisive}."is not newsletter ($score/$limit)");
  }
  $message->{prefilterreport} .= ", Newsl (".$Newsl::conf{neg_decisive}."score=".$score.", required=".$limit.", ".$rulesum.")";
  return 0;
}

sub dispose {
  MailScanner::Log::InfoLog("$MODULE module disposing...");
}

1;
