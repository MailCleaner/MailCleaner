#!/usr/bin/perl

#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2020 John Mertz <git@john.me.tz>
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
#   This script will generate outbound relaying summaries to assist in the
#   diagnosing of outbound blacklisting or other unexpected relaying behaviour
#
#   See Usage mene below or by running with the --help option

use strict;
use warnings;
use Module::Load::Conditional qw| check_install |;

my $VAR = "/var/mailcleaner";
my $domains_list = "$VAR/spool/tmp/mailcleaner/domains.list";

sub usage {
    print STDERR <<EOL;

usage: $0 [--detailed] [--ids] [--debug] [<days>] [<sender>]

Options can be given in any order, however the first numeric option will be
treated as the <days> value and the first non-numberic option (which is not
listed below) will be treated as the <sender> value.

Options:

--recipients    Count sender/recipient pairs, instead of just unique senders.

--ids           Include the IDs for each sender or sender/recipient pair.

--debug         Print the input settings that were recognized and the sender
                regex used to STDERR.

<days>          Number of days to search. In reverse, prior to today.
                'ALL' can be used for all available.
                Warning will be printed to STDERR for a large number of days.
                Default: 0 (just today)

<sender>        Simple filter for a specific sender or domain.
                If the string entered matches one of your domains exactly,
                only that domain will be searched.
                Any string with a '\@' will be searched for as a complete
                email address. As a side-effect, external messages from a
                specific sender could also be searched this way.
                Any other string will be searched as a username at any of
                your existing domains.
                Default: all internal addresses.
EOL
    exit 0;
}

my ($days, $recipients, $ids, $debug, $unlisted) = (0, 0, 0, 0, 1);
my $filter;

# Collect arguments. Print usage if an argument is not understood or
if (scalar @ARGV) {
    foreach (@ARGV) {
        if ($_ eq '-h' || $_ eq '--help' || $_ eq '?') {
            usage();
        } elsif ($_ eq '--debug' && !$debug) {
            $debug = 1;
        } elsif ($_ eq '--recipients' && !$recipients) {
            $recipients = 1;
        } elsif ($_ eq '--ids' && !$ids) {
            $ids = 1;
        } elsif ($_ =~ /^(\d+|ALL)$/) {
            $days = $_;
        } elsif (!defined($filter)) {
            $filter = $_;
            # Ignore unlisted domains when using a filter is in use
            $unlisted = 0;
        } else {
            print STDERR "Argument $_ not understood, or redundant.\n";
            usage();
        }
    }
}

# Get the number of log days available if ALL is selected
if ($days eq 'ALL') {
    $days = 1;
    while (-e "$VAR/log/exim_stage1/mainlog.$days.gz") {
        $days++;
    }
}

# Print warning for large number of days
if ($days gt 30) {
    print STDERR "Searching " . ($days+1) . " days. This can take a long time. Use Ctrl+C to exit.\n";
}

# Parse sender argument, if given and define a regex
my $regex = '';

# If sender given and contains @, search for this as complete address
if (defined $filter && $filter =~ /@/) {
    $regex = "<= $filter ";
# Otherwise, get the domains list
} else {
    my @domains;
    open(my $df, '<', $domains_list) || die "Unable to open domain list: $domains_list\nYou probably don't have any domains configured.\n";
    while (<$df>) {
        push @domains, (split ':', $_)[0];
        # If the sender argument matches this domain exactly, set that as the regex
        if (defined($filter) && (split ':', $_)[0] eq $filter) {
            $regex = "<= [^\ ]*\@$filter ";
            last;
        }
    }
    close $df;
    unless (scalar @domains) {
        die "Didn't find any internal domains in $domains_list\n";
    }
    # If regex already set, move on
    unless ($regex) {
        # If a sender is defined without a @, search as username at all domains.
        if (defined $filter) {
            $regex = "<= $filter@(" . join('|', @domains) . ") ";
        # Otherwise search any user at all domains
        } else {
            $regex = "<= [^\ ]*@(" . join('|', @domains) . ") ";
        }
    }
}

# If requiring more than just today and yesterday, we need the gzip module
if ($days gt 1) {
    check_install( 'module' => 'PerlIO::gzip', 'version' => 0.18) || die "Searching logs from previous days requires the library PerlIO::gzip. Please install with:\n  apt-get install libperlio-gzip-perl\n\n";
}

# Output parsed arguments and regex if requested
if ($debug) {
    print STDERR "\nSearch settings:\nrecipient: $recipients, ids: $ids, days: $days, sender: " . ($filter || 'NONE') . ", unlisted: " . $unlisted . ", regex: /$regex/\n\n";
}

# Messages relayed by IP have a dedicated log line mentioning this.
my %outstanding_senders;

# If unlisted domains are allowed to relay, our regex won't include them. Collect that first to catch any senders who might be from an unlisted domain.
my %unlisted;

# Senders are collected on a different line than recipient.
# It is possible for these to be non-sequential, so we need to track which sender and ID we're waiting to complete.
my %outstanding_ids;

# Go through all log files requested and collect all sender, recipient and id data
my %results;
for (my $i = 0; $i <= $days; $i++) {
    my $fh;
    if ($i eq 0) {
        open $fh, '<', "$VAR/log/exim_stage1/mainlog" or die $!;
    } elsif ($i eq 1) {
        open $fh, '<', "$VAR/log/exim_stage1/mainlog.0" or die $!;
    } else {
        open $fh, '<:gzip', "$VAR/log/exim_stage1/mainlog.".($i-1).".gz" or die $!;
    }
    while (<$fh>) {
        # Search for initial log line to for both listed and unlisted domains
        if ($_ =~ /Accepting authorized relaying session from [^,]*, sender ([^\ ]*)/) {
            # Create or increment key for this sender to indicate that we're expecting <= line for that sender
            if ($unlisted) {
                if (!defined $outstanding_senders{$1}) {
                    $outstanding_senders{$1} = 1;
                } else {
                    $outstanding_senders{$1}++;
                }
            }
        # Search for sender log line with specified sender regex
        } elsif ($_ =~ /$regex/) {
            my ($date, $time, $id, $direction, $sender) = split(' ',$_);
            # Push the id to the outstanding_ids has with sender ast the value for faster searching on key
            $outstanding_ids{$id} = $sender;
            # Decrement outstanding messages from this sender; remove the hash key if 0
            $outstanding_senders{$sender}--;
            unless ($outstanding_senders{$sender}) {
                delete $outstanding_senders{$sender};
            }
        } elsif ($_ =~ / <= / && scalar(keys %outstanding_senders)) {
            my ($date, $time, $id, $direction, $sender) = split(' ',$_);
            foreach my $outstanding_sender (keys %outstanding_senders) {
                if ($outstanding_sender eq $sender) {
                    # Push the id to the outstanding_ids has with sender ast the value for faster searching on key
                    $outstanding_ids{$id} = '*'.$sender;
                    # Set flag to output additional warning if unlisted domains are found
                    $unlisted = 2;
                    # Decrement outstanding messages from this sender; remove the hash key if 0
                    $outstanding_senders{$sender}--;
                    unless ($outstanding_senders{$sender}) {
                        delete $outstanding_senders{$sender};
                    }
                }
                last;
            }
        # Search for recipient log line if there are outstanding_ids ids
        } elsif ($_ =~ / => / && scalar(keys %outstanding_ids)) {
            my ($date, $time, $id, $direction, $recipient) = split(' ',$_);
            # See if this recipient ID matches one in our outstanding_ids list
            foreach my $out_id (keys %outstanding_ids) {
                if ($id eq $out_id) {
                    # If the ID array for the sender/recipient combo is not yet defined, open array
                    unless ($results{$outstanding_ids{$out_id}}{$recipient}) {
                        @{$results{$outstanding_ids{$out_id}}{$recipient}} = ();
                    }
                    push @{$results{$outstanding_ids{$out_id}}{$recipient}}, $id;
                    # Make sure to delete from outstanding_ids so that we don't look for that ID anymore
                    delete $outstanding_ids{$id};
                    last;
                }
            }
        }
    }
    close $fh;
}

# Break hash into domains
my %domains;
foreach my $sender (keys %results) {
    my ($user, $domain) = split('@',$sender);
    unless ($domains{$domain}) {
        @{$domains{$domain}} = ();
    }
    push @{$domains{$domain}}, $sender;
}

# Sort domains
my @sorted_domains = sort {lc($a) cmp lc($b)} (keys %domains);

# Get list of senders sorted by domain, then sender
my @sorted;
foreach my $domain (@sorted_domains) {
    my @users = sort {lc($a) cmp lc($b)} (@{$domains{$domain}});
    foreach (@users) {
        push @sorted, $_;
    }
}

# Create nicely formatted output based on provided options
my $output = '';
# Tally for all senders
my $all = 0;
foreach my $sender (@sorted) {
    # Tally for all messages to specific sender
    my $total = 0;
    # Collect all ids if --ids, but not --recipients
    my @ids = ();
    # Start output for individual sender
    my $sender_out = $sender;
    foreach my $recipient (keys %{$results{$sender}}) {
        # Add count for this recipient to total tally.
        $total += scalar @{$results{$sender}{$recipient}};
        # If recipients were requested, a new line of output is required for each
        if ($recipients) {
            # Additionally IDs for each sender/recipient pair, if requested
            if ($ids) {
                $sender_out .= "\n  " . $recipient . " (" . scalar(@{$results{$sender}{$recipient}}) . "): " . join(', ',@{$results{$sender}{$recipient}});
            } else {
                $sender_out .= "\n  " . $recipient . " (" . scalar(@{$results{$sender}{$recipient}}) . ")";
            }
        # Otherwise, all recipients are tallied on the same line. If IDs or requested, we need to track them.
        } elsif ($ids) {
            foreach (@{$results{$sender}{$recipient}}) {
                push @ids, $_;
            }
        }
    }
    # Add to overall tally
    $all += $total;
    # If --recipients then sender output is already generated, otherwise we need to do so
    if (!$recipients) {
        $sender_out .= ' (' . $total . ')';
        # Additionally add IDs, if requested
        if ($ids) {
            $sender_out .= ': ' . join(', ', @ids);
        }
    }
    # Append this sender to the complete output
    $output .= $sender_out . "\n";
}

# Append overall count to the end
$output .= "Total: $all\n";

# Print results or print warning to STDERR
if ($output eq '') {
    print STDERR "No results found using this criteria.\n";
    exit 0;
} else {
    print $output;
    # If an unlisted address is found, this flag will be set
    if ($unlisted == 2) {
        print STDERR "Note: addresses beginning with '*' are from unlisted domains\n";
    }
    exit 1;
}

