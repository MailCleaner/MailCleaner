#!/usr/bin/perl -w
use strict;
use File::Copy;
use File::Path;
if ($0 =~ m/(\S*)\/\S+.pl$/) {
  my $path = $1."/../lib";
  unshift (@INC, $path);
}

require DB;
my $db = DB::connect('slave', 'mc_config');

my $dbh;
my %domains;
my %senders;
my $rules_file = '/usr/mailcleaner/share/spamassassin/98_mc_custom.cf';
my $rcpt_id = 0;
my $sender_id = 0;

sub set_current_rule {
    my ($current_rule) = @_;
        my $current_rule_w = $current_rule;
        $current_rule_w =~ s/\s+/_/;
        $current_rule_w =~ s/-/_/;
        $current_rule_w =~ s/\./_/;

	return ($current_rule, $current_rule_w);
}

# rules to detect if the wanted rule did hit for those recipients (/senders)
sub print_custom_rule {
    my ($current_rule, $current_rule_w, $current_sender, @current_rule_domains) = @_;

    my ($rule, $score) = split(' ', $current_rule);
    my $rule_string = "meta __RCPT_CUSTOM_$current_rule_w (";
    foreach (@current_rule_domains) {
        $rule_string .= " __RCPT_$_ ||"
    }
    $rule_string =~ s/\|\|$//;
    $rule_string .= ")\n";
    print RULEFILE $rule_string;
    print RULEFILE "meta RCPT_CUSTOM_$current_rule_w ( $rule && ";
    if ($current_sender ne '') {
        print RULEFILE '__SENDER_' .$senders{$current_sender}. ' && ';
    }
    print RULEFILE "__RCPT_CUSTOM_$current_rule_w )\n";
    print RULEFILE "score RCPT_CUSTOM_$current_rule_w $score\n\n";
}

# Rules to identify domains
sub print_recipient_rules {
    my ($recipient) = @_;

    return if defined $domains{$recipient};

    $domains{$recipient} = $rcpt_id;

    $recipient =~ s/\./\\\./g;
    $recipient =~ s/\@/\\\@/g;

    print RULEFILE "header __RCPT_TO_$rcpt_id  To =~ /$recipient/i\n";
    print RULEFILE "header __RCPT_CC_$rcpt_id  Cc =~ /$recipient/i\n";
    print RULEFILE "header __RCPT_BCC_$rcpt_id Bcc =~ /$recipient/i\n";
    print RULEFILE "meta   __RCPT_$rcpt_id     ( __RCPT_TO_$rcpt_id || __RCPT_CC_$rcpt_id || __RCPT_BCC_$rcpt_id )\n\n";

    $rcpt_id++;
}

# Rules to identify senders
sub print_sender_rules {
    my ($sender) = @_;

    return if ($sender eq '');
    return if defined $senders{$sender};

    $senders{$sender} = $sender_id;
    $sender =~ s/\./\\\./g;
    $sender =~ s/\@/\\\@/g;

    print RULEFILE "header __SENDER_$sender_id  From =~ /$sender/i\n";

    $sender_id++;
}

# first remove file if exists
unlink $rules_file if ( -f $rules_file );


# get list of SpamC exceptions
my @wwlists = $db->getListOfHash("SELECT * from wwlists where type = 'SpamC' order by comments ASC, sender DESC");
exit if (!@wwlists);
if ( ! open(RULEFILE, '>', $rules_file )) {
    print STDERR "Cannot open full log file: $rules_file\n";
    exit();
}

my $current_rule;
my $current_rule_w;
my $current_sender;
my @current_rule_domains;
foreach my $l (@wwlists) {
    my %rule = %{$l};

    # Do SpamC rules for recipients
    print_recipient_rules($rule{'recipient'});

    # Do SpamC rules for senders if needed
    if ( defined ($rule{'sender'}) ) {
        $rule{'sender'} =~ s/\s*//g;
    } else {
        $rule{'sender'} = '';
    }
    if ( defined ($rule{'sender'}) && ($rule{'sender'} ne '') ) {
        print_sender_rules($rule{'sender'});
    }

    # Make sure rules have the right format or ignore them
    if ( defined ($rule{'comments'}) ) {
        $rule{'comments'} =~ s/^\s*//;
        $rule{'comments'} =~ s/\s*$//;
    }
    if ( $rule{'comments'} !~ m/[^\s]+ -?\d+\.?\d*/ ) {
	next;
    }

    # Set current variables (rules and senders) to keep track of a change in order to write the rules when needed
    if ( ! defined($current_rule)) {
        ($current_rule, $current_rule_w) = set_current_rule($rule{'comments'});
    }

    if ( ! defined($current_sender)) {
        $current_sender = $rule{'sender'};
    }

    my $domain_id;
    my $t = $rule{'recipient'};
    $domain_id = $domains{$t};

    # If we changed rule, in this script rule means SpamC rule name + score
    if ( ($rule{'comments'} ne $current_rule) || ($rule{'sender'} ne $current_sender) ) {

	print_custom_rule($current_rule, $current_rule_w, $current_sender, @current_rule_domains);

        ($current_rule, $current_rule_w) = set_current_rule($rule{'comments'});
        $current_sender = $rule{'sender'};
	@current_rule_domains = ();
	push @current_rule_domains, $domain_id;
    } else {
        push @current_rule_domains, $domain_id;
    }
}
print_custom_rule($current_rule, $current_rule_w, $current_sender, @current_rule_domains);

close RULEFILE;
