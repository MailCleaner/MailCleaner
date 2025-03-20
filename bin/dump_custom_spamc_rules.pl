#!/usr/bin/env perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2023 John Mertz <git@john.me.tz>
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

use v5.36;
use strict;
use warnings;
use utf8;
use Carp qw( confess );

our ($conf, $SRCDIR);
BEGIN {
    if ($0 =~ m/(\S*)\/\S+.pl$/) {
        my $path = $1."/../lib";
        unshift (@INC, $path);
    }
    require ReadConfig;
    $conf = ReadConfig::getInstance();
    $SRCDIR = $conf->getOption('SRCDIR');
    unshift(@INC, $SRCDIR."/lib");
}

use lib_utils qw( open_as );

require DB;
my $db = DB::connect('slave', 'mc_config');
our %domains;
our %senders;
my $rules_file = '/usr/mailcleaner/share/spamassassin/98_mc_custom.cf';
my $rcpt_id = 0;
my $sender_id = 0;
our $RULEFILE;

# first remove file if exists
unlink $rules_file if ( -f $rules_file );

# get list of SpamC exceptions
my @wwlists = $db->getListOfHash("SELECT * from wwlists where type = 'SpamC' order by comments ASC, sender DESC");
exit unless(scalar(@wwlists));
confess "Cannot open full log file: $rules_file\n" unless ( $RULEFILE = ${open_as($rules_file)} );

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
close $RULEFILE;

sub set_current_rule($current_rule)
{
    my $current_rule_w = $current_rule;
    $current_rule_w =~ s/\s+/_/;
    $current_rule_w =~ s/-/_/;
    $current_rule_w =~ s/\./_/;

    return ($current_rule, $current_rule_w);
}

# rules to detect if the wanted rule did hit for those recipients (/senders)
sub print_custom_rule($current_rule, $current_rule_w, $current_sender, @current_rule_domains)
{
    my ($rule, $score) = split(' ', $current_rule);
    print $RULEFILE "meta RCPT_CUSTOM_$current_rule_w ( $rule ";
    if ($current_sender ne '') {
        print $RULEFILE '&& __SENDER_' .$senders{$current_sender}. ' ';
    }
    my $global = 0;
    my $rcpt_string = "&& (";
    foreach (@current_rule_domains) {
    if (!defined($_)) {
        $rcpt_string = '';
        last;
    }
        $rcpt_string .= "__RCPT_$_ || "
    }
    if ($rcpt_string) {
        $rcpt_string =~ s/\ \|\|\ $/\) /;
    }
    print $RULEFILE "$rcpt_string)\n";
    print $RULEFILE "score RCPT_CUSTOM_$current_rule_w $score\n\n";
}

# Rules to identify domains
sub print_recipient_rules($recipient)
{
    return if defined $domains{$recipient};
    return if $recipient =~ m/\@__global__/;

    $domains{$recipient} = $rcpt_id;

    $recipient =~ s/\./\\\./g;
    $recipient =~ s/\@/\\\@/g;

    print $RULEFILE "header __RCPT_TO_$rcpt_id  To =~ /$recipient/i\n";
    print $RULEFILE "header __RCPT_CC_$rcpt_id  Cc =~ /$recipient/i\n";
    print $RULEFILE "header __RCPT_BCC_$rcpt_id Bcc =~ /$recipient/i\n";
    print $RULEFILE "meta   __RCPT_$rcpt_id     ( __RCPT_TO_$rcpt_id || __RCPT_CC_$rcpt_id || __RCPT_BCC_$rcpt_id )\n\n";

    $rcpt_id++;
}

# Rules to identify senders
sub print_sender_rules($sender)
{
    return if ($sender eq '');
    return if defined $senders{$sender};

    $senders{$sender} = $sender_id;
    $sender =~ s/\./\\\./g;
    $sender =~ s/\@/\\\@/g;

    print $RULEFILE "header __SENDER_$sender_id  From =~ /$sender/i\n";

    $sender_id++;
}
