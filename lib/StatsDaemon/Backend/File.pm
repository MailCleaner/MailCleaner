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

package StatsDaemon::Backend::File;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;
use threads;
use threads::shared;
require ReadConfig;
use File::Path qw(mkpath);
use Fcntl qw(:flock SEEK_END);

our @ISA        = qw(Exporter);
our @EXPORT     = qw(new threadInit accessFlatElement stabilizeFlatElement getStats announceMonthChange announceDayChange);
our $VERSION    = 1.0;

my $_need_day_change : shared = 0;
my $_changing_day : shared = 0;

sub new($class,$daemon)
{
    my $conf = ReadConfig::getInstance();

    my $self = {
        'class' => $class,
        'daemon' => $daemon,
        'data' => undef,
        'basepath' => $conf->getOption('VARDIR') . '/spool/mailcleaner/stats',
        'today_filename' => '_today',
        'history_filename' => '_history'
    };

    bless $self, $class;

    foreach my $option (keys %{ $self->{daemon} }) {
        if (defined($self->{$option})) {
            $self->{$option} = $self->{daemon}->{$option};
        }
    }
    if (! -d $self->{basepath}) {
        mkpath($self->{basepath});
        $self->doLog("base path created: ".$self->{basepath});
    }
    $self->doLog("backend loaded", 'statsdaemon');

    $self->{data} = $StatsDaemon::data_;
    return $self;
}

sub threadInit($self)
{
    $self->doLog("backend thread initialization", 'statsdaemon');
}

sub accessFlatElement($self,$element)
{
    my $value = 0;

    my ($path, $file, $base, $el_key) = $self->getPathFileBaseAndKeyFromElement($element);
    if ( open(my $FILE, '<', $file)) {
        while (<$FILE>) {
            if (/^([^:\s]+)\s*:\s*(\d+)/) {
                my $newkey = $1;
                my $v = $2;
                if ($newkey eq $el_key) {
                    $value = $v;
                } else {
                    $self->{daemon}->createElement($base.":".$newkey);
                    $self->{daemon}->setElementValueByName($base.":".$newkey, 'value', $v);
                }
            }
        }
        close $FILE;
    }

    return $value;
}

sub stabilizeFlatElement($self,$element)
{
    my ($path, $file, $base, $el_key) = $self->getPathFileBaseAndKeyFromElement($element);
    if (! -d $path) {
        mkpath($path);
    }

    if ($self->{daemon}->isChangingDay()) {
        my $sfile = $path."/".$self->{history_filename};
        if (! open(my $FILE, '>>', $sfile)) {
            return '_CANNOTWRITEHISTORYFILE';
        }
        my $cdate = $self->{daemon}->getCurrentDate();
        print $FILE sprintf('%.4u%.2u%.2u' ,$cdate->{'year'},$cdate->{'month'},$cdate->{'day'}).":";
        print $FILE $el_key.":".$self->{daemon}->getElementValueByName($element, 'value')."\n";
        close $FILE;

        if (-f $file) {
            unlink($file);
        }
        return 'STABILIZED';
    }

    my %els = ();
    if ( open(my $FILE, '<', $file)) {
        while (<$FILE>) {
            if (/^([^:\s]+)\s*:\s*(\d+)/) {
                my $key = $1;
                my $val = $2;
                if ($key ne $el_key) {
                    $els{$1} = $2;
                }
            }
        }
        close $FILE;
    }

    if ( open(my $FILE, '>', $file)) {
        flock $FILE, LOCK_EX;
        foreach my $key (keys %els) {
            print $FILE $key.":".$els{$key}."\n";
        }
        print $FILE $el_key.":".$self->{daemon}->getElementValueByName($element, 'value')."\n";
        flock $FILE, LOCK_UN;
        close $FILE;
    }

    return 'STABILIZED';
}

sub getStats($self,$start,$stop,$what,$data)
{
    return 'OK';
}

sub announceMonthChange($self)
{
    return;
}

sub doLog($self,$message,$given_set,$priority='info')
{
    my $msg = $self->{class}." ".$message;
    if ($self->{daemon}) {
        $self->{daemon}->doLog($msg, $given_set, $priority);
    }
}

##
sub getPathFileBaseAndKeyFromElement($self,$element)
{
    my @els = split(/:/, $element);
    my $key = pop @els;

    my $path = $self->{basepath}.'/'.join('/',@els);
    my $file = $path.'/'.$self->{today_filename};
    my $base = join(':', @els);
    return (lc($path), lc($file), lc($base), lc($key));
}
1;
