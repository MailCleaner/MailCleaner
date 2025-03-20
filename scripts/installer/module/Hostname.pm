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

package module::Hostname;

use v5.36;
use strict;
use warnings;
use utf8;

require Exporter;
require DialogFactory;
use strict;

our @ISA = qw(Exporter);
our @EXPORT = qw(get ask do);
our $VERSION = 1.0;

sub get
{
    my $this = {
        hostnamefile => "/etc/hostname",
        hostsfile => "/etc/hosts"
    };

    bless $this, 'module::Hostname';
    return $this;
}

sub do($this)
{
    my $dfact = DialogFactory::get('InLine');
    my $dlg = $dfact->getSimpleDialog();
    $dlg->clear();

    my $current = `hostnamectl hostname`;
    chomp($current);
    $current //= 'mailcleaner';
    $dlg->build('Enter the new hostname', $current);
    my $name = $dlg->display();

    if ($name =~ m/^[-a-zA-Z0-9_.]+$/) {
        `hostnamectl hostname $name`;
        `echo $name > ".$this->{hostnamefile}`;
        `sed -i '/127\.0\.0\.1/d' $this->{hostsfile}`;
        if ($name =~ m/^(\w+)\..*/) {
            $name .= " $1";
        }
        `echo 127.0.0.1 localhost $name >> $this->{hostsfile}`;
        `echo "UPDATE httpd_config SET servername = '$name';" | /usr/mailcleaner/bin/mc_mysql -m mc_config`;
        `sed -i -r 's/(MCHOSTNAME *= *).*/\\1$name/' /etc/mailcleaner.conf`;
        `/usr/mailcleaner/etc/init.d/apache restart`;
    } else {
        print("Invalid hostname: $name\n");
    }
}

1;
