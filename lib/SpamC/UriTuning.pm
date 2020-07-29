package UriTuning;

use Mail::SpamAssassin::Plugin;

our @ISA = qw(Mail::SpamAssassin::Plugin);

sub new {
        my ($class, $mailsa) = @_;
          
        # the usual perlobj boilerplate to create a subclass object
        $class = ref($class) || $class;
        my $self = $class->SUPER::new($mailsa);
        bless ($self, $class);

        # then register an eval rule, if desired...
        $self->register_eval_rule ("gglapi_domain");
    
        # and return the new plugin object
        return $self;
}

sub _domain {
        my ($string) = @_;

        $string =~ m/\@(.*)/;
        return $1;
}

# Forbids the use of given strings in a URL that also contains the domain of a recipient
# List of strings in /usr/mailcleaner/share/spamassassin/plugins/UriTuning.list
sub gglapi_domain {
        my ($self, $permsgstatus, $body, $body_html) = @_;
	my @elems;

	# This module only runs if we have a file of domains to exclude
	return 0 if ( ! -f '/usr/mailcleaner/share/spamassassin/plugins/UriTuning.list' );

	# Get list of strings
	open LIST, '<', '/usr/mailcleaner/share/spamassassin/plugins/UriTuning.list';
	@elems = <LIST>;
	close LIST;
	chomp(@elems);

        # Recipient detection
        my $Recipients = lc( $permsgstatus->get('X-MailCleaner-recipients') );
        chomp($Recipients);
        my @AllRecipients = split(', ', $Recipients);
        my %AllRecipientsDomains;
        foreach my $Recip (@AllRecipients) {
                $Recip = _domain($Recip);
                $AllRecipientsDomains{$Recip} = 1;
        }

        # URI detection
        my $uris = $permsgstatus->get_uri_detail_list ();

	# For all URIs
        while (my($uri, $info) = each %{$uris}) {
		# Check if it contains one of the strings
		foreach my $elem (@elems) {
	                if ( $uri =~ m/\Q$elem/ ) {
				# Check if it contains one of the recipient s domain
				foreach my $k (keys %AllRecipientsDomains) {
                        	        if ($uri =~ m/\Q$k/) {
                                	        return 1;
	                                }
        	                }
			}
                }
        }

	# If we are here nothing was found
        return 0;
}
