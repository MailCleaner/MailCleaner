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

package SNMPAgent;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;
use DB;
use NetSNMP::agent;
use NetSNMP::OID (':all');
use NetSNMP::agent (':all');
use NetSNMP::ASN (':all');
use ReadConfig;

our @ISA = qw(Exporter);
our @EXPORT = qw(getInstance new getOption);
our $VERSION = 1.0;

my $rootOID = ".1.3.6.1.4.1.36661";

my $logfile = '/tmp/snmpd.debug';
my %log_prio_levels = ( 'error' => 0, 'info' => 1, 'debug' => 2 );
my $log_sets = 'all';
my $log_priority = 'info';
my @logged_sets;
my $syslog_progname = '';
my $syslog_facility = '';
our $LOGGERLOG;

my %mib = ();

sub init
{
    doLog('MailCleaner SNMP Agent Initializing...', 'daemon', 'debug');

    my $conf = ReadConfig::getInstance();
    my $agents_dir = $conf->getOption('SRCDIR')."/lib/SNMPAgent/";

    my $dh;
    if (! opendir($dh, $agents_dir)) {
        doLog('No valid agents directory : '.$agents_dir, 'daemon', 'error');
        return 0;
    }
    my @agents;
    while (my $dir = readdir $dh) {
        if ($dir =~ m/^([A-Z]\S+).pm$/) {
            push @agents, $1;
        }
    }
    closedir $dh;

    foreach my $agent (@agents) {
        my $agent_class = 'SNMPAgent::'.ucfirst($agent);

        if (! eval "require $agent_class") {
            die('Agent type does not exists: '.$agent_class." $!");
        }
        my $position = $agent_class->initAgent();
        $mib{$position} = $agent_class->getMIB();
    }

    my $agent = NetSNMP::agent->new(
        'dont_init_agent' => 1,
        'dont_init_lib' => 1
    );

    my $regoid = NetSNMP::OID->new($rootOID);
    $agent->register("MailCleaner SNMP agent", $regoid, \&SNMPHandler);

    doLog('MailCleaner SNMP Agent Initialized.', 'daemon', 'debug');
}

sub SNMPHandler
{
    my ($handler, $registration_info, $request_info, $requests) = @_;

    for (my $request = $requests; $request; $request = $request->next()) {

        my $oid = $request->getOID();
        if ($request_info->getMode() == MODE_GET) {
            doLog("GET : $oid", 'daemon', 'debug');
            my $value_call = getValueForOID($oid);
            if (defined($value_call)) {
                my ($type, $value) = $value_call->($oid);
                doLog("type: $type => $value", 'oid', 'debug');
                $request->setValue($type, $value);
            }
        }
        if ($request_info->getMode() == MODE_GETNEXT) {
            doLog("GETNEXT : $oid", 'daemon', 'debug');

            my $nextoid = getNextForOID($oid);
            if (defined($nextoid)) {
                my $value_call = getValueForOID(NetSNMP::OID->new($nextoid));
                if (defined($value_call)) {
                    my ($type, $value) = $value_call->(NetSNMP::OID->new($nextoid));
                    doLog("type: $type => $value", 'oid', 'debug');
                    $request->setOID($nextoid);
                    $request->setValue($type, $value);
                }
            }
        }
    }
}

sub getValueForOID($oid)
{
    my $el = getOIDElement($oid);
    if (ref($el) eq 'CODE') {
        return $el;
    }
}

sub getOIDElement($oid)
{
    return if (!defined($oid));
    doLog("Getting element for oid : $oid", 'oid', 'debug');
    my @oid = $oid->to_array();
    my $regoid = NetSNMP::OID->new($rootOID);
    my @rootoid = $regoid->to_array();

    my @local_oid = splice(@oid, @rootoid);

    my $branch = \%mib;
    foreach my $b (@local_oid) {
        if (ref($branch) eq 'HASH') {
            if (defined($branch->{$b})) {
                $branch = $branch->{$b};
            } else {
                return;
            }
        } else {
            return;
        }
    }
    return $branch;
}

sub getNextForOID($oid,$nextbranch)
{
    return if (NetSNMP::OID->new($oid) < NetSNMP::OID->new($rootOID));
    my $el = getOIDElement(NetSNMP::OID->new($oid));
    if (defined($el) && ref($el) eq 'HASH' && (!defined($nextbranch) || !$nextbranch)) {
        # searching inside
        doLog("is HASH, looking inside $oid", 'oid', 'debug');
        return $oid.".".getNextElementInBranch($el);
    } else {
        # look into current branch for next
        my $oido = NetSNMP::OID->new($oid);
        my @oida = $oido->to_array();
        my $pos = pop(@oida);
        $oid = join('.', @oida);
        my $branch = getOIDElement(NetSNMP::OID->new($oid));
        #foreach my $selpos (sort(keys(%{$branch}))) {
        foreach my $selpos ( sort { $a <=> $b} keys %{$branch} ) {
            if ($selpos > $pos) {
                doLog("Got a higer element at pos $oid.$selpos", 'oid', 'debug');
                my $sel = getOIDElement(NetSNMP::OID->new("$oid.$selpos"));
                if (ref($sel) eq 'CODE') {
                    return "$oid.$selpos";
                }
                if (ref($sel) eq 'HASH') {
                    my $tpos = getNextElementInBranch($sel);
                    if (defined($tpos)) {
                        return $oid.".".$selpos.".".$tpos;
                    }
                    return;
                }
            }
        }
        # if nobody, pop to higer level
        if ($oid ne '') {
            doLog('got to jump higher of '.$oid, 'oid', 'debug');
            return getNextForOID($oid, 1);
        }
        return;
    }
}

sub getNextElementInBranch($branch)
{
    if ( ref($branch) ne 'HASH') {
        return;
    }
 #     foreach my $e (sort(keys %{$branch})) {
    foreach my $e ( sort { $a <=> $b} keys %{$branch} ) {
        if (ref($branch->{$e}) eq 'CODE') {
            return $e;
        }
        if (ref($branch->{$e}) eq 'HASH') {
            return $e.".".getNextElementInBranch($branch->{$e});
        }
    }
    return;
}
##### Log management

## add log_sets
foreach my $set ( split( /,/, $log_sets ) ) {
    push @logged_sets, $set;
}
my $log_prio_level = $log_prio_levels{ $log_priority };

sub doLog($message,$given_set,$priority='info')
{
    foreach my $set ( @logged_sets  ) {
        if ( $set eq 'all' || !defined($given_set) || $set eq $given_set ) {
            if ( $log_prio_levels{$priority} <= $log_prio_level ) {
                doEffectiveLog($message);
            }
            last;
        }
    }
}

sub doEffectiveLog($message)
{
    foreach my $line ( split( /\n/, $message ) ) {
        if ( $logfile ne '' ) {
            writeLogToFile($line);
        }
        if ( $syslog_facility ne '' && $syslog_progname ne '' ) {
            syslog( 'info', $line );
        }
    }
}

sub writeLogToFile($message)
{
    chomp($message);
    return if ( $logfile eq '' );

    my $LOCK_SH = 1;
    my $LOCK_EX = 2;
    my $LOCK_NB = 4;
    my $LOCK_UN = 8;
    $| = 1;

    if ( !defined($LOGGERLOG) || !fileno($LOGGERLOG) ) {
        openLog();
        open($LOGGERLOG, ">>", $logfile);
        if ( !defined( fileno($LOGGERLOG) ) ) {
            open($LOGGERLOG, ">>", "/tmp/" . $logfile);
            $| = 1;
        }
        doLog( 'Log file has been opened, hello !', 'daemon' );
    }
    my ( $sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst ) = localtime(time);
    $mon++;
    $year += 1900;
    my $date = sprintf( "%d-%.2d-%.2d %.2d:%.2d:%.2d", $year, $mon, $mday, $hour, $min, $sec );
    flock( $LOGGERLOG, $LOCK_EX );
    print $LOGGERLOG "$date " . $message . "\n";
    flock( $LOGGERLOG, $LOCK_UN );
}

sub openLog()
{
    if (!defined($logfile) || $logfile eq '') {
        print STDERR "SNMP does not have a configured log file\n";
        $LOGGERLOG = &STDERR();
    } else {
        unless (open($LOGGERLOG, '>>', $logfile)) {
            print STDERR "Unable to open $logfile for writing.\n";
            my @path = split(/\//, $logfile);
            shift(@path);
            my $file = pop(@path);
            my $d = '/tmp';
            foreach my $dir (@path) {
                $d .= "/$dir";
                unless (-d $d) {
                    unless (mkdir($d)) {
                        print STDERR "Cannot create $d: $!\n";
                        $d = '/tmp';
                        last;
                    }
                }
            }
            $logfile = $d.'/'.$file;
            open $LOGGERLOG, '>>', $logfile;
            print STDERR "Logging to $logfile\n";
        }
        $| = 1;
    }
    print $LOGGERLOG "Log file has been opened, hello !\n";
}

sub closeLog()
{
    doLog( 'Closing log file now.', 'daemon' );
    close $LOGGERLOG;
    exit;
}

1;
