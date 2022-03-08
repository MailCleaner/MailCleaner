#!/usr/bin/perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2022 John Mertz <git@john.me.tz>
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
#   Library to decode URLs from URL scanning and Rewriting services.

package URLRedirects;

use strict;
use warnings;
use URI::Escape;

# If you find a rewriter domain but are unable to determine a reliable way to
# decoder the original URL, you should whitelist it to prevent UriRBL listing
# for the legitimate service. This could be the case if the URL references a
# database which we can't query.

our @whitelist = (
    "mailcleaner.net",
    "mailcleaner.org"
);
foreach (@whitelist) {
    $_ =~ s/\./\\./;
}
our $whitelists = 'https?://(www\.)?(' . ( join '|', @whitelist) . ')/';

sub new
{
	my ($class, $args) = @_;
	my $self = $args;
	$self->{'services'} = getServices();
	return bless $self;
}

sub getServices
{
	# List of known rewriting services. Each requires a 'regex' for the URL input
	# pattern and a 'decoder' function which returns the decoded URL.
	my %services = (
		"Google Redirect" => {
			"regex"	    => qr#https?://www\.google\.com/url\?q=#,
			"decoder"   => sub {
	   			my $url = shift;
           			$url =~ s#https?://www\.google\.com/url\?q=(.*)#$1#;
	   			$url = uri_unescape($url);
	   			return $url;
			}
		},
		"Proofpoint-v2" => {
       			"regex"     => qr#https?://urldefense\.proofpoint\.com/v2#,
       			"decoder"   => sub {
           			my $url = shift;
           			$url =~ s|\-|\%|g;
           			$url =~ s|_|\/|g;
           			$url = uri_unescape($url);
           			$url =~ s/^[^\?]*\?u=([^&]*)&.*$/$1/;
           			return $url;
       			}
		},
		"Proofpoint-v3" => {
       			"regex"     => qr#https?://urldefense\.com/v3#,
       			"decoder"   => sub {
           			my $url = shift;
           			#$url =~ s|[^_]*__(.*)\$$|$1|;
           			$url =~ s|[^_]*__(.*)/__.*|$1|;
           			$url = uri_unescape($url);
           			$url =~ s/^[^\?]*\?u=([^&]*)&.*$/$1/;
           			return $url;
       			}
		},
		"Roaring Penguin" => {
       			"regex"     => qr#https?://[^/]*/canit/urlproxy.php\?_q=[a-zA-Z0-9]+#,
       			"decoder"   => sub {
           			use MIME::Base64;
           			my $url = shift;
           			$url =~ s|https?://[^/]*/canit/urlproxy\.php\?_q\=([^&]*).*|$1|;
           			$url = uri_unescape($url) ;
           			$url = decode_base64($url);
           			return $url;
       			}
		}
	);
	return \%services;
}

sub get301 
{
	my $url = shift;
	require LWP::UserAgent;
	my $ua = new LWP::UserAgent;

	my $request = new HTTP::Request('HEAD', $url);
	my $response = $ua->request($request);
	unless ($response->{'_msg'} eq 'OK') {
   		return undef;
	}
	if ($response->{'_previous'}->{'_headers'}->{'location'}) {
 		return $response->{'_previous'}->{'_headers'}->{'location'};
	} else {
        	return undef;
	}
}

# The actual simple search and decode function
sub decode
{
	my $self = shift;
	my $url = shift;

	my $service;
	foreach my $service (keys(%{$self->{'services'}})) {
		if ($url =~ $self->{'services'}->{$service}->{'regex'}) {
			my $decoded = $self->{'services'}->{$service}->{'decoder'}($url);
			if ($decoded) {
				return $decoded;
			} else {
				return undef;
			}
		}
	}
	return 0;
}

1;
