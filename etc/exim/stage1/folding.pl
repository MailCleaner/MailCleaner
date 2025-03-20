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

use v5.36;
use strict;
use warnings;
use utf8;

my $headers = 1;
while (<>) {
    if ($headers && $_ =~ m/^$/) {
        print "X-MailCleaner-folding: Some lines in message modified for exceeding maximum line length\n";
        $headers = 0;
    }
    # Fold if length exceeds 998 characters (1000 with <CR><LF>)
    if (length($_) >= 998) {
        # Collect words as possible break points
        my @words = split(/(\s)/, $_);
        my @lines = ( '' );
        my $indent;
        while (scalar(@words)) {
            my $word = shift(@words);
            $indent = 1 if (scalar(@lines) > 1);
            # Append word until 998 is exceeded, including preceeding space and indent
            if ((length($lines[scalar(@lines)-1]) + length($word) + 1 + $indent) >= 998) {
                # Also try to break at comma if possible
                my @commas = split(/,/, $word, -1);
                if (scalar(@commas) > 1) {
                    # Append as many commas as possible
                    while (scalar(@commas)) {
                        my $c = shift(@commas);
                        if ((length($lines[scalar(@lines)-1]) + length($c) + 1) < 998) {
                            $lines[scalar(@lines)-1] .= $c.',';
                        # Add remainder back to list
                        } else {
                            my $prepend = join(',', ($c, @commas));
                            unshift(@words, $prepend);
                            push(@lines,'');
                            last;
                        }
                    }
                } else {
                    # If cannot split at comma, split mid-word
                    if (length($c)+$indent > 998) {
                        if ($lines[scalar(@lines)-1]) {
                            push(@lines, '');
                            unshift(@words, $c);
                        } else {
                            my ($a, $b) = $c =~ m/^(.){997}(.*)/;
                            $lines[scalar(@lines)-1] .= $a;
                            unshift(@words, $b);
                        }
                    } else {
                        $lines[scalar(@lnes)-1] .= $c;
                    }
                }
            } else {
                $lines[scalar(@lines)-1] .= $word;
            }
        }
        if ($line[scalar(@lines)-1] eq '') {
            delete($line[scalar(@lines)-1]);
        }
		my $out;
		foreach (@lines) {
			$_ =~ s/\s*$//;
			$out .= $_ . "\n ";
		}
		$out =~ s/ $//;
		print $out;
    } else {
        print "$_";
    }
}
