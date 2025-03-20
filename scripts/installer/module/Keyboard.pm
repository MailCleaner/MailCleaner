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

package module::RootPassword;

use v5.36;
use strict;
use warnings;
use utf8;

package module::Keyboard;

require Exporter;
require DialogFactory;
use Storable;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(get ask do);
our $VERSION    = 1.0;

my @levels = ( 'layout', 'language', 'variant', 'keymap' );

sub get
{
    my $this = {
        dlg => '',
        mapfile => "/usr/share/console/lists/keymaps/console-data.keymaps",
        mapdir => '/usr/share/keymaps/i386'
    };

    bless $this, 'module::Keyboard';
    return $this;
}

sub do($this)
{
    require $this->{mapfile};
    my $dfact = DialogFactory::get('InLine');
    $this->{dlg} = $dfact->getListDialog();

    my %list = %{$::keymaps->{pc}};

    my $layout = '';
    my $file = $this->dolist(\%list, 'pc', \$layout);

    # Load temporary keymap
    `loadkeys $file 2>&1 > /dev/null`;
    # Load persistent keymap
    `ln -s $this->{mapdir}/$layout/$file.kmap.gz /etc/console/boottime.kmap.gz 2>&1 > /dev/null`;
}


sub dolist($this, $listh, $parent, $layout, $depth=-1)
{
    $depth++;
    return $listh unless ref($listh) eq 'HASH';
    my %list = %{$listh};

    my @dlglist;
    my $default = $list{'default'} || '';
    my $standard = 1 if (defined($list{'Standard'}));
    foreach my $key (sort(keys %list)) {
        if ($key eq 'default' || $key eq 'Standard') {
            next;
        } elsif ($default ne '' && $key eq $default) {
            next;
        } else {
            push @dlglist, $key;
        }
    }
    unshift(@dlglist, 'Standard') if ($standard);
    unshift(@dlglist, $default) if ($default ne '');

    return $this->dolist($list{$dlglist[0]}, $dlglist[0], \'skip', $depth) if (scalar(@dlglist) == 1);
    my $dlg = $this->{dlg};
    $dlg->build('Select '.$levels[$depth].':', \@dlglist, 1, 1);
    my $res = $dlg->display();
    $$layout = $res if ($$layout eq '');
    return $this->dolist($list{$res}, $res, \'skip', $depth);
}

1;
