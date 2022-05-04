#!/usr/bin/perl

use strict;
if ($0 =~ m/(\S*)\/\S+.pl$/) {
  my $path = $1."/../lib";
  unshift (@INC, $path);
}
require ReadConfig;
use Date::Calc qw(:all);

my $start = shift;
my $stop = shift;
my $what = shift;
my $batch = 0;
my $includerefused = 1;
my @filter = ();
my $fakeids = 1;
my $batchwithlog = 0;
my $batchid = 0;
my $config = ReadConfig::new();
my $tmpdir = $config->getOption('VARDIR').'/run/mailcleaner/log_search/';

my $MAXRESULTS = 10000000;

if (! $start  || ! $stop || ! $what ) {
  print_usage();
}
while (my $opt = shift) {
  if ($opt =~ /^-\S*b/) {
    $batch = 1;
  }
  if ($opt =~ /^-\S*R/) {
    $includerefused = 0;
  }
  if ($opt =~ /^-\S*B/) {
    $batchid = shift;
    $batch = 1;
    if (!$batchid) {
       print "Batch ID not provided\n";
       print_usage();
    } else {
       $batchwithlog = 1;
    }
  }
  if ($opt !~ /^-/) {
    push(@filter, $opt);
  }  
}

if ($start !~ /^\d{8}$/ || $stop !~ /^\d{8}$/) {
  print "Bad time format given\n";
  print_usage();
}

if ($stop < $start) {
 my $tmp = $start;
 $start = $stop;
 $stop = $tmp;
}
if (!$start || $start !~ m/^(\d\d\d\d)(\d\d)(\d\d)$/) {
   print "Bad usage: startdate\n";
}
my %starto = ( 'year' => $1, 'month' => $2, 'day' => $3 );
if (!$stop || $stop !~ m/^(\d\d\d\d)(\d\d)(\d\d)$/) {
   print "Bad usage: stopdate\n";
}
my %stopo = ( 'year' => $1, 'month' => $2, 'day' => $3 );

print "PID ".$$."\n" if $batch;
print "STARTTIME ".time()."\n" if $batch;

my $conf = ReadConfig::getInstance();
chdir($conf->getOption('VARDIR')."/log") or die("cannot move to log directory: ".$conf->getOption('VARDIR')."/log\n");

my $today = `date +%Y%m%d`;
my $today_str = `date +%Y%m%d`;
if ($today_str !~ m/^(\d\d\d\d)(\d\d)(\d\d)$/) {
   print "Error, bad today string: $today_str\n";
}
my %today = ( 'year' => $1, 'month' => $2, 'day' => $3 );
my $LOGDIR=$conf->getOption('VARDIR')."/log";
if ($start > $today_str && $stop > $today_str) {
  $starto{'year'}--;
  $start = sprintf('%04d%02d%02d', $starto{'year'}, $starto{'month'}, $starto{'day'});
  $stopo{'year'}--;
  $stop = sprintf('%04d%02d%02d', $stopo{'year'}, $stopo{'month'}, $stopo{'day'});
}

my @messages = ();
my @nf_messages = ();
my %internal_messages = ();
my %stage1_ids = ();
my %stage2_ids = ();
my %ms_ids = ();
my %stage4_ids = ();
my %spamhandler_ids = ();

### do first pass (exim_stage1), populate ids
print "Doing first pass (finding messages)...\n" if !$batch;
loopThroughLogs('exim_stage1/mainlog', 'exim', $what, \%stage1_ids);

## apply filter
foreach my $msg (@nf_messages) {
	my %msg_o = %{$msg};
	my $filtermatch = 0;
        foreach my $f (@filter) {
	    foreach my $line (split '\n', $stage1_ids{$msg_o{'id'}}) {
	        if ($line =~ m/$f/i) {
	   	    $filtermatch++;
	   	    last;
	        }
            }
	}
	if ($filtermatch == scalar(@filter)) {
		my $refused = 0;
		if ( ! $includerefused ) {
			# List of regexp matching the refused messages
			my @regex = (
				'rejected RCPT',
				'Authentication failed',
				'Authentication not allowed for the domain',
				'Plaintext authentication disallowed on non-secure',
				'no @ found in the subject of an address list match',
				'fixed_login authenticator failed',
				'SSL verify error .during R-verify'
			);

			foreach my $re (@regex) {
				if ( $stage1_ids{$msg_o{'id'}} =~ m/$re/ ) {
					$refused = 1;
					last;
				}
			}
		}
		# Only add messages that were not refused
		push @messages, $msg unless $refused;
	}
}


print "Found ".@messages." occurrence(s)\n";

if (@messages > 0) {
  print "Doing  second pass (finding log traces)...\n" if !$batch;
  ### do second pass (search for real ID through all files)
  loopThroughLogs('exim_stage2/mainlog', 'exim', '', \%stage2_ids);
  loopThroughLogs('mailscanner/infolog', 'mailscanner', '', \%ms_ids);
  loopThroughLogs('mailcleaner/SpamHandler.log', 'spamhandler', '', \%spamhandler_ids);
  loopThroughLogs('exim_stage4/mainlog', 'exim', '', \%stage4_ids);

  my $fullogfile = '/dev/stdout';
  if ($batchwithlog && $batchid) {
    $fullogfile = $tmpdir.'/'.$batchid.".full";
  }
  if (!open(FULLOG, ">>".$fullogfile )) {
    print STDERR "Cannot open full log file: $fullogfile\n";
    exit();
  }
  ### output results...
  foreach my $msg (@messages) {
    if ($batch) {
       printBatchResult($msg);
    }
    my %msg_o = %{$msg};
    if ($msg_o{'nid'} eq '') {
       $msg_o{'nid'} = $msg_o{'id'};
    }
    print FULLOG "***********\n";
    #print " found: ".$msg_o{'id'}." => ".$msg_o{'nid'}."\n";
    foreach my $line (split /\n/, $stage1_ids{$msg_o{'id'}}) {
      print FULLOG $msg_o{'nid'}.'|' if ($batchwithlog);
      print FULLOG $line;
      print FULLOG "\n";
    }
    print FULLOG "\n";
    if ($stage2_ids{$msg_o{'nid'}}) {
      foreach my $line (split /\n/, $stage2_ids{$msg_o{'nid'}}) { 
        print FULLOG $msg_o{'nid'}.'|' if ($batchwithlog);
        print FULLOG $line;
        print FULLOG "\n";
      }
      print FULLOG "\n";
    }
    if ($ms_ids{$msg_o{'nid'}}) {
      foreach my $line (split /\n/, $ms_ids{$msg_o{'nid'}}) {
        print FULLOG $msg_o{'nid'}.'|' if ($batchwithlog);
        print FULLOG $line;
        print FULLOG "\n";
      }
      print FULLOG "\n";
    }
    if ($stage4_ids{$msg_o{'nid'}}) {
      foreach my $line (split /\n/, $stage4_ids{$msg_o{'nid'}}) {
        print FULLOG $msg_o{'nid'}.'|' if ($batchwithlog);
        print FULLOG $line;
        print FULLOG "\n";
      }
      print FULLOG "\n";
    }
    my $out_id = 0;
    if ($spamhandler_ids{$msg_o{'nid'}}) {
    	foreach my $line (split /\n/, $spamhandler_ids{$msg_o{'nid'}}) {
        print FULLOG $msg_o{'nid'}.'|' if ($batchwithlog);
        print FULLOG $line;
        print FULLOG "\n";
        if ($line =~ m/ready to be delivered with new id: (\S{16})/) {
        	$out_id = $1;
        }
      }
      print FULLOG "\n";
    }
    if ($stage4_ids{$out_id}) {
    	foreach my $line (split /\n/, $stage4_ids{$out_id}) {
        print FULLOG $msg_o{'nid'}.'|' if ($batchwithlog);
        print FULLOG $line;
        print FULLOG "\n";
      }
      print FULLOG "\n";
    }
  }
  close FULLOG;
}
print "STOPTIME ".time()."\n" if $batch;
print "done.\n";

exit 0;

sub loopThroughLogs {
  my $filename = shift;
  my $type = shift;
  my $what = shift;
  my $store = shift;

  my @files = getFileListFromDates($filename, \%starto, \%stopo);
  foreach my $file (@files) {
    if ($what eq '') {
      searchInFile($file, $type, $store);
    } else {
      populateIDs($file, $what, $store);
    }
   }
}

sub populateIDs {
  my $file = shift;
  my $what = shift;
  my $store = shift;

  my $cmd = "/opt/exim4/bin/exigrep '$what' ".$conf->getOption('VARDIR')."/log/$file";
  print " -> searching $file... \n" if !$batch;
  my $result = '';
  if  ( -f $conf->getOption('VARDIR')."/log/$file") { 
     $result = `$cmd`;
  }
  my @lines = split /\n/, $result, $MAXRESULTS;
  my %last = (id=>'', nid=>'');
  foreach my $line (@lines) {
    next if ($line !~ /^(\d\d\d\d)-(\d\d)-(\d\d)/);
    my $date = "$1$2$3";
    next if $date < $start;
    next if ($line =~ /^(\d\d\d\d)-(\d\d)-(\d\d) \d\d:\d\d:\d\d\ (\S{16} )?DEBUG /);
    next if ($line =~ /^(\d\d\d\d)-(\d\d)-(\d\d) \d\d:\d\d:\d\d\ Authentication passed/);
    next if ($line =~ /^(\d\d\d\d)-(\d\d)-(\d\d) \d\d:\d\d:\d\d\ Accepting authenticated/);
    next if ($line =~ /^(\d\d\d\d)-(\d\d)-(\d\d) \d\d:\d\d:\d\d\ Accepting authorized relaying/);
    next if ($line =~ /Warning: Doing LDAP verification for/);
    if ($line =~ /^(\d\d\d\d)-(\d\d)-(\d\d)\ \d\d:\d\d:\d\d\ ([-a-zA-Z0-9]{16})\ /) {
      my $id = $4;
      my $nid = '';
      if ($line =~ /250 OK id=(\S{16})/ && $line !~ /T=remote_smtp/) {
        $nid = $1; 
        $internal_messages{$nid} = 1;
      } 
      if ($last{id} eq $id) {
        if ( ! ($nid eq '') ) {
          $last{nid} = $nid;
        }
      } else { 
        if (! ($last{id} eq '') ) {
          push @nf_messages, {%last};
        }
        $last{id} = $id;
        $last{nid} = $nid;
      }
      if (! ($last{id} eq '') ) {
        $store->{$last{id}} .= $line."\n";
      }
    } else {
        if (! ($last{id} eq '') ) {
          push @nf_messages, {%last};
        }
        my $id = $fakeids++;
        $last{id} = $id;
        $last{nid} = $id;
        $store->{$last{id}} .= $line."\n";    
    }
  }
  if (! ($last{id} eq '') ) {
    push @nf_messages, {%last};
  }
   
}

sub searchInFile {
  my $file = shift;
  my $type = shift;
  my $store = shift;

  print " -> looking in file $file ($type)...\n" if !$batch;
  if ($type eq 'exim') {
   searchExim($file, $store);
  } elsif ($type eq 'mailscanner') {
   searchMailScanner($file, $store);
  } elsif ($type eq 'spamhandler') {
   searchSpamHandler($file, $store);
  }
}

sub print_usage {
  print "Usage:  search_log.pl starttime stoptime searchstring [-bR] [-B id]\n";
  exit 1;
}

sub searchExim {
  my $file = shift;
  my $store = shift;

  my $fh;
  my $ffile = $file;
  return if ! -f $file;
  if ($file =~ /.gz$/) {
    $ffile = "zcat $file |";
  }
  if (!open $fh, $ffile) {
    print "Warning, cannot open file: $file !\n";
    return;
  }

  while (<$fh>) {
    if (/^(\d\d\d\d)-(\d\d)-(\d\d)\ \d\d:\d\d:\d\d\ (\S{16})\ /) {
     if (defined($internal_messages{$4}) ) {
       $store->{$4} .= $_;
     }
    }
  }
  close $fh;
}

sub searchMailScanner {
  my $file = shift;
  my $store = shift;

  my $fh;
  my $ffile = $file;
  return if ! -f $file;
  if ($file =~ /.gz$/) {
    $ffile = "zcat $file |";
  }
  if (!open $fh, $ffile) {
    print "Warning, cannot open file: $file !\n";
    return;
  }
 
  while (<$fh>) {
    if (/\b(\S{6}-\S{6}-\S{2})\b/) {
      if (defined($internal_messages{$1}) ) {
       $store->{$1} .= $_;
     }
    }
  }
  close $fh;

}

sub searchSpamHandler {
  my $file = shift;
  my $store = shift;
	
  my $fh;
  my $ffile = $file;
  return if ! -f $file;
  if ($file =~ /.gz$/) {
    $ffile = "zcat $file |";
  }
  if (!open $fh, $ffile) {
    print "Warning, cannot open file: $file !\n";
    return;
  }

  while (<$fh>) {
    if (/^(\d\d\d\d)-(\d\d)-(\d\d)\ \d\d:\d\d:\d\d\ \(\d+\)\ \d+: message (\S{16})/) {
     if (defined($internal_messages{$4}) ) {
       $store->{$4} .= $_;
       if (/ready to be delivered with new id: (\S{16})/) {
       	  $internal_messages{$1} = 1;
       }
     }
    }
  }
  close $fh;
}

sub printBatchResult {
  my $msg = shift;
  my %msg_o = %{$msg};
 
  my $_datein = '';
  my $_dateout = '';
  my $_outhost = '';
  my $_inid = $msg_o{'id'};
  my $_outid = $msg_o{'nid'};
  my $_from = '';
  my $_tos = '';
  my $_accepted = 0;
  my $_inreport = '';
  my $_delivered = 0;
  my $_outreport = '';
  my $_outmessage = '';
  my $_senderhostname = '';
  my $_senderhostip = '';
  my $_relayed = 0;

  foreach my $line (split '\n', $stage1_ids{$msg_o{'id'}}) {
    if ($line =~ m/^(\d{4}-\d\d-\d\d\ \d\d:\d\d:\d\d)/ ) {
    	$_datein = $1;
    }
    if ($line =~ m/<=\ (\S+)/) {
    	$_from = $1;
    	$_accepted = 1;
    	$_inreport = 'Accepted (id='.$msg_o{'id'}.')';
    	if ($line =~ /P=esmtpa A=[^:]+:(\S+)/) {
            $_inreport = "Authenticated relay ($1)";
    	}
    }
    if ($line =~ m/[-=]> (\S+)(?:\ <\S+>)?\ R=(\S+) T=(\S+) .* C=\"([^\"]+)\"/) {
    	$_tos .= ','.$1;
    	if ($3 eq 'remote_smtp') {
    	   $_relayed = 1;
           $_outmessage = $4;
           $msg_o{'nid'} = $msg_o{'id'};
           if ($line =~ m/H=(\S+(?:\ \[\S+\]))/) {
             $_outhost = $1;
           }
    	}
    }
    if ($line =~ /== (\S+) R=(\S+) (?:T=\S+ )?(.*)/) {
       if ($2 eq 'dnslookup') {
          $_tos = $1;
          $_inreport = $3;
          $msg_o{'nid'} = $msg_o{'id'};
       }
    }
    if ($line =~ /^(\d{4}-\d\d-\d\d\ \d\d:\d\d:\d\d) (\S+) Completed/ && $_relayed) {
    	$_dateout = $1;
	if ($_outreport ne 'Rejected') {
          $_outreport = 'Completed';
          $_delivered = 1;
	}
    }
    if ($line =~ /F=<([^>]+)>/ ) {
        $_from = $1;
    }
    if ($line =~ /rejected RCPT <?([^>:]+)>:\s(.*)?/ ) {
        $_tos = $1;
        $_inreport = $2;
    } elsif ($line =~ /F=<\S+> temporarily rejected RCPT <?([^>:]+)>:\s(.*)/ ) {
    	$_accepted = 0;
    	$_inreport = $1;
    } elsif ($_inreport eq '') {
    	$_inreport = $line;
    }
    if ( $line !~ /[=-]\>/ && $line =~ /H=(\S+)\s(\S+)\s\[([^\]]+)\]/) {
        $_senderhostname = $1;
        $_senderhostip = $3;
    } elsif ($line !~ /[=-]\>/ && $line =~ /H=(\S+)\s\[([^\]]+)\]/) {
        $_senderhostname = $1;
        $_senderhostip = $2;
    }
    if ( $line =~ /\*\* (\S+).*SMTP error.*: host (.*): (.*)/) {
	$_outreport = 'Rejected';
	$_delivered = 0;
	$msg_o{'nid'} = $msg_o{'id'};
	$_outmessage = $3;
	$_outhost = $2;
	$_relayed = 1;
	$_tos = $1;
    } 
    if ($_senderhostname =~ /^\((\S*)\)/) {
        $_senderhostname = $1.'/U';
    }
    if ($line =~ /=> \S+ R=filter_forward T=\S+ .* C=\"([^\"]+)/) {
        $_inreport = $1;
    }
    if ($_inreport =~ /^\"(.*)\"$/) {
        $_inreport = $1;
    }

  }
  $_tos =~ s/^,//;

  if ($_accepted == 1 && $_senderhostip eq '') {
    $_accepted = 2;
  }
  
  print $_datein."|".$config->getOption('HOSTID')."|".$_senderhostname."|".$_senderhostip."|".$_accepted."|".$_relayed."|".$_inreport."|".$msg_o{'id'}."|".$_from."|".$_tos."|".$msg_o{'nid'};
  
  # $_spam will be 0 for ham, 1 for spam, 2 for newsletter and 3 for spam and newsletter
  my $_spam = 0;
  my $_spamreport = '';
  my $_content = '';
  my $_contentreport = '';
  my $_fstatus = '';
  foreach my $line (split '\n', $ms_ids{$msg_o{'nid'}}) {
 	if ($line =~ m/to\ \S+\ is\ (not spam|spam)[^,]*, (.*)/) {
  		if ($1 eq 'spam') {
			if ($_spam eq 2) {
  			     $_spam = 3;
			} else {
  			     $_spam = 1;
			}


  		}
  		$_spamreport = $2;
  	}
	if ($line =~ m/to\ \S+\ is\ (not spam|spam).*Newsl \(score=([^,]*), required=([^,]*)/) {
		if ( int($2) >= int($3) ) {
		
			if ($_spam eq 1) {
  			     $_spam = 3;
			} else {
	  		     $_spam = 2;
			}
		}
	}
        if ($line =~ m/result is newsletter/) {
		if ($_spam eq 1) {
  		     $_spam += 2;
		} else {
  		     $_spam = 2;
		}
        }
  	## TO DO: check for viruses and content...
  	if ($line =~ m/Content Checks: Detected (.*)/) {
  		$_content = 'Detected';
  		$_contentreport = $1;
  	}
        if ($line =~ m/Filename Checks:\s+\(\S+\ (.*)\)/) {
                $_content = 'Detected';
                $_contentreport = $1;
        }

  	if ($line =~ m/(Saved entire message to|Saved infected)/ ) {
  		$_content = 'Quarantined';
  	}
  	
  	if ($line =~ m/::INFECTED:: (\S+) :: .\/\S+\/(\S+)/ ) {
  		$_contentreport = "Virus found: ".$1." in file ".$2;
  		$_content = 'Deleted';
  	}
  }
  
  print "|".$_spam."|".$_spamreport."|".$_content."|".$_contentreport;

  if (!$_relayed) {
  	($_dateout, $_delivered, $_outreport, $_outmessage, $_outhost) = processStage4Logs($msg_o{'nid'});    
  }
  print "|".$_dateout."|".$_delivered."|".$_outreport."|".$_outmessage."|".$_outhost;
  print "\n";
}

sub processStage4Logs {
  my $id = shift;
  my $spamquarantined = 0;
  my $spamtagged = 0;
  
  my $dateout;
  my $delivered;
  my $outreport;
  my $outhost;
  my $outmessage;
  my $outdateset = 0;
  
  foreach my $line (split '\n', $stage4_ids{$id}) {
    if ($line =~ m/^(\d{4}-\d\d-\d\d\ \d\d:\d\d:\d\d)\ \S+\ (==|=>|->) (.*)/ ) {
        next if $line =~ /T=stockme/;
        next if $line =~ /R=archiver_route/;
        $dateout = $1;
        my $rest = $3;
        if ($2 eq '==' && $3 =~/\S+\ \S+\ \S+\ (.*)/) {
            $delivered = 0;
            $outreport = 'Pending';
                $outhost = $1;
        } elsif ($2 eq '=>') {
                if ($rest =~ m/C=\"([^\"]+)\"/) {
                   $outmessage = $1;
                }
                if ($rest =~ m/H=(\S+\s+(\[\S+\])?)/) {
                   $outhost = $1;
                }
        } 
       
    }
    if ($line =~ m/^(\d{4}-\d\d-\d\d\ \d\d:\d\d:\d\d)\ \S+ (\S+\ \[\S+\])\ (.*)/ ){
        $outhost = $2;
        $outmessage = $1." ".$2." ".$3;
    }
    if ($line =~ /T=spam_store/) {
       $spamquarantined = 1;
       $outreport = 'Quarantined'; 
    }
    if ($line =~ /R=filter_checktag/) {
       $spamtagged = 1;
       $outreport = 'Tagged';  
    }
    if ($line =~ m/^(\d{4}-\d\d-\d\d\ \d\d:\d\d:\d\d)\ \S+\ Completed/ ) {
        $delivered = 1;
        if (!$outdateset) {
           $dateout = $1;
        }
        if (!$spamquarantined && !$spamtagged) {
            $outreport = 'Completed';
        }
    }
    if ($line =~ m/\ T=spam_store/) {
        foreach my $shline (split '\n', $spamhandler_ids{$id}) {
        	my $date = '';
        	if ($shline =~ m/^(\d{4}-\d\d-\d\d\ \d\d:\d\d:\d\d)/ ) {
        		 $date = $1;
        	}
        	if ($shline =~ /ready to be delivered with new id: (\S{16})/) {
        		($dateout, $delivered, $outreport, $outmessage, $outhost) = processStage4Logs($1);
        		$outdateset = 1;
        	}
            if ($shline =~ /want tag/) {
                $outreport = 'Tagged';
            }
            if ($shline =~ /want drop/) {
                $outreport = 'Dropped';
                $dateout = $date;
                $outdateset = 1;
            }
            if ($shline =~ /is warnlisted/) {
                $outreport = 'Warnlisted';
            }
            if ($shline =~ /is whitelisted/) {
                $outreport = 'Whitelisted';
            }
            if ($shline =~ /want quarantine/) {
                $outreport = 'Quarantined';
                $dateout = $date;
                $outdateset = 1;
            }
        }
    }
  }
  return ($dateout, $delivered, $outreport, $outmessage, $outhost);
}

sub getEstimatedCount {
  my $dateh = shift;
  my %date = %{$dateh};

#  my $count = 3;
#  $count = ($today{'year'}*365 - $date{'year'}*365) + ($today{'month'}*31 - $date{'month'}*31) + ($today{'day'} - $date{'day'}) - 1;

  my $days = Delta_Days($today{'year'}, $today{'month'}, $today{'day'}, $date{'year'}, $date{'month'}, $date{'day'});
  return abs($days);

}

sub getFileExtFromCount {
  my $count = shift;

  if ($count > 0) {
    return '.'.$count.'.gz';
  }
  if ($count == 0) {
    return '.0';
  }
  if ($count < -1) {
    return 'NOTVALID';
  }
  return '';
}

sub getFileFromCount {
  my $file = shift;
  my $count = shift;

  my $tfile = getFileExtFromCount($count);
  if ($tfile eq 'NOTVALID') {
    return 'NOTVALID';
  }
  return $file.getFileExtFromCount($count);
}

sub getFileListFromDates {
  my $filename = shift;
  my $start_dateh = shift;
  my $stop_dateh = shift;

  my @list = ();
  my $start_count = getEstimatedCount($start_dateh);
  my $stop_count = getEstimatedCount($stop_dateh);
  if ($start_count < $stop_count) {
      my $tmp_count = $start_count;
      $start_count = $stop_count;
      $stop_count = $tmp_count;
  }

  my $i = $start_count;
  while ($i >= -1) {
     my $tfile = getFileFromCount($filename, $i);

     if ($tfile ne 'NOTVALID') {
        my %logdate = getDateFromLog($tfile);
        if (%logdate) {
          if (rawDate(\%logdate) > rawDate($stop_dateh)) {
            last;
          }
          if (rawDate(\%logdate) >= rawDate($start_dateh)) {
            push @list, $tfile;
          }
        }
     }
     $i--;
  }

  return @list;
}

sub rawDate {
  my $date = shift;

  return sprintf('%.4d%.2d%.2d', $date->{'year'},$date->{'month'},$date->{'day'});
}

sub getDateFromLog {
   my $file = shift;
   my $position = shift;

   if ($position ne 'tail') {
    $position = 'head';
   }

   my $fullfile = $LOGDIR."/".$file;
   if (! -f $fullfile) {
      return;
   }

   my $line = "";
   if ($file =~  /.gz$/) {
      $line = `zcat $fullfile | $position -1`;
   } else {
      $line = `$position -1 $fullfile`;
   }
   my %months = ('Jan' => '01', 'Feb' => '02', 'Mar' => '03', 'Apr' => '04', 'May' => '05', 'Jun' => '06', 'Jul' => '07',
                'Aug' => '08', 'Sep' => '09', 'Oct' => '10', 'Nov' => '11', 'Dec' => '12');

   ## Exim
   if ($line =~ /^(\d\d\d\d)\-(\d\d)\-(\d\d)/) {
      return ('year' => $1, 'month' => $2, 'day' => $3);
   }

   ## MailScanner
   if ($line =~ /^(\w+)\s+(\d+)/) {
     my $fm = $1;
     my $fd = $2;
     my $y = '0000'; my $m = '00'; my $d='00';
     if (defined($months{$fm})) {
        $m = $months{$fm};
     } else {
        return;
     }
     if ($fd !~ /^\d\d?$/) {
       return;
     }

     if ($today_str =~ /(\d\d\d\d)(\d\d)(\d\d)/) {
        $y = $1;
        $m = $2;
        $d = $3;
     }
     if (defined($months{$fm})) {
        $m = $months{$fm};
     }
     $d = sprintf("%.2d", $fd);
     if ("$y$m$d" > $today_str) { 
         $y = $y-1;
     } 
     return ('year' => $y, 'month' => $m, 'day' => $d);
  }

   return;
}

