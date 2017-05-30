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

package MailScanner::Profiler;

use strict 'vars';
use strict 'refs';
no  strict 'subs'; # Allow bare words for parameter %'s

use vars qw($VERSION);

use Time::HiRes qw(gettimeofday tv_interval);

# Constructor.
sub new {
  my (%start_times, %res_times) = ();

  my $this = {
     %start_times => (),
     %res_times => (),
  };

  bless $this, 'MailScanner::Profiler';
  return $this;
}

sub start {
  my $this = shift;
  my $var = shift;

  return unless MailScanner::Config::Value('profile');
  
  $this->{start_times}{$var} = [gettimeofday];
}

sub stop {
  my $this = shift;
  my $var = shift;

  return unless MailScanner::Config::Value('profile');

  return unless defined($this->{start_times}{$var});
  my $interval = tv_interval ($this->{start_times}{$var});
  $this->{res_times}{$var} = (int($interval*10000)/10000);
}

sub getResult {
  my $this = shift;

  return unless MailScanner::Config::Value('profile');

  my $out = "";
 
  my @keys = sort keys %{$this->{res_times}};
  foreach my $key (@keys) {
    $out .= " ($key:".$this->{res_times}{$key}."s)";
  }
  return $out;
}

sub log {
  my $this = shift;
  my $extra = shift;

  return unless MailScanner::Config::Value('profile');

  MailScanner::Log::InfoLog($extra.$this->getResult());
  return 1;
}

1;

