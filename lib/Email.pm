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
#
#   This module will just read the configuration file

package Email;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;
require ReadConfig;
require SystemPref;
require Domain;
require PrefClient;
require User;

our @ISA = qw(Exporter);
our @EXPORT = qw(create getPref);
our $VERSION = 1.0;


sub create($address, $domain=undef)
{
    my %prefs;
    my $d;

    if (!defined($domain) && $address =~ /(\S+)\@(\S+)/) {
        $domain = $2;
        $d = Domain::create($domain);
    } else {
    	return;
    }

    my $self = {
        address => $address,
        domain => $domain,
        prefs => \%prefs,
        d => $d,
        user => undef
        #prefdaemon => $pref_daemon,
    };

    bless $self, "Email";
    return $self;
}

sub getPref($self,$pref,$default=undef)
{
    if (!defined($self->{prefs}) || !defined($self->{prefs}{$pref})) {
        my $prefclient = PrefClient->new();
        $prefclient->setTimeout(2);
        my $dpref = $prefclient->getRecursivePref($self->{address}, $pref);
        if ($dpref !~ /^_/ && $dpref !~ /(NOTSET|NOTFOUND)/) {
            $self->{prefs}->{$pref} = $dpref;
            return $dpref;
        }
        $self->loadPrefs();
    }

    if (defined($self->{prefs}->{$pref}) && $self->{prefs}->{$pref} !~ /(NOTSET|NOTFOUND)/ ) {
        return $self->{prefs}->{$pref};
    }
    my $dpref = $self->{d}->getPref($pref, $default);
    if (defined($dpref) && $dpref !~ /(NOTSET|NOTFOUND)/) {
        return $dpref;
    }
    if (defined($default)) {
        return $default;
    }
    return "";
}

sub getDomainObject($self)
{
    return $self->{d};
}

sub getAddress($self)
{
    return $self->{address};
}

sub getUser($self)
{
    if (!$self->{user}) {
        $self->{user} = User::create($self->{address});
    }
    return $self->{user};
}

sub getUserPref($self,$pref)
{
    $self->getUser();
    if ($self->{user}) {
        return $self->{user}->getPref($pref);
    }
    return;
}

sub loadPrefs($self)
{
    require DB;
    my $db = DB::connect('slave', 'mc_config', 0);

    my $to = $self->{address};
    my $to_domain = $self->{domain};
    my %res;

    if ($db && $db->ping()) {
	    my $query = "SELECT p.* FROM email e, user_pref p WHERE e.pref=p.id AND e.address='$to'";
	    %res = $db->getHashRow($query);
        if ( !%res || !$res{id} ) {
            return 0;
        }
	} else {
	    return 0;
	}
	foreach my $p (keys %res) {
	    $self->{prefs}->{$p} = $res{$p};
	}
}

sub hasInWhiteWarnList($self,$type,$sender)
{
    $sender =~ s/\'//g;

    my $sysprefs = SystemPref::getInstance();
    my $filename = 'white.list';
    if ($type =~ /^warnlist$/) {
    	if (! $sysprefs->getPref('enable_warnlists')) {
    	    return 0;
    	}
    	if (! $self->{d}->getPref('enable_warnlists') && ! $self->getPref('has_warnlist')) {
    	    return 0;
    	}
        $filename = 'warn.list';
    } elsif ($type =~ /^whitelist$/)    {
        if (! $sysprefs->getPref('enable_whitelists')) {
    	    return 0;
    	}
    	if (! $self->{d}->getPref('enable_whitelists') && ! $self->getPref('has_whitelist')) {
    	    return 0;
        }
    } elsif ($type =~ /^blacklist$/) {
        $filename = 'black.list';
    }

    my $conf = ReadConfig::getInstance();
    my $basedir = $conf->getOption('VARDIR')."/spool/mailcleaner/prefs";
    my $wwfile = $basedir."/_global/".$filename;

    my $prefclient = PrefClient->new();
    $prefclient->setTimeout(2);
    my $retvalues = {'GLOBAL' => 1, 'DOMAIN' => 2, 'USER' => 3 };
    if ($type eq 'whitelist') {
        my $result = $prefclient->isWhitelisted($self->{address}, $sender);
        if ($result =~ m/^LISTED (USER|DOMAIN|GLOBAL)/ ) {
            return $retvalues->{$1};
        } elsif ($result =~ /^_/) {
            return $self->loadedIsWWListed('white', $sender);
        }
        return 0;
    }
    if ($type eq 'warnlist') {
        my $result = $prefclient->isWarnlisted($self->{address}, $sender);
        if ($result =~ m/^LISTED (USER|DOMAIN|GLOBAL)/ ) {
            return $retvalues->{$1};
        } elsif ($result =~ /^_/) {
            return $self->loadedIsWWListed('warn', $sender);
        }
        return 0;
    }
    if ($type eq 'blacklist') {
        my $result = $prefclient->isBlacklisted($self->{address}, $sender);
        if ($result =~ m/^LISTED (USER|DOMAIN|GLOBAL)/ ) {
            return $retvalues->{$1};
        } elsif ($result =~ /^_/) {
            return $self->loadedIsWWListed('black', $sender);
        }
        return 0;
    }

}

sub loadedIsWWListed($self,$type,$sender)
{
    require DB;
    my $db = DB::connect('slave', 'mc_config', 0);

    my $to = $self->{address};
    $sender =~ s/[^a-zA-Z0-9.\-_=+@]//g;
    my %res;

    if ($db && $db->ping()) {
	    my $query = "SELECT sender FROM wwlists WHERE recipient='".$self->{address}."' AND type='$type' AND status=1";
	    my @senders = $db->getListOfHash($query);
	    foreach my $listedsender (@senders) {
	        if (Email::listMatch($listedsender->{'sender'}, $sender)) {
	            return 3;
	        }
	    }

	    $query = "SELECT sender FROM wwlists WHERE recipient='@".$self->{domain}."' AND type='$type' AND status=1";
            @senders = $db->getListOfHash($query);
	    foreach my $listedsender (@senders) {
	        if (Email::listMatch($listedsender->{'sender'}, $sender)) {
	            return 2;
	        }
	    }

	    $query = "SELECT sender FROM wwlists WHERE recipient='' AND type='$type' AND status=1";
	    @senders = $db->getListOfHash($query);
	    foreach my $listedsender (@senders) {
	        if (Email::listMatch($listedsender->{'sender'}, $sender)) {
	            return 1;
	        }
	    }
    }
    return 0;
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

sub inWW($self,$type,$sender,$destination)
{
    my $prefclient = PrefClient->new();
    $prefclient->setTimeout(2);

    if ($type eq 'whitelist') {
        if ($prefclient->isWhitelisted($destination, $sender)) {
            return 1;
        }
        return 0;
    }
    if ($prefclient->isWarnlisted($destination, $sender)) {
    	return 1;
    }
    return 0;
}

sub sendWarnlistHit($self,$sender,$reason,$msgid)
{
    require MailTemplate;
    my $template = MailTemplate::create('warnhit', 'warnhit', $self->{d}->getPref('summary_template'), \$self, $self->getPref('language'), 'html');

    my %level = (1 => 'system', 2 => 'domain', 3 => 'user');
    my %replace = (
        '__SENDER__' => $sender,
        '__REASON__' => $level{$reason},
        '__ADDRESS__' => $self->{address},
        '__LANGUAGE__' => $self->getPref('language'),
        '__ID__' => $msgid
    );

    my $from = $self->{d}->getPref('support_email');
    if ($from eq "") {
    	my $sys = SystemPref::getInstance();
        $from = $sys->getPref('summary_from');
    }
    $template->setReplacements(\%replace);
    return $template->send();
}

sub sendWWHitNotice($self,$whitelisted,$warnlisted,$sender,$msgh)
{
    require MailTemplate;
    my $template = MailTemplate::create('warnhit', 'noticehit', $self->{d}->getPref('summary_template'), \$self, 'en', 'text');

    my $reason = 'whitelist';
    my $level = $whitelisted;
    if (!$whitelisted) {
        $reason = 'warnlist';
        $level = $warnlisted;
    }
    my %levels = (1 => 'system', 2 => 'domain', 3 => 'user');
    my %replace = (
        '__LEVEL__' => $levels{$level},
        '__LIST__' => $reason,
        '__TO__' => $self->{address},
        '__SENDER__' => $sender
    );

    my $admin = $self->{d}->getPref('support_email');
    if ($admin eq "") {
    	my $sys = SystemPref::getInstance();
        $admin = $sys->getPref('analyse_to');
    }
    $template->setReplacements(\%replace);
    $template->setDestination($admin);
    $template->addAttachement('TEXT', \$$msgh);
    return $template->send();
}

sub getLinkedAddresses($self)
{
    my %addresses;

    if (!$self->{user}) {
        $self->{user} = User::create($self->{address});
    }

    return $self->{user}->getAddresses();
}

1;
