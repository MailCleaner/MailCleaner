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

package MailScanner::UriRBLs;

use strict 'vars';
use strict 'refs';
no strict 'subs';    # Allow bare words for parameter %'s

#use English; # Needed for $PERL_VERSION to work in all versions of Perl

use IO;
use POSIX qw(:signal_h);    # For Solaris 9 SIG bug workaround
use MIME::Parser;
use Net::IP;
use Net::CIDR::Lite;
use MCDnsLists;

my $MODULE = "UriRBLs";
my %conf;
my $dnslists;

sub initialise {
	MailScanner::Log::InfoLog("$MODULE module initializing...");

	my $confdir    = MailScanner::Config::Value('prefilterconfigurations');
	my $configfile = $confdir . "/$MODULE.cf";
	%UriRBLs::conf = (
		header               => "X-$MODULE",
		putHamHeader         => 0,
		putSpamHeader        => 1,
		maxURIs              => 10,
		maxURIlength         => 200,
		timeOut              => 30,
		rbls                 => '',
		maxrbltimeouts       => 3,
		listeduristobespam   => 1,
		listedemailtobespam  => 1,
		rblsDefsPath         => '.',
		whitelistDomainsFile => 'whitelisted_domains.txt',
		TLDsFiles            => 'two-level-tlds.txt tlds.txt',
		localDomainsFile     => 'domains.list',
		resolveShorteners    => 1,
		avoidhosts           => '',
		temporarydir         => '/tmp',
     		decisive_field 	     => 'none',
                pos_text             => '',
                neg_text             => '',
     		pos_decisive 	     => 0,
     		neg_decisive 	     => 0,
     		position 	     => 0
	);

	if ( open( CONFIG, $configfile ) ) {
		while (<CONFIG>) {
			if (/^(\S+)\s*\=\s*(.*)$/) {
				$UriRBLs::conf{$1} = $2;
			}
		}
		close CONFIG;
	}
	else {
		MailScanner::Log::WarnLog(
			"$MODULE configuration file ($configfile) could not be found !");
	}

	$UriRBLs::dnslists =
	  new MCDnsLists( \&MailScanner::Log::WarnLog, $UriRBLs::conf{debug} );
	$UriRBLs::dnslists->loadRBLs(
		$UriRBLs::conf{rblsDefsPath}, $UriRBLs::conf{rbls},
		'URIRBL',                     $UriRBLs::conf{whitelistDomainsFile},
		$UriRBLs::conf{TLDsFiles},    $UriRBLs::conf{localDomainsFile},
		$MODULE
	);

  	if ($UriRBLs::conf{'pos_decisive'} && ($UriRBLs::conf{'decisive_field'} eq 'pos_decisive' || $UriRBLs::conf{'decisive_field'} eq 'both')) {
    		$UriRBLs::conf{'pos_text'} = 'position : '.$UriRBLs::conf{'position'}.', spam decisive';
  	} else {
    		$UriRBLs::conf{'pos_text'} = 'position : '.$UriRBLs::conf{'position'}.', not decisive';
  	}
  	if ($UriRBLs::conf{'neg_decisive'} && ($UriRBLs::conf{'decisive_field'} eq 'neg_decisive' || $UriRBLs::conf{'decisive_field'} eq 'both')) {
    		$UriRBLs::conf{'neg_text'} = 'position : '.$UriRBLs::conf{'position'}.', ham decisive';
  	} else {
    		$UriRBLs::conf{'neg_text'} = 'position : '.$UriRBLs::conf{'position'}.', not decisive';
  	}
}

sub Checks {
	my $this    = shift;
	my $message = shift;

	## check maximum message size
	my $maxsize     = $UriRBLs::conf{'maxSize'};
	my $header_size = 0;
	if ( -e $message->{headerspath} ) {
		$header_size = -s $message->{headerspath};
	}
	my $body_size = $message->{size} - $header_size;

	if ( $maxsize > 0 && $body_size > $maxsize ) {
		MailScanner::Log::InfoLog(
			"Message %s is too big for UriRBLs checks (%d > %d bytes)",
			$message->{id}, $message->{size}, $maxsize );
		$global::MS->{mta}->AddHeaderToOriginal(
			$message,
			$UriRBLs::conf{'header'},
			"too big (" . $message->{size} . " > $maxsize)"
		);
		return 0;
	}
        my $senderhostname = '';
        my $senderdomain = $message->{fromdomain};
        my $senderip = $message->{clientip};

        ## try to find sender hostname 
        ## find out any previous SPF control
        foreach my $hl ($global::MS->{mta}->OriginalMsgHeaders($message)) {
            if ($senderhostname eq '' && $hl =~ m/^Received: from (\S+) \(\[$senderip\]/) {
                  $senderhostname = $1;
                  MailScanner::Log::InfoLog("$MODULE found sender hostname: $senderhostname for $senderip on message ".$message->{id});
            }
            if ($hl =~ m/^X-MailCleaner-SPF: (.*)/) {
                 last; ## we can here because X-MailCleaner-SPF will always be after the Received fields.
            }
        }

        ## check if in avoided hosts
        foreach my $avoidhost ( split(/,/, $UriRBLs::conf{avoidhosts})) {
            if ($avoidhost =~ m/^[\d\.\:\/]+$/) {
                if ($UriRBLs::conf{debug}) {
                      MailScanner::Log::InfoLog("$MODULE should avoid control on IP ".$avoidhost." for message ".$message->{id});
                }
                my $acidr = Net::CIDR::Lite->new();
                eval { $acidr->add_any($avoidhost); };
                if ($acidr->find($message->{clientip})) {
                     MailScanner::Log::InfoLog("$MODULE not checking UriRBL on ".$message->{clientip}." because IP is whitelisted for message ".$message->{id});
                     return 0;
                }
            }
            if ($avoidhost =~ m/^[a-zA-Z\.\-\_\d\*]+$/) {
                  $avoidhost =~ s/([^\\])\./\1\\\./g;
                  $avoidhost =~ s/^\./\\\./g;
                  $avoidhost =~ s/([^\\])\*/\1\.\*/g;
                  $avoidhost =~ s/^\*/.\*/g;
                  if ($UriRBLs::conf{debug}) {
                        MailScanner::Log::InfoLog("$MODULE should avoid control on hostname ".$avoidhost." for message ".$message->{id});
                  }
                  if ($senderhostname =~ m/$avoidhost$/) {
                       MailScanner::Log::InfoLog("$MODULE not checking UriRBL on ".$message->{clientip}." because hostname $senderhostname is whitelisted for message ".$message->{id});
                       return 0;
                  }
            }
         }

	my (@WholeMessage);
	push( @WholeMessage, "\n" );
	$message->{store}->ReadBody( \@WholeMessage, 0 );

    my $parser = new MIME::Parser;
    $parser->extract_uuencode(1);
    $parser->ignore_errors(1);
    $parser->output_under( $UriRBLs::conf{'temporarydir'} );
    my $fullmsg = "";

    foreach my $hl ($global::MS->{mta}->OriginalMsgHeaders($message)) {
        $fullmsg .= $hl."\n";
    }
    foreach my $line (@WholeMessage) {
        $fullmsg .= $line;
    }
    my $entity = $parser->parse_data($fullmsg);

    my %uris;
    my %emails;
    my %shorts; 

    if ($entity->is_multipart) {
        foreach my $part ($entity->parts) {
            if ($part->is_multipart) {
                foreach my $second_part ($part->parts) {
                    if ($second_part->effective_type =~ m/^text\//) {
                        processPart($message, $second_part, \%uris, \%emails, \%shorts);
                    }
                }
            } else {
                if ($part->effective_type =~ m/^text\//) {
                    processPart($message, $part, \%uris, \%emails, \%shorts);
                }
            }
        }
    } else {
        processPart($message, $entity, \%uris, \%emails, \%shorts);
    }
    $parser->filer->purge();

	my $uhits      = 0;
	my %urihits    = ();
	my $fullheader = '';
	my $domain     = '';
	foreach my $uri ( keys %uris ) {
		( $domain, $urihits{$uri}{'count'}, $urihits{$uri}{'header'} ) =
		  $UriRBLs::dnslists->check_dns( $uri, 'URIRBL',
			"$MODULE (" . $message->{id} . ")" );
		if ( $urihits{$uri}{'count'} > 0 ) {
			$uhits++;
			$fullheader .= " - " . $domain;
			if ( defined( $shorts{$domain} ) ) {
				$fullheader .= "/S";
			}
			$fullheader .= ":" . $urihits{$uri}{'header'};
			if ( $UriRBLs::conf{debug} ) {
				MailScanner::Log::InfoLog( "$MODULE got hit for: $domain ("
					  . $urihits{$uri}{'header'} . ") in "
					  . $message->{id} );
			}
			last if ( $uhits >= $UriRBLs::conf{'listeduristobespam'} );
		}
	}
	my $ehits     = 0;
	my %emailhits = ();
	my $emailres  = '';
	foreach my $email ( keys %emails ) {
		( $emailres, $emailhits{$email}{'count'}, $emailhits{$email}{'header'} )
		  = $UriRBLs::dnslists->check_dns( $email, 'ERBL',
			"$MODULE (" . $message->{id} . ")" );
		if ( $emailhits{$email}{'count'} > 0 ) {
			$ehits++;
			$fullheader .= " - " . $email . ":" . $emailhits{$email}{'header'};
			if ( $UriRBLs::conf{debug} ) {
				MailScanner::Log::InfoLog( "$MODULE got hit for: $email ("
					  . $emailhits{$email}{'header'} . ") in "
					  . $message->{id} );
			}
			last if ( $ehits >= $UriRBLs::conf{'listedemailstobespam'} );
		}
	}

	$fullheader =~ s/^\ -\ //;

	if (   $uhits >= $UriRBLs::conf{'listeduristobespam'}
		|| $ehits >= $UriRBLs::conf{'listedemailtobespam'} )
	{
		print "HITS: $uhits-$ehits\n";
 		$message->{prefilterreport} .= " $MODULE ($fullheader, ".$UriRBLs::conf{pos_text}.")";
		MailScanner::Log::InfoLog("$MODULE result is spam (".$fullheader.") for " . $message->{id} );
		if ( $UriRBLs::conf{'putSpamHeader'} ) {
			$global::MS->{mta}->AddHeaderToOriginal(
				$message,
				$UriRBLs::conf{'header'},
 				"is spam ($fullheader) ".$UriRBLs::conf{'pos_text'}
			);
		}
		return 1;
	}
	if ( $UriRBLs::conf{'putHamHeader'} ) {
		MailScanner::Log::InfoLog("$MODULE result is not spam (".$fullheader.") for " . $message->{id} );
		$global::MS->{mta}->AddHeaderToOriginal(
			$message,
			$UriRBLs::conf{'header'},
 			"is not spam ($fullheader) ".$UriRBLs::conf{'neg_text'}
		);
	}
	return 0;
}

sub dispose {
	MailScanner::Log::InfoLog("$MODULE module disposing...");
}

sub processPart {
    my $message = shift;
    my $part = shift;
    my $uris = shift;
    my $emails = shift;
    my $shorts = shift;

    my $body = $part->bodyhandle();
    if (!$body) {

        if ( $UriRBLs::conf{debug} ) {
            MailScanner::Log::InfoLog( "$MODULE cannot find body handle for part: ".$part->effective_type." in ".$message->{id} );
        }
        return 0;
    }

    my $msgtext      = "";
	my $maxuris      = $UriRBLs::conf{'maxURIs'};
	my $maxurilength = $UriRBLs::conf{'maxURIlength'};

	my $in_header = 1;
	foreach my $line ($body->as_lines) {

		my $ret =
		  $UriRBLs::dnslists->findUri( $line,
			"$MODULE (" . $message->{id} . ")" );
		if ($ret) {
			$uris->{$ret} = 1;
			if ( keys(%{$uris}) >= $maxuris ) {
				last;
			}
		}

		if ( $UriRBLs::conf{'resolveShorteners'} ) {
			my $ret =
			  $UriRBLs::dnslists->findUriShortener( $line,
				"$MODULE (" . $message->{id} . ")" );
			if ($ret) {
				$uris->{$ret}   = 1;
				$shorts->{$ret} = 1;
				if ( keys(%{$uris}) >= $maxuris ) {
					last;
				}
			}
		}

		$ret =
		  $UriRBLs::dnslists->findEmail( $line,
			"$MODULE (" . $message->{id} . ")" );
		if ($ret) {
			$emails->{$ret}++;
			if ( keys(%{$emails}) >= $maxuris ) {
				last;
			}
		}
	}
    return 1;
}
1;
