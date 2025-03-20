package Mail::SpamAssassin::Plugin::Botnet;

#   Copyright (C) 2003  The Regents of the University of California
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
#   The Author, John Rudd, can be reached via email at
#      jrudd@ucsc.edu
#


# Botnet - perform DNS validations on the first untrusted relay
#    looking for signs of a Botnet infected host, such as no reverse
#    DNS,  a hostname that would indicate an ISP client or domain
#    workstation, or other hosts that aren't intended to be acting as
#    a direct mail submitter outside of their own domain.

use Socket;
use Net::DNS;
use Mail::SpamAssassin::Plugin;
use strict;
use warnings;
use vars qw(@ISA);
@ISA = qw(Mail::SpamAssassin::Plugin);
my $VERSION = 0.9;


sub new {
   my ($class, $mailsa) = @_;

   $class = ref($class) || $class;
   my $self = $class->SUPER::new($mailsa);
   bless ($self, $class);

   Mail::SpamAssassin::Plugin::dbg("Botnet: version " . $VERSION);
   $self->register_eval_rule("botnet_nordns");
   $self->register_eval_rule("botnet_baddns");
   $self->register_eval_rule("botnet_ipinhostname");
   $self->register_eval_rule("botnet_clientwords");
   $self->register_eval_rule("botnet_serverwords");
   $self->register_eval_rule("botnet_soho");
   $self->register_eval_rule("botnet_client");
   $self->register_eval_rule("botnet");

   $self->{main}->{conf}->{botnet_pass_auth}		= 0;
   $self->{main}->{conf}->{botnet_pass_trusted}		= "public";
   $self->{main}->{conf}->{botnet_skip_ip}		= "";
   $self->{main}->{conf}->{botnet_pass_ip}		= "";
   $self->{main}->{conf}->{botnet_pass_domains}		= "";
   $self->{main}->{conf}->{botnet_clientwords}		= "";
   $self->{main}->{conf}->{botnet_serverwords}		= "";

   return $self;
   }


sub parse_config {
   my ($self, $opts) = @_;
   my ($temp);
   my $key = $opts->{key};
   my $value = $opts->{value};

   if ( ($key eq "botnet_pass_auth") ||
        ($key eq "botnet_pass_trusted") ) {
      Mail::SpamAssassin::Plugin::dbg("Botnet: setting $key to $value");
      $self->{main}->{conf}->{$key} = $value;
      $self->inhibit_further_callbacks();
      }
   elsif ( ($key eq "botnet_skip_ip")
        || ($key eq "botnet_pass_ip")
        || ($key eq "botnet_pass_domains")
        || ($key eq "botnet_clientwords")
        || ($key eq "botnet_serverwords") ) {

      foreach $temp (split(/\s+/, $value)) {
         if ($temp eq "=") { next ; } # not sure why that happens

         if ( ($key eq "botnet_clientwords") ||
              ($key eq "botnet_pass_domains") ||
              ($key eq "botnet_serverwords") ) {
            $temp =~ s/\^//g; # remove any carets
            $temp =~ s/\$//g; # remove any dollars
            }

         if ( ($key eq "botnet_clientwords") ||
              ($key eq "botnet_serverwords") ) {
            $temp = '(\b|\d)' . $temp . '(\b|\d)';
            }

         if (($key eq "botnet_pass_domains") && ($temp !~ /^\\(\.|A)/)) {
            $temp = '(\.|\A)' . $temp;
            }
         
         if ($temp eq "") {
            # don't add empty terms
            next;
            }

         if ($key eq "botnet_pass_domains") {
            # anchor each domain to end of string
            $temp .= '$';
            }

         Mail::SpamAssassin::Plugin::dbg("Botnet: adding " . $temp
                                         . " to $key");

         if ($self->{main}->{conf}->{$key} ne "") {
            $self->{main}->{conf}->{$key} =
                          $self->{main}->{conf}->{$key} . "|(" . $temp . ")";
            }
         else {
            $self->{main}->{conf}->{$key} = "(" . $temp . ")";
            }
         }
      $self->inhibit_further_callbacks();
      }
   else {
      return 0;
      }
   return 1;
   }
 

sub _botnet_get_relay {
   my ($self, $pms) = @_;
   my $msg = $pms->get_message();
   my @untrusted = @{$msg->{metadata}->{relays_untrusted}};
   my @trusted = @{$msg->{metadata}->{relays_trusted}};
   my ($relay, $rdns, $ip, $auth, $tmp, $iaddr, $hostname, $helo);
   my $skip_ip = $self->{main}->{conf}->{botnet_skip_ip};
   my $pass_ip = $self->{main}->{conf}->{botnet_pass_ip};
   my $pass_trusted = $self->{main}->{conf}->{botnet_pass_trusted};
   my $pass_auth = $self->{main}->{conf}->{botnet_pass_auth};
   my $pass_domains = '(?:' . $self->{main}->{conf}->{botnet_pass_domains} .
                      ')';
   my $private_ips = '(?:^127\..*$|^10\..*$|^172\.1[6789]\..*$|' .
                     '^172\.2[0-9]\..*$|^172\.3[01]\..*$|^192\.168\..*$)';

   # if there are any trusted relays, AND $pass_trusted is set to
   # public, private, or any,  or $pass_auth is true, then check the
   # trusted relays for any pass conditions
   if ( (defined($trusted[0]->{ip})) && 
        (($pass_auth) ||
         ($pass_trusted eq "any") ||
         ($pass_trusted eq "public") ||
         ($pass_trusted eq "private")) ) {
      foreach $relay (@trusted) {
         if ($pass_trusted eq "any") {
            Mail::SpamAssassin::Plugin::dbg("Botnet: found any trusted");
            return (0, "", "", "");
            }
         elsif (($pass_auth) && ($relay->{auth} ne "")) {
            $auth = $relay->{auth};
            Mail::SpamAssassin::Plugin::dbg("Botnet: Passed auth " . $auth);
            return (0, "", "", "");
            }
         elsif ( ($pass_trusted eq "private") &&
              ($relay->{ip} =~ /$private_ips/) ) {
            Mail::SpamAssassin::Plugin::dbg("Botnet: found private trusted");
            return (0, "", "", "");
            }
         elsif ( ($pass_trusted eq "public") &&
                 ($relay->{ip} !~ /$private_ips/) ) {
            Mail::SpamAssassin::Plugin::dbg("Botnet: found public trusted");
            return (0, "", "", "");
            }
         }
      if ( ($pass_trusted eq "any") ||
           ($pass_trusted eq "public") ||
           ($pass_trusted eq "private") ) {
         # didn't find what we were looking for above
         Mail::SpamAssassin::Plugin::dbg("Botnet: " .  $pass_trusted .
                                         " trusted relays not found");
         }
      if ($pass_auth) {
         Mail::SpamAssassin::Plugin::dbg("Botnet: authenticated and" .
                                         " trusted relay not found");
         }
      }
   elsif (!defined ($trusted[0]->{ip})) {
      Mail::SpamAssassin::Plugin::dbg("Botnet: no trusted relays");
      }

   while (1) {
      $relay = shift(@untrusted);

      if (! defined ($relay)) {
         Mail::SpamAssassin::Plugin::dbg("Botnet: All skipped/no untrusted");
         return (0, "", "", "");
         }

      $ip = $relay->{ip};

      if (! defined ($ip) ) {
         Mail::SpamAssassin::Plugin::dbg("Botnet: All skipped/no untrusted");
         return (0, "", "", "");
         }
      elsif (($pass_ip ne "") && ($ip =~ /(?:$pass_ip)/)) {
         Mail::SpamAssassin::Plugin::dbg("Botnet: Passed ip $ip");
         return (0, "", "", "");
         }
      elsif (($skip_ip ne "") && ($ip =~ /(?:$skip_ip)/)) {
         Mail::SpamAssassin::Plugin::dbg("Botnet: Skipped ip $ip");
         next;
         }
      ## I think we should only look for authenticated relays in the
      ## trusted relays
      #elsif (($pass_auth) && ($relay->{auth} ne "")) {
      #   $auth = $relay->{auth};
      #   Mail::SpamAssassin::Plugin::dbg("Botnet: Passed auth " . $auth);
      #   return (0, "", "", "");
      #   }
      else {
         if ((exists $relay->{rdns}) &&
             ($relay->{rdns} ne "") &&
             ($relay->{rdns} ne "-1")) {
            # we've got this relay's RDNS
            Mail::SpamAssassin::Plugin::dbg("Botnet: get_relay good RDNS");
            $rdns = $relay->{rdns};
            }
         elsif ((exists $relay->{rdns}) && ($relay->{rdns} eq "-1")) {
            # rdns = -1, which means we set it to that, because the
            # MTA didn't include it, and then we couldn't find it on
            # lookup, which means the IP addr REALLY doesn't have RDNS
            Mail::SpamAssassin::Plugin::dbg("Botnet: get_relay -1 RDNS");
            $rdns = "";
            }
         else {
            # rdns hasn't been set in the hash, which means _either_
            # the IP addr really doesn't have RDNS, _OR_ the MTA
            # is lame, like CommuniGate Pro, and doesn't put the RDNS
            # data into the Received header.  So, we'll try to look
            # it up _one_ time, and if we don't get anything we'll
            # set the value in the hash to -1
            Mail::SpamAssassin::Plugin::dbg(
                                          "Botnet: get_relay didn't find RDNS");
            $hostname = get_rdns($ip);

            if ((defined $hostname) && ($hostname ne "")) {
               $relay->{rdns} = $hostname;
               $rdns = $hostname;
               }
            else {
               $relay->{rdns} = "-1";
               $rdns = "";
               }
            }
         $helo = $relay->{helo};

         Mail::SpamAssassin::Plugin::dbg("Botnet: IP is '$ip'");
         Mail::SpamAssassin::Plugin::dbg("Botnet: RDNS is '$rdns'");
         Mail::SpamAssassin::Plugin::dbg("Botnet: HELO is '$helo'");

         if ($rdns =~ /(?:$pass_domains)/i) {
            # is this a domain we exempt/pass?
            Mail::SpamAssassin::Plugin::dbg("Botnet: pass_domain '$rdns'");
            return(0, "", "", "");
            }

         return (1, $ip, $rdns, $helo);
         }
      }
   }
 

sub botnet_nordns {
   my ($self, $pms) = @_;
   my ($code, $ip, $helo);
   my $hostname = "";

   Mail::SpamAssassin::Plugin::dbg("Botnet: checking NORDNS");

   ($code, $ip, $hostname, $helo) = $self->_botnet_get_relay($pms);
   unless ($code) {
      Mail::SpamAssassin::Plugin::dbg("Botnet: NORDNS skipped");
      return 0;
      }
   
   if ($hostname eq "") {
      # the IP address doesn't have a PTR record
      $pms->test_log("botnet_nordns,ip=$ip");
      Mail::SpamAssassin::Plugin::dbg("Botnet: NORDNS hit");
      return (1);
      }
   Mail::SpamAssassin::Plugin::dbg("Botnet: NORDNS miss");
   return (0);
   }


sub botnet_baddns {
   my ($self, $pms) = @_;
   my ($code, $ip, $helo);
   my $hostname = "";

   Mail::SpamAssassin::Plugin::dbg("Botnet: checking BADDNS");

   ($code, $ip, $hostname, $helo) = $self->_botnet_get_relay($pms);
   unless ($code) {
      Mail::SpamAssassin::Plugin::dbg("Botnet: BADDNS skipped");
      return 0;
      }

   if ($hostname eq "") {
      # covered by NORDNS
      Mail::SpamAssassin::Plugin::dbg("Botnet: BADDNS miss");
      return (0);
      }
   elsif (check_dns($hostname, $ip, "A", -1)) {
      # resolved the hostname
      Mail::SpamAssassin::Plugin::dbg("Botnet: BADDNS miss");
      return (0);
      }
   else {
      # failed to resolve the hostname
      $pms->test_log("botnet_baddns,ip=$ip,rdns=$hostname");
      Mail::SpamAssassin::Plugin::dbg("Botnet: BADDNS hit");
      return (1);
      }
   }


sub botnet_ipinhostname {
   my ($self, $pms) = @_;
   my ($code, $ip, $helo);
   my $hostname = "";

   Mail::SpamAssassin::Plugin::dbg("Botnet: checking IPINHOSTNAME");

   ($code, $ip, $hostname, $helo) = $self->_botnet_get_relay($pms);
   unless ($code) {
      Mail::SpamAssassin::Plugin::dbg("Botnet: IPINHOSTNAME skipped");
      return 0;
      }

   if ($hostname eq "") {
      # covered by NORDNS
      Mail::SpamAssassin::Plugin::dbg("Botnet: IPINHOSTNAME miss");
      return (0);
      }
   elsif (check_ipinhostname($hostname, $ip))  {
      $pms->test_log("botnet_ipinhosntame,ip=$ip,rdns=$hostname");
      Mail::SpamAssassin::Plugin::dbg("Botnet: IPINHOSTNAME hit");
      return(1);
      }
   else {
      Mail::SpamAssassin::Plugin::dbg("Botnet: IPINHOSTNAME miss");
      return (0);
      }
   }


sub botnet_clientwords {
   my ($self, $pms) = @_;
   my ($code, $ip, $helo);
   my $hostname = "";
   my $wordre = $self->{main}->{conf}->{botnet_clientwords};

   Mail::SpamAssassin::Plugin::dbg("Botnet: checking CLIENTWORDS");

   if ($wordre eq "") {
      Mail::SpamAssassin::Plugin::dbg("Botnet: CLIENTWORDS miss");
      return (0);
      }
   else {
      Mail::SpamAssassin::Plugin::dbg("Botnet: client words regexp is" .
                                      $wordre);
      }

   ($code, $ip, $hostname, $helo) = $self->_botnet_get_relay($pms);
   unless ($code) {
      Mail::SpamAssassin::Plugin::dbg("Botnet: CLIENTWORDS skipped");
      return 0;
      }

   if ($hostname eq "") {
      # covered by NORDNS
      Mail::SpamAssassin::Plugin::dbg("Botnet: CLIENTWORDS miss");
      return (0);
      }
   elsif (check_words($hostname, $wordre)) {
      # hostname contains client keywords, outside of the registered domain
      $pms->test_log("botnet_clientwords,ip=$ip,rdns=$hostname");
      Mail::SpamAssassin::Plugin::dbg("Botnet: CLIENTWORDS hit");
      return (1);
      }
   else {
      Mail::SpamAssassin::Plugin::dbg("Botnet: CLIENTWORDS miss");
      return (0);
      }
   }
   

sub botnet_serverwords {
   my ($self, $pms) = @_;
   my ($code, $ip, $helo);
   my $hostname = "";
   my $wordre = $self->{main}->{conf}->{botnet_serverwords};

   Mail::SpamAssassin::Plugin::dbg("Botnet: checking SERVERWORDS");

   if ($wordre eq "") {
      Mail::SpamAssassin::Plugin::dbg("Botnet: SERVERWORDS miss");
      return (0);
      }
   else {
      Mail::SpamAssassin::Plugin::dbg("Botnet: server words list is" .
                                      $wordre);
      }

   ($code, $ip, $hostname, $helo) = $self->_botnet_get_relay($pms);
   unless ($code) {
      Mail::SpamAssassin::Plugin::dbg("Botnet: SERVERWORDS skipped");
      return 0;
      }

   if ($hostname eq "") {
      # covered by NORDNS
      Mail::SpamAssassin::Plugin::dbg("Botnet: SERVERWORDS miss");
      return (0);
      }
   elsif (check_words($hostname, $wordre)) {
      # hostname contains server keywords outside of the registered domain
      $pms->test_log("botnet_serverwords,ip=$ip,rdns=$hostname");
      Mail::SpamAssassin::Plugin::dbg("Botnet: SERVERWORDS hit");
      return (1);
      }
   else {
      Mail::SpamAssassin::Plugin::dbg("Botnet: SERVERWORDS miss");
      return (0);
      }
   }


sub botnet_soho {
   my ($self, $pms) = @_;
   my ($code, $ip, $helo);
   my $hostname = "";
   my ($sender, $user, $domain);

   Mail::SpamAssassin::Plugin::dbg("Botnet: checking for SOHO server");

   ($code, $ip, $hostname, $helo) = $self->_botnet_get_relay($pms);
   unless ($code) {
      Mail::SpamAssassin::Plugin::dbg("Botnet: SOHO skipped");
      return 0;
      }

   if (defined ($sender = $pms->get("EnvelopeFrom"))) {
      Mail::SpamAssassin::Plugin::dbg("Botnet: EnvelopeFrom is " . $sender);
      }
   elsif (defined ($sender = $pms->get("Return-Path:addr"))) {
      Mail::SpamAssassin::Plugin::dbg("Botnet: Return-Path is " . $sender);
      }
   elsif (defined ($sender = $pms->get("From:addr"))) {
      Mail::SpamAssassin::Plugin::dbg("Botnet: From is " . $sender);
      }
   else {
      Mail::SpamAssassin::Plugin::dbg("Botnet: no sender");
      Mail::SpamAssassin::Plugin::dbg("Botnet: SOHO miss");
      return 0;
      }

   ($user, $domain) = split (/\@/, $sender);

   if ( (defined ($domain)) &&
        ($domain ne "") &&
        (check_soho($hostname, $ip, $domain, $helo)) ) {
      # looks like a SOHO mail server
      $pms->test_log("botnet_soho,ip=$ip,maildomain=$domain,helo=$helo");
      Mail::SpamAssassin::Plugin::dbg("Botnet: mail domain is " . $domain);
      Mail::SpamAssassin::Plugin::dbg("Botnet: SOHO hit");
      return 1;
      }
   elsif ( (defined($domain)) && ($domain ne "")) {
      # does not look lik a SOHO mail server
      Mail::SpamAssassin::Plugin::dbg("Botnet: mail domain is " . $domain);
      Mail::SpamAssassin::Plugin::dbg("Botnet: SOHO miss");
      return 0;
      }
   else {
      # no domain
      Mail::SpamAssassin::Plugin::dbg("Botnet: no sender domain");
      Mail::SpamAssassin::Plugin::dbg("Botnet: SOHO miss");
      return 0;
      }
   # shouldn't get here
   Mail::SpamAssassin::Plugin::dbg("Botnet: SOHO miss");
   return (0);
   }


sub botnet_client {
   my ($self, $pms) = @_;
   my ($code, $ip, $helo);
   my $hostname = "";
   my $cwordre = $self->{main}->{conf}->{botnet_clientwords};
   my $swordre = $self->{main}->{conf}->{botnet_serverwords};
   my $tests = 0;

   Mail::SpamAssassin::Plugin::dbg("Botnet: checking for CLIENT");

   ($code, $ip, $hostname, $helo) = $self->_botnet_get_relay($pms);
   unless ($code) {
      Mail::SpamAssassin::Plugin::dbg("Botnet: CLIENT skipped");
      return 0;
      }

   if (check_client($hostname, $ip, $cwordre, $swordre, \$tests)) {
      $pms->test_log("botnet_client,ip=$ip,rdns=$hostname," . $tests);
      Mail::SpamAssassin::Plugin::dbg("Botnet: CLIENT hit (" . $tests . ")");
      return 1;
      }
   else {
      $tests = "none" if ($tests eq "");
      Mail::SpamAssassin::Plugin::dbg("Botnet: CLIENT miss (" . $tests . ")");
      return 0;
      }
   }


sub botnet {
   my ($self, $pms) = @_;
   my ($code, $ip, $helo);
   my $hostname = "";
   my $cwordre = $self->{main}->{conf}->{botnet_clientwords};
   my $swordre = $self->{main}->{conf}->{botnet_serverwords};
   my ($sender, $user, $domain);
   my $tests = "";

   Mail::SpamAssassin::Plugin::dbg("Botnet: starting");

   ($code, $ip, $hostname, $helo) = $self->_botnet_get_relay($pms);
   unless ($code) {
      Mail::SpamAssassin::Plugin::dbg("Botnet: skipping");
      return 0;
      }

   if ( (defined ($sender = $pms->get("EnvelopeFrom"))) ||
        (defined ($sender = $pms->get("Return-Path:addr"))) ||
        (defined ($sender = $pms->get("From:addr"))) ) {
      # if we find a sender
      Mail::SpamAssassin::Plugin::dbg("Botnet: sender '$sender'");
      ($user, $domain) = split (/\@/, $sender);
      unless (defined $domain) { $domain = ""; }
      }
   else {
      $domain = "";
      }

   if (check_botnet($hostname, $ip, $cwordre, $swordre,
                    $domain, $helo, \$tests)) {
      if (($tests =~ /nordns/) && ($domain eq "")) {
         $pms->test_log("botnet" . $VERSION . ",ip=$ip," . $tests);
         }
      elsif ($tests =~ /nordns/) { # could use "eq", but used "=~" to be safe
         $pms->test_log("botnet" . $VERSION . ",ip=$ip,maildomain=$domain," .
                        $tests);
         }
      elsif ($domain eq "") {
         $pms->test_log("botnet" . $VERSION . ",ip=$ip,rdns=$hostname," .
                        $tests);
         }
      else {
         $pms->test_log("botnet" . $VERSION . ",ip=$ip,rdns=$hostname," .
                        "maildomain=$domain," . $tests);
         }
      Mail::SpamAssassin::Plugin::dbg("Botnet: hit (" . $tests . ")");
      return 1;
      }
   else {
      $tests = "none" if ($tests eq "");
      Mail::SpamAssassin::Plugin::dbg("Botnet: miss (" . $tests . ")");
      return 0;
      }
   }


sub check_client {
   my ($hostname, $ip, $cwordre, $swordre, $tests) = @_;
   my $iphost = check_ipinhostname($hostname, $ip);
   my $cwords = check_words($hostname, $cwordre);

   if (defined ($tests)) {
      if ($iphost && $cwords) { $$tests = "ipinhostname,clientwords"; }
      elsif ($iphost) { $$tests = "ipinhostname"; }
      elsif ($cwords) { $$tests = "clientwords"; }
      else { $$tests = ""; }
      }

   if ( ($iphost || $cwords) &&     # only run swordsre check if necessary
        (check_words($hostname, $swordre)) ) {
      if (defined ($tests)) { $$tests = $$tests . ",serverwords"; }
      return 0;
      }
   elsif ($iphost || $cwords) {
      return 1;
      }
   else {
      return 0;
      }
   }


sub check_botnet {
   my ($hostname, $ip, $cwordre, $swordre, $domain, $helo, $tests) = @_;
   my ($baddns, $client, $temp);

   if ($hostname eq "") {
      if (defined ($tests)) { $$tests = "nordns"; }
      return 1;
      }

   $baddns = ! (check_dns($hostname, $ip, "A", -1));

   $client = check_client($hostname, $ip, $cwordre, $swordre, \$temp);

   if (defined ($tests)) {
      if ($baddns && $client) { $$tests = "baddns,client," . $temp; }
      elsif ($baddns) { $$tests = "baddns"; }
      elsif ($client) { $$tests = "client," . $temp; }
      else { $$tests = ""; }
      }

   # if the above things triggered, check for soho mail server
   if ( ($baddns || $client) &&  # only run soho check if necessary
        (check_soho($hostname, $ip, $domain, $helo)) ) {
      # looks like a SOHO mail server
      if (defined ($tests)) { $$tests = $$tests . ",soho"; }
      return 0;
      }
   elsif ($baddns || $client) {
      return 1;
      }
   else {
      return 0;
      }
   }


sub check_soho {
   my ($hostname, $ip, $domain, $helo) = @_;

   if ((defined $domain) && ($domain ne "")) {
      if ( (defined ($hostname)) && (lc($hostname) eq lc($domain)) ) {
         # if the mail domain is the hostname, and the hostname looks
         # like a botnet (or we wouldn't have gotten here), then it's
         # probably a botnet attempting to abuse the soho exemption
         return 0;
         }
      elsif (check_dns($domain, $ip, "A", 5)) {
         # we only check 5 because we expect a SOHO to not have a huge
         # round-robin DNS A record
         return (1);
         }
      # I don't like the suggested HELO check, because the HELO string is
      # within the botnet coder's control, and thus cannot be relied upon,
      # so I have commented out the code for it.  I have left it here, its
      # head upon a pike, as a warning, and so everyone knows it wasn't
      # an oversight.
      # 0.8 update: I give an exemption above based on the mail domain,
      # and the botnet coder has as much control over that as they do
      # over the HELO string.  So, under the same circumstances (HELO !=
      # Hostname) I'll let the HELO string act in the same capacity as
      # the mail domain.
      elsif ( (defined $helo) && (defined $hostname) &&
              (lc($hostname) ne lc($helo)) &&
              ($helo ne "") &&
              (check_dns($helo, $ip, "A", 5)) ) {
         # we only check 5 because we expect a SOHO to not have a huge
         # round-robin DNS A record
         return (1);
         }
      elsif (check_dns($domain, $ip, "MX", 5)) {
         # we only check 5 because we expect a SOHO to not have a huge
         # number of MX hosts
         return (1);
         }
      return (0);
      }
   else {
      return (0);
      }
   # shouldn't get here
   return (0);
   }


sub check_dns {
   my ($name, $ip, $type, $max) = @_;
   my ($resolver, $query, $rr, $i, @a);

   if ( (!defined $name) ||
        ($name eq "") ||
        (!defined $ip) ||
        (!defined $type) ||
        ($type !~ /^(?:A|MX)$/) ||
        (!defined $max) ||
        ($max !~ /^-?\d+$/) ) {
      return (0);
      }

   if ($ip !~ /^\d+\.\d+\.\d+\.\d+$/) {
      if ($ip =~ /^[0-9a-f:]{3,39}$/i) {
         $type = "AAAA" if $type eq "A";
         }
      else {
         return (0);
         }
      }

	$resolver = Net::DNS::Resolver->new(
               udp_timeout => 5,
               tcp_timeout => 5,
               retrans => 0,
               retry => 1,
               persistent_tcp => 0,
               persistent_udp => 0,
               dnsrch => 0,
               defnames => 0,
        );

	if ($query = $resolver->send($name, $type)) {
	if ($query->header->rcode eq 'SERVFAIL') {
	 	# avoid FP due to timeout or other error
 		return (-1);
 	}
	 if ($query->header->rcode eq 'NXDOMAIN') {
 		# found no matches
 		return (0);
 	}
 # check for matches
      $i = 0;
      foreach $rr ($query->answer()) {
         $i++;
         if (($max != -1) && ($i >= $max)) {
            # max == -1 means "check all of the records"
            # $ip isn't in the first $max A records for $name
            return(0);
            } 
         elsif (($type eq "A") && ($rr->type eq "A")) {
            if ($rr->address eq $ip) {
               # $name resolves back to this ip addr
               return(1);
               }
            }
         elsif (($type eq "AAAA") && ($rr->type eq "AAAA")) {
            if (expand_ipv6($rr->address) eq expand_ipv6($ip)) {
               # $name resolves back to this ip addr
               return(1);
               }
            }
         elsif (($type eq "MX") && ($rr->type eq "MX")) {
            if (check_dns($rr->exchange, $ip, "A", $max)) {
               # found $ip in the first MX hosts for $domain
               return(1);
               }
            }
         }
      # found no matches
      return(0);
      }
   else {
	 # avoid FP due to timeout or other error
	 return (-1);
      }

   # can't resolve an empty name nor ip that doesn't look like an address
   return (0);
   }


sub expand_ipv6 {
   # fully pad out an ipv6 address, so it can be compared to another address
   my ($ip) = @_;

   $ip = lc "0$ip";
   $ip =~ s/::$/::0/;
   if ((my $len = () = split(/:/, $ip, -1)) < 8) {
      $ip =~ s/::/":" . "0:" x (9 - $len)/e;
      }
   $ip = join(":", map {substr "0000$_", -4} split(/:/, $ip));
   return $ip;
   }


sub check_ipinhostname {
   # check for 2 octets of the IP address within the hostname, in
   # hexidecimal or decimal format, with zero padding or not, and with
   # optional spacing or not.  And, for decimal format, check for
   # combined decimal values (ex: 3rd octet * 256 + 4th octet)
   my ($name, $ip) = @_;
   my ($a, $b, $c, $d, $e, $f, $g, $h, $i, $j, $k, $l, $m, $n);
   
   unless ( (defined ($name)) && ($name ne "") ) { return 0; }

   unless ($ip =~ /^\d+\.\d+\.\d+\.\d+$/) { return 0; }

   ($a, $b, $c, $d) = split(/\./, $ip); # decimal octets

   # permutations of combined decimal octets into single decimal values
   $e = ($a * 256 * 256 * 256) + ($b * 256 * 256)
      + ($c * 256) + $d;                           # all 4 octets
   $f = ($a * 256 * 256) + ($b * 256) + $c;        # first 3 octets
   $g = ($b * 256 * 256) + ($c * 256) + $d;        # last 3 octets
   $h = ($a * 256) + $b;                           # first 2 octets
   $i = ($b * 256) + $c;                           # middle 2 octets
   $j = ($c * 256) + $d;                           # last 2 octets

   # hex versions of the ip address octets, in lower case
   # we don't need combined hex octets, as they'll
   # just look like sequential individual octets
   $k = sprintf("%02x", $a);                       # first octet
   $l = sprintf("%02x", $b);                       # second octet
   $m = sprintf("%02x", $c);                       # third octet
   $n = sprintf("%02x", $d);                       # fourth octet

   #$k = lc (sprintf("%02x", $a));                  # first octet
   #$l = lc (sprintf("%02x", $b));                  # second octet
   #$m = lc (sprintf("%02x", $c));                  # third octet
   #$n = lc (sprintf("%02x", $d));                  # fourth octet
   #
   #$name = lc ($name); # so that we're all lower case

   return check_words($name, "$a.*$b|$b.*$c|$c.*$d|$d.*$c|$c.*$b|$b.*$a|" .
                             "$n.*$m|$m.*$l|$l.*$k|$k.*$l|$l.*$m|$m.*$n|" .
                             "$e|$f|$g|$h|$i|$j");

   #"(?:$a.*$b|$b.*$c|$c.*$d|$n.*$m|$m.*$l|$l.*$k|$k.*$l|$l.*$m|" .
   #          "$m.*$n|$e|$f|$g|$h|$i|$j)" . ".*\..+\..+$';

   #if ( ($name =~ /(?:$a.*$b|$b.*$c|$c.*$d).*\..+\..+$/) ||
   #     ($name =~ /(?:$d.*$c|$c.*$b|$b.*$a).*\..+\..+$/) ||
   #     ($name =~ /(?:$n.*$m|$m.*$l|$l.*$k).*\..+\..+$/) ||
   #     ($name =~ /(?:$k.*$l|$l.*$m|$m.*$n).*\..+\..+$/) ||
   #     ($name =~ /(?:$e|$f|$g|$h|$i|$j).*\..+\..+$/   ) ) {
   #   # hostname contains two or more octets of its own IP addr
   #   # in hex or decimal form, with or w/o leading 0's or separators
   #   # but don't check in the tld nor registered domain
   #   # probably a spambot since this is an untrusted relay
   #   return(1);
   #   }
   #
   #return(0);
   }


sub check_words {
   # check for words outside of the top 2 levels of the hostname
   my ($name, $wordre) = @_;
   my $wordexp = '(' . $wordre . ')\S*\.\S+\.\S+$';

   return check_hostname($name, $wordexp);
   #if (($name ne "") && ($wordre ne "") && ($name =~ /(?:$wordexp)/i) ) {
   #   return (1);
   #   }
   #return (0);
   }


sub check_hostname {
   # check for an expression within the entire hostname
   my ($name, $regexp) = @_;
   
   if (($name ne "") && ($regexp ne "") && ($name =~ /(?:$regexp)/i) ) {
      return (1);
      }
   return (0);
   }


sub get_rdns {
   my ($ip) = @_;
   my ($query, @answer, $rr);
   my $resolver = Net::DNS::Resolver->new(
               udp_timeout => 5,
               tcp_timeout => 5,
               retrans => 0,
               retry => 1,
               persistent_tcp => 0,
               persistent_udp => 0,
               dnsrch => 0,
               defnames => 0,
       );

   my $name = "";

   if ($query = $resolver->query($ip, 'PTR', 'IN')) {
      @answer = $query->answer();
      #if ($answer[0]->type eq "PTR") {
      #   # just return the first one, even if it returns many
      #   $name = $answer[0]->ptrdname();
      #   }
      
      # just return the first PTR record, even if it returns many
      foreach $rr (@answer) {
         if ($rr->type eq "PTR") {
            return ($rr->ptrdname());
            }
         }
      }
   return "";
   }


sub get_version {
   return $VERSION;
   }


1;
