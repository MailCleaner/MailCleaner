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
#   
## largely inspired and copied from Julian Field's work for the SpamAssassin cache in SA.pm

package MailScanner::SpamCache;

use v5.36;
use strict 'vars';
use strict 'refs';
no  strict 'subs'; # Allow bare words for parameter %'s
#use English; # Needed for $PERL_VERSION to work in all versions of Perl

use POSIX qw(:signal_h); # For Solaris 9 SIG bug workaround
use Compress::Zlib;

my %conf;
my $cachedbh;

our $SpamCacheLife   = 5*60;     # Lifetime of low-scoring spam from first seen
our $ExpireFrequency = 10*60;    # How often to run the expiry of the cache

my $NextCacheExpire;

sub initialise
{
    MailScanner::Log::InfoLog("Initializing SpamCache...");

    %SpamCache::conf = (
        cache_useable => 0
    );

    if (!MailScanner::Config::IsSimpleValue('usespamcache') || !MailScanner::Config::Value('usespamcache')) {
        MailScanner::Log::WarnLog("SpamCache disable by config");
        return 1;
    }

    if (! eval "require DBD::SQLite") {
        MailScanner::Log::WarnLog("WARNING: You are trying to use the SpamAssassin cache but your DBI and/or DBD::SQLite Perl modules are not properly installed!");
        return 1;
    }

    if (! eval "require Digest::MD5") {
        MailScanner::Log::WarnLog("WARNING: You are trying to use the SpamAssassin cache but your Digest::MD5 Perl module is not properly installed!");
        return 1;
    }
    ## init db
    my $spamcachepath = MailScanner::Config::Value('spamcachedatabasefile');

    ## connect to db
    $MailScanner::SpamCache::cachedbh = DBI->connect("dbi:SQLite:$spamcachepath","","",{PrintError=>0,InactiveDestroy=>1});
    if ( !$MailScanner::SpamCache::cachedbh ) {
        MailScanner::Log::WarnLog("WARNING: Could not connect or create database at: $spamcachepath");
        return 1;
    }

    ## create structure (silent when already created)
    $MailScanner::SpamCache::cachedbh->do("CREATE TABLE cache (md5 TEXT, count INTEGER, last TIMESTAMP, first TIMESTAMP, spamreport BLOB, virusinfected INT)");
    $MailScanner::SpamCache::cachedbh->do("CREATE UNIQUE INDEX md5_uniq ON cache(md5)");
    $MailScanner::SpamCache::cachedbh->do("CREATE INDEX last_seen_idx ON cache(last)");
    $MailScanner::SpamCache::cachedbh->do("CREATE INDEX first_seen_idx ON cache(first)");

    MailScanner::Log::InfoLog("Using spam results cache in: $spamcachepath");
    $SpamCache::conf{cache_useable} = 1;

    SetCacheTimes();
    CacheExpire();

    return 1;
}

## called per message ##
sub isUseable
{
    return $SpamCache::conf{cache_useable};
}

sub CheckCache($md5)
{
    return if (!$SpamCache::conf{cache_useable});
    return unless (defined($md5));

    my($sql, $sth);
    $sql = "SELECT md5, count, last, first, spamreport FROM cache WHERE md5=?";
    my $hash = $MailScanner::SpamCache::cachedbh->selectrow_hashref($sql,undef,$md5);

    if (defined($hash)) {
        # Cache hit!
        #print STDERR "Cache hit $hash!\n";
        # Update the counter and timestamp
        $sql = "UPDATE cache SET count=count+1, last=strftime('%s','now') WHERE md5=?";
        $sth = $MailScanner::SpamCache::cachedbh->prepare($sql);
        $sth->execute($md5);
        return $hash;
    } else {
        # Cache miss... we'll create the cache record later.
        #print STDERR "Cache miss!\n";
        return undef;
    }
}

sub CacheResult
{
    my ($md5, $spamreport) = @_;

    return if (!$SpamCache::conf{cache_useable});

    my $dbh = $MailScanner::SpamCache::cachedbh;

    my $sql = "INSERT INTO cache (md5, count, last, first, spamreport) VALUES (?,?,?,?,?)";
    my $sth = $dbh->prepare($sql);
    #print STDERR "$sth, $@\n";
    my $now = time;
    $sth->execute($md5,1,$now,$now, $spamreport);
}

## called per batch ##
sub CheckForCacheExpire($self)
{
    return if (!$SpamCache::conf{cache_useable});

    CacheExpire() if $NextCacheExpire<=time;
}

sub AddVirusStats($message)
{
    return unless $message;

    return if (!$SpamCache::conf{cache_useable});

    my $sth = $MailScanner::SpamCache::cachedbh->prepare(
        'UPDATE cache SET virusinfected=? WHERE md5=?');
        $sth->execute($message->{virusinfected},
        $message->{md5}
    ) or MailScanner::Log::WarnLog($DBI::errstr);
}

## Internal calls
# Set all the cache expiry timings from the cachetiming conf option
sub SetCacheTimes
{
    my $line = MailScanner::Config::Value('spamcachetiming') || return;
    $line =~ s/^\D+//;
    return unless $line;
    my @numbers = split /\D+/, $line;
    return unless @numbers;

    $SpamCacheLife   = $numbers[0] if $numbers[0];
    $ExpireFrequency = $numbers[1] if $numbers[1];
}

# Expire records from the cache database
sub CacheExpire($expire1=$SpamCacheLife)
{
    return if (! MailScanner::SpamCache::isUseable());

    my $sth = $MailScanner::SpamCache::cachedbh->prepare(
        "DELETE FROM cache WHERE (first<=(strftime('%s','now')-?))"
    );
    MailScanner::Log::DieLog("Database complained about this: %s. I suggest you delete your %s file and let me re-create it for you", $DBI::errstr, MailScanner::Config::Value("spamcache")) unless $sth;
    my $rows = $sth->execute($expire1);
    $sth->finish;

    MailScanner::Log::InfoLog("Expired %s records from the spam cache", $rows) if $rows>0;

    # This is when we should do our next cache expiry (20 minutes from now)
    $NextCacheExpire = time + $ExpireFrequency;
}

1;
