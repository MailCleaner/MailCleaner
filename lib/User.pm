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

package          User;
require          Exporter;
require          ReadConfig;
require          SystemPref;
require          Domain;
require          PrefClient;
use strict;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(create);
our $VERSION    = 1.0;


sub create {
  my %prefs;
  my %addresses;
  my $username = shift;
  my $domain = shift;

  my $this = {
       id => 0,
       username => '',
       domain => '',
       prefs => \%prefs,
       addresses => \%addresses,
       d => undef,
       db => undef
       };

  bless $this, "User";

  if (defined($username) && defined($domain)) {
      $this->loadFromUsername($username, $domain);
  } elsif (defined($username) && $username =~ m/^\d+$/) {
      $this->loadFromId($username);
  } elsif (defined($username) && $username =~ m/^\S+\@\S+$/) {
      $this->loadFromLinkedAddress($username);
  }
  return $this;
}

sub loadFromId {
    my $this = shift;
    my $id = shift;

    if (!$id) {
        return;
    }

    my $query = "SELECT u.username, u.domain, u.id FROM user u, WHERE u.id=".$id;
    $this->load($query);
}

sub loadFromUsername {
    my $this = shift;
    my $username = shift;
    my $domain = shift;

    my $query = "SELECT u.username, u.domain, u.id FROM user u, WHERE u.username='".$username."' AND u.domain='".$domain."'";
    $this->load($query);
}

sub loadFromLinkedAddress {
    my $this = shift;
    my $email = shift;

    if ($email =~ m/^(\S+)\@(\S+)$/) {
        $this->{domain} = $2;
    }

    $this->{addresses}->{$email} = 1;

    my $query = "SELECT u.username, u.domain, u.id FROM user u, email e WHERE e.address='".$email."' AND e.user=u.id";
    $this->load($query);
}

sub load {
    my $this = shift;
    my $query = shift;

    if (!$this->{db}) {
      require DB;
      $this->{db} = DB::connect('slave', 'mc_config', 0);
    }
    my %userdata = $this->{db}->getHashRow($query);
    if (keys %userdata) {
        $this->{username} = $userdata{'username'};
        $this->{domain} = $userdata{'domain'};
        $this->{id} = $userdata{'id'};
    }
    return 0;
}

sub getAddresses {
    my $this = shift;

    if ($this->{id}) {
      ## get registered addresses
      if (!$this->{db}) {
        require DB;
        $this->{db} = DB::connect('slave', 'mc_config', 0);
      }

      my $query = "SELECT e.address, e.is_main FROM email e WHERE e.user=".$this->{'id'};
      my @addresslist = $this->{db}->getListOfHash($query);
      foreach my $regadd (@addresslist) {
        $this->{addresses}->{$regadd->{'address'}} = 1;
        if ($regadd->{is_main}) {
            foreach my $add (keys %{$this->{addresses}}) {
                $this->{addresses}->{$add} = 1;
            }
            $this->{addresses}->{$regadd->{'address'}} = 2;
        }
      }
    }

    ## adding connector addresses
    if (!$this->{d} && $this->{domain}) {
        $this->{d} = Domain::create($this->{domain});
    }
    if ($this->{d}) {
       if ($this->{d}->getPref('address_fetcher') eq 'ldap') {
          require SMTPAuthenticator::LDAP;
          my $serverstr =  $this->{d}->getPref('auth_server');
          my $server = $serverstr;
          my $port = 0;
          if ($serverstr =~ /^(\S+):(\d+)$/) {
            $server = $1;
            $port = $2;
          }
          my $auth = SMTPAuthenticator::LDAP::create($server, $port, $this->{d}->getPref('auth_param'));
          my @ldap_addesses;
          if ($this->{username} ne '') {
            @ldap_addesses = $auth->fetchLinkedAddressesFromUsername($this->{username});
          } elsif (scalar(keys %{$this->{addresses}})) {
            my @keys = keys %{$this->{addresses}};
            @ldap_addesses = $auth->fetchLinkedAddressesFromEmail(pop(@keys));
          }
          if (!@ldap_addesses ) {
              ## check for errors
              if ($auth->{'error_text'} ne '') {
                 #print STDERR "Got ldap error: ".$auth->{'error_text'}."\n";
              }
          } else {
              foreach my $add (@ldap_addesses) {
                  $this->{addresses}->{$add} = 1;
              }
          }
       }
    }

    return keys %{$this->{addresses}};
}

sub getMainAddress {
    my $this = shift;

    if (!keys %{$this->{addresses}}) {
        $this->getAddresses();
    }
    my $first;
    foreach my $add (keys %{$this->{addresses}}) {
        if (!$first) {
            $first = $add;
        }
        if ($this->{addresses}->{$add} == 2) {
            return $add;
        }
    }
    if (!$this->{d} && $this->{domain}) {
        $this->{d} = Domain::create($this->{domain});
    }
    if ($this->{d}->getPref('address_fetcher') eq 'at_login') {
        if ($this->{username} =~ /\@/) {
            return $this->{username};
        }
        if ($this->{username} && $this->{domain}) {
            return $this->{username}.'@'.$this->{domain};
        }
    }
    if ($first) {
        return $first;
    }
    return undef;
}

sub getPref {
    my $this = shift;
    my $pref = shift;

    if (keys %{$this->{prefs}} < 1) {
        $this->loadPrefs();
    }

    if (defined($this->{prefs}->{$pref}) && $this->{prefs}->{$pref} ne 'NOTSET') {
        return $this->{prefs}->{$pref};
    }
    ## find out if domain has pref
    if (!$this->{d} && $this->{domain}) {
        $this->{d} = Domain::create($this->{domain});
        if ($this->{d}) {
          return $this->{d}->getPref($pref);
        }
    }
    if ($this->{d}) {
        return $this->{d}->getPref($pref);
    }
    return undef;
}

sub loadPrefs {
    my $this = shift;

    if (!$this->{id}) {
        return 0;
    }
    if (!$this->{db}) {
        require DB;
        $this->{db} = DB::connect('slave', 'mc_config', 0);
    }

    if ($this->{db} && $this->{db}->ping()) {
        my $query = "SELECT p.* FROM user u, user_pref p WHERE u.pref=p.id AND u.id=".$this->{id};
        my %res = $this->{db}->getHashRow($query);
        if ( !%res || !$res{id} ) {
            return 0;
        } 
        foreach my $p (keys %res) {
            $this->{prefs}->{$p} = $res{$p};
        }
    }
}

