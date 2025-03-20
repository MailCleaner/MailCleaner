#!/usr/bin/env perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2025 John Mertz <git@john.me.tz>
#
#   This program is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; either version 2 of the License, or
#   (at your option) any later version.
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
#
#   Library to decode URLs from URL scanning and Rewriting services.

package URLRedirects;

use v5.36;
use strict;
use warnings;
use utf8;

use URI::Escape;

sub new
{
    my ($class, $args) = @_;
    my $self = $args;
    $self->{'services'} = getServices();
    $self->{'generics'} = getGenerics();
    # Prioritize specific services over generic patterns. Split with 'undef' to indicate when search has has changed
    $self->{'all'} = [ keys(%{$self->{'services'}}), undef, keys(%{$self->{'generics'}}) ];
    return bless $self;
}

sub getServices
{
    # List of known rewriting services. Each requires a 'regex' for the URL input
    # pattern and a 'decoder' function which returns the decoded URL.
    my %services = (
        "Bing" => {
            "regex" => qr%bing\.com/ck/a\?!&&%,
            "decoder" => sub($url) {
                $url =~ s%.*bing\.com/ck/a\?!&&(?:[^u=]+=[^&]+&)*u=a1([^&]+)&.*%$1%;
                return decode_base64($url);
            }
        },
        "LinkedIn" => {
            "regex" => qr%linkedin.com/slink\?code=([^#]+)%,
            "decoder" => sub($url) {
                return head($url);
            }
        },
        "Proofpoint-v2" => {
            "regex"   => qr#urldefense\.proofpoint\.com/v2#,
            "decoder" => sub($url) {
                $url =~ s|\-|\%|g;
                $url =~ s|_|\/|g;
                $url = uri_unescape($url);
                $url =~ s/^[^\?]*\?u=([^&]*)&.*$/$1/;
                return $url;
            }
        },
        "Proofpoint-v3" => {
            "regex"   => qr#urldefense\.com/v3#,
            "decoder" => sub($url) {
                $url =~ s|[^_]*__(.*)/__.*|$1|;
                $url = uri_unescape($url);
                $url =~ s/^[^\?]*\?u=([^&]*)&.*$/$1/;
                return $url;
            }
        },
        "Roaring Penguin" => {
            "regex"   => qr#[^/]*/canit/urlproxy.php\?_q=[a-zA-Z0-9]+#,
            "decoder" => sub($url) {
                use MIME::Base64;
                $url =~ s|[^/]*/canit/urlproxy\.php\?_q\=([^&]*).*|$1|;
                $url = uri_unescape($url) ;
                return decode_base64($url);
            }
        }
    );
    return \%services;
}

sub getGenerics
{
    my %generics = (
        # Generic uri_encoded path included as a url argument
        "uri_encoded_arg" => {
            "regex"   => qr#^[^/]*/[^\?]*\?.*=https?\%3A\%2F\%2F#,
            "decoder" => sub {
                my $url = shift;
                $url =~ s#^[^/]*/[^\?]*\?.*=https?\%3A\%2F\%2F([^&]*)&?.*#$1#;
                return uri_unescape($url);
            }
        }
    );
    return \%generics;
}

# The actual simple search and decode function
sub decode($self,$url,$recursed=0)
{
    $url =~ s#^https?://##;
    my $type = 'services';
    foreach my $service (@{$self->{'services'}}) {
        if (!defined($service)) {
            $type = 'generics';
            next;
        }
        if ($url =~ $self->{$type}->{$service}->{'regex'}) {
            my $decoded = $self->{$type}->{$service}->{'decoder'}($url);
            if ($decoded) {
                # Limit recursion to 10 steps
                return $decoded if ($recursed == 10);
                return $self->decode($decoded, ++$recursed);
            } else {
                return $url if ($recursed);
                return undef;
            }
        }
    }
    return $url if ($recursed);
    return 0;
}

1;
