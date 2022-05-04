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

package          module::MCInstaller;

require          Exporter;
require          DialogFactory;
use strict;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(get ask do);
our $VERSION    = 1.0;

sub get {

 my $this = {
   logfile => '/tmp/mailcleaner_install.log',
   srcdir => '/usr/mailcleaner',
   vardir => '/var/mailcleaner',
   logfile => '/tmp/mailcleaner_install.log',
   conffile => '/etc/mailcleaner.conf'
 };

 bless $this, 'module::MCInstaller';
 return $this;
}

sub do {
  my $this = shift;

  my $dfact = DialogFactory::get('InLine');
  my $dlg = $dfact->getSimpleDialog();
  my $yndlg = $dfact->getYesNoDialog();

  if ($this->isMCInstalled) {
    $yndlg->build("\nA previous installation of MailCleaner has been detected and will then be deleted!!\nAre you sure you want to continue ? ", 'yes');
    return if ($yndlg->display() eq 'no');

    ## remove any mailcleaner related cron cron
    print "\n";
    print " - Removing scheduled jobs... ";
    `crontab -l | grep -v 'mailcleaner' > /tmp/crontab.tmp`;
    `crontab /tmp/crontab.tmp`;
    print "[done]\n";

    print " - Shutting down MailCleaner services... ";
    `/etc/init.d/mailcleaner stop 2>&1 > /dev/null`;
    # make sure everything is gone
    `killall -TERM mysqld_safe mysqld httpd snmpd exim MailScanner 2>&1 > /dev/null`;
    print "[done]\n";
   
    print " - Removing MailCleaner directories... ";
    my $cmd = "rm -rf ".$this->{srcdir};
    `$cmd 2>&1 > /dev/null`; 
    $cmd = "rm -rf ".$this->{vardir}; 
    `$cmd 2>&1 > /dev/null`; 
    print "[done]\n";
  }

  $dlg->clear();
  print "MailCleaner installation\n";
  print "------------------------\n\n";

  $dlg->build('Enter the unique ID of this MailCleaner in your infrastucture', '1');
  my $hostid = '';
  while ( $hostid !~ m/^\d+$/ ) {
    print "Please enter a numeric value\n" if ! $hostid eq '';
    $hostid = $dlg->display();
  }

  #$dlg->build('Enter the default domain name', 'localdomain');
  my $domain = '';
  #while ( $domain !~ /^[-_.a-zA-Z0-9]+$/ ) {
  #  print "Please enter valid domain name\n" if ! $domain eq '';
  #  $domain = $dlg->display();
  #}

  my $pass1 = '-';
  my $pass2 = '';
  my $pdlg = $dfact->getPasswordDialog();
  while ( $pass1 ne $pass2) {
    print "Password mismatch, please try again.\n" if ! $pass2 eq ''; 
    $pdlg->build('Enter the admin user password', '');
    $pass1 = $pdlg->display();
    $pdlg->build('Please confirm the admin user password', '');
    $pass2 = $pdlg->display();
  }

  #$dlg->build('Enter the technical support email address', '');
  my $supportemail = '';
  #while ( $supportemail !~ m/^[-_.a-zA-Z0-9\@]+$/ ) {
  #  print "Please enter a valid email address.\n" if ! $supportemail eq '';
  #  $supportemail = $dlg->display();
  #}

  print "\n\nHere is your information\n";
  print "------------------------------\n\n";
  print " Host ID: $hostid\n";
  #print " Default domain: $domain\n";
  #print " Support email: $supportemail\n";

  $yndlg->build("\nProceed with installation ? ", 'yes');
  return if ($yndlg->display() eq 'no');
  $dlg->clear();

  print "Installing MailCleaner\n";
  print "----------------------\n\n";
  ## setting environment variable
  my $hostname = `hostname`;
  chomp $hostname;
  $ENV{'SRCDIR'} = $this->{srcdir};
  $ENV{'VARDIR'} = $this->{vardir};
  $ENV{'HOSTID'} = $hostid;
  $ENV{'MCHOSTNAME'} = $hostname;
  $ENV{'DEFAULTDOMAIN'} = $domain;
  $ENV{'ISSLAVE'} = 'N';
  $ENV{'ISMASTER'} = 'Y';
  $ENV{'MYROOTPWD'} = $pass1;
  $ENV{'MASTERHOST'} = '';
  $ENV{'MASTERKEY'} = '';
  $ENV{'MASTERPASSWD'} = '';
  $ENV{'WEBADMINPWD'} = $pass1;
  $ENV{'CLIENTORG'} = 'MailCleaner';
  $ENV{'CLIENTTECHMAIL'} = $supportemail;
  $ENV{'INTERACTIVE'} = 'n';
  $ENV{'LOGFILE'} = $this->{logfile};
  $ENV{'USEDEBS'} = 'Y';
  #print `/root/testenv.sh`;
 
  print " - Creating configuration file...                      ";
  open CONF, ">".$this->{conffile};
  print CONF "SRCDIR = ".$this->{srcdir}."\n";
  print CONF "VARDIR = ".$this->{vardir}."\n";
  print CONF "MCHOSTNAME = ".$hostname."\n";
  print CONF "HOSTID = ".$hostid."\n";
  print CONF "DEFAULTDOMAIN = ".$domain."\n";
  print CONF "ISMASTER = Y\n";
  close CONF;
  print "[done]\n";

  print " - Unpacking MailCleaner files...                      ";
  chdir('/root');
  if ( -d 'mailcleaner') {
    `rm -rf mailcleaner`;
  }
  if ( -f 'mailcleaner_install.tar.lzma') {
    `tar --lzma -xf mailcleaner_install.tar.lzma`;
  } else {
    `tar -xzf mailcleaner_install.tar.gz`;
  }
  if ( ! -d '/root/mailcleaner' ) {
    print "HuHo ! Cannot unpack MailCleaner archive. Maybe try to reinstall MailCleaner.\n";
    return;
  }
  my $cmd = "mv /root/mailcleaner ".$this->{srcdir};
  `$cmd 2>&1 > /dev/null`;

  $cmd = $this->{srcdir}."/install/install.sh";
  if (! -x $cmd) {
    print "HuHo ! Cannot find main installer script. Maybe try to reinstall MailCleaner\n";
    return;
  }
  print "[done]\n";

  ## run main installer
  chdir $this->{srcdir}."/install/";
  open INST, "|./install.sh";
  while (<INST>) {
   print $_;
  }
  wait;
  close INST;
  print "*** finished ! ***\n";
  $dlg->build('Press any key to continue', ''); 
  $dlg->display();
}


sub isMCInstalled {
  my $this = shift;

  if ( -d $this->{srcdir} ) {
   return 1;
  }
  return 0;
}

1;
