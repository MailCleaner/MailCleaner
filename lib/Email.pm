#!/usr/bin/perl -w
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
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
#

package          Email;
require          Exporter;
require          ReadConfig;
require			 SystemPref;
require          Domain;
require          PrefClient;
require          User;
use strict;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(create getPref);
our $VERSION    = 1.0;


sub create {
  my $address = shift;
  my $domain = $address;
  my %prefs;
  my $d;
#-#  my $pref_daemon = PrefDaemon::create();

  if ($address =~ /(\S+)\@(\S+)/) {
    $domain = $2;
    $d = Domain::create($domain);
  } else {
  	return;
  }

  my $this = {
         address => $address,
         domain => $domain,
         prefs => \%prefs,
         d => $d,
         user => undef
#-#         prefdaemon => $pref_daemon,
         };

  bless $this, "Email";
  return $this;
}

sub getPref {
  my $this = shift;
  my $pref = shift;
  my $default = shift;

  if (!defined($this->{prefs}) || !defined($this->{prefs}{$pref})) {

  	## get user prefs
 #-# 	my $cachedpref = $this->{prefdaemon}->getPref('PREF', $this->{address}." ".$pref);
 #-# 	if ($cachedpref !~ /^(BADPREF|NOTFOUND|NOCACHE|TIMEDOUT|NODAEMON)/ ) {
 #-# 	  $this->{prefs}->{$pref} = $cachedpref;
 #-# 	  return $cachedpref;
 #-# 	}
 #-# 	if ($cachedpref =~ /^(NOCACHE|TIMEDOUT|NODAEMON)/) {
 #-# 	  $this->loadPrefs();
 #-# 	}

    my $prefclient = PrefClient->new();
    $prefclient->setTimeout(2);
    my $dpref = $prefclient->getRecursivePref($this->{address}, $pref);
    if ($dpref !~ /^_/ && $dpref !~ /(NOTSET|NOTFOUND)/) {
      $this->{prefs}->{$pref} = $dpref;
      return $dpref;
    }
    ## fallback loading
    $this->loadPrefs();
  }

  if (defined($this->{prefs}->{$pref}) && $this->{prefs}->{$pref} !~ /(NOTSET|NOTFOUND)/ ) {
    return $this->{prefs}->{$pref};
  }
  my $dpref = $this->{d}->getPref($pref, $default);
  if (defined($dpref) && $dpref !~ /(NOTSET|NOTFOUND)/) {
    return $dpref;
  }
  if (defined($default)) {
    return $default;
  }
  return "";
}

sub getDomainObject {
  my $this = shift;

  return $this->{d};
}

sub getAddress {
  my $this = shift;

  return $this->{address};
}

sub getUser {
  my $this = shift;

  if (!$this->{user}) {
      $this->{user} = User::create($this->{address});
  }
  return $this->{user};
}

sub getUserPref {
  my $this = shift;
  my $pref = shift;

  $this->getUser();
  if ($this->{user}) {
      return $this->{user}->getPref($pref);
  }
  return undef;
}

sub loadPrefs {
  my $this = shift;

  require DB;
  my $db = DB::connect('slave', 'mc_config', 0);

  my $to = $this->{address};
  my $to_domain = $this->{domain};
  my %res;

  if ($db && $db->ping()) {
	  my $query = "SELECT p.* FROM email e, user_pref p WHERE e.pref=p.id AND e.address='$to'";
	  %res = $db->getHashRow($query);
      if ( !%res || !$res{id} ) {
      	  return 0;
        } else {
          #print "user pref";
        }
	} else {
	  return 0;
	}
	foreach my $p (keys %res) {
	  $this->{prefs}->{$p} = $res{$p};
	}
}

sub hasInWhiteWarnList {
  my $this = shift;
  my $type = shift;
  my $sender = shift;
  $sender =~ s/\'//g;

  my $sysprefs = SystemPref::getInstance();
  my $filename = 'white.list';
  if ($type =~ /^warnlist$/) {
  	 if (! $sysprefs->getPref('enable_warnlists')) {
  	 	return 0;
  	 }
  	 if (! $this->{d}->getPref('enable_warnlists') && ! $this->getPref('has_warnlist')) {
  	   return 0;
  	 }
     $filename = 'warn.list';
  }
  elsif ($type =~ /^whitelist$/)  {
    if (! $sysprefs->getPref('enable_whitelists')) {
  	 	return 0;
  	}
  	if (! $this->{d}->getPref('enable_whitelists') && ! $this->getPref('has_whitelist')) {
  	   return 0;
  	 }
  }
  elsif ($type =~ /^blacklist$/) {
    $filename = 'black.list';
  }

  my $conf = ReadConfig::getInstance();
  my $basedir = $conf->getOption('VARDIR')."/spool/mailcleaner/prefs";
  my $wwfile = $basedir."/_global/".$filename;

  ## check global
#  if ($this->inWW($type, $sender, '_')) {
#  	return 1;
#  }

  ## check domain
#  if ($this->inWW($type, $sender, '@'.$this->{domain})) {
#  	return 2;
#  }

  ## check user
# if ($this->inWW($type, $sender, $this->{address})) {
#  	return 3;
#  }

  my $prefclient = PrefClient->new();
  $prefclient->setTimeout(2);
  my $retvalues = {'GLOBAL' => 1, 'DOMAIN' => 2, 'USER' => 3 };
  if ($type eq 'whitelist') {
     my $result = $prefclient->isWhitelisted($this->{address}, $sender);
     if ($result =~ m/^LISTED (USER|DOMAIN|GLOBAL)/ ) {
       return $retvalues->{$1};
     } elsif ($result =~ /^_/) {
       return $this->loadedIsWWListed('white', $sender);
     }
     return 0;
  }
  if ($type eq 'warnlist') {
    my $result = $prefclient->isWarnlisted($this->{address}, $sender);
    if ($result =~ m/^LISTED (USER|DOMAIN|GLOBAL)/ ) {
      return $retvalues->{$1};
    } elsif ($result =~ /^_/) {
      return $this->loadedIsWWListed('warn', $sender);
    }
    return 0;
  }
  if ($type eq 'blacklist') {
    my $result = $prefclient->isBlacklisted($this->{address}, $sender);
    if ($result =~ m/^LISTED (USER|DOMAIN|GLOBAL)/ ) {
      return $retvalues->{$1};
    } elsif ($result =~ /^_/) {
      return $this->loadedIsWWListed('black', $sender);
    }
    return 0;
  }

}

sub loadedIsWWListed {
  my $this = shift;
  my $type = shift;
  my $sender = shift;

  require DB;
  my $db = DB::connect('slave', 'mc_config', 0);

  my $to = $this->{address};
  $sender =~ s/[^a-zA-Z0-9.\-_=+@]//g;
  my %res;

  if ($db && $db->ping()) {
	  my $query = "SELECT sender FROM wwlists WHERE recipient='".$this->{address}."' AND type='$type' AND status=1";
	  my @senders = $db->getListOfHash($query);
	  foreach my $listedsender (@senders) {
	     if (Email::listMatch($listedsender->{'sender'}, $sender)) {
	       return 3;
	     }
	  }

	  $query = "SELECT sender FROM wwlists WHERE recipient='@".$this->{domain}."' AND type='$type' AND status=1";
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

sub listMatch {
    my $reg = shift;
    my $sender = shift;

    # Use only the actual address as pattern
    if ($reg =~ /^.*<(.*\@.*\..*)>$/) {
        $reg = $1;
    }
    $reg =~ s/\./\\\./g; # Escape all dots
    $reg =~ s/\@/\\\@/g; # Escape @
    $reg =~ s/\*/\.\*/g; # Glob on all characters when using *
    $reg =~ s/[^a-zA-Z0-9.\\\-_=@\*\$\^]//g; # Remove unwanted characters
    if ($sender =~ /$reg/) {
        return 1;
    }
    return 0;
}

sub inWW {
  my $this = shift;
  my $type = shift;
  my $sender = shift;
  my $destination = shift;

  my $prefclient = PrefClient->new();
  $prefclient->setTimeout(2);

  if ($type eq 'whitelist') {
     if ($prefclient->isWhitelisted($destination, $sender)) {
#-#     if ($this->{prefdaemon}->getPref('WHITELIST', $destination." ".$sender) =~ /^FOUND/) {
     	return 1;
     }
     return 0;
  }
  if ($prefclient->isWarnlisted($destination, $sender)) {
#-#  if ($this->{prefdaemon}->getPref('WARNLIST', $destination." ".$sender) =~ /^FOUND/) {
  	return 1;
  }
  return 0;
}

sub sendWarnlistHit {
  my $this = shift;
  my $sender = shift;
  my $reason = shift;
  my $msgid = shift;

  require MailTemplate;
  #print "sending warn list hit\n";
  my $template = MailTemplate::create('warnhit', 'warnhit', $this->{d}->getPref('summary_template'), \$this, $this->getPref('language'), 'html');

  my %level = (1 => 'system', 2 => 'domain', 3 => 'user');
  my %replace = (
    '__SENDER__' => $sender,
    '__REASON__' => $level{$reason},
    '__ADDRESS__' => $this->{address},
    '__LANGUAGE__' => $this->getPref('language'),
    '__ID__' => $msgid
  );

  my $from = $this->{d}->getPref('support_email');
  if ($from eq "") {
  	my $sys = SystemPref::getInstance();
    $from = $sys->getPref('summary_from');
  }
  $template->setReplacements(\%replace);
  return $template->send();
}

sub sendWWHitNotice {
  my $this = shift;
  my $whitelisted = shift;
  my $warnlisted = shift;
  my $sender = shift;
  my $msgh = shift;

  require MailTemplate;
  #print "sending wwlist notice\n";
  my $template = MailTemplate::create('warnhit', 'noticehit', $this->{d}->getPref('summary_template'), \$this, 'en', 'text');

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
     '__TO__' => $this->{address},
     '__SENDER__' => $sender
  );

  my $admin = $this->{d}->getPref('support_email');
  if ($admin eq "") {
  	my $sys = SystemPref::getInstance();
    $admin = $sys->getPref('analyse_to');
  }
  $template->setReplacements(\%replace);
  $template->setDestination($admin);
  $template->addAttachement('TEXT', \$$msgh);
  return $template->send();
}

sub getLinkedAddresses {
  my $this = shift;

  my %addresses;

  if (!$this->{user}) {
    $this->{user} = User::create($this->{address});
  }

  return $this->{user}->getAddresses();
}

1;
