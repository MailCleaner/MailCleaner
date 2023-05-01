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

package          module::Keyboard;

require          Exporter;
require          DialogFactory;
use Storable;
use strict;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(get ask do);
our $VERSION    = 1.0;

sub get {

 my $this = {
   dlg => '',
   mapfile => "/usr/share/console/lists/keymaps/console-data.keymaps",
   mapdir => '/usr/share/keymaps/i386'
 };

 bless $this, 'module::Keyboard';
 return $this;
}

sub do {
  my $this = shift;
  my $layout = '';

  require $this->{mapfile};
  my $dfact = DialogFactory::get('InLine');
  $this->{dlg} = $dfact->getListDialog();

  my %list = %{$::keymaps->{pc}};
  my $file = $this->dolist(\%list, 'pc', \$layout);

  # Load temporary keymap
  `loadkeys $file 2>&1 > /dev/null`;
  # Load persistent keymap
  unlink("/etc/console/boottime.kmap.gz") if ( -e "/etc/console/boottime.kmap.gz");
  `ln -s $this->{mapdir}/$layout/$file.kmap.gz /etc/console/boottime.kmap.gz 2>&1 > /dev/null`;
}


sub dolist {
  my $this = shift;
  my $listh = shift;
  my $parent = shift;
  my $layout = shift;
  return $listh unless ref($listh) eq 'HASH';
  my %list = %{$listh};

  my @dlglist;
  foreach my $key (keys %list) {
    next if $key eq 'default';
    push @dlglist, $key;
  }
  my $dlg = $this->{dlg};
  $dlg->build('Make your choice ('.$parent.')', \@dlglist, 1);
  my $res = $dlg->display();
  $$layout = $res if ($$layout eq '');
  return $this->dolist($list{$res}, $res, \'skip');
}

1;
