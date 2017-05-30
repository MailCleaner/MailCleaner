
package MailScanner::AlwaysSpam;

use strict 'vars';
use strict 'refs';
no  strict 'subs'; # Allow bare words for parameter %'s
#use English; # Needed for $PERL_VERSION to work in all versions of Perl

use IO;
use POSIX qw(:signal_h); # For Solaris 9 SIG bug workaround

sub initialise {
  MailScanner::Log::InfoLog('AlwaysSpam module initializing...');
}

sub Checks {
  my $message = shift;

  MailScanner::Log::InfoLog('AlwaysSpam module checking... well guess what ? it\'s spam !');

  return 1;
}

sub dispose {
  MailScanner::Log::InfoLog('AlwaysSpam module disposing...');
}

1;
