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

package PrefDaemon;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;
require ReadConfig;
require DB;
use POSIX;
use Sys::Hostname;
use Socket;
use Symbol;
use IO::Socket::INET;
require Mail::SpamAssassin::Timeout;

our @ISA = qw(Exporter);
our @EXPORT = qw(create getPref);
our $VERSION = 1.0;
our $LOGGERLOG;

sub create
{
    my $conf = ReadConfig::getInstance();
    my $configfile = $conf->getOption('SRCDIR')."/etc/exim/prefDaemon.conf";

    ## default values
    my $pidfile = $conf->getOption('VARDIR')."/run/prefdaemon.pid";
    my $port = 4352;
    my $logfile = $conf->getOption('VARDIR')."/log/mailcleaner/prefdaemom.log";
    my $daemontimeout = 1200;
    my $clientTimeout = 5;
    my $sockettimeout = 120;
    my $listenmax = 100;
    my $prefork = 5;
    my $debug = 1;
    my $cachesystem = 600;
    my $cachedomain = 120;
    my $cacheuser = 30;
    my $cleancache = 3600;
    my $slaveDB;
    my %childrens;
    my %cache;
    my %wwusercache;
    my %wwdomaincache;
    my %wwglobalcache;
    my $wwusercachemax = 100000;
    my $wwdomaincachemax = 10000;
    my $wwglobalcachemax = 1000;
    my $wwcachepos = 120;
    my $wwcacheneg = 120;

    my $self = {
        port => $port,
        server => '',
        pidfile => $pidfile,
        logfile => $logfile,
        daemontimeout => $daemontimeout,
        clienttimeout => $clientTimeout,
        sockettimeout => $sockettimeout,
        listenmax => $listenmax,
        debug => $debug,
        prefork => $prefork,
        children => 0,
        basefork => 0,
        inexit => 0,
        cachesystem => $cachesystem,
        cachedomain    => $cachedomain,
        cacheuser => $cacheuser,
        cleancache => $cleancache,
        lastcleanup => 0,
        slaveDB => $slaveDB,
        %childrens => (),
        %cache => (),
        time_to_die => 0,
        %wwusercache => (),
        %wwdomaincache => (),
        %wwglobalcache => (),
        wwusercachemax => $wwusercachemax,
        wwdomaincachemax => $wwdomaincachemax,
        wwglobalcachemax => $wwglobalcachemax,
        wwcachepos => $wwcachepos,
        wwcacheneg => $wwcacheneg
    };

    # replace with configuration file values
    if (open(my $CONFFILE, '<', $configfile)) {
        while (<$CONFFILE>) {
            chomp;
            next if /^\#/;
            if (/^(\S+)\ ?\=\ ?(.*)$/) {
                if (defined($self->{$1})) {
                    $self->{$1} = $2;
                }
            }
        }
        close $CONFFILE;
    }

    bless $self, "PrefDaemon";
    return $self;
}

sub logMessage($self,$message)
{
    if ($self->{debug}) {
        if ( !defined(fileno($LOGGERLOG))) {
             open($LOGGERLOG, '>>', "/tmp/".$self->{logfile});
             $| = 1;
        }
        my $date=`date "+%Y-%m-%d %H:%M:%S"`;
        chomp($date);
        print $LOGGERLOG "$date: $message\n";
    }
}

######
## startDaemon
######
sub startDaemon($self)
{
    open($LOGGERLOG, '>>', $self->{logfile});

    my $pid = fork();
    if (!defined($pid)) {
        die "Couldn't fork: $!";
    }
    if ($pid) {
        exit;
    } else {
        # Dameonize
        POSIX::setsid();

        $self->logMessage("Starting Daemon");

        $SIG{INT} = $SIG{TERM} = $SIG{HUP} = $SIG{ALRM} = sub { $self->parentGotSignal(); };


        #alarm $self->{daemontimeout};
        $0 = "PrefDaemon";
        $self->initDaemon();
        $self->launchChilds();
        until ($self->{time_to_die}) {
        };
    }
    exit;
}

sub parentGotSignal($self)
{
    $self->{time_to_die} = 1;
}


sub reaper($self)
{
    $self->logMessage("Got child death...");
    $SIG{CHLD} = sub { $self->reaper(); };
    my $pid = wait;
    $self->{children}--;
    delete $self->{childrens}{$pid};
    if ($self->{time_to_die} < 1 ) {
        $self->logMessage("Not yet death.. relauching new child");
        $self->makeChild();
    }
}

sub huntsMan($self)
{
    local($SIG{CHLD}) = 'IGNORE';
    $self->{time_to_die} = 1;
    $self->logMessage("Shutting down childs");
    kill 'INT' => keys %{$self->{childrens}};
    $self->logMessage("Daemon shut down");
    exit;
}

sub initDaemon($self)
{
     $self->logMessage("Initializing Daemon");
     $self->{server} = IO::Socket::INET->new(
        LocalAddr => '127.0.0.1',
        LocalPort => $self->{port},
        Proto         => 'udp'
    ) or die "Couldn't be an udp server on port ".$self->{port}." : $@\n";

    $self->logMessage("Listening on port ".$self->{port});

    return 0;
}

sub launchChilds($self)
{
    for (1 .. $self->{prefork}) {
        $self->logMessage("Launching child ".$self->{children}." on ".$self->{prefork}."...");
        $self->makeChild();
    }
    # Install signal handlers
    $SIG{CHLD} = sub { $self->reaper(); };
    $SIG{INT} = sub { $self->huntsMan(); };

    while (1) {
        sleep;
        $self->logMessage("Child death... still: ".$self->{children});
        for (my $i = $self->{children}; $i < $self->{prefork}; $i++) {
            $self->makeChild();
        }
    }
}

sub makeChild($self)
{
    my $pid;
    my $sigset;

    # block signal for fork
    $sigset = POSIX::SigSet->new(SIGINT);
    sigprocmask(SIG_BLOCK, $sigset) or die "Can't block SIGINT for fork: $!\n";

    die "fork: $!" unless defined ($pid = fork);

    if ($pid) {
        # Parent records the child's birth and returns.
        sigprocmask(SIG_UNBLOCK, $sigset) or die "Can't unblock SIGINT for fork: $!\n";
        $self->{childrens}{$pid} = 1;
        $self->{children}++;
        $self->logMessage("Child created with pid: $pid");
        return;
    } else {
        # Child can *not* return from this subroutine.
        $SIG{INT} = 'DEFAULT';

        # unblock signals
        sigprocmask(SIG_UNBLOCK, $sigset) or die "Can't unblock SIGINT for fork: $!\n";

        $self->connectDB();

        $self->logMessage("In child listening...");
        $self->listenForQuery();
        exit;
    }
}

sub connectDB($self)
{
    $self->{slaveDB} = DB::connect('slave', 'mc_config', 0);
    if ($self->{slaveDB}->ping()) {
        $self->logMessage("Connected to configuration database");
        return 1;
    }
    $self->logMessage("WARNING, could not connect to configuration database");
    return 0;
}

sub listenForQuery($self)
{
    my $message;
    my $serv = $self->{server};
    my $MAXLEN = 1024;

    $self->{lastcleanup} = time();
    my $datas;
    while (my $cli = $serv->recv($datas, $MAXLEN)) {
        my($cli_add, $cli_port) =    sockaddr_in($serv->peername);
        $self->manageClient($cli, $cli_port, $datas);
        my $deltaclean = time() - $self->{lastcleanup};
        if ($deltaclean > $self->{cleancache}) {
            $self->logMessage("Global cache cleanup requested.. ($deltaclean)");
            delete($self->{cache});
            $self->{lastcleanup} = time();
        }
    }
}

sub manageClient($self,$cli,$cli_add,$datas)
{
    alarm $self->{daemontimeout};

    $self->logMessage("Accepting connection");
    if ($datas =~ /^EXIT/) {
        $self->logMessage("Received EXIT command");
        $self->huntsMan();
        exit;
    }
    my $query .= $datas;
    if ($query =~ /^HELO\ (\S+)/) {
        $self->{server}->send("NICE TO MEET YOU: $1\n");
        $self->logMessage("Command HELO answered");
    } elsif ($query =~ /^NULL/) {
        $self->{server}->send("\n");
        $self->logMessage("Command NULL answered");
    } elsif ($query =~ /^PREF\ (\S+)\ (\S+)/) {
        ## now fetch the value and return it as fast as possible
        $self->logMessage("Command: pref $2 requested for $1");
        $self->{server}->send($self->fetchPref($1, $2)."\n");
    } elsif ($query =~ /^(WHITE|WARN|BLACK)LIST (\S+)\ (\S+)/) {
        if ($1 eq "WHITE") {
            $self->logMessage("Command: whitelist query: from $3 to $2");
            $self->{server}->send($self->fetchWW('white', $2, $3)."\n");
        } elsif ($1 eq "WARN") {
            $self->logMessage("Command: warnlist query: from $3 to $2");
            $self->{server}->send($self->fetchWW('warn', $2, $3)."\n");
        } elsif ($1 eq "BLACK") {
            $self->logMessage("Command: blacklist query: from $3 to $2");
            $self->{server}->send($self->fetchWW('black', $2, $3)."\n");
        }
    } else {
        $self->logMessage("BAD command found: $query");
        $self->{server}->send("BAD COMMAND\n");
    }
}

sub fetchPref($self,$who,$what)
{
    my $cachevalue = $self->getCacheValue($who, $what);
    if ( $cachevalue !~ /^NOCACHE/) {
        $self->logMessage("Using cached value for: $who / $what");
        if ($cachevalue eq 'NOTSET') {
            return 'NOTFOUND';
        }
        return $cachevalue;
    }

    my $res = "BADPREF";
    if ($who =~ /^\S+\@\S+$/) {
        $self->logMessage("Fetching preference for user: $who");
        $res = $self->fetchAddressPref($who, $what);
    } elsif ($who =~ /^\@(\S+)$/) {
        $self->logMessage("Fetching preference for domain: $1");
        $res = $self->fetchDomainPref($1, $what);
    } elsif ($who eq 'global') {
        $self->logMessage("Fetching global preference");
        $res = $self->fetchGlobalPref($what);
    }
    if ($res eq "BADPREF") {
        $self->logMessage("BAD preference who value: $who");
    } elsif ($res eq "NOTFOUND" || $res eq "NOTSET") {
        return 'NOTFOUND';
    } else {
        $self->setCache($who, $what, $res);
    }
    return $res;
}

sub fetchWW($self,$type,$dest,$sender)
{
    my $atype = 1; # address
    if ($dest =~ /^\@(\S+)$/) {
        $atype = 2; # domain
    }
    if ($dest eq 'global' || $dest eq '' || $dest eq '_') {
        $atype = 3; # global
    }
    my $cachevalue = $self->getWWCacheValues($type, $dest, $sender, $atype);
    if ( $cachevalue !~ /^NOCACHE/) {
        $self->logMessage("Using cached value for: $type / $dest - $sender");
        return $cachevalue;
    }

    return $self->fetchDatabaseWW($type, $dest, $sender, $atype);
}

sub fetchDatabaseWW($self,$type,$recipient,$sender,$atype)
{
    if (!$self->{slaveDB}->ping()) {
        if (!$self->connectDB()) {
            return 'NOTFOUND';
        }
    }
    if ($recipient eq "_") {
        $recipient = "";
    }
    $sender =~ s/[\/\'\"\<\>\:\;\?\*\%\&\+]//g;
    $recipient =~ s/[\/\'\"\<\>\:\;\?\*\%\&\+]//g;
    my $query = "SELECT sender FROM wwlists WHERE type='$type' AND ( recipient='$recipient' OR recipient='' ) AND status=1";
    #$self->logMessage("Using query: $query");
    my @entries = $self->{slaveDB}->getListOfHash($query);
    foreach my $entry (@entries) {
        my %entryv = %$entry;
        my $test = $entryv{sender};
        $test =~ s/(\.)?\*/\\S+/g;
        $test =~ s/[\/\'\"\<\>\:\;\?\*\%\&\+]//g;
        if ($sender =~ /$test/i) {
            $self->setWWCache($type, $recipient, $sender, $atype, 'FOUND');
            return "FOUND";
        }
    }
    $self->setWWCache($type, $recipient, $sender, $atype, 'NOTFOUND');
    return "NOTFOUND";
}

sub fetchAddressPref($self,$who,$what)
{
    if (!$self->{slaveDB}->ping()) {
        if (!$self->connectDB()) {
            return 'NOTFOUND';
        }
    }

    my $query = "SELECT $what FROM user_pref p, email e WHERE p.id=e.pref AND e.address='$who'";
    my %res = $self->{slaveDB}->getHashRow($query);
    if (defined($res{$what})) {
        return $res{$what};
    }
    return "NOTFOUND";
}

sub fetchDomainPref($self,$who,$what)
{
    if (!$self->{slaveDB}->ping()) {
        if (!$self->connectDB()) {
            return 'NOTFOUND';
        }
    }

    if ($what eq 'has_whitelist') {
        $what = 'enable_whitelists';
    }
    if ($what eq 'has_warnlist') {
        $what = 'enable_warnlists';
    }
    if ($what eq 'has_blacklist') {
        $what = 'enable_blacklists';
    }
    ## check this domain
    my $query = "SELECT $what FROM domain_pref p, domain d WHERE p.id=d.prefs AND d.name='$who'";
    my %res = $self->{slaveDB}->getHashRow($query);
    if (defined($res{$what})) {
        return $res{$what};
    }
    ## check for jocker domain
    $query = "SELECT $what FROM domain_pref p, domain d WHERE p.id=d.prefs AND d.name='*'";
    %res = $self->{slaveDB}->getHashRow($query);
    if (defined($res{$what})) {
        return $res{$what};
    }
    ## check for default domain
    my $dd = $self->fetchGlobalPref('default_domain');
    $query = "SELECT $what FROM domain_pref p, domain d WHERE p.id=d.prefs AND d.name='$dd'";
    %res = $self->{slaveDB}->getHashRow($query);
    if (defined($res{$what})) {
        return $res{$what};
    }
    return "NOTFOUND";
}

sub fetchGlobalPref($self,$what)
{
    if (!$self->{slaveDB}->ping()) {
        if (!$self->connectDB()) {
            return 'NOTFOUND';
        }
    }
    my $query = "SELECT $what FROM system_conf, antispam, antivirus, httpd_config";
    my %res = $self->{slaveDB}->getHashRow($query);
    if (defined($res{$what})) {
        return $res{$what};
    }
    return "NOTFOUND";
}


####################
## cache management

sub getCacheValue($self,$who,$what)
{
    my $timeout = $self->{cacheuser};
    if ($who =~ /^\@/) {
        $timeout = $self->{cachedomain};
    } elsif ($who eq 'global') {
        $timeout = $self->{cachesystem};
    }
    return "NOCACHE" if $timeout < 1;

    my $cachekey = getCacheKey($who, $what);

    if ( defined($self->{cache}{$cachekey}) && $self->{cache}{$cachekey} =~ /^(\d+)\-(.*)/) {
        $self->logMessage("Cache key hit for: $cachekey ($1, $2)");
        my $deltatime = time() - $1;
        if ($deltatime < $timeout) {
            return $2 ;
        }
        $self->logMessage("Cache key too old: $deltatime s.");
    }
    return "NOCACHE";
}


sub setCache($self,$who,$what,$value)
{
    my $timeout = $self->{cacheuser};
    if ($who =~ /^\@/) {
        $timeout = $self->{cachedomain};
    } elsif ($who eq 'global') {
            $timeout = $self->{cachesystem};
    }
    return if ($timeout < 1);

    my $cachekey = getCacheKey($who, $what);

    my $time = time();
    $self->{cache}{$cachekey} = $time."-".$value;
    $self->logMessage("Saved cache for: $cachekey");
}

sub dumpCache($self)
{
    foreach my $key (keys %{$self->{cache}}) {
        $self->logMessage(" === cache key: $key     /    ".$self->{cache}{$key}[1]);
    }
}

sub getCacheKey($who,$what)
{
    return $who."/".$what;
#    use Digest::MD5 qw(md5_hex);
#    return md5_hex($who."/".$what);
}

sub getWWCacheValues($self,$type,$dest,$sender,$atype)
{
    ## purging caches
    my $cachehash = \%{$self->{wwusercache}};
    if ($atype == 2) {
        if (keys(%{$self->{wwdomaincache}}) > $self->{wwdomaincachemax}) {
            unset ($self->{wwdomaincache});
            $self->logMessage(" Purged ww domain cache, maximum of ".$self->{wwdomaincachemax}." entries reached");
            return "NOCACHE";
        }
        $cachehash = \%{$self->{wwdomaincache}};
    } elsif ($atype == 3) {
         if (keys(%{$self->{wwglobalcache}}) > $self->{wwglobalcachemax}) {
            unset ($self->{wwglobalcache});
            $self->logMessage(" Purged ww global cache, maximum of ".$self->{wwglobalcachemax}." entries reached");
            return "NOCACHE";
         }
         $cachehash = \%{$self->{wwglobalcache}};
    }
    if (keys(%{$self->{wwusercache}}) > $self->{wwusercachemax}) {
        unset ($self->{wwusercache});
        $self->logMessage(" Purged ww user cache, maximum of ".$self->{wwusercachemax}." entries reached");
        return "NOCACHE";
    }

    my $cachekey = getWWCacheKey($type, $dest, $sender);

    if ( defined($cachehash->{$cachekey}) && $cachehash->{$cachekey} =~ /^(\d+)\-(.*)/) {
        $self->logMessage("WW Cache key hit for: $cachekey ($1, $2)");
        my $deltatime = time() - $1;
        my $result = $2;
        if ($result eq "FOUND" && $deltatime > $self->{wwcachepos}) {
            $self->logMessage("WW positive Cache key too old: $deltatime s.");
            return "NOCACHE";
        }
        if ($result eq "NOTFOUND" && $deltatime > $self->{wwcacheneg}) {
            $self->logMessage("WW negative Cache key too old: $deltatime s.");
            return "NOCACHE";
        }
        if ($result =~ /^(NOT)?FOUND$/) {
            return $result;
        }
        $self->logMessage(" Illegal WW cache value: $2");
        return "NOCACHE";
    }

    return "NOCACHE";
}

sub setWWCache($self,$type,$dest,$sender,$atype,$value)
{
    return if ($value eq "FOUND" && $self->{wwcachepos} < 1);
    return if ($value eq "NOTFOUND" && $self->{wwcacheneg} < 1);
    return if (! $value eq "FOUND" && ! $value eq "NOTFOUND");

    my $cachekey = getWWCacheKey($type, $dest, $sender);

    my $cachehash = \%{$self->{wwusercache}};
    if ($atype == 2) {
        $cachehash = \%{$self->{wwdomaincache}};
    } elsif ($atype == 3) {
        $cachehash = \%{$self->{wwglobalcache}};
    }

    my $time = time();
    $cachehash->{$cachekey} = $time."-".$value;
    $self->logMessage("Saved WW cache for: $cachekey");
}

sub getWWCacheKey($type,$dest,$sender)
{
    return $type."/".$dest."/".$sender;
}

###########################
## client call

sub getPref($self,$type,$pref)
{
    my $res = "NOTFOUND";
    my $t = Mail::SpamAssassin::Timeout->new({ secs => $self->{clienttimeout} });
    $t->run_and_catch( sub { $res = $self->queryDaemon($type, $pref);});

    if ($t->timed_out()) { return "TIMEDOUT"; };

    if (defined($res)) {
        return $res;
    }
    return 'NOTFOUND';
}

sub queryDaemon($self,$type,$pref)
{
    my $socket;
    if ( $socket = IO::Socket::INET->new(
        PeerAddr => '127.0.0.1',
        PeerPort => $self->{port},
        Proto        => "udp"
    )) {

        $socket->send($type." ".$pref."\n");
        my $MAXLEN    = 1024;
        my $response;

        $! = 0;

        $socket->recv($response, $MAXLEN);
        if ($! !~ /^$/) {
            return "NODAEMON";
        }
        my $res = $response;
        chomp($res);
        return $res;
    }
    return "NODAEMON";
}

sub timedOut($self)
{
    exit();
}

1;
