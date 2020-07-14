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

sub gglapi_domain {
        my ($self, $permsgstatus, $body, $body_html) = @_;

        # Recipient detection
        my $Recipients = lc( $permsgstatus->get('X-MailCleaner-recipients') );
        chomp($Recipients);
        my @AllRecipients = split(', ', $Recipients);
        my %AllRecipientsDomains;
        foreach my $Recip(@AllRecipients) {
                $Recip = _domain($Recip);
                $AllRecipientsDomains{$Recip} = 1;
        }
        # URI detection
        my $uris = $permsgstatus->get_uri_detail_list ();

        while (my($uri, $info) = each %{$uris}) {
                if ( $uri =~ m/googleapis.com/ ) {
                        foreach my $k (keys %AllRecipientsDomains) {
                                if ($uri =~ m/\Q$k/) {
                                        return 1;
                                }
                        }
                }
        }

        return 0;
}
