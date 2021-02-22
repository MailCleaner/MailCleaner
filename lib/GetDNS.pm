package GetDNS;

use strict;
use warnings;
use Net::DNS;
use Data::Validate::IP;

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

	unless (defined($raw)) {
		return ();
	}

	# Disabling recursion in order to utilize caching
	my $recursion = $self->{recursion};
	$self->{recursion} = 0;

	chomp($raw);
	$raw =~ s/([\s\n\r;])+/ /g;

	my %cache;
	my @list;
	foreach my $line (split(" ", $raw)) {
		# Some fields allow for a hostname which should not be resolved to IPs
		# Push without looking up DNS info
		if ($line =~ m/^([a-z0-9\-*]+\.)+[a-z]*$/) {
			push(@list,$line);
		} else {
			$cache{$line} = undef;
		}
	}

	my $continue;
	do {
		foreach my $item (keys %cache) {
			if ($item eq '*' || $item eq '0.0.0.0/0') {
				$self->{recursion} = $recursion;
				return ( '0.0.0.0/0' );
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
                                delete($cache{$item});
                                print(STDERR "Did not understand hostlist entry $item\n");
                        }
		}

		$continue = 0;
		foreach my $key (keys %cache) {
			unless (defined($cache{$key})) {
				$continue = 1;
			}
		}
	} while ($continue);

	foreach (keys %cache) {
		if ($cache{$_} eq 'cached') {
			next;
		} else {
			push(@list,$cache{$_});
		}
	}
			
	$self->{recursion} = $recursion;
	return $self->uniq(@list);
}
			
sub getA
{
	my $self = shift;
	my $target = shift;
	
	my $res = $self->{'resolver'}->query($target, 'A');
	if ($res) {
		return ($res->answer)[0]->address;
	}

	return ();
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
			@blocks = split(' ', $_->{'txtdata'}->[0]->[0]);
			last;
		}
	}

	my @ips;
	foreach (@blocks) {
		if ($_ =~ m/^[\?\-\~]/) {
			next;
		} elsif ($_ =~ /\+?(v=spf.|all|ptr|exists)$/i) {
			next;
		} elsif ($_ =~ /^([+\-\?])?a$/i) {
			if ($self->{'recursion'}) {
				push(@ips,$self->getA($target));
			} else {
				push(@ips,$target.'/a');
			}
		} elsif ($_ =~ /^([+\-\?])?aaaa?$/i) {
			if ($self->{'recursion'}) {
				push(@ips,$self->getAAAA($target));
			} else {
				push(@ips,$target.'/aaaa');
			}
		} elsif ($_ =~ /^([+\-\?])?mx$/i) {
			if ($self->{'recursion'}) {
				push(@ips,$self->getMX($target));
			} else {
				push(@ips,$target.'/mx');
			}
		} elsif ($_ =~ /([+\-\?])?ip[46]:/i) {
			my ($type, @ip) = split(':',$_);
			push(@ips,join(':',@ip));
		} elsif ($_ =~ /([+\-\?])?include:/i) {
			my ($tmp, $include) = split(':',$_);
			if ($self->{'recursion'}) {
				push(@ips,$self->getSPF($include));
			} else {
				push(@ips,$include.'/spf');
			}
		} elsif ($_ =~ /([+\-\?])?redirect=/i) {
			my ($tmp, $redirect) = split('=',$_);
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

	return @uniq
}
1;
