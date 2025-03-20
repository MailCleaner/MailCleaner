#!/usr/bin/env perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
#   Copyright (C) 2023 John Mertz <git@john.me.tz>
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

package module::MCSetup;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;
require DialogFactory;
BEGIN {
    if ($0 =~ m/(\S*)\/\S+\.pl/) {
        my $path = $1."/../../lib";
        unshift (@INC, $path);
    }
    require ReadConfig;
}

our @ISA = qw(Exporter);
our @EXPORT = qw(get ask do);
our $VERSION = 1.0;
my $conf = ReadConfig::getInstance() if (-e '/etc/mailcleaner.conf');

sub get
{
    my $this = {
        dfact => DialogFactory::get('InLine'),
        logfile => '/tmp/mailcleaner_install.log',
        conffile => '/etc/mailcleaner.conf',
        config_variables => {
            'SRCDIR' => '/usr/mailcleaner',
            'VARDIR' => '/var/mailcleaner',
            'HOSTID' => undef,
            'DEFAULTDOMAIN' => '',
            'ISMASTER' => 'Y',
            'MYMAILCLEANERPWD' => undef,
            'HELONAME' => undef,
            'MASTERIP' => undef,
            'MASTERPWD' => undef,
        },
        install_variables => {
            'WEBADMINPWD' => undef,
            'ORGANIZATION' => 'Anonymous',
            'MCHOSTNAME' => 'mailcleaner',
            'CLIENTTECHMAIL' => 'root@localhost',
        },
        default_configs => {
            'HOSTID' => 1,
            'MYMAILCLEANERPWD' => 'MCPassw0rd',
            'HELONAME' => '',
            'MASTERIP' => '127.0.0.1',
            'MASTERPWD' => 'MCPassw0rd',
        }
    };
    # Load variables from existing config file, if set
    if (defined($conf)) {
        foreach (keys(%{$this->{config_variables}})) {
            $this->{config_variables}->{$_} = $conf->getOption($_) if ($conf->getOption($_));
        }
        foreach (keys(%{$this->{install_variables}})) {
            $this->{install_variables}->{$_} = $conf->getOption($_) if ($conf->getOption($_));
        }
    }
    # Override with ENV variables, if set
    foreach (keys(%{$this->{config_variables}})) {
        $this->{config_variables}->{$_} = $ENV{$_} if (defined($ENV{$_}));
    }
    $this->{install_variables}->{'CLIENTTECHMAIL'} = 'support@'.$this->{config_variables}->{'DEFAULTDOMAIN'} if ($this->{config_variables}->{'DEFAULTDOMAIN'} ne '');
    foreach (keys(%{$this->{install_variables}})) {
        $this->{install_variables}->{$_} = $ENV{$_} if (defined($ENV{$_}));
    }
    # Default hostname unless defined above
    my $hostname = `hostname`;
    chomp($hostname);
    $this->{config_variables}->{HELONAME} = $hostname unless (defined($this->{'config_variables'}->{'HELONAME'} && $this->{'config_variables'}->{'HELONAME'} ne ''));
    $this->{install_variables}->{MCHOSTNAME} = $hostname unless (defined($this->{'install_variables'}->{'MCHOSTNAME'} && $this->{'install_variables'}->{'MCHOSTNAME'} ne ''));

    bless $this, 'module::MCSetup';
    return $this;
}

sub do($this)
{
    my @basemenu = (
        'Host ID', 
        'Web admin password', 
        'Database password', 
        'Admin details',
        'Apply configuration', 
        'Exit'
    );
    my $currentstep = 1;
    my $error = '';

    while ($this->doMenu(\@basemenu, \$currentstep, \$error)) {
    }
}

sub doMenu($this, $basemenu, $currentstep, $error)
{
    my $dlg = $this->{'dfact'}->getListDialog();
    $dlg->build($$error.'MailCleaner setup', $basemenu, $$currentstep, 1);
    $$error = '';

    my $res = $dlg->display();
    return 0 if $res eq 'Exit';

    if ($res eq 'Host ID') {
        $this->{'config_variables'}->{'HOSTID'} = $this->hostID();
        $$currentstep = 2;
        return 1;
    }

    if ($res eq 'Web admin password') {
        $this->{'install_variables'}->{'WEBADMINPWD'} = $this->webAdminPWD();
        $$currentstep = 3;
        return 1;
    }

    if ($res eq 'Database password') {
        $this->{'config_variables'}->{'MYMAILCLEANERPWD'} = $this->databasePWD();
        $this->{'config_variables'}->{'MASTERPWD'} = $this->{'config_variables'}->{'MYMAILCLEANERPWD'};
        $$currentstep = 4;
        return 1;
    }

    if ($res eq 'Admin details') {
    $this->{'install_variables'}->{'ORGANIZATION'} = $this->organization();
    $this->{'install_variables'}->{'CLIENTTECHMAIL'} = $this->supportEmail();
        $$currentstep = 5;
        return 1;
    }

    if ($res eq 'Apply configuration') {
        my $ret = $this->applyConfiguration;
        if ($ret) {
            if ($ret == 255) {
                $$error = "Fatal error: Failed to open $this->{'conffile'} for writing. Quitting.\n";
            } elsif ($ret == 254) {
                $$error = "Setup abandoned\n";
            } else {
                $$error = "Missing necessary variable. Please follow all earlier steps before applying.\n";
                $$currentstep = $ret;
            } 
        }
        return 0;
    }

    die "Invalid selection: $res\n";
}

sub hostID($this)
{
    my $dlg = $this->{'dfact'}->getSimpleDialog();
    my $suggest = $this->{'config_variables'}->{'HOSTID'} //= $this->{'default_configs'}->{'HOSTID'};
    $suggest = 1 if ($suggest eq '');
    $dlg->build('Enter the unique ID of this MailCleaner in your infrastucture', $suggest);
    return $dlg->display();
}

sub webAdminPWD($this)
{
    my $pass1 = '-';
    my $pass2 = '';
    my $pdlg = $this->{'dfact'}->getPasswordDialog();
    my $suggest = $this->{'install_variables'}->{'WEBADMINPWD'};
    $suggest //= 'SAME AS DATABASE PASSWORD' if (defined($this->{'config_variables'}->{'MYMAILCLEANERPWD'}) && $this->{'config_variables'}->{'MYMAILCLEANERPWD'} ne 'MCPassw0rd');
    unless (defined($suggest)) {
        $suggest = 'RANDOM: '.`pwgen -N 1 16`;
        chomp($suggest);
    }
    while ( $pass1 ne $pass2 || $pass1 eq "" || $pass1 eq "MCPassw0rd" ) {
        print "Password mismatch, please try again.\n" unless ($pass2 eq '');
        print "Password is require, please try again.\n" if ($pass1 eq '');
        if (defined($suggest)) {
            $pdlg->build('Enter the admin user password for the web interface', $suggest);
        } else {
            $pdlg->build('Enter the admin user password for the web interface', '');
        }
        $pass1 = $pdlg->display();
        if ($pass1 eq 'MCPassw0rd') {
            print "Cannot use default password. Please try something else.\n";
        next;
        }
    last if (defined($suggest) && $pass1 eq $suggest && $pass1 ne '');
        $pdlg->build('Please confirm the admin user password', '');
        $pass2 = $pdlg->display();
    }
    $pass1 =~ s/RANDOM: // if ($pass1 =~ m/RANDOM: /);
    return $pass1;
}

sub databasePWD($this)
{
    my $pass1 = '-';
    my $pass2 = '';
    my $pdlg = $this->{'dfact'}->getPasswordDialog();
    my $suggest = $this->{'config_variables'}->{'MYMAILCLEANERPWD'};
    $suggest = undef if ($suggest eq $this->{'default_configs'}->{'MYMAILCLEANERPWD'});
    $suggest //= 'SAME AS WEB ADMIN PASSWORD' if (defined($this->{'install_variables'}->{'WEBADMINPWD'}) && $this->{'install_variables'}->{'WEBADMINPWD'} ne 'MCPassw0rd');
    unless (defined($suggest)) {
        $suggest = 'RANDOM: '.`pwgen -N 1 16`;
        chomp($suggest);
    }
    while ( $pass1 ne $pass2 || $pass1 eq "" || $pass1 eq "MCPassw0rd" ) {
        print "Password mismatch, please try again.\n" unless ($pass2 eq '');
        print "Password is require, please try again.\n" if ($pass1 eq '');
        if (defined($suggest)) {
            $pdlg->build('Enter the password for the local database', $suggest);
    } else {
            $pdlg->build('Enter the password for the local database', '');
        }
        $pass1 = $pdlg->display();
        if ($pass1 eq 'MCPassw0rd') {
            print "Cannot use default password. Please try something else.\n";
        next;
        }
    last if (defined($suggest) && $pass1 eq $suggest && $pass1 ne '');
        $pdlg->build('Please confirm the local database password', '');
        $pass2 = $pdlg->display();
    }
    $pass1 = $this->{'install_variables'}->{'WEBADMINPWD'} if ($pass1 eq 'SAME AS WEB ADMIN PASSWORD');
    $pass1 =~ s/RANDOM: // if ($pass1 =~ m/RANDOM: /);
    return $pass1;
}

sub organization($this)
{
    my $dlg = $this->{'dfact'}->getSimpleDialog();
    my $suggest = $this->{'install_variables'}->{'ORGANIZATION'} || 'Anonymous';
    $dlg->build('Enter your organization name', $suggest);
    $this->{'install_variables'}->{'ORGANIZATION'} = $dlg->display();
}

sub supportEmail($this)
{
    my $dlg = $this->{'dfact'}->getSimpleDialog();
    my $suggest = $this->{'install_variables'}->{'CLIENTTECHMAIL'} || 'root@localhost';
    $dlg->build('Support email address for your users', $suggest);
    $this->{'install_variables'}->{'CLIENTTECHMAIL'} = $dlg->display();
}

sub checkVariables($this)
{
    return 1 unless (defined($this->{'config_variables'}->{'HOSTID'}) && $this->{'config_variables'}->{'HOSTID'} ne '');
    return 2 unless (defined($this->{'install_variables'}->{'WEBADMINPWD'}) && $this->{'install_variables'}->{'WEBADMINPWD'} ne '' && $this->{'install_variables'}->{'WEBADMINPWD'} ne 'MCPassw0rd');
    return 3 unless (defined($this->{'config_variables'}->{'MYMAILCLEANERPWD'}) && $this->{'config_variables'}->{'MYMAILCLEANERPWD'} ne '' && $this->{'config_variables'}->{'MYMAILCLEANERPWD'} ne 'MCPassw0rd');
    return 3 unless (defined($this->{'config_variables'}->{'MASTERPWD'}) && $this->{'config_variables'}->{'MASTERPWD'} ne 'MCPassw0rd');
    return 4 unless (defined($this->{'install_variables'}->{'ORGANIZATION'}) && $this->{'install_variables'}->{'ORGANIZATION'} ne '');
    return 4 unless (defined($this->{'install_variables'}->{'CLIENTTECHMAIL'}) && $this->{'install_variables'}->{'CLIENTTECHMAIL'} ne '');
    return 0;
}

sub writeConfig($this)
{
    if (open(my $fh, '>', $this->{'conffile'})) {
        foreach (keys(%{$this->{'config_variables'}})) {
            if (defined($this->{'config_variables'}->{$_})) {
                print $fh "$_ = $this->{'config_variables'}->{$_}\n";
            }
        }
        close($fh);
    } else {
        return 0;
    }
    return 1;
}

sub applyConfiguration($this)
{
    my $check = $this->checkVariables();
    return $check if ($check);

    my $yndlg = $this->{'dfact'}->getYesNoDialog();
    $yndlg->build('WARNING: this operation will overwrite any existing MailCleaner database, if one exists. Do you want to proceed?', 'n');

    return 254 unless ($yndlg->display());
    return 255 unless ($this->writeConfig());

    foreach (keys(%{$this->{'config_variables'}})) {
        $ENV{$_} = $this->{'config_variables'}->{$_};
    }
    foreach (keys(%{$this->{'install_variables'}})) {
        $ENV{$_} = $this->{'install_variables'}->{$_};
    }
    $this->{'install_variables'}->{'WEBADMINPWD'} = $this->{'config_variables'}->{'MYMAILCLEANERPWD'} if ($this->{'install_variables'} eq 'SAME AS DATABASE PASSWORD');
    print("Running $this->{'config_variables'}->{SRCDIR}/install/MC_prepare_dbs.sh. This will take some time. Installation logs will be saved to /tmp/mailcleaner-db-install.log\n");
    `$this->{'config_variables'}->{SRCDIR}/install/MC_prepare_dbs.sh > /tmp/mailcleaner-db-install.log 2>/dev/null`;
    print("Restarting all services...\n");
    `$this->{'config_variables'}->{SRCDIR}/etc/init.d/mailcleaner restart`;

    my $dlg = $this->{'dfact'}->getSimpleDialog();
    $dlg->clear();

    if (! -e '/var/mailcleaner/run/first-time-configuration') {
        if (open(my $fh, '>>', '/var/mailcleaner/run/first-time-configuration')) {
            print $fh '';
            close $fh;
        }
        return 0;
    }
}

sub isBootstrapped($this)
{
    # TODO: Update to look for mc-exim instead when available
    # if (-d '/opt/exim4/bin/exim') {
    if (-e '/opt/exim4/bin/exim') {
        return 1;
    }
    return 0;
}

sub isInstalled($this)
{
    if ( -e '/etc/mailcleaner.conf' ) {
        return 1;
    }
    return 0;
}

1;
