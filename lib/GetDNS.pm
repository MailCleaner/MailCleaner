package GetDNS;

use strict;
use warnings;
no warnings qw ( portable overflow ); # Avoid warnings for the temporary use of >64bit numbers
use Net::DNS;
use Net::CIDR qw( cidradd cidr2range range2cidr );
use Math::Int128 qw( int128 );
use Data::Validate::IP;

our $debug = 0;

sub new
{
	my $resolver = Net::DNS::Resolver->new;
	my $validator = Data::Validate::IP->new;

	my $self = {
		'resolver' => $resolver,
		'validator' => $validator,
		'recursion' => 1
	};
	bless $self;

	return $self;
}

sub dumper {
	my $self = shift;
	my $raw = shift;
	my %args = @_;

	unless (defined($raw)) {
		return ();
	}

	unless (defined($args{'dumper'})) {
		$args{'dumper'} = 'unknown';
	}

	unless (defined($args{'log'})) {
		$args{'log'} = '/var/mailcleaner/log/mailcleaner/dumper.log';
	}
	# Disabling recursion in order to utilize caching
	my $recursion = $self->{recursion};
	$self->{recursion} = 0;

	chomp($raw);
	$raw =~ s/([\s\n\r;,])+/ /g;

	my %cache;
	my @list;
	my @hostnames;
	foreach my $line (split(" ", $raw)) {
		# Some fields allow for a hostname which should not be resolved to IPs
		# Push without looking up DNS info
		if ($line =~ m/^([a-z0-9\-*]+\.)+[a-z]*$/) {
			push(@hostnames,$line);
		# Ignore comment
		} elsif ($line =~ m/^#/) {
			next;
		} else {
			$cache{$line} = undef;
		}
	}

	my @invalid;
	my @exceptions;
	my $continue;
	do {
		foreach my $item (keys %cache) {
			if ($item eq '*') {
				$self->{recursion} = $recursion;
				return ( '::0/0', '0.0.0.0/0' );
			} elsif ($item =~ m/\*/) {
				push(@invalid,$item);
				delete($cache{$item});
			} elsif ($item =~ m#^\!(.*)$#) {
				push(@exceptions, $1);
				delete($cache{$item});
			} elsif (defined($cache{$item})) {
				next;
			} elsif ($item =~ m#\*#) {
				$cache{$item} = $item;
			} elsif ($item =~ m#/\d+$#) {
				$cache{$item} = $item;
			} elsif ($self->validIP4($item)) {
				$cache{$item} = $item.'/32';
			} elsif ($self->validIP6($item)) {
				$cache{$item} = $item.'/128';
			} elsif ($item =~ m#/a$#i) {
				unless(defined($cache{$item})) {
					my $a = $item;
					$a =~ s#/a$##i;
					foreach ($self->getA($a)) {
						unless (defined($cache{$_})) {
							$cache{$_} = undef;
						}
					}
					$cache{$item} = 'cached';
				}
			} elsif ($item =~ m#/aaaa$#i) {
				unless(defined($cache{$item})) {
					my $aaaa = $item;
					$aaaa =~ s#/aaaa$##i;
					foreach ($self->getAAAA($aaaa)) {
						unless (defined($cache{$_})) {
							$cache{$_} = undef;
						}
					}
					$cache{$item} = 'cached';
				}
			} elsif ($item =~ m#/mx$#i) {
				unless(defined($cache{$item})) {
					my $mx = $item;
					$mx =~ s#/mx$##i;
					foreach ($self->getMX($mx)) {
						unless (defined($cache{$_})) {
							$cache{$_} = undef;
						}
					}
					$cache{$item} = 'cached';
				}
			} elsif ($item =~ m#/spf$#i) {
				unless(defined($cache{$item})) {
					my $spf = $item;
					$spf =~ s#/spf$##i;
					my @records = $self->getSPF($spf);
					foreach (@records) {
						unless (defined($cache{$_})) {
							$cache{$_} = undef;
						}
					}
					$cache{$item} = 'cached';
				}
			} else {
				push(@invalid,$item);
				delete($cache{$item});
			}
		}

		$continue = 0;
		foreach my $key (keys %cache) {
			unless (defined($cache{$key})) {
				$continue = 1;
			}
		}
	} while ($continue);

	if (scalar(@invalid)) {
		if (open(my $fh, '>>', $args{'log'})) {
		        print($fh "Did not understand hostlist entries '" .
				join("', '",@invalid) .
				"' in '$args{'dumper'}'\n");
			close($fh);
		}
	}

	foreach (keys %cache) {
		if ($cache{$_} eq 'cached') {
			next;
		} else {
			push(@list,$cache{$_});
		}
	}
			
	if (scalar(@exceptions)) {
		@exceptions = $self->dumper(join(' ',@exceptions));
	}

	@list = $self->simplify(\@list,\@exceptions);
	push(@list,@hostnames);

	$self->{recursion} = $recursion;
	return @list;
}
			
sub getA
{
	my $self = shift;
	my $target = shift;
	
	my $res = $self->{'resolver'}->query($target, 'A');
	if (defined($res->{'answer'}->[0]->{'address'})) {
		return ($res->answer)[0]->address;
	} elsif (defined($res->{'answer'}->[0]->{'cname'})) {
		return $self->getA(join('.',@{$res->{'answer'}->[0]->{'cname'}->{'label'}}));
	} else {
		return ();
	}
}

sub getAAAA
{
	my $self = shift;
	my $target = shift;
	
	my $res = $self->{'resolver'}->query($target, 'AAAA');
	if ($res) {
		return ($res->answer)[0]->address;
	}

	return ();
}

sub getMX
{
	my $self = shift;
	my $target = shift;
	my @res  = mx($self->{'resolver'}, $target);

	my @ips = ();
	if (@res) {
		if ($self->{'recursion'}) {
			foreach (@res) {
				@ips = (
					@ips,
					$self->getA($_->exchange),
					$self->getAAAA($_->exchange)
				);
			}
		} else {
			foreach (@res) {
				push(@ips,$_->exchange.'/a');
				push(@ips,$_->exchange.'/aaaa');
			}
		}
	}

	return $self->uniq(@ips);
}

sub getSPF
{
	my $self = shift;
	my $target = shift;
	my $res  = $self->{'resolver'}->query($target, 'TXT');

	unless ($res) {
		return ();
	}

	my @blocks;
	foreach ($res->answer) {
		if ($_->{'txtdata'}->[0]->[0] =~ /^v=spf/) {
			my $whole;
			if (scalar(@{$_->{'txtdata'}}) >= 1) {
				foreach my $part (@{$_->{'txtdata'}}) {
					$whole .= $part->[0];
				}
			} else {
				$whole = $_->{'txtdata'}->[0]->[0];
			}
			@blocks = split(' ', $whole);
			last;
		}
	}

	my @ips;
	foreach (@blocks) {
		if ($_ =~ m/^[\?\-\~]/) {
			next;
		} elsif ($_ =~ m/\+?(v=spf.|all|ptr)$/i) {
			next;
		} elsif ($_ =~ m/^exists:(.*)/i) {
			print STDERR "Cannot dump 'exists' argument '$_'.\n";
		} elsif ($_ =~ m/%\{/) {
			print STDERR "Cannot dump macro argument '$_'.\n";
		} elsif ($_ =~ m/^\+?a$/i) {
			if ($self->{'recursion'}) {
				push(@ips,$self->getA($target));
			} else {
				push(@ips,$target.'/a');
			}
		} elsif ($_ =~ m/^\+?a:(.*)/i) {
			my $a = $1;
			if ($self->{'recursion'}) {
				push(@ips,$self->getA($a));
			} else {
				push(@ips,$a.'/a');
			}
		} elsif ($_ =~ m/^\+?aaaa$/i) {
			if ($self->{'recursion'}) {
				push(@ips,$self->getAAAA($target));
			} else {
				push(@ips,$target.'/aaaa');
			}
		} elsif ($_ =~ m/^\+?aaaa:(.*)/i) {
			my $aaaa = $1;
			if ($self->{'recursion'}) {
				push(@ips,$self->getAAAA($aaaa));
			} else {
				push(@ips,$aaaa.'/aaaa');
			}
		} elsif ($_ =~ m/^\+?mx$/i) {
			if ($self->{'recursion'}) {
				push(@ips,$self->getMX($target));
			} else {
				push(@ips,$target.'/mx');
			}
		} elsif ($_ =~ m/^\+?mx:(.*)/i) {
			my $mx = $1;
			if ($self->{'recursion'}) {
				push(@ips,$self->getMX($mx));
			} else {
				push(@ips,$mx.'/mx');
			}
		} elsif ($_ =~ m/^\+?ipv?[46]:(.*)/i) {
			push(@ips,$1);
		} elsif ($_ =~ m/\+?include:(.*)/i) {
			my $include = $1;
			if ($self->{'recursion'}) {
				push(@ips,$self->getSPF($include));
			} else {
				push(@ips,$include.'/spf');
			}
		} elsif ($_ =~ m/\+?redirect=(.*)/i) {
			my $redirect = $1;
			if ($self->{'recursion'}) {
				push(@ips,$self->getSPF($redirect));
			} else {
				push(@ips,$redirect.'/spf');
			}
		} else {
			print("Unrecognized pattern $_\n");
		}
	}

	return $self->uniq(@ips);
}

sub validIP4
{
	my $self = shift;
	my $target = shift;
	
	return $self->{'validator'}->is_ipv4($target);
}

sub validIP6
{
	my $self = shift;
	my $target = shift;
	
	return $self->{'validator'}->is_ipv6($target);
}

sub inIPList
{
	my $self = shift;
	my $target = shift;
	my @ips = @_;

	my $version;
	if ($self->{'validator'}->is_ipv4($target)) {
		foreach my $range (@ips) {
			unless ($self->{'validator'}->is_ipv4((split('/',$range))[0])) {
				next;
			}
			unless ($range =~ m#/\d+$#) {
				$range .= '/32';
			}
			if ($self->{'validator'}->is_innet_ipv4($target,$range)) {
				return 1;
			}
		}
	} elsif ($self->{'validator'}->is_ipv6($target)) {
		foreach my $range (@ips) {
			unless ($self->{'validator'}->is_ipv6((split('/',$range))[0])) {
				next;
			}
			unless ($range =~ m#/\d+$#) {
				$range .= '/128';
			}
			if ($self->{'validator'}->is_innet_ipv4($target,$range)) {
				return 1;
			}
		}
	} else {
		die "Invalid IP $target\n";
	}
	return 0;
}

sub simplify
{
	my $self = shift;
	my $list = shift;
	my $exceptions = shift;

	my ($wanted4, $wanted6) = $self->merge($list);

	unless (scalar(@{$exceptions})) {
		return ( @{$wanted4}, @{$wanted6} );
	}

	my ($unwanted4, $unwanted6) = $self->merge($exceptions);

	my @unwanted4 = @{$unwanted4};
	my @wanted4 = @{$wanted4};
	foreach my $block (cidr2range(@unwanted4)) {
		my ($ubottom, $utop) = split('-',$block);
		my @new_wanted;
		while (scalar(@wanted4)) {
			my $current = shift(@wanted4);
			my ($wbottom, $wtop) = split('-',(cidr2range($current))[0]);
			my $string;
			my $continue = 1;
			# No overlap
			if (ip4todec($ubottom) > ip4todec($wtop) || ip4todec($utop) < ip4todec($wbottom)) {
				$string .= "No overlap";
				push( @new_wanted, $current );
			# Starts before, ends after
			} elsif (ip4todec($ubottom) < ip4todec($wbottom) && ip4todec($utop) > ip4todec($wtop)) {
				$string .= "Starts before, ends after";
			# Match beginning
			} elsif (ip4todec($ubottom) == ip4todec($wbottom)) {
				# Match end, remove exact match and jump to next
				if (ip4todec($utop) == ip4todec($wtop)) {
					$string .= "Exact match";
					@new_wanted = ( @new_wanted, @wanted4 );
					$continue = 0;
				# Ends after, remove entire block, but look for other matches
				} elsif (ip4todec($utop) > ip4todec($wtop)) {
					$string .= "Same start, ends after";
				# Ends before, shift start
				} else {
					$string .= "Same start, ends before";
					push( @new_wanted, range2cidr((dectoip4(ip4todec($utop)+1)).'-'.$wtop ));
				}
			# Match end
			} elsif (ip4todec($utop) == ip4todec($wtop)) {
				# Starts before, remove entire block, but look for other matches
				if (ip4todec($ubottom) < ip4todec($wbottom)) {
					$string .= "Starts before, same end";
				# Starts after, shift start
				} else {
					$string .= "Starts after, same end";
					push( @new_wanted, range2cidr($wbottom.'-'.dectoip4(ip4todec($ubottom)-1)) );
					@new_wanted = ( @new_wanted, @wanted4 );
					$continue = 0;
				}
			# Mid-range, add preceding and following blocks
			} elsif (ip4todec($ubottom) > ip4todec($wbottom) && ip4todec($utop) < ip4todec($wtop))  {
				$string .= "Mid-range";
				push( @new_wanted, range2cidr($wbottom.'-'.dectoip4(ip4todec($utop)-1)) );
				push( @new_wanted, range2cidr((dectoip4(ip4todec($utop)+1)).'-'.$wtop ));
				@new_wanted = ( @new_wanted, @wanted4 );
				$continue = 0;
			# Starts after, ends after, shift start; should not be possible
			} elsif (ip4todec($ubottom) > ip4todec($wbottom)) {
				$string .= "INVALID, starts after and continues";
				push( @new_wanted, range2cidr($wbottom.'-'.dectoip4(ip4todec($ubottom)-1)) );
			# Starts before, ends before, push start; should not be possible
			} elsif (ip4todec($utop) < ip4todec($wtop)) {
				$string .= "INVALID, Starts before, ends mid-way";
				push( @new_wanted, range2cidr(dectoip4(ip4todec($utop)+1)).'-'.$wtop );
			# Default should not ever hit
			} else {
				$string .= "This should not be possible";
			}
			if ($debug) {
				if (defined($string)) {
					print "unwanted: $block, wanted: $wbottom-$wtop - $string\n";
				} else {
					print "unwanted: $block, wanted: $wbottom-$wtop - No match\n";
				}
			}
			unless ($continue) {
				last;
			}
		}
		@wanted4 = @new_wanted;
	}

	my @unwanted6 = @{$unwanted6};
	my @wanted6 = @{$wanted6};
	foreach my $block (cidr2range(@unwanted6)) {
		my ($ubottom, $utop) = split('-',$block);
		my @new_wanted;
		while (scalar(@wanted6)) {
			my $current = shift(@wanted6);
			my ($wbottom, $wtop) = split('-',(cidr2range($current))[0]);
			my $string;
			my $continue = 0;
			# No overlap
			if (ip6todec($ubottom) > ip6todec($wtop) || ip6todec($utop) < ip6todec($wbottom)) {
				$string .= "No overlap";
				push( @new_wanted, $current );
			# Starts before, ends after
			} elsif (ip6todec($ubottom) < ip6todec($wbottom) && ip6todec($utop) > ip6todec($wtop)) {
				$string .= "Starts before, ends after";
			# Match beginning
			} elsif (ip6todec($ubottom) == ip6todec($wbottom)) {
				# Match end, remove exact match and jump to next
				if (ip6todec($utop) == ip6todec($wtop)) {
					$string .= "Exact match";
					@new_wanted = ( @new_wanted, @wanted6 );
					$continue = 0;
				# Ends after, remove entire block, but look for other matches
				} elsif (ip6todec($utop) > ip6todec($wtop)) {
					$string .= "Same start, ends after";
				# Ends before, shift start
				} else {
					$string .= "Same start, ends before";
					push( @new_wanted, range2cidr((dectoip6(ip6todec($utop)+1)).'-'.$wtop ));
				}
			# Match end
			} elsif (ip6todec($utop) == ip6todec($wtop)) {
				# Starts before, remove entire block, but look for other matches
				if (ip6todec($ubottom) < ip6todec($wbottom)) {
				$string .= "Starts before, same end";
				# Starts after, shift start
				} else {
				$string .= "Starts after, same end";
					push( @new_wanted, range2cidr($wbottom.'-'.dectoip6(ip6todec($ubottom)-1)) );
					@new_wanted = ( @new_wanted, @wanted6 );
					$continue = 0;
				}
			# Mid-range, add preceding and following blocks
			} elsif (ip6todec($ubottom) > ip6todec($wbottom) && ip6todec($utop) < ip6todec($wtop))  {
				$string .= "Mid-range";
				push( @new_wanted, range2cidr($wbottom.'-'.dectoip6(ip6todec($utop)-1)) );
				push( @new_wanted, range2cidr((dectoip6(ip6todec($utop)+1)).'-'.$wtop ));
				@new_wanted = ( @new_wanted, @wanted6 );
				$continue = 0;
			# Starts after, ends after, shift start; should not be possible
			} elsif (ip6todec($ubottom) > ip6todec($wbottom)) {
				$string .= "INVALID, starts after and continues";
				push( @new_wanted, range2cidr($wbottom.'-'.dectoip6(ip6todec($ubottom)-1)) );
			# Starts before, ends before, push start; should not be possible
			} elsif (ip6todec($utop) < ip6todec($wtop)) {
				$string .= "INVALID, Starts before, ends mid-way";
				push( @new_wanted, range2cidr(dectoip6(ip6todec($utop)+1)).'-'.$wtop );
			# Default should not ever hit
			} else {
				$string .= "This should not be possible";
			}
			if ($debug) {
				if (defined($string)) {
					print "unwanted: $block, wanted: $wbottom-$wtop - $string\n";
				} else {
					print "unwanted: $block, wanted: $wbottom-$wtop - No match\n";
				}
			}
			unless ($continue) {
				last;
			}
		}
		@wanted6 = @new_wanted;
	}

	return ( @wanted4, @wanted6 );
}

sub merge
{
	my $self = shift;
	my $list = shift;
	my (@ip4, @ip6);
	foreach (@{$list}) {
		if ($_ =~ m/:/) {
			@ip6 = cidradd($_,@ip6);
		} else {
			@ip4 = cidradd($_,@ip4);
		}
	}

	return ( \@ip4, \@ip6 );
}

sub ip4todec
{
	my @bytes = split /\./, shift;
	return ($bytes[0] << 24) + ($bytes[1] << 16) + ($bytes[2] << 8) + $bytes[3];
}

sub dectoip4
{
	my $decimal = shift;
	my @bytes;
	push @bytes, ($decimal & 0xff000000) >> 24;
	push @bytes, ($decimal & 0x00ff0000) >> 16;
	push @bytes, ($decimal & 0x0000ff00) >>  8;
	push @bytes, ($decimal & 0x000000ff);
	return join '.', @bytes;
}

sub ip6todec
{
	my @bytes = split(/:/, expandip6(shift));
	my $decimal = 0;
	return (int128(hex($bytes[0])) << 112) + (int128(hex($bytes[1])) << 96) + (int128(hex($bytes[2])) << 80) + (int128(hex($bytes[3])) << 64) + (hex($bytes[4]) << 48) + (hex($bytes[5]) << 32) + (hex($bytes[6]) << 16) + hex($bytes[7]);
}

sub dectoip6
{
	my $decimal = int128(shift);
	my @bytes;
	push( @bytes, sprintf("%x", ($decimal & 0xffff0000000000000000000000000000) >> 112) );
	push( @bytes, sprintf("%x", ($decimal & 0x0000ffff000000000000000000000000) >>  96) );
	push( @bytes, sprintf("%x", ($decimal & 0x00000000ffff00000000000000000000) >>  80) );
	push( @bytes, sprintf("%x", ($decimal & 0x000000000000ffff0000000000000000) >>  64) );
	push( @bytes, sprintf("%x", ($decimal & 0x0000000000000000ffff000000000000) >>  48) );
	push( @bytes, sprintf("%x", ($decimal & 0x00000000000000000000ffff00000000) >>  32) );
	push( @bytes, sprintf("%x", ($decimal & 0x000000000000000000000000ffff0000) >>  16) );
	push( @bytes, sprintf("%x", ($decimal & 0x0000000000000000000000000000ffff)       ) );
	return join ':', @bytes;
}

sub expandip6
{
	my $ip = shift;
	if ($ip =~ m/^:/) {
		$ip = "0$ip";
	}
	if ($ip =~ m/:$/) {
		$ip .= "0";
	}
	if ($ip =~ m/::/) {
		my $missing = '0:' x (9-(scalar(split(/:/, $ip))));
		$ip =~ s/::/:$missing/;
	}
	return $ip;
}

sub uniq
{
	my $self = shift;
	my @ips = @_;
	
	my %ips;
	foreach (@ips) {
		$ips{$_} = 1;
	}

	my @uniq;
	foreach (keys %ips) {
		push(@uniq, $_);
	}

	return @uniq;
}

1;
