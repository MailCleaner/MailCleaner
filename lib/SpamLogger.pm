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
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

package SpamLogger;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;
require ReadConfig;
require UDPDaemon;
require DB;
use POSIX;
use Sys::Hostname;
use Socket;
use Storable(qw[freeze thaw]);
use MIME::Base64;

our @ISA = "UDPDaemon";
my @fields = ('id', 'tolocal', 'todomain', 'sender', 'subject', 'score', 'rbls', 'prefilter', 'globalscore');

sub new($class,$name,$file)
{
    my $self = $class->SUPER::create($name, $file);

    my $conf = ReadConfig::getInstance();
    $self->{slaveID} = $conf->getOption('HOSTID');
    bless $self, $class;
    return $self;
}


sub processDatas($self,$datas)
{
    if ($datas =~ /^LOG (.*)/) {
        my $tmp = $1;
        my @gotfields = split "_", $tmp;
        my %msg;
        my $i = 0;
        foreach my $field (@fields) {
            if (defined($gotfields[$i])) {
                $msg{$field} = decode_base64($gotfields[$i]);
                # some cleanup
                $msg{$field} =~ s/\\//g;
                $msg{$field} =~ s/'/\\'/g;
                $i++;
            } else {
                $msg{$field} = "";
            }
        }

        if (!defined($msg{id})) {
            $self->logMessage("WARNING ! no id found for message ($tmp)");
            next;
        }
        my $logged_in_master = $self->logInMaster(\%msg);
        if (!$logged_in_master) {
            $self->logMessage("Message ".$msg{id}." cannot be logged in master DB !");
        }
        my $logged_in_slave = $self->logInSlave(\%msg, $logged_in_master);
        if (!$logged_in_slave) {
            $self->logMessage("Message ".$msg{id}." cannot be logged in slave DB !");
        }

        if ($logged_in_master && $logged_in_slave) {
            $self->logMessage("Message ".$msg{id}." logged both");
            return "LOGGED BOTH";
        }
        return "LOGGED $logged_in_slave $logged_in_master";
    }
    return "UNKNOWN COMMAND";
}

#####
## logSpam
#####
sub logSpam($self, @fields)
{
    my %msg;

    my $query = "LOG";
    my $params = "";
    foreach my $field (@fields) {
        $msg{$field} = $_;
        if (defined($msg{$field})) {
            my $value = encode_base64($msg{$field});
            chomp($value);
            $params .= "_".$value;
        }
    }
    $params =~ s/^_//;
    $params =~ s/^ //;
    $query .= " ".$params;
    $query =~ s/\n//g;
    my $res = $self->exec($query);
    return $res;
}

#####
## logInMaster
#####
sub logInMaster($self,$msg_h)
{
    my %message = %$msg_h;

    if (!defined($self->{masterDB}) || !$self->{masterDB}->ping()) {
        $self->{masterDB} = DB::connect('realmaster', 'mc_spool', 0);
        if ( !defined($self->{masterDB}) || !$self->{masterDB}->ping()) { return 0; }
    }

    my $table = "misc";
    if ( $message{tolocal} =~ /^([a-z,A-Z])/ ) {
        $table = lc($1);
    } elsif ( $message{tolocal}    =~ /^[0-9]/ ) {
        $table = 'num';
    } else {
        $table = 'misc';
    }
    my $query = "INSERT IGNORE INTO spam_$table (date_in, time_in, to_domain, to_user, sender, exim_id, M_score, M_rbls, M_prefilter, M_subject, M_globalscore, forced, in_master, store_slave) ".
        "VALUES (NOW(), NOW(), '".$message{todomain}."', '".$message{tolocal}."', ".
        "'".$message{sender}."', '".$message{id}."', ".
        "'".$message{score}."', '".$message{rbls}."', '". $message{prefilter}."', '".$message{subject}."', '".$message{globalscore}."', '0', '0', '".$self->{slaveID}."')";

    if (!$self->{masterDB}->execute($query)) {
        return 0;
    }
    return 1;
}

#####
## logInSlave
#####
sub logInSlave($self,$msg_h,$master_stored)
{
    my %message = %$msg_h;

    if (!defined($self->{slaveDB}) || !$self->{slaveDB}->ping()) {
        $self->{slaveDB} = DB::connect('slave', 'mc_spool', 0);
        if ( !defined($self->{slaveDB}) || !$self->{slaveDB}->ping()) { return 0; }
    }

    my $table = "misc";
    if ( $message{tolocal} =~ /^([a-z,A-Z])/ ) {
        $table = lc($1);
    } elsif ( $message{tolocal}    =~ /^[0-9]/ ) {
        $table = 'num';
    } else {
        $table = 'misc';
    }
    my $query = "INSERT IGNORE INTO spam_$table (date_in, time_in, to_domain, to_user, sender, exim_id, M_score, M_rbls, M_prefilter, M_subject, M_globalscore, forced, in_master, store_slave) ".
        "VALUES (NOW(), NOW(), '".$message{todomain}."', '".$message{tolocal}."', ".
        "'".$message{sender}."', '".$message{id}."', ".
        "'".$message{score}."', '".$message{rbls}."', '". $message{prefilter}."', '".$message{subject}."', '".$message{globalscore}."', '0', '".$master_stored."', '".$self->{slaveID}."')";

    if (!$self->{slaveDB}->execute($query)) {
        return 0;
    }
    return 1;
}

1;
