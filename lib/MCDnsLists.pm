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

package          MCDnsLists;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;
use IO::Pipe;
use POSIX qw(:signal_h);    # For Solaris 9 SIG bug workaround
use Net::HTTP;
use Net::IP;
use URLRedirects;

our @ISA     = qw(Exporter);
our @EXPORT  = qw(readFile);
our $VERSION = 1.0;

my %rblsfailure;
my %shorteners;

sub new($class,$logfunction,$debug)
{
    my $self        = {};

    $self->{rbls}                      = {};
    $self->{useableRbls}               = ();
    $self->{whitelistedDomains}        = {};
    $self->{TLDs}                      = {};
    $self->{localDomains}              = {};
    $self->{maxurilength}              = 150;
    $self->{logfunction}               = $logfunction;
    $self->{debug}                     = $debug;
    $self->{timeOut}                   = 10;
    $self->{failuretobedead}           = 1;
    $self->{retrydeadinterval}         = 120;
    $self->{shortner_resolver_maxdeep} = 10;
    $self->{shortner_resolver_timeout} = 5;
    $self->{URLRedirects}           = URLRedirects->new();

    %rblsfailure = ();

    bless $self, $class;
    return $self;
}

sub loadRBLs($self,$rblspath,$selectedRbls,$rblsType,$whitelistDomainsFile,$TLDsFiles,$localDomainsFile,$prelog)
{
    if ( opendir( my $dir, $rblspath ) ) {
        while ( my $entry = readdir($dir) ) {
            if ( $entry =~ m/\S+\.cf$/ ) {
                my $rblname = '';
                if ( open(my $RBLFILE, '<', $rblspath."/".$entry) ) {
                    while (<$RBLFILE>) {
                        if (/^name\s*=\s*(\S+)$/) {
                            $rblname                           = $1;
                            $self->{rbls}{$rblname}            = ();
                            $self->{rbls}{$rblname}{'subrbls'} = ();
                            $self->{rbls}{$rblname}{'callonip'} = 1;
                        }
                        next if ( $rblname eq '' );
                        if (/dnsname\s*=\s*([a-zA-Z0-9._-]+)$/) {
                            $self->{rbls}{$rblname}{'dnsname'} = $1.'.';
                        }
                        if (/type\s*=\s*([a-zA-Z0-9._-]+)$/) {
                            $self->{rbls}{$rblname}{'type'} = $1;
                        }
                        if (/sublist\s*=\s*([^,]+),([a-zA-Z0-9._-]+),(.+)$/) {
                            my $subrbl = {
                                'mask' => $1,
                                'id'   => $2,
                                'info' => $3
                            };
                            push @{ $self->{rbls}{$rblname}{'subrbls'} }, $subrbl;
                        }
                        if (/callonip\s*=\s*(0|false|no)/i) {
                            $self->{rbls}{$rblname}{'callonip'} = 0;
                        }
                    }
                    close $RBLFILE;
                }
            }
        }
        close $dir;
        if ( $self->{debug} ) {
            &{ $self->{logfunction} }(
                "$prelog loaded " .
                keys( %{ $self->{rbls} } ) .
                " useable RBLs"
            );
        }
    } else {
        &{ $self->{logfunction} }(
            "$prelog could not open RBL's definition directory ($rblspath)"
        );
    }

    my %deadrbls = ();
    my @neededrbls = split( ' ', $selectedRbls );
    foreach my $r (@neededrbls) {
        if ( defined( $self->{rbls}{$r}{'dnsname'} ) ) {
            push @{ $self->{useableRbls} }, $r;
        } else {
            &{ $self->{logfunction} }(
                "$prelog configured to use $r, but this RBLs is not available !"
            );
        }
    }
    if ( $self->{useableRbls} ) {
        &{ $self->{logfunction} }(
            "$prelog using " .
            @{ $self->{useableRbls} } .
            " RBLs (" .
            join( ', ', @{ $self->{useableRbls} } ) .
            ")"
        );
    } else {
        &{ $self->{logfunction} }("$prelog not using any RBLs");
    }

    ## loading whitelisted domains
    if ( open(my $FILE, '<', $whitelistDomainsFile ) ) {
        while (<$FILE>) {
            if (/\s*([-_.a-zA-Z0-9]+)/) {
                $self->{whitelistedDomains}{$1} = 1;
            }
        }
        close $FILE;
        &{ $self->{logfunction} }(
            "$prelog loaded " .
            keys( %{ $self->{whitelistedDomains} } ) .
            " whitelisted domains"
        );
    } elsif ( $whitelistDomainsFile ne '' ) {
        &{ $self->{logfunction} }(
            "$prelog could not load domains whitelist file (" .
            $whitelistDomainsFile .
            ") !"
        );
    }

    ## loading tlds
    foreach my $tldfile ( split( '\s', $TLDsFiles ) ) {
        if ( open(my $FILE, '<', $tldfile ) ) {
            while (<$FILE>) {
                if (/^([-_.a-zA-Z0-9]+)/i) {
                    $self->{TLDs}{ lc($1) } = 1;
                }
            }
            close $FILE;
        } elsif ( $tldfile ne '' ) {
            &{ $self->{logfunction} }(
                "$prelog could not load two levels TLDs file ($tldfile) !"
            );
        }
    }
    if ( $TLDsFiles ne '' ) {
        &{ $self->{logfunction} }(
            "$prelog loaded " . keys( %{ $self->{TLDs} } ) . " TLDs"
        );
    }

    ## loading local domains
    if ( open(my $FILE, '<', $localDomainsFile ) ) {
        while (<$FILE>) {
            if (/^(\S+):/) {
                $self->{localDomains}{$1} = 1;
            }
        }
        close $FILE;
        &{ $self->{logfunction} }(
            "$prelog loaded " . keys( %{ $self->{localDomains} } ) . " local domains"
        );
    }

    ## loading url shorteners
    my $shortfile = $rblspath . '/url_shorteners.txt';
    if ( open(my $FILE, '<', $shortfile ) ) {
        while (<$FILE>) {
            if (/^([a-z.\-]+)/i) {
                $self->{shorteners}{$1} = 1;
            }
        }
        close $FILE;
    }
    if ( keys( %{ $self->{shorteners} } ) ) {
        &{ $self->{logfunction} }(
            "$prelog loaded " . keys( %{ $self->{shorteners} } ) . " shorteners"
        );
    }
    return;
}

sub findUri($self,$line,$prelog)
{
    if ( $line =~ m|(?:http?s?)://([^#/" ><=\[\]()]{3,$self->{maxurilength}})| ) {
        my $authority = $1;
        $authority =~ s/\n//g;
        $authority = lc($authority);
        my $u = $authority;

        ## avoid some easy fooling
        $u =~ s/[*,=]//g;
        $u =~ s/=2E/./g;

        return $self->isValidDomain( $u, 1, $prelog );
    }
    return 0;
}

sub findUriShortener($self,$line,$prelog)
{
    my $deep           = 0;
    my $newloc         = $line;
    my $continue       = 1;
    my $final_location = 0;
    my $first_link     = '';
    while ( $deep++ <= $self->{shortner_resolver_maxdeep} ) {
        my ( $link, $nl ) = $self->getNextLocation($newloc);
        if ( !$nl ) {
            last;
        }
        if ( $first_link eq '' ) {
            $first_link = $link;
        }
        $newloc         = $nl;
        $final_location = $newloc;
    }
    $final_location =~ s/([%?].*)//g;
    if ( $final_location =~ m|bit\.ly/a/warning| ) {
        &{ $self->{logfunction} }(
            "$prelog found urlshortener with disabled link: $first_link"
        );
        return 'disabled-link-bit.ly';
    }
    my $final_domain = $self->findUri( $final_location, $prelog );
    if ( $deep > 1 ) {
        &{ $self->{logfunction} }(
            "$prelog found urlshortener/redirect to $final_location"
        );
    }
    if ( $deep >= $self->{shortner_resolver_maxdeep} ) {
        &{ $self->{logfunction} }(
            "$prelog urlshortner finder reached max depth ($deep)"
        );
    }
    return $final_domain;
}

sub getNextLocation($self,$uri)
{
    my ($domain, $get) = $uri =~ m#(?:(?:(?^:https?))://((?:(?:(?:(?:(?:[a-zA-Z0-9][-a-zA-Z0-9]*)?[a-zA-Z0-9])[.])*(?:[a-zA-Z][-a-zA-Z0-9]*[a-zA-Z0-9]|[a-zA-Z])[.]?)|(?:[0-9]+[.][0-9]+[.][0-9]+[.][0-9]+)))(?::(?:(?:[0-9]*)))?(?:/(((?:(?:(?:(?:[a-zA-Z0-9\-_.!~*'():@&=+$,]+|(?:%[a-fA-F0-9][a-fA-F0-9]))*)(?:;(?:(?:[a-zA-Z0-9\-_.!~*'():@&=+$,]+|(?:%[a-fA-F0-9][a-fA-F0-9]))*))*)(?:/(?:(?:(?:[a-zA-Z0-9\-_.!~*'():@&=+$,]+|(?:%[a-fA-F0-9][a-fA-F0-9]))*)(?:;(?:(?:[a-zA-Z0-9\-_.!~*'():@&=+$,]+|(?:%[a-fA-F0-9][a-fA-F0-9]))*))*))*))(?:[?](?:(?:(?:[;/?:@&=+$,a-zA-Z0-9\-_.!~*'()]+|(?:%[a-fA-F0-9][a-fA-F0-9]))*)))?))?)#mg;
    unless (defined($domain)) {
        return ( $uri, 0 );
    }
    $domain = lc($domain);
    $domain =~ s/[*,=]//g;
    $domain =~ s/=2E/./g;

    # Test Redirect (when it contains a URL query)
    if ( defined($get) && ($get =~ m/\?([a-zA-Z0-9\$\-_\.\+!\*'\(\),\/\?&]+)=/) ) {
        if ( defined($shorteners{$domain.'/'.$get}) ) {
            return $shorteners{$domain.'/'.$get};
        }
        my $redirect = $self->{URLRedirects}->decode($domain.'/'.$get);
        if ($redirect) {
            $shorteners{$domain.'/'.$get} = $redirect;
            return ( $domain.'/'.$get , $redirect );
        } else {
            return ( '' , 0 );
        }

    # Test shortener (no query, but simple GET path)
    } elsif ( defined($get) && $get =~ m|^[a-zA-Z0-9]{5,}$| ) {
        my $request = $domain.'/'.$get;

        if ( defined( $shorteners{$request} ) ) {
            return $shorteners{$request};
        }
        if ( !defined( $self->{shorteners}{$domain} ) ) {
            return ( '', 0 );
        }

        my $s = Net::HTTP->new(
            Host    => $domain,
            Timeout => $self->{shortner_resolver_timeout}
        );
        if ($s) {
            $s->write_request(
                GET          => "/" . $get,
                'User-Agent' => "Mozilla/5.0"
            );
            my ( $code, $mess, %h );
            eval {
                ( $code, $mess, %h ) = $s->read_response_headers( laxed => 1 );
            };
            if ( $code >= 300 && $code < 400 ) {
                if ( defined( $h{'Location'} ) ) {
                    my $new_location = $h{'Location'};
                    $shorteners{$request} = $new_location;
                    return ( $request, $new_location );
                }
            }
            $shorteners{$request} = 0;
        }
        return ( $request, 0 );
    }
    return ( '', 0 );
}

sub findEmail($self,$line,$prelog)
{
    if ( my ( $local, $domain ) =
        $line =~
m/([a-zA-Z0-9-_.]{4,25})[ |[(*'"]{0,5}@[ |\])*'"]{0,5}([a-zA-Z0-9-_\.]{4,25}\.[ |[(*'"]{0,5}[a-z]{2,3})/
      )
    {
        my $add = $1 . "@" . $2;
        my $dom = $2;

        $add =~ s/^3D//;
        $add = lc($add);
        if ( !defined( $self->{localDomains}{$dom} )
            && $self->isValidDomain( $dom, 0, $prelog ) )
        {
            return $add;
        }
    }
    return 0;
}

sub isValidDomain($self,$domain,$usewhitelist,$prelog)
{
    $domain =~ s/\%//g;

    if ( $domain =~ m/[a-z0-9\-_.:]+[.:][a-z0-9]{2,15}$/ ) {
        if ($usewhitelist) {
            foreach my $wd ( keys %{ $self->{whitelistedDomains} } ) {
                if ( $domain =~ m/([^.]{0,150}\.$wd)$/ || $domain eq $wd ) {
                    if ( $self->{debug} ) {
                        &{ $self->{logfunction} }( $prelog
                              . " has found whitelisted domain: $domain" );
                    }
                    return 0;
                }
            }
        }

        if ( $domain =~ m/^(\d+\.\d+\.\d+\.\d+|[a-f0-9:]+)$/ ) {
            if ( $self->{debug} ) {
                &{ $self->{logfunction} }(
                    $prelog . " has found literal IP domain: $domain" );
            }
            return $domain;
        }

        foreach my $ld ( keys %{ $self->{localDomains} } ) {
            next if ( $ld =~ m/\*$/ );
            if ( $domain =~ m/([^.]{0,150}\.$ld)$/ || $domain eq $ld ) {
                if ( $self->{debug} ) {
                    &{ $self->{logfunction} }(
                        $prelog . " has found a local domain: $ld ($domain)" );
                }
                return 0;
            }
        }

        foreach my $tld ( keys %{ $self->{TLDs} } ) {
            if ( $domain =~ m/([^.]{0,150}\.$tld)$/ ) {
                my $ret = $1;
                if ( $self->{debug} ) {
                    &{ $self->{logfunction} }(
                        $prelog . " has found a valid domain: $ret" );
                }
                return $ret;
            }
        }
    }
    if ( $self->{debug} ) {
        &{ $self->{logfunction} }(
            $prelog . " has found an invalid domain: '$domain'"
        );
    }
    return 0;
}

sub check_dns($self,$value,$type,$prelog,$maxhitcount=0,$maxbshitcount=1)
{
    if ( $self->{debug} ) {
        &{ $self->{logfunction} }( $prelog . " will check value: $value" );
    }

    my ( @HitList, $Checked, $HitOrMiss );

    my $pipe = IO::Pipe->new();
    if ( !$pipe ) {
        &{ $self->{logfunction} }(
            'Failed to create pipe, %s, try reducing the maximum number of unscanned messages ' .
            'per batch', $!
        );
        return 0;
    }

    my $PipeReturn = 0;
    my $GotAHit    = 0;
    my $pid        = fork();
    die "Can't fork: $!" unless defined($pid);

    if ( $pid == 0 ) {

        # In the child
        my $IsSpam = 0;
        my $RBLEntry;
        $pipe->writer();
        POSIX::setsid();
        $pipe->autoflush();

        my $hitcount   = 0;
        my $bshitcount = 0;

        foreach my $r ( @{ $self->{useableRbls} } ) {
            if (   defined( $rblsfailure{$r}{'disabled'} )
                && $rblsfailure{$r}{'disabled'}
                && defined( $rblsfailure{$r}{'lastfailure'} ) )
            {
                if (
                    time - $rblsfailure{$r}{'lastfailure'} >=
                    $self->{'retrydeadinterval'} )
                {
                    &{ $self->{logfunction} }(
                        "$prelog list $r disabled time exceeded, rehabilitating this RBL."
                    );
                    $rblsfailure{$r}{'disabled'} = 0;
                } else {
                    if ( $self->{debug} ) {
                        &{ $self->{logfunction} }(
                            $prelog . " list $r disabled."
                        );
                    }
                    next;
                }
            }
            next if ( !defined($self->{rbls}{$r}{'dnsname'}) );
            next if ( !defined($self->{rbls}{$r}{'type'}) || $self->{rbls}{$r}{'type'} ne $type );
            last if ( $maxhitcount   && $hitcount >= $maxhitcount );
            last if ( $maxbshitcount && $bshitcount >= $maxbshitcount );

            my $callvalue = $value;
            if ($callvalue =~ m/^(\d+\.\d+\.\d+\.\d+|[a-f0-9\:]{5,71})$/) {
                if ($self->{rbls}{$r}{'type'} =~ m/^URIRBL$/i && $self->{rbls}{$r}{'callonip'} == 0) {
                    if ( $self->{debug} ) {
                        &{ $self->{logfunction} }(
                            "$prelog not checking literal IP $callvalue against " .
                            "$self->{rbls}{$r}{'dnsname'} ( callonip = " .
                            "$self->{rbls}{$r}{'callonip'} )"
                        );
                    }
                    next;
                }
                my $IPobject = Net::IP->new($callvalue);
                if ($IPobject) {
                    $callvalue = $IPobject->reverse_ip;
                    $callvalue =~ s/[a-z0-9]\.arpa\.$//;
                    $callvalue =~ s/\.in-add$//;
                    $callvalue =~ s/\.ip$//;
                    $callvalue =~ s/\.\./\./;
                }
            }

            if ( $self->{debug} ) {
                &{ $self->{logfunction} }(
                    "$prelog checking '$callvalue' against $self->{rbls}{$r}{'dnsname'}"
                );
            }

            $RBLEntry = gethostbyname( "$callvalue." . $self->{rbls}{$r}{'dnsname'} );
            if ($RBLEntry) {
                $RBLEntry = Socket::inet_ntoa($RBLEntry);
                # Got a hit!
                if ( $RBLEntry =~ /^127\.[01]\.[0-9]\.[0123456789]\d*$/ ) {
                    # Check the sublists masks
                    my $subhit = 0;
                    foreach my $sub ( @{ $self->{rbls}{$r}{'subrbls'} } ) {
                        my $reg = $sub->{'mask'};
                        if ( $RBLEntry =~ m/$reg/ ) {
                            print $pipe $r . "\n";
                            $IsSpam = 1;
                            print $pipe "Hit $RBLEntry\n";
                            if ( $self->{rbls}{$r}{'type'} eq 'BSRBL' ) {
                                $bshitcount++;
                            } else {
                                $hitcount++;
                            }
                        } else {
                            print $pipe $r . "\n";
                            print $pipe "Miss\n";
                        }
                    }
                    print $pipe $r . "\n";
                    print $pipe "Miss\n";
                } else {
                    print $pipe $r . "\n";
                    print $pipe "Miss\n";
                }
            } else {
                print $pipe $r . "\n";
                print $pipe "Miss\n";
            }
        }
        $pipe->close();
        exit $IsSpam;
    }

    eval {
        $pipe->reader();
        local $SIG{ALRM} = sub { die "Command Timed Out" };
        alarm $self->{'timeOut'};

        while (<$pipe>) {
            chomp;
            $Checked   = $_;
            $HitOrMiss = <$pipe>;
            chomp $HitOrMiss;
            if ($HitOrMiss =~ m/Hit (127\.\d+\.\d+\.\d+)/) {
                push @HitList, $Checked;
                &{ $self->{logfunction} }(
                    "$prelog $value $Checked => $HitOrMiss"
                );
            }
        }
        $pipe->close();
        waitpid $pid, 0;
        $PipeReturn = $?;
        alarm 0;
        $pid = 0;
    };
    alarm 0;

    # Catch failures other than the alarm
    if ( $@ and $@ !~ /Command Timed Out/ ) {
        &{ $self->{logfunction} }(
            "$prelog Checks failed with real error: $@"
        );
        die();
    }

    # In which case any failures must be the alarm
    if ( $pid > 0 ) {
        &{ $self->{logfunction} }(
            "$prelog Check $Checked timed out and was killed"
        );
        $rblsfailure{$Checked}{'lastfailure'} = time;
        if ( defined( $rblsfailure{$Checked}{'failures'} ) ) {
            $rblsfailure{$Checked}{'failures'}++;
        } else {
            $rblsfailure{$Checked}{'failures'} = 1;
        }
        if ( $rblsfailure{$Checked}{'failures'} >= $self->{'failuretobedead'} ) {
            $rblsfailure{$Checked}{'disabled'} = 1;
            &{ $self->{logfunction} }(
                "$prelog disabling $Checked, not answering ! will retry in " .
                "$self->{'retrydeadinterval'} seconds."
            );
        }

        # Kill the running child process
        my ($i);
        kill -15, $pid;
        for ( $i = 0 ; $i < 5 ; $i++ ) {
            sleep 1;
            waitpid( $pid, &POSIX::WNOHANG() );
            ( $pid = 0 ), last unless kill( 0, $pid );
            kill -15, $pid;
        }

        # And if it didn't respond to 11 nice kills, we kill -9 it
        if ($pid) {
            kill -9, $pid;
            waitpid $pid, 0;    # 2.53
        }
    }

    my $temp = @HitList;
    $temp = $temp + 0;
    if ( !$HitList[0] ) {
        $temp = 0;
    } else {
        $temp = 0 unless $HitList[0] =~ /[a-z]/i;
    }
    return ( $value, $temp, join( ',', @HitList ) );
}

sub getAllRBLs($self)
{
    return $self->{rbls};
}

sub getUseablRBLs($self)
{
    return $self->{useableRbls};
}

sub getDebugValue($self)
{
    return $self->{debug};
}

1;
