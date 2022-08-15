#!/usr/bin/perl

while (<>) {
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
                my $out = join("\n ", @lines);
                print "$out";
        } else {
                print "$_";
        }
}
