#!/usr/bin/env perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
#   Copyright (C) 2025 John Mertz <git@john.me.tz>
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
#   This module will just read the configuration file

package SpamHandler::Batch;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;
require SpamHandler::Message;
use Time::HiRes qw(gettimeofday tv_interval);

our @ISA     = qw(Exporter);
our @EXPORT  = qw(new);
our $VERSION = 1.0;

sub new($dir,$daemon)
{
    my %timers;

    my $self = {
        spamdir => $dir,
        daemon  => $daemon,
        batchid => 0,
        %timers => (),
    };
    $self->{messages} = {};

    bless $self, 'SpamHandler::Batch';

    if ( !$self->{spamdir} || !-d $self->{spamdir} ) {
        return 0;
    }

    return $self;
}

sub prepareRun($self)
{
    #generate a random id
    srand;
    $self->{batchid} = int( rand(1000000) );
    return 1;
}

sub getMessagesToProcess($self)
{
    $self->{daemon}->profile_start('BATCHLOAD');
    chdir( $self->{spamdir} ) or return 0;
    my $sdir;
    opendir($sdir, '.' ) or return 0;

    my $waitingcount = 0;
    my $batchsize    = 0;
    my $maxbatchsize = $self->{daemon}->{maxbatchsize};
    while ( my $entry = readdir($sdir) ) {
        next if ( $entry !~ m/(\S+)\.env$/ );
        $waitingcount++;
        my $id = $1;

        next if ( $batchsize >= $maxbatchsize );
        next if ( $self->{daemon}->isLocked($id) );
        if ( -f $id . ".msg" ) {
            $batchsize++;
            $self->addMessage($id);
        } else {
            $self->{daemon}->doLog(
                $self->{batchid} . ": NOTICE: message $id has envelope file but no body...",
                'spamhandler'
            );
        }
    }

    if ( $waitingcount > 0 ) {
        $self->{daemon}->doLog(
            "$self->{batchid}: $waitingcount messages waiting, taken ".keys %{ $self->{messages} },
            'spamhandler',
            'debug'
        );
    } else {
        $self->{daemon}->doLog(
            "$self->{batchid}: $waitingcount messages waiting, taken ".keys %{ $self->{messages} },
            'spamhandler',
            'debug'
        );
    }
    closedir($sdir);
    my $btime = $self->{daemon}->profile_stop('BATCHLOAD');
    $self->{daemon}->doLog(
        "$self->{batchid}: finished batch loading in $btime seconds",
        'spamhandler',
        'debug'
    );
    return 1;
}

sub addMessage($self,$id)
{
    $self->{daemon}->addLock($id);
    $self->{messages}{$id} = $id;
}

sub run($self)
{
    $self->{daemon}->profile_start('BATCHRUN');
    my $nbmsgs = keys %{ $self->{messages} };

    my $t   = threads->self;
    my $tid = $t->tid;

    return if ( $nbmsgs < 1 );
    $self->{daemon}->doLog(
        "$self->{batchid}: starting batch run",
        'spamhanler',
        'debug'
    );
    my $count = 0;
    foreach my $msgid ( keys %{ $self->{messages} } ) {
        $count++;
        $self->{daemon}->doLog(
            "($tid) $self->{batchid}: processing message: $msgid ($count/$nbmsgs)",
            'spamhandler',
            'debug'
        );

        my $msg = SpamHandler::Message::new(
            $msgid,
            $self->{daemon},
            $self->{batchid}
        );
        $msg->load();
        $msg->process();

        ## then log
        my %prepared = %{ $self->{daemon}->{prepared} };
        my $inmaster = 0;
        foreach my $dbname ( keys %prepared ) {
            $msg->log( $dbname, \$inmaster );
        }
        $msg->purge();
        my $msgtimers = $msg->getTimers();
        $self->addTimers($msgtimers);
    }
    delete $self->{messages};

    $self->startTimer('Batch logging message');
    foreach my $dbname ( keys %{ $self->{daemon}->{dbs} } ) {
        if ( $self->{daemon}->{dbs}{$dbname} ) {
            $self->{daemon}->{dbs}{$dbname}->commit();
        }
    }
    $self->endTimer('Batch logging message');
    $self->logTimers();
    my $btime = $self->{daemon}->profile_stop('BATCHRUN');
    $self->{daemon}->doLog(
        "$self->{batchid}: finished batch run in $btime seconds for $nbmsgs messages",
        'spamhandler',
        'debug'
    );
}

sub addTimers($self,$msgtimers)
{
    return if !$msgtimers;
    my %timers = %{$msgtimers};
    foreach my $t ( keys %timers ) {
        next if ( $t !~ m/^d_/ );
        $self->{'timers'}{$t} += $timers{$t};
    }
    return 1;
}

#######
## profiling timers

sub startTimer($self,$timer)
{
    $self->{'timers'}{$timer} = [gettimeofday];
}

sub endTimer($self,$timer)
{
    my $interval = tv_interval( $self->{timers}{$timer} );
    $self->{'timers'}{ 'd_' . $timer } = ( int( $interval * 10000 ) / 10000 );
}

sub getTimers($self)
{
    return $self->{'timers'};
}

sub logTimers($self)
{
    foreach my $t ( keys %{ $self->{'timers'} } ) {
        next if ( $t !~ m/^d_/ );
        my $tn = $t;
        $tn =~ s/^d_//;
        $self->{daemon}->doLog(
            'Batch spent ' . $self->{'timers'}{$t} . "s. in " . $tn,
            'spamhanler',
            'debug'
        );
    }
}

1;
