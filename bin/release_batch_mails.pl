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
#
#   This script can be used to release in batch emails that were put in
#   quarantine

use v5.36;
use strict;
use warnings;
use utf8;

if ($0 =~ m/(\S*)\/\S+.pl$/) {
    my $path = $1."/../lib";
    unshift (@INC, $path);
}

use Term::ReadKey;

sub usage
{
    my ($year,$month,$day) = split(/-/,`date +%Y-%m-%d`);
    chomp $day;
    my $today = sprintf("%04d-%02d-%02d",$year,$month,$day);
    my $previous;
    if ($day ne '01') {
        $previous = sprintf("%04d-%02d-%02d",$year,$month,$day-1);
    } elsif ($month ne '01') {
        $previous = sprintf("%04d-%02d-28",$year,$month-1);
    } else {
        $previous = sprintf("%04d-12-31",$year-1);
    }

    print <<EOF;

Bulk Release of Quarantined Messages

Requires at least one of the following:

-s address      Sender address. Full address or domain accepted.
-r address      Recipient address. Full address or domain accepted.
-f YYYY-MM-DD   From date. Requires at least one more option.
-t YYYY-MM-DD   To date. Requires at least one more option.

Additional options:

-n          Restrict to newsletters only.
-m N        Restrict to messages with a maximum score of N.
-R          Re-send messages that have already been forced.
-y          Automatically confirm all realeased messages.
-h --help ? Print this menu.

EOF
    exit(1);
}

sub check_and_split($addr)
{
    $addr = lc($addr);
    if ($addr =~ m/.\@/) {
        my $domain = $addr;
        $addr =~ s/^([0-9a-z\.\-\@\+]+)\@[^\@]+$/$1/;
        $domain =~ s/.*@([^\@]*)$/$1/;
        return ($addr,$domain);
        if (($addr =~ m/^[0-9a-z\.\-\@\+]+$/) && ($domain =~ m/^[0-9a-z\-\.]{2,}\.[a-z]{2,}$/)) {
            return ($addr,$domain)
        } else {
            die "Invalid recipient address\n";
        }
    } elsif ($addr =~ m/^\@?[0-9a-z\-\.]{2,}\.[a-z]{2,}$/ ) {
        return (undef,$addr);
    }
}

sub check_date($date)
{
    if ($date =~ m/[0-9]{4}-[0-9]{2}-[0-9]{2}/) {
        return $date;
    } else {
        die "Invalid date $date\n";
    }
}

my %args = (
    'sender'        => undef,
    'to_user'       => undef,
    'to_domain'     => undef,
    'from'          => undef,
    'to'            => undef,
    'M_score'       => undef,
    'is_newsletter' => undef,
    'forced'        => 0,
    'agree'         => undef
);

while (@ARGV) {
    my $arg = shift;
    if ($arg eq '-s' && !(defined $args{sender})) {
        $args{sender} = shift;
        ($args{from_user},$args{from_domain}) = check_and_split($args{sender});
    } elsif ($arg eq '-r' && !(defined $args{rcpt})) {
        $args{to_user} = shift;
        ($args{to_user},$args{to_domain}) = check_and_split($args{to_user});
    } elsif ($arg eq '-f' && !(defined $args{from})) {
        $args{from} = shift;
        check_date($args{from});
    } elsif ($arg eq '-t' && !(defined $args{to})) {
        $args{to} = shift;
        check_date($args{to});
    } elsif ($arg eq '-R' && defined $args{forced}) {
        $args{forced} = undef;
    } elsif ($arg eq '-y' && !$args{agree}) {
        $args{agree} = 1;
    } elsif ($arg eq '-m' && !$args{M_score}) {
        $args{M_score} = shift;
        die "Maximum score must be a number.\n" unless ($args{M_score} =~ m/[0-9]+(\.[0-9]+)?/);
    } elsif ($arg eq '-n' && !$args{is_newsletter}) {
        $args{is_newsletter} = 1;
    } elsif ($arg eq '-h' || $arg eq '--help' || $arg eq '?') {
        usage();
    } else {
        die "Invalid or redundant argument $_\n";
    }
}

unless (defined $args{from_domain} || defined $args{to_domain} || (defined $args{from} && defined $args{to})) {
    usage();
}

my $VARDIR=`grep 'VARDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`;
chomp $VARDIR;
if ( $VARDIR eq '') {
    $VARDIR="/var/mailcleaner";
}

my $SRCDIR=`grep 'SRCDIR' /etc/mailcleaner.conf | cut -d ' ' -f3`;
chomp $SRCDIR;
if ( $SRCDIR eq '' ) {
    $SRCDIR="/usr/mailcleaner";
}

my $MYMAILCLEANERPWD=`grep '^MYMAILCLEANERPWD' /etc/mailcleaner.conf | cut -d ' ' -f3`;
chomp $MYMAILCLEANERPWD;

my $SOCKET="$VARDIR/run/mysql_slave/mysqld.sock";
my $COMMAND="/opt/mysql5/bin/mysql";

my $QUERY="SELECT exim_id, to_user, to_domain, sender, M_score, is_newsletter, M_subject FROM spam WHERE";
foreach (qw | to_domain to_user is_newsletter forced |) {
    if (defined $args{$_}) {
        $QUERY .= " $_ = '$args{$_}' AND";
    }
}

if (defined $args{from_user}) {
    $QUERY .= " sender = '$args{from_user}\@$args{from_domain}' AND";
} elsif (defined $args{from_domain}) {
    $QUERY .= " sender LIKE '%$args{from_domain}' AND";
}
if (defined $args{from} && defined $args{to}) {
    if ($args{from} eq $args{to}) {
        $QUERY .= " date_in = '$args{from}' AND";
    } elsif ($args{from} gt $args{to}) {
        die "From date is more recent than To date\n";
    } else {
        $QUERY .= " date_in >= '$args{from}' AND date_in <= '$args{to}' AND";
    }
} elsif (defined $args{from}) {
    $QUERY .= " date_in >= '$args{from}' AND";
} elsif (defined $args{to}) {
    $QUERY .= " date_in <= '$args{to}' AND";
}
if (defined $args{M_score}) {
    $QUERY .= " M_score <= $args{M_score} AND";
}

$QUERY =~ s/^(.*) AND/$1;/;
print "\nSearch Query:\n" . $QUERY . "\n\n";

my $results = `echo \"$QUERY\" | $COMMAND -S $SOCKET -umailcleaner -p$MYMAILCLEANERPWD -N mc_spool`;
unless ($results) {
    die "No quarantined items found using this criteria.\n";
}
my @lines = split('\n', $results);
my @columns = qw( exim_id to_user to_domain sender M_score is_newsletter M_subject );
my @messages;
for (my $i = 0; $i < (scalar @lines); $i++) {
    my @cols = split('\t',$lines[$i]);
    my $col = 0;
    foreach (@columns) {
        if ($_ eq 'to_domain') {
            $messages[$i]{to_user} .= '@' . $cols[$col];
        } else {
            $messages[$i]{$_} = $cols[$col];
        }
        $col++;
    }
}
my @table;
push @table, { name => 'exim_id', length => 23, heading => 'Exim ID' };
push @table, { name => 'to_user', length => 20, heading => 'Recipient' };
push @table, { name => 'sender', length => 20, heading => 'Sender' };
push @table, { name => 'M_subject', length => 15, heading => 'Subject' };
push @table, { name => 'M_score', length => 3, heading => 'Scr' };
push @table, { name => 'is_newsletter', length => 1, heading => 'N' };

foreach (@table) {
    printf("%-$_->{length}s ", $_->{heading});
}
print "\n";
foreach (1..80) {
    print '-';
}
print "\n";
foreach my $msg (@messages) {
    if ($msg->{M_subject} =~ m/^\ ?=\?/) {
        $msg->{M_subject} = substr($msg->{M_subject},1);
    }
    foreach (@table) {
        printf("%-$_->{length}s ", substr($msg->{$_->{name}},0,$_->{length}));
    }
    print "\n";
}

ReadMode('cbreak');
while (! (defined $args{agree}) ) {
    print "\nWould you like to release these messages? [Y/n] ";
    $args{agree} = ReadKey(0);
    if ($args{agree} eq 'n' || $args{agree} eq 'N') {
        ReadMode('normal');
        die "\nAborting\n";
    } elsif ($args{agree} ne 'y' && $args{agree} ne 'Y') {
        $args{agree} = undef;
    }
}
ReadMode('normal');

foreach (@messages) {
    my $UPDATE="UPDATE spam SET forced = '1' WHERE exim_id = '$_->{exim_id}';";
    print "\n$SRCDIR/bin/force_message.pl $_->{exim_id} $_->{to_user}";
    `$SRCDIR/bin/force_message.pl $_->{exim_id} $_->{to_user}`;
    `echo \"$UPDATE\" | $COMMAND -S $SOCKET -umailcleaner -p$MYMAILCLEANERPWD -N mc_spool`;
}
printf "\nFinished\n";
