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

package PrefTDaemon;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;
use Time::HiRes qw(gettimeofday tv_interval);
require ReadConfig;
require DB;
use Digest::MD5 qw(md5_hex);
use threads;
use threads::shared;

require SockTDaemon;

our @ISA = "SockTDaemon";

my $prefs_      = &share( {} );
my $whitelists_ = &share( {} );
my $warnlists_  = &share( {} );
my $blacklists_  = &share( {} );
my %stats_ : shared = (
    'prefqueries'      => 0,
    'prefsubqueries'   => 0,
    'cacheprefhits'    => 0,
    'cacheprefexpired' => 0,
    'prefnotcached'    => 0,
    'cachingprefvalue' => 0,
    'backendprefcall'  => 0,
    'wwqueries'        => 0,
    'wwsubqueries'     => 0,
    'cachewwhits'      => 0,
    'cachewwexpired'   => 0,
    'wwnotcached'      => 0,
    'cachingwwvalue'   => 0,
    'backendwwcall'    => 0
);

sub new($class,$myspec_thish)
{
    my %myspec_this;
    if ($myspec_thish) {
        %myspec_this = %$myspec_thish;
    }

    my $conf = ReadConfig::getInstance();

    my $spec_this = {
        name       => 'PrefTDaemon',
        socketpath => $conf->getOption('VARDIR') . "/run/prefdaemon.sock",
        configfile => $conf->getOption('SRCDIR') . "/etc/mailcleaner/prefdaemon.conf",
        pidfile    => $conf->getOption('VARDIR') . "/run/prefdaemon.pid",
        profile    => 0,
        prefork    => 5,
        clean_thread_exit => 1,
        timeout_pref => 60,
        timeout_ww   => 60,
        backend      => undef,
    };

    # add specific options of child object
    foreach my $sk ( keys %myspec_this ) {
        $spec_this->{$sk} = $myspec_this{$sk};
    }

    my $self = $class->SUPER::new( $spec_this->{'name'}, undef, $spec_this );

    bless $self, $class;
    return $self;
}

sub initThreadHook($self)
{
    $self->doLog('PrefDaemon thread initialization...', 'prefdaemon', 'debug');
    $self->connectBackend();

    return 1;
}

sub connectBackend($self)
{
    return 1 if ( defined( $self->{backend} ) && $self->{backend}->ping() );

    $self->{backend} = DB::connect( 'slave', 'mc_config', 0 );
    if ( $self->{backend}->ping() ) {
        $self->doLog("Connected to configuration database", 'prefdaemon');
        return 1;
    }
    $self->doLog("WARNING, could not connect to configuration database", 'prefdaemon', 'error');
    return 0;
}

sub dataRead($self,$data,$server)
{
    $self->doLog("Received datas: $data", 'prefdaemon', 'debug');
    my $ret = 'NOTHINGDONE';

    ## PREF query
    if ( $data =~ m/^PREF\s+([-_.!\$#=*&\@a-z0-9]+)\s+([-_a-z0-9]+)\s*(R)?/i ) {
        my $object = lc($1);
        my $pref   = $2;

        ## recurse will force us to find the domain pref if it's not defined, or explicitely not set for the user
        ## the recurse is a: "if no pref found, then use domain's default"
        my $recurs = 0;
        if ( defined($3) && $3 eq 'R' ) {
            $recurs = 1;
        }

        my $result = $self->getObjectPreference( $object, $pref, $recurs );
        return $result;
    }

    $self->doLog('BLACKLIST PREFT'.$data, 'prefdaemon', 'debug');

    ## WHITELIST and WARNLSIT query
    if ( $data =~
        m/^(WHITE|WARN|BLACK)\s+([-_.!\/\$+#=*&\@a-z0-9]+)\s+([-_.!\/\$+#=*&a-z0-9]+\@[-_.a-z0-9]+)/i
    ) {
        my $type   = $1;
        my $object = lc($2);
        my $sender = lc($3);

        $self->addStat( 'wwqueries', 1 );

        my $result = "NOTLISTED";

        ## first check if global system allows wwlist
        if ( $type eq 'BLACK' && !$self->getObjectPreference( '_global', 'enable_blacklists' ) ) {
            return $result;
        }
        if ( $type eq 'WHITE' && !$self->getObjectPreference( '_global', 'enable_whitelists' ) ) {
            return $result;
        }
        if ( $type eq 'WARN' && !$self->getObjectPreference( '_global', 'enable_warnlists' ) ) {
            return $result;
        }

        ## then check if domain allows wwlist
        my $domain = PrefTDaemon::getDomain($object);
        if ($domain) {
            if ( $type eq 'BLACK' && !$self->getObjectPreference( $domain, 'enable_blacklists' ) ) {
                return $result;
            }
            if ( $type eq 'WHITE' && !$self->getObjectPreference( $domain, 'enable_whitelists' ) ) {
                return $result;
            }
            if ( $type eq 'WARN' && !$self->getObjectPreference( $domain, 'enable_warnlists' ) ) {
                return $result;
            }
        }

        ## if here, then wwlists are allowed
        $result = $self->getObjectWWList( $type, $object, $sender );

        return $result;
    }

    ## CLEAR command
    if ( $data =~ m/^CLEAR\s+(PREF|BLACK|WHITE|WARN|STATS)\s+([-_.!\$+#=*&\@a-z0-9]+)?/i ) {
        my $what   = $1;
        my $object = lc($2);

        return "NOTCLEARED";
    }

    ## GETINTERNALSTATS command
    if ( $data =~ m/^GETINTERNALSTATS/i ) {
        return $self->logStats();
    }

    return "_UNKNOWNCOMMAND";
}

##################
## utils
sub isGlobal($object)
{
    if ( $object =~ /^_global/ ) {
        return 1;
    }
    return 0;
}

sub isDomain($object)
{
    if ( $object =~ /^[-_.a-z0-9]+$/ ) {
        return 1;
    }
    return 0;
}

sub isEmail($object)
{
    if ( $object =~ /^[-_.!\$#=*&\@'`a-z0-9]+\@[-_.a-z0-9]+$/ ) {
        return 1;
    }
    return 0;
}

sub isUserID($object)
{
    if ( $object =~ /^\*\d+$/ ) {
        return 1;
    }
    return 0;
}

sub getDomain($object)
{
    if ( $object =~ /^[-_.!\$#=*&\@'`a-z0-9]+\@([-_.a-z0-9]+)$/ ) {
        return $1;
    }
    return undef;
}

#######################################################
##  Preferences management

sub getObjectPreference($self,$object,$pref,$recurs=0)
{
    $self->addStat( 'prefqueries', 1 );

    ## first check if value is already being cached
    my $cachedvalue = $self->getObjectCachedPref( $object, $pref );
    if ( $cachedvalue !~ m/^_/ && !(
        $cachedvalue =~ m/^(NOTSET|NOTFOUND)$/
        && ( $recurs || PrefTDaemon::isDomain($object) )
    )) {
        return $cachedvalue;
    }

    my $result = '_BADOBJECT';

    ## if notcached or not defined, fetch pref by type
    if ( PrefTDaemon::isGlobal($object) ) {
        $result = $self->fetchGlobalPref( $object, $pref );
        return $result;
    } elsif ( PrefTDaemon::isDomain($object) ) {
        $result = $cachedvalue;
        if ( $result !~ m/^(NOTSET|NOTFOUND)$/ ) {
            $result = $self->fetchDomainPref( $object, $pref );
        }
        if ( $result =~ m/^_/ || $result =~ m/NOTFOUND/ ) {
            my $dom = '*';
            $cachedvalue = $self->getObjectCachedPref( $dom, $pref );
            if ( $cachedvalue !~ m/^_/ && $cachedvalue !~ m/NOTFOUND/ ) {
                $self->addStat( 'prefqueries', 1 );
                return $cachedvalue;
            }
            $result = $self->fetchDomainPref( $dom, $pref );
        }
        return $result;
    } elsif ( PrefTDaemon::isEmail($object) ) {
        $result = $cachedvalue;
        if ( $result !~ m/^(NOTSET|NOTFOUND)$/ ) {
            $result = $self->fetchEmailPref( $object, $pref );
        }
        if ( $result =~ m/^(NOTSET|NOTFOUND)$/ ) {
            my $dom = PrefTDaemon::getDomain($object);
            $cachedvalue = $self->getObjectCachedPref( $dom, $pref );
            if ( $cachedvalue !~ m/^_/ && $cachedvalue !~ m/NOTFOUND/ ) {
                $self->addStat( 'prefqueries', 1 );
                return $cachedvalue;
            }
            $result = $self->fetchDomainPref( $dom, $pref );
            if ( $result =~ m/^_/ || $result =~ m/NOTFOUND/ ) {
                $dom = '*';
                $cachedvalue = $self->getObjectCachedPref( $dom, $pref );
                if ( $cachedvalue !~ m/^_/ && $cachedvalue !~ m/NOTFOUND/ ) {
                    $self->addStat( 'prefqueries', 1 );
                    return $cachedvalue;
                }
                $result = $self->fetchDomainPref( $dom, $pref );
            }
            $self->addStat( 'prefqueries', 1 );
            return $result;
        }
        return $result;
    } elsif ( PrefTDaemon::isUserID($object) ) {
        $result = $cachedvalue;
        return 'NOTIMPLEMENTED';
    }

    return $result;
}

sub getPrefCacheKey($object,$pref)
{
    return md5_hex( $object . "-" . $pref );
}

sub getObjectCachedPref($self,$object,$pref)
{
    my $key = PrefTDaemon::getPrefCacheKey( $object, $pref );

    if (
        defined( $prefs_->{$key} )
        && defined( $prefs_->{$key}->{'value'} )
        && defined( $prefs_->{$key}->{'time'} )
    ) {
        lock( %{ $prefs_->{$key} } );
        ## have to check time for expired cached value
        my $deltatime = time() - $prefs_->{$key}->{'time'};

        ## if not expired, then return cached value
        if ( $deltatime < $self->{'timeout_pref'} ) {
            $self->doLog("Cache key hit for: $key ($object, $pref)", 'prefdaemon', 'debug');
            $self->addStat( 'cacheprefhits', 1 );
            return $prefs_->{$key}->{'value'};
        }
        $self->addStat( 'cacheprefexpired', 1 );
        $self->doLog("Cache key ($key) too old: $deltatime s.", 'prefdaemon', 'debug');
        return '_CACHEEXPIRED';
    }
    $self->addStat( 'prefnotcached', 1 );
    $self->doLog("No cache key hit for: $key ($object, $pref)", 'prefdaemon', 'debug');
    return '_NOTCACHED';
}

sub setObjectPrefCache($self,$object,$pref,$value)
{
    my $key = PrefTDaemon::getPrefCacheKey( $object, $pref );
    if ( !defined( $prefs_->{$key} ) ) {
        $prefs_->{$key} = &share( {} );
    }

    lock( %{ $prefs_->{$key} } );
    $prefs_->{$key}->{'value'} = $value;
    $prefs_->{$key}->{'time'}  = time();
    $self->doLog("Caching value for: $key ($object, $pref)", 'prefdaemon', 'debug');
    $self->addStat( 'cachingprefvalue', 1 );

    return 1;
}

sub fetchEmailPref($self,$object,$pref)
{
    my $query = "SELECT $pref FROM user_pref p, email e WHERE p.id=e.pref AND e.address='$object'";
    my $result = $self->fetchBackendPref( $query, $pref );
    $self->addStat( 'backendprefcall', 1 );
    $self->setObjectPrefCache( $object, $pref, $result );
    return $result;
}

sub fetchUserPref($self,$object,$pref)
{
    $object =~ s/^\*//g;
    my $query = "SELECT $pref FROM user_pref p, user u WHERE u.id=".$object;
    my $result = $self->fetchBackendPref( $query, $pref );
    $self->addStat( 'backendprefcall', 1 );
    $self->setObjectPrefCache( $object, $pref, $result );
    return $result;
}

sub fetchDomainPref($self,$object,$pref)
{
    $self->addStat( 'prefsubqueries', 1 );

    if ( $pref eq 'has_whitelist' ) {
        $pref = 'enable_whitelists';
    }
    if ( $pref eq 'has_warnlist' ) {
        $pref = 'enable_warnlists';
    }
    if ( $pref eq 'has_blacklist' ) {
        $pref = 'enable_blacklists';
    }
    my $query = "SELECT $pref FROM domain_pref p, domain d WHERE p.id=d.prefs AND d.name='$object'";
    my $result = $self->fetchBackendPref( $query, $pref );
    $self->addStat( 'backendprefcall', 1 );
    $self->setObjectPrefCache( $object, $pref, $result );
    return $result;
}

sub fetchGlobalPref($self,$object,$pref)
{
    my $query = "SELECT $pref FROM system_conf, antispam, antivirus, httpd_config";
    my $result = $self->fetchBackendPref( $query, $pref );
    $self->addStat( 'backendprefcall', 1 );
    $self->setObjectPrefCache( $object, $pref, $result );
    return $result;
}

sub fetchBackendPref($self,$query,$pref)
{
    return '_NOBACKEND' if ( !$self->connectBackend() );

    my %res = $self->{backend}->getHashRow($query);
    if ( defined( $res{$pref} ) ) {
        return $res{$pref};
    }

    return 'NOTFOUND';
}

##########################
## WWList management

sub getWWCacheKey($object)
{
    return md5_hex($object);
}

sub getObjectWWList($self,$type,$object,$sender)
{
    ## first check if already cached
    my $iscachelisted = $self->getObjectCachedWW( $type, $object, $sender );
    return 'LISTED USER' if ( $iscachelisted eq 'LISTED' );

    my $islisted = '';

    if ( $iscachelisted =~ /^_/ ) {
        ## fetch user list
        $islisted = $self->getObjectBackendWW( $type, $object, $sender );
        return 'LISTED USER' if ( $islisted eq 'LISTED' );
    }

    ## then search for domain list if needed
    my $domain = '@' . PrefTDaemon::getDomain($object);
    if ($domain) {
        $iscachelisted = $self->getObjectCachedWW( $type, $domain, $sender );
        return 'LISTED DOMAIN' if ( $iscachelisted eq 'LISTED' );

        if ( $iscachelisted =~ /^_/ ) {
            ## fetch domain list
            $islisted = $self->getObjectBackendWW( $type, $domain, $sender );
            return 'LISTED DOMAIN' if ( $islisted eq 'LISTED' );
        }
    }

    ## finally search fot global list
    $iscachelisted = $self->getObjectCachedWW( $type, '_global', $sender );
    return 'LISTED GLOBAL' if ( $iscachelisted eq 'LISTED' );

    if ( $iscachelisted =~ /^_/ ) {
        ## fetch global list
        $islisted = $self->getObjectBackendWW( $type, '_global', $sender );
        return 'LISTED GLOBAL' if ( $islisted eq 'LISTED' );
    }

    return 'NOTLISTED';
}

sub getObjectCachedWW($self,$type,$object,$sender)
{
    $self->addStat( 'wwsubqueries', 1 );
    my $key = PrefTDaemon::getWWCacheKey($object);

    my $cache_ = $whitelists_;
    if ( $type eq 'WARN' ) {
        $cache_ = $warnlists_;
    }
    if ( $type eq 'BLACK' ) {
        $cache_ = $blacklists_;
    }
    if (
        defined( $cache_->{$key} )
        && defined( $cache_->{$key}->{'value'} )
        && defined( $cache_->{$key}->{'time'} )
    ) {
        lock( %{ $cache_->{$key} } );
        ## have to check time for expired cached value
        my $deltatime = $self->{'timeout_ww'};
        if ( $cache_->{$key}->{'time'} ) {
            $self->doLog(
                "found WW cache with time: " . $cache_->{$key}->{'time'}, 'prefdaemon', 'debug'
            );
            $deltatime = time() - $cache_->{$key}->{'time'};
        }
        ## if not expired, then return cached value
        if ( $deltatime < $self->{'timeout_ww'} ) {
            $self->doLog("Cache key hit for: $key ($object)", 'prefdaemon', 'debug');
            $self->addStat( 'cachewwhits', 1 );
            foreach my $l ( @{ $cache_->{$key}->{'value'} } ) {
                $self->doLog(
                    "testing cached WW value for $object: $sender <-> " . $l, 'prefdaemon', 'debug' );
                if ( PrefTDaemon::listMatch( $l, $sender ) ) {
                    $self->doLog(
                        "Found WW cached MATCH for $object: $sender <-> "
                        . $l, 'prefdaemon', 'debug'
                    );
                    return 'LISTED';
                }
            }
            return 'NOTLISTED';
        }
        $self->addStat( 'cachewwexpired', 1 );
        $self->doLog("Cache key ($key) too old: $deltatime s.", 'prefdaemon', 'debug');
        return '_CACHEEXPIRED';
    }
    $self->addStat( 'wwnotcached', 1 );
    $self->doLog("No cache key hit for: $key ($object)", 'prefdaemon', 'debug');
    return '_NOTCACHED';
}

sub getObjectBackendWW($self,$type,$object,$sender)
{
    $type   = lc($type);

    $self->addStat( 'wwsubqueries', 1 );
    my $cache_ = $whitelists_;
    if ( $type eq 'warn' ) {
        $cache_ = $warnlists_;
    }
    if ( $type eq 'black' ) {
        $cache_ = $blacklists_;
    }
    if ( !$self->connectBackend() ) {
        return '_NOBACKEND';
    }

    my $query = "SELECT sender FROM wwlists WHERE recipient='$object' AND type='$type' AND status=1";
    if ( $object eq '_global' ) {
        $query = "SELECT sender FROM wwlists WHERE recipient='$object' OR recipient='' AND type='$type' AND status=1";
    }

    my @reslist = $self->{backend}->getListOfHash($query);

    $self->addStat( 'backendwwcall', 1 );

    my $key = PrefTDaemon::getWWCacheKey($object);

    my $result = 'NOTLISTED';
    $self->createWWArrayCache( $type, $key );
    foreach my $resh (@reslist) {

        $self->doLog( "testing backend WW entry for $object: $sender: <-> "
              . $resh->{'sender'}, 'prefdaemon', 'debug' );
        $self->addToCache( $cache_, $key, $resh->{'sender'} );

        if ( PrefTDaemon::listMatch( $resh->{'sender'}, $sender ) ) {
            $self->doLog( "Found WW entry MATCH for $object: $sender <-> "
                  . $resh->{'sender'}, 'prefdaemon', 'debug' );
            $result = 'LISTED';
        }
    }

    return $result;
}

sub addToCache($self,$cache,$key,$data)
{
    lock $cache;
    push @{ $cache->{$key}->{'value'} }, $data;
}

sub listMatch($reg,$sender)
{
    # Use only the actual address as pattern
    if ($reg =~ /^.*<(.*\@.*\..*)>$/) {
        $reg = $1;
    }
    $reg =~ s/\./\\\./g; # Escape all dots
    $reg =~ s/\@/\\\@/g; # Escape @
    $reg =~ s/\*/\.\*/g; # Glob on all characters when using *
    $reg =~ s/\+/\\\+/g; # Escape +
    $reg =~ s/\|/\\\|/g; # Escape |
    $reg =~ s/\{/\\\{/g; # Escape {
    $reg =~ s/\}/\\\}/g; # Escape }
    $reg =~ s/\?/\\\?/g; # Escape ?
    $reg =~ s/[^a-zA-Z0-9\+.\\\-_=@\*\$\^!#%&'\/\?`{|}~]//g; # Remove unwanted characters
    if ( $reg eq "" ) {
        $reg = '.*';
    }
    if ($sender =~ /$reg/i) {
        return 1;
    }
    return 0;
}

sub createWWArrayCache($self,$type,$key)
{
    my $cache_ = $whitelists_;
    if ( $type eq 'warn' ) {
        $cache_ = $warnlists_;
    }
    if ( $type eq 'black' ) {
        $cache_ = $blacklists_;
    }

    lock $cache_;
    $cache_->{$key} = &share( {} );
    $cache_->{$key}->{'time'}  = time();
    $cache_->{$key}->{'value'} = &share( [] );

    return 1;
}

##########################
## Stats and counts utils
sub addStat($self,$what,$amount)
{
    lock %stats_;
    if ( !defined( $stats_{$what} ) ) {
        $stats_{$what} = 0;
    }
    $stats_{$what} += $amount;
    return 1;
}

sub statusHook($self)
{
    my $res = '-------------------'."\n";
    $res .= 'Current statistics:'."\n";
    $res .= '-------------------' ."\n";

    $res .= $self->SUPER::statusHook();
    require PrefClient;
    my $client = PrefClient->new();
    $res .= $client->query('GETINTERNALSTATS');

    $res .= '-------------------' ."\n";

    $self->doLog($res, 'prefdaemon');

    return $res;
}

sub logStats($self)
{
    lock %stats_;

    my $prefpercencached = 0;
    if ( $stats_{'prefqueries'} > 0 ) {
        $prefpercencached = (
            int(
                ( ( 100 / $stats_{'prefqueries'} ) * $stats_{'cacheprefhits'} )
                * 100
            )
        ) / 100;
    }

    my $wwpercencached = 0;
    if ( $stats_{'wwsubqueries'} > 0 ) {
        $wwpercencached = (
            int(
                ( ( 100 / $stats_{'wwsubqueries'} ) * $stats_{'cachewwhits'} ) *
                  100
            )
        ) / 100;
    }

    my $totalqueries = $stats_{'prefqueries'} + $stats_{'wwqueries'};

    my $res = '  Preference queries processed: ' . $stats_{'prefqueries'}."\n";
    $res .= '  Preference queries cached: '
          . $stats_{'cacheprefhits'}
          . " ($prefpercencached %)" ."\n";
    $res .= '  Preference backend calls: ' . $stats_{'backendprefcall'}."\n";
    $res .= '  WWlists queries processed: ' . $stats_{'wwqueries'}."\n";
    $res .= '  WWlists sub queries processed: ' . $stats_{'wwsubqueries'}."\n";
    $res .= '  WWlists queries cached: '
          . $stats_{'cachewwhits'}
          . " ($wwpercencached %)"."\n";
    $res .= '  WWLists backend calls: ' . $stats_{'backendwwcall'} ."\n";

    return $res;
}

1;
