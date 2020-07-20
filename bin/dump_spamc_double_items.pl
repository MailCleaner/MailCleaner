#! /usr/bin/perl 

my $i = 0;
my $j = 0;

unlink '/usr/mailcleaner/share/spamassassin/double_item_uri.cf';

exit if ( ! -f '/usr/mailcleaner/share/spamassassin/double_item_uri.txt' );

open FH_TXT, '<', '/usr/mailcleaner/share/spamassassin/double_item_uri.txt';
open RULES, '>', '/usr/mailcleaner/share/spamassassin/double_item_uri.cf';
while (<FH_TXT>) {
        $_ =~ s/^\s*//;
        $_ =~ s/\s*$//;
        next if ( $_ =~ m/^#/ );
        my ($w1, $w2) = split(' ', $_);
        next if ( ! defined($w2) );
        print RULES "# auto generated rule to prevent links containing both $w1 and $w2\n";
        print RULES "uri __MC_URI_DBL_ITEM_$i /$w1.*$w2/i\n";
        $j = $i;
        $i++;
        print RULES "uri __MC_URI_DBL_ITEM_$i /$w2.*$w1/i\n";

        print RULES "\nmeta MC_URI_DBL_ITEM_$j ( __MC_URI_DBL_ITEM_$j || __MC_URI_DBL_ITEM_$i )\n";
        print RULES "describe MC_URI_DBL_ITEM_$j Link containing both $w1 and $w2\n";
        print RULES "score MC_URI_DBL_ITEM_$j 7.0\n\n";
        $i++;
}
close FH_TXT;
close RULES;
