#!/usr/bin/perl -w
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
#   Copyright (C) 2022 John Mertz <mail@john.me.tz>
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

package          MCDnsLists;
require Exporter;
use strict;

use IO::Pipe;
use POSIX qw(:signal_h);    # For Solaris 9 SIG bug workaround
use Net::HTTP;
use Net::IP;
use URLRedirects;

our @ISA     = qw(Exporter);
our @EXPORT  = qw(readFile);
our $VERSION = 1.0;

my %rblsfailure;
my %shorteners;

sub new {
	my $class       = shift;
	my $this        = {};
	my $logfunction = shift;
	my $debug       = shift;

	$this->{rbls}                      = {};
	$this->{useableRbls}               = ();
	$this->{whitelistedDomains}        = {};
	$this->{TLDs}                      = {};
	$this->{localDomains}              = {};
	$this->{maxurilength}              = 150;
	$this->{logfunction}               = $logfunction;
	$this->{debug}                     = $debug;
	$this->{timeOut}                   = 10;
	$this->{failuretobedead}           = 1;
	$this->{retrydeadinterval}         = 120;
	$this->{shortner_resolver_maxdeep} = 10;
	$this->{shortner_resolver_timeout} = 5;
	$this->{URLRedirects}		   = URLRedirects->new();

	%rblsfailure = ();

	bless $this, $class;
	return $this;
}

sub loadRBLs {
	my $this                 = shift;
	my $rblspath             = shift;
	my $selectedRbls         = shift;
	my $rblsType             = shift;
	my $whitelistDomainsFile = shift;
	my $TLDsFiles            = shift;
	my $localDomainsFile     = shift;
	my $prelog               = shift;

	if ( opendir( DIR, $rblspath ) ) {
		while ( my $entry = readdir(DIR) ) {
			if ( $entry =~ m/\S+\.cf$/ ) {
				my $rblname = '';
				if ( open( RBLFILE, $rblspath . "/" . $entry ) ) {
					while (<RBLFILE>) {
						if (/^name\s*=\s*(\S+)$/) {
							$rblname                           = $1;
							$this->{rbls}{$rblname}            = ();
							$this->{rbls}{$rblname}{'subrbls'} = ();
                            $this->{rbls}{$rblname}{'callonip'} = 1;
						}
						next if ( $rblname eq '' );
						if (/dnsname\s*=\s*([a-zA-Z0-9._-]+)$/) {
							$this->{rbls}{$rblname}{'dnsname'} = $1.'.';
						}
						if (/type\s*=\s*([a-zA-Z0-9._-]+)$/) {
							$this->{rbls}{$rblname}{'type'} = $1;
						}
						if (/sublist\s*=\s*([^,]+),([a-zA-Z0-9._-]+),(.+)$/) {
							my $subrbl = {
								'mask' => $1,
								'id'   => $2,
								'info' => $3
							};
							push @{ $this->{rbls}{$rblname}{'subrbls'} },
							  $subrbl;
						}
                        if (/callonip\s*=\s*(0|false|no)/i) {
                            $this->{rbls}{$rblname}{'callonip'} = 0;
                        }
					}
					close RBLFILE;
				}
			}
		}
		close DIR;
		if ( $this->{debug} ) {
			&{ $this->{logfunction} }( "$prelog loaded " .
				  keys( %{ $this->{rbls} } ) . " useable RBLs" );
		}
	}
	else {
		&{ $this->{logfunction} }(
			    "$prelog could not open RBL's definition directory ("
			  . $rblspath
			  . ")" );
	}

	my %deadrbls = ();
	my @neededrbls = split( ' ', $selectedRbls );
	foreach my $r (@neededrbls) {
		if ( defined( $this->{rbls}{$r}{'dnsname'} ) ) {
			push @{ $this->{useableRbls} }, $r;
		}
		else {
			&{ $this->{logfunction} }(
				"$prelog configured to use $r, but this RBLs is not available !"
			);
		}
	}
	if ( $this->{useableRbls} ) {
		&{ $this->{logfunction} }( "$prelog using "
			  . @{ $this->{useableRbls} }
			  . " RBLs ("
			  . join( ', ', @{ $this->{useableRbls} } )
			  . ")" );
	}
	else {
		&{ $this->{logfunction} }("$prelog not using any RBLs");
	}

	## loading whitelisted domains
	if ( open( FILE, $whitelistDomainsFile ) ) {
		while (<FILE>) {
			if (/\s*([-_.a-zA-Z0-9]+)/) {
				$this->{whitelistedDomains}{$1} = 1;
			}
		}
		close FILE;
		&{ $this->{logfunction} }( "$prelog loaded " .
			  keys( %{ $this->{whitelistedDomains} } )
			  . " whitelisted domains" );
	}
	elsif ( $whitelistDomainsFile ne '' ) {
		&{ $this->{logfunction} }(
			    "$prelog could not load domains whitelist file ("
			  . $whitelistDomainsFile
			  . ") !" );
	}

	## loading tlds
	foreach my $tldfile ( split( '\s', $TLDsFiles ) ) {
		if ( open( FILE, $tldfile ) ) {
			while (<FILE>) {
				if (/^([-_.a-zA-Z0-9]+)/i) {
					$this->{TLDs}{ lc($1) } = 1;
				}
			}
			close FILE;
		}
		elsif ( $tldfile ne '' ) {
			&{ $this->{logfunction} }(
				    "$prelog could not load two levels TLDs file (" . $tldfile
				  . ") !" );
		}
	}
	if ( $TLDsFiles ne '' ) {
		&{ $this->{logfunction} }(
			"$prelog loaded " . keys( %{ $this->{TLDs} } ) . " TLDs" );
	}

	## loading local domains
	if ( open( FILE, $localDomainsFile ) ) {
		while (<FILE>) {
			if (/^(\S+):/) {
				$this->{localDomains}{$1} = 1;
			}
		}
		close FILE;
		&{ $this->{logfunction} }( "$prelog loaded " .
			  keys( %{ $this->{localDomains} } ) . " local domains" );
	}

	## loading url shorteners
	my $shortfile = $rblspath . '/url_shorteners.txt';
	if ( open( FILE, $shortfile ) ) {
		while (<FILE>) {
			if (/^([a-z.\-]+)/i) {
				$this->{shorteners}{$1} = 1;
			}
		}
	}
	if ( keys( %{ $this->{shorteners} } ) ) {
		&{ $this->{logfunction} }( "$prelog loaded " .
			  keys( %{ $this->{shorteners} } ) . " shorteners" );
	}
	return;
}

sub findUri {
	my $this   = shift;
	my $line   = shift;
	my $prelog = shift;

	if ( my ( $scheme, $authority ) =
		$line =~ m|(http?s?)://([^#/" ><=\[\]()]{3,$this->{maxurilength}})| )
	{
		$authority =~ s/\n//g;
		$authority = lc($authority);
		my $u = $authority;

		## avoid some easy fooling
		$u =~ s/[*,=]//g;
		$u =~ s/=2E/./g;

		return $this->isValidDomain( $u, 1, $prelog );
	}
	return 0;
}

sub findUriShortener {
	my $this   = shift;
	my $line   = shift;
	my $prelog = shift;

	my $deep           = 0;
	my $newloc         = $line;
	my $continue       = 1;
	my $final_location = 0;
	my $first_link     = '';
	while ( $deep++ <= $this->{shortner_resolver_maxdeep} ) {
		my ( $link, $nl ) = $this->getNextLocation($newloc);
		if ( !$nl ) {
			last;
		}
		if ( $first_link eq '' ) {
			$first_link = $link;
		}
		$newloc         = $nl;
		$final_location = $newloc;
	}
	$final_location =~ s/[%?]//g;
	if ( $final_location =~ m|bit\.ly/a/warning| ) {
		&{ $this->{logfunction} }(
			"$prelog found urlshortener with disabled link: $first_link");
		return 'disabled-link-bit.ly';
	}
	my $final_domain = $this->findUri( $final_location, $prelog );
	if ( $deep > 1 ) {
		&{ $this->{logfunction} }(
"$prelog found urlshortener/redirect for: $first_link resolving to $final_location"
		);
	}
	if ( $deep >= $this->{shortner_resolver_maxdeep} ) {
		&{ $this->{logfunction} }(
			    "$prelog urlshortner finder reached max depth ("
			  . $first_link
			  . ")" );
	}
	return $final_domain;
}

sub getNextLocation {
	my $this = shift;
	my $uri  = shift;

	my ($domain, $get);
	# Test Redirect
	if ( ($domain, $get) =
		$uri =~
m|\W(?:https?://)?((?:www\.)?(?:[a-zA-Z0-9\-]+(?:\.[a-zA-Z]{2,3})+))/([a-zA-Z0-9]+\?[^"<\s]+)|
		)
	{
		my $redirect = $this->{URLRedirects}->decode($domain.'/'.$get);
		&{ $this->{logfunction} }("$domain/$get => $redirect");
		if ($redirect) {
			$shorteners{$domain.'/'.$get} = $redirect;
			return ( $domain.'/'.$get , $redirect );
		} else {
			return ( $domain.'/'.$get , 0 );
		}

	# Test shortener
	} elsif ( ($domain, $get) =
		$uri =~
m|\W(?:https?://)?((?:www\.)?[a-zA-Z]{2,5}(?:\.[a-zA-Z]{2,3})+)/([a-zA-Z0-9]{3,10}[^"<\?\s])[\s\/\W]?|
		)
	{
		$domain = lc($domain);
		$domain =~ s/[*,=]//g;
		$domain =~ s/=2E/./g;

		my $request = $domain . '/' . $get;

		if ( defined( $shorteners{$request} ) ) {
			return $shorteners{$request};
		}
		if ( !defined( $this->{shorteners}{$domain} ) ) {
			return ( '', 0 );
		}

		my $s = Net::HTTP->new(
			Host    => $domain,
			Timeout => $this->{shortner_resolver_timeout}
		);
		if ($s) {
			$s->write_request(
				GET          => "/" . $get,
				'User-Agent' => "Mozilla/5.0"
			);
			my ( $code, $mess, %h );
			eval {
				( $code, $mess, %h ) = $s->read_response_headers( laxed => 1 );
			};
			if ( $code >= 300 && $code < 400 ) {
				if ( defined( $h{'Location'} ) ) {
					my $new_location = $h{'Location'};
					$shorteners{$request} = $new_location;
					return ( $request, $new_location );
				}
			}
			$shorteners{$request} = 0;
		}
		return ( $request, 0 );
	}
	return ( '', 0 );
}

sub findEmail {
	my $this   = shift;
	my $line   = shift;
	my $prelog = shift;

	return 0 unless $line;

	if ( my ( $local, $domain ) =
		$line =~
m/([a-zA-Z0-9-_.]{4,25})[ |[(*'"]{0,5}@[ |\])*'"]{0,5}([a-zA-Z0-9-_\.]{4,25}\.[ |[(*'"]{0,5}[a-z]{2,3})/
	  )
	{
		my $add = $1 . "@" . $2;
		my $dom = $2;

		$add =~ s/^3D//;
		$add = lc($add);
		if ( !defined( $this->{localDomains}{$dom} )
			&& $this->isValidDomain( $dom, 0, $prelog ) )
		{
			return $add;
		}
	}
	return 0;
}

sub isValidDomain {
	my $this         = shift;
	my $domain       = shift;
	my $usewhitelist = shift;
	my $prelog       = shift;

        $domain =~ s/\%//g;

	if ( $domain =~ m/[a-z0-9\-_.:]+[.:][a-z0-9]{2,15}$/ ) {
		if ($usewhitelist) {
			foreach my $wd ( keys %{ $this->{whitelistedDomains} } ) {
				if ( $domain =~ m/([^.]{0,150}\.$wd)$/ || $domain eq $wd ) {
					if ( $this->{debug} ) {
						&{ $this->{logfunction} }( $prelog
							  . " has found whitelisted domain: $domain" );
					}
					return 0;
				}
			}
		}

        if ( $domain =~ m/^(\d+\.\d+\.\d+\.\d+|[a-f0-9:]+)$/ ) {
			if ( $this->{debug} ) {
				&{ $this->{logfunction} }(
					$prelog . " has found literal IP domain: $domain" );
			}
			return $domain;
		}

		foreach my $ld ( keys %{ $this->{localDomains} } ) {
			next if ( $ld =~ m/\*$/ );
			if ( $domain =~ m/([^.]{0,150}\.$ld)$/ || $domain eq $ld ) {
				if ( $this->{debug} ) {
					&{ $this->{logfunction} }(
						$prelog . " has found a local domain: $ld ($domain)" );
				}
				return 0;
			}
		}

		foreach my $tld ( keys %{ $this->{TLDs} } ) {
			if ( $domain =~ m/([^.]{0,150}\.$tld)$/ ) {
				my $ret = $1;
				if ( $this->{debug} ) {
					&{ $this->{logfunction} }(
						$prelog . " has found a valid domain: $ret" );
				}
				return $ret;
			}
		}
	}
	if ( $this->{debug} ) {
		&{ $this->{logfunction} }(
			$prelog . " has found an invalid domain: '$domain'" );
	}
	return 0;
}

sub check_dns {
	my $this          = shift;
	my $value         = shift;
	my $type          = shift;
	my $prelog        = shift;
	my $maxhitcount   = shift;
	my $maxbshitcount = shift;

	if ( !defined($maxhitcount) ) {
		$maxhitcount = 0;
	}
	if ( !defined($maxbshitcount) ) {
		$maxbshitcount = 1;
	}

	if ( $this->{debug} ) {
		&{ $this->{logfunction} }( $prelog . " will check value: $value" );
	}

	my ( @HitList, $Checked, $HitOrMiss );

	my $pipe = new IO::Pipe;
	if ( !$pipe ) {
		&{ $this->{logfunction} }(
'Failed to create pipe, %s, try reducing the maximum number of unscanned messages per batch',
			$!
		);
		return 0;
	}

	my $PipeReturn = 0;
	my $GotAHit    = 0;
	my $pid        = fork();
	die "Can't fork: $!" unless defined($pid);

	if ( $pid == 0 ) {

		# In the child
		my $IsSpam = 0;
		my $RBLEntry;
		$pipe->writer();
		POSIX::setsid();
		$pipe->autoflush();

		my $hitcount   = 0;
		my $bshitcount = 0;

		foreach my $r ( @{ $this->{useableRbls} } ) {
			if (   defined( $rblsfailure{$r}{'disabled'} )
				&& $rblsfailure{$r}{'disabled'}
				&& defined( $rblsfailure{$r}{'lastfailure'} ) )
			{
				if (
					time - $rblsfailure{$r}{'lastfailure'} >=
					$this->{'retrydeadinterval'} )
				{
					&{ $this->{logfunction} }( $prelog
						  . " list $r disabled time exceeded, rehabilitating this RBL."
					);
					$rblsfailure{$r}{'disabled'} = 0;
				}
				else {
					if ( $this->{debug} ) {
						&{ $this->{logfunction} }(
							$prelog . " list $r disabled." );
					}
					next;
				}
			}
			next if ( !defined( $this->{rbls}{$r}{'dnsname'} ) );
			next
			  if ( !defined( $this->{rbls}{$r}{'type'} )
				|| $this->{rbls}{$r}{'type'} ne $type );
			last if ( $maxhitcount   && $hitcount >= $maxhitcount );
			last if ( $maxbshitcount && $bshitcount >= $maxbshitcount );

            my $callvalue = $value;
            if ($callvalue =~ m/^(\d+\.\d+\.\d+\.\d+|[a-f0-9\:]{5,71})$/) {
                if ($this->{rbls}{$r}{'type'} =~ m/^URIRBL$/i && $this->{rbls}{$r}{'callonip'} == 0) {
                    if ( $this->{debug} ) {
                        &{ $this->{logfunction} }( $prelog . " not checking literal IP ".$callvalue." against ".$this->{rbls}{$r}{'dnsname'}." ( callonip = ".$this->{rbls}{$r}{'callonip'}." )" );
                    }
                    next;
                }
                my $IPobject = Net::IP->new($callvalue);
                if ($IPobject) {
                    $callvalue = $IPobject->reverse_ip;
                    $callvalue =~ s/[a-z0-9]\.arpa\.$//; 
                    $callvalue =~ s/\.in-add$//;
                    $callvalue =~ s/\.ip$//;
                    $callvalue =~ s/\.\./\./;
                }
            }

			if ( $this->{debug} ) {
				&{ $this->{logfunction} }( $prelog
					  . " checking '$callvalue' against "
					  . $this->{rbls}{$r}{'dnsname'} );
			}

			$RBLEntry =
			  gethostbyname( "$callvalue." . $this->{rbls}{$r}{'dnsname'} );
			if ($RBLEntry) {
				$RBLEntry = Socket::inet_ntoa($RBLEntry);
				if ( $RBLEntry =~ /^127\.[01]\.[0-9]\.[0123456789]\d*$/ ) {

					# Got a hit!

					# now check with sublists masks
					my $subhit = 0;
					foreach my $sub ( @{ $this->{rbls}{$r}{'subrbls'} } ) {
						my $reg = $sub->{'mask'};
						if ( $RBLEntry =~ m/$reg/ ) {
							print $pipe $r . "\n";
							$IsSpam = 1;
							print $pipe "Hit $RBLEntry\n";
							if ( $this->{rbls}{$r}{'type'} eq 'BSRBL' ) {
								$bshitcount++;
							}
							else {
								$hitcount++;
							}
						}
						else {
							print $pipe $r . "\n";
							print $pipe "Miss\n";
						}
					}
					print $pipe $r . "\n";
					print $pipe "Miss\n";
				}
				else {
					print $pipe $r . "\n";
					print $pipe "Miss\n";
				}
			}
			else {
				print $pipe $r . "\n";
				print $pipe "Miss\n";
			}
		}
		$pipe->close();
		exit $IsSpam;
	}

	eval {
		$pipe->reader();
		local $SIG{ALRM} = sub { die "Command Timed Out" };
		alarm $this->{'timeOut'};

		while (<$pipe>) {
			chomp;
			$Checked   = $_;
			$HitOrMiss = <$pipe>;
			chomp $HitOrMiss;
			if ($HitOrMiss =~ m/Hit (127\.\d+\.\d+\.\d+)/) {
				push @HitList, $Checked;
				alarm 0;
				last;
			} elsif ($HitOrMiss =~ m/Miss/) {
				alarm 0;
				last;
			}
		}
		$pipe->close();
		waitpid $pid, 0;
		$PipeReturn = $?;
		alarm 0;
		$pid = 0;
	};
	alarm 0;

	# Workaround for bug in perl shipped with Solaris 9,
	# it doesn't unblock the SIGALRM after handling it.
	#eval {
	#  my $unblockset = POSIX::SigSet->new(SIGALRM);
	#  sigprocmask(SIG_UNBLOCK, $unblockset)
	#    or die "Could not unblock alarm: $!\n";
	#};

	# Catch failures other than the alarm
	if ( $@ and $@ !~ /Command Timed Out/ ) {
		&{ $this->{logfunction} }(
			$prelog . " Checks failed with real error: $@" );
		die();
	}

	# In which case any failures must be the alarm
	if ( $pid > 0 ) {
		&{ $this->{logfunction} }(
			$prelog . " Check $Checked timed out and was killed" );
		$rblsfailure{$Checked}{'lastfailure'} = time;
		if ( defined( $rblsfailure{$Checked}{'failures'} ) ) {
			$rblsfailure{$Checked}{'failures'}++;
		}
		else {
			$rblsfailure{$Checked}{'failures'} = 1;
		}
		if ( $rblsfailure{$Checked}{'failures'} >= $this->{'failuretobedead'} )
		{
			$rblsfailure{$Checked}{'disabled'} = 1;
			&{ $this->{logfunction} }( $prelog
				  . " disabling $Checked, not answering ! will retry in "
				  . $this->{'retrydeadinterval'}
				  . " seconds." );
		}

		# Kill the running child process
		my ($i);
		kill -15, $pid;
		for ( $i = 0 ; $i < 5 ; $i++ ) {
			sleep 1;
			waitpid( $pid, &POSIX::WNOHANG );
			( $pid = 0 ), last unless kill( 0, $pid );
			kill -15, $pid;
		}

		# And if it didn't respond to 11 nice kills, we kill -9 it
		if ($pid) {
			kill -9, $pid;
			waitpid $pid, 0;    # 2.53
		}
	}

	my $temp = @HitList;
	$temp = $temp + 0;
	if ( !$HitList[0] ) {
		$temp = 0;
	}
	else {
		$temp = 0 unless $HitList[0] =~ /[a-z]/i;
	}
	return ( $value, $temp, join( ',', @HitList ) );
}

sub getAllRBLs {
	my $this = shift;

	return $this->{rbls};
}

sub getUseablRBLs {
	my $this = shift;
	return $this->{useableRbls};
}

sub getDebugValue {
	my $this = shift;
	return $this->{debug};
}

1;
