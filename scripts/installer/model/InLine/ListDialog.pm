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

package          model::InLine::ListDialog ;
require          Exporter;
use Term::ReadKey;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(build display);
our $VERSION    = 1.0;

sub get {
  
  my $text = '';
  my $default = '';

  my $this =  {
    text => $text,
    list => '',
    default => $default,
    keeporder => 0
  };

  bless $this, "model::InLine::ListDialog";
  return $this;
}

sub build {
  my $this = shift;
  my $text = shift;
  my $listh = shift;
  my $default = shift;
  my $keeporder = shift;

  if (! defined($keeporder)) {
    $keeporder = 0;
  }
  $this->{text} = $text;
  $this->{default} = $default;
  $this->{list} = $listh;
  $this->{keeporder} = $keeporder;
  
  return $this;
}

sub clear {
  my $this = shift;

  system('clear');
}

sub display {
  my $this = shift;

  system('clear');
  print $this->{text}."\n";
  for (my $j=0; $j<length($this->{text}); $j++) {
    print "-";
  }
  print "\n";

  my $nbln = 15;
  my @llist = @{$this->{list}};
  my @slist;
  if ($this->{keeporder} < 1) {
    @slist = sort @llist;
  } else {
    @slist = @llist;
  }
  my $i = 0;
  foreach my $el (@slist ) {
    last if $i >= $nbln;
    my $str;
    if (defined($slist[$i+2*$nbln])) {
      $str = sprintf "  (%2d) %-20s (%2d) %-20s (%2d) %-20s\n", $i+1, $el, $i+$nbln+1, $slist[$i+$nbln], $i+2*$nbln+1, $slist[$i+2*$nbln];
    } elsif (defined($slist[$i+$nbln])) {
       $str = sprintf "  (%2d) %-20s (%2d) %-20s\n", $i+1, $el, $i+$nbln+1, $slist[$i+$nbln];
    } else {
       $str = sprintf "  (%2d) %-20s\n", $i+1, $el;
    }
    print $str;
    #print "  ($i) ".$el."\n";
    $i++;
  }
  print "\nPlease enter the number of the element chosen [".$this->{default}."]: ";
  ReadMode 'normal';
  my $result = ReadLine(0);
  chomp $result;
  if ($result eq '') {
    return  $slist[$this->{default}-1];
  }
  if ( $result !~ m/^[0-9]+$/ || $result < 1 || $result > @slist) {
   return -1;
   $result = $this->{default};
  }
  return $slist[$result-1];
}

