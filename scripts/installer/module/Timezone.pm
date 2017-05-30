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

package          module::Timezone;

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
   mapdir => '/usr/share/zoneinfo/'
 };

 bless $this, 'module::Timezone';
 return $this;
}

sub do {
  my $this = shift;

  my $dfact = DialogFactory::get('InLine');
  $this->{dlg} = $dfact->getListDialog();

   my @dlglist = ('Africa', 'America', 'US time zones', 'Canada time zones', 'Asia', 'Atlantic Ocean', 'Australia', 'Europe', 'Indian Ocean', 'Pacific Ocean', 'Use System V style time zones', 'None of the above') ;
  $this->{dlg}->build('Choose your continent', \@dlglist, 1, 1);
  my $cont = $this->{dlg}->display();

  $cont = 'US' if ($cont eq 'US time zones'); 
  $cont = 'Canada' if ($cont eq 'Canada time zones');
  $cont = 'Atlantic' if ($cont eq 'Atlantic Ocean');
  $cont = 'Indian' if ($cont eq 'Indian Ocean');
  $cont = 'Pacific' if ($cont eq 'Pacific Ocean');
  $cont = 'SystemV' if ($cont eq 'Use System V style time zones');
  $cont = 'Etc' if ($cont eq 'None of the above');

  my $zone = $this->dolist($this->{mapdir}."/$cont", $cont);

  my $onlyzone = $zone;
  my $dir = $this->{mapdir};
  $onlyzone =~ s/$dir//;
  $onlyzone =~ s/^\///;

  my $cmd = "echo '$onlyzone' > /etc/timezone";
  #print "CMD: $cmd\n";
  `$cmd`;
  `rm /etc/localtime 2>&1 > /dev/null`;
  $cmd = "ln -s ".$zone."  /etc/localtime";
  `$cmd`;
  #print "RESULT: $cmd\n";
}


sub dolist {
  my $this = shift;
  my $dir = shift;
  my $parent = shift;
  return $dir unless -d $dir;

  my @dlglist;
  opendir(DIR, $dir) or return $dir;
  while(my $entry = readdir(DIR)) {
    next if $entry =~ m/\./;
    if ( -d $dir."/".$entry || -f $dir."/".$entry) {
      push @dlglist, $entry;
    }
  }
  close(DIR);
  my $dlg = $this->{dlg};
  $dlg->build('Make your choice ('.$parent.')', \@dlglist, 1);
  my $res = $dlg->display();
  return $this->dolist($dir."/".$res, $res);
}

1;
