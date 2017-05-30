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

package StatsDaemon;

use threads;
use threads::shared;

require Exporter;
use Time::HiRes qw(gettimeofday tv_interval);
require ReadConfig;
require DB;
use Digest::MD5 qw(md5_hex);
use strict;
use Data::Dumper;
use Date::Calc qw(Add_Delta_Days Today);
use Devel::Size qw(size total_size);

require SockTDaemon;

our @ISA = "SockTDaemon";

## define all shared data
my %stats_ : shared = (
    'queries'           => 0,
    'queries_add'       => 0,
    'queries_get'       => 0,
    'stabilize_element' => 0,
    'stabilize_all'     => 0,
    'backend_read'      => 0,
    'backend_write'     => 0
);

my %current_date_ : shared = ( 'day' => 0, 'month' => 0, 'year' => 0 );
my %backend_infos_ : shared = (
    'current_table'          => '',
    'current_table_exists'   => 0,
    'current_table_creating' => 0,
    'stabilizing'            => 0,
    'long_read'              => 0
);
my $closing_ : shared             = 0;
my $clearing_ : shared            = 0;
my $changing_day_ : shared        = 0;
my $last_stable_ : shared         = 0;
my $set_socks_available_ : shared = 0;

sub new {
    my $class        = shift;
    my $myspec_thish = shift;
    my %myspec_this;
    if ($myspec_thish) {
        %myspec_this = %$myspec_thish;
    }

    my $conf = ReadConfig::getInstance();
    
    my $spec_this = {
        name              => 'StatsDaemon',
        max_unstable_time => 20,
        stabilize_every   => 60,
        purge_limit       => 0,
        reserve_set_socks => 1,
        backend           => undef,
        socketpath => $conf->getOption('VARDIR') . "/run/statsdaemon.sock",
        pidfile    => $conf->getOption('VARDIR') . "/run/statsdaemon.pid",
        configfile => $conf->getOption('SRCDIR')
          . "/etc/mailcleaner/statsdaemon.conf",
        clean_thread_exit => 0,
        backend_type => 'none',
        'history_avoid_keys' => '',
        'history_avoid_keys_a' => []
    };

    # add specific options of child object
    foreach my $sk ( keys %myspec_this ) {
        $spec_this->{$sk} = $myspec_this{$sk};
    }

    ## call parent class creation
    my $this = $class->SUPER::new( $spec_this->{'name'}, undef, $spec_this );
    bless $this, 'StatsDaemon';

    ##
    $this->{history_avoid_keys} =~ s/\'//gi;
    foreach my $o (split(/\s*,\s*/, $this->{history_avoid_keys})) {
        push @{$this->{history_avoid_keys_a}}, $o;
    }
    
    ## set startup shared variables
    my ( $sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst ) =
      localtime time;
    %current_date_ =
      ( 'day' => $mday, 'month' => $mon + 1, 'year' => $year + 1900 );
    $set_socks_available_ = $this->{'prefork'};
    
    if ($this->{reserve_set_socks} >= $this->{prefork}) {
        $this->{reserve_set_socks} = $this->{prefork}-1;
    }
    
    my $data_ = &share( {} );
    $this->{data_} = $data_;
    return $this;
}

sub preForkHook {
   my $this = shift;

   my $backend_class = 'StatsDaemon::Backend::'.ucfirst($this->{backend_type});

   if (! eval "require $backend_class") {
     die('Backend type does not exists: '.$backend_class);
   }
   $this->{backend_object} = $backend_class->new($this);

   $this->SUPER::preForkHook();
}

### define specific hooks
sub exitHook {
   my $this = shift;
      
   $this->SUPER::exitHook(); 
      
   $this->doLog('Close called, stabilizing and cleaning data...', 'statsdaemon');
   $this->stabilizeFlatAll();
   $this->doLog('Data stabilized. Can shutdown cleanly.', 'statsdaemon');
           
}

sub initThreadHook {
    my $this = shift;

    $this->doLog( 'StatsDaemon thread initialization...', 'statsdaemon' );
    $this->{backend_object}->threadInit();

    $last_stable_ = time();

    return;
}

sub exitThreadHook {
    my $this = shift;

    $this->doLog( 'StatsDaemon thread exiting hook...', 'statsdaemon' );
    return;
}

sub postKillHook {
    my $this = shift;
    return;
}

sub statusHook {
    my $this = shift;
    
    my $res = '-------------------'."\n";
    $res .= 'Current statistics:'."\n";
    $res .= '-------------------' ."\n";
    
    $res .= $this->SUPER::statusHook();
    require StatsClient;
    my $client = new StatsClient();
    $res .= $client->query('GETINTERNALSTATS');
    #$res .= $this->logStats();
    
    $res .= '-------------------' ."\n";
        
    $this->doLog($res, 'statsdaemon');
    
    return $res;
}

####### Main processing
sub dataRead {
    my $this = shift;
    my $data = shift;
    
    my $data_ = $this->{data_};

    $this->doLog( 'Got ' . $set_socks_available_ . " available set sockets",
        'statsdaemon', 'debug' );

    $this->doLog( "Received datas: $data", 'statsdaemon', 'debug' );
    my $ret = 'NOTHINGDONE';

    $this->addStat( 'queries', 1 );

    ## check if we changed day
    my ( $sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst ) =
      localtime time;
    $year += 1900;
    $mon  += 1;
    if ( $year != $current_date_{'year'} || $mon != $current_date_{'month'} ) {
    	$this->{backend_object}->announceMonthChange();
    }
    if ( $mday != $current_date_{'day'} ) {
        if ( !$changing_day_ ) {
            $changing_day_ = 1;
            ## stabilize all and reset full %data_
            $this->doLog(
                'Day change initialized, stabilizing and clearing data...',
                'statsdaemon' );
            $this->stabilizeFlatAll();
            $this->clearAllData();
            $current_date_{'day'} = $mday;
            $this->doLog( 'Day change done', 'statsdaemon' );
            $changing_day_ = 0;
        }
        else {
            return '_RETRY';
        }
    }

    ## ADD command
    ##   ADD element value
    if ( $data =~ m/^ADD\s+(\S+)\s+(\d+)/i ) {
        my $element = $1;
        $element =~ s/'/\\'/;
        $element = lc($element);
        my $value = $2;

        my $valh = $this->accessFlatElement( $element, 1 );
        $this->addElementValue( $valh, $value );
        $this->addStat( 'queries_add', 1 );
        $this->setElementValue( $data_->{$element}, 'stable', 0 );
        $this->checkForStabilization($element);
        
        ## check if its time to stabilize all
        my $time = time();
        if ( $time - $last_stable_ > $this->{'stabilize_every'} ) {
           $this->stabilizeFlatAll();
        }
    
        return "ADDED " . $valh->{'value'};
    }

    ## GET command
    ##    GET element
    if ( $data =~ m/^GET\s+(\S+)\s*$/i ) {
        my $element = $1;
        $element =~ s/'/\\'/;
        $element = lc($element);

        my $valh = $this->accessFlatElement( $element, 0 );
        $this->addStat( 'queries_get', 1 );
        return $valh->{'value'};
    }

    ## GET what date date command
    if ( $data =~ m/^GET\s+(\S+)\s+([+-]?\d+)\s+([+-]?\d+)/i ) {
        my $element = $1;
        $element =~ s/'/\\'/;
        $element = lc($element);
        my $fromdate = $2;
        my $todate   = $3;

        if ( $set_socks_available_ > $this->{reserve_set_socks} ) {
            $set_socks_available_--;
            my $ret = $this->calcStats( $element, $fromdate, $todate );
            $set_socks_available_++;
            return $ret;
        }
        else {
            return '_NOSOCKAVAILABLE '.$set_socks_available_." <=> ".$this->{reserve_set_socks};
        }
    }

    ## STABILIZE command
    ##   STABILIZE [element]
    if ( $data =~ m/^STABILIZE\s*(\S+)?/i ) {
        my $element = $1;
        $element =~ s/'/\\'/;
        $element = lc($element);
        if ($element) {
            return $this->stabilizeFlatElement($element);
        }
        else {
            return $this->stabilizeFlatAll();
        }
    }

    ## DUMP data
    if ( $data =~ m/^DUMP/i ) {
    	return $this->dumpData();
    }
    
    ## CLEAR command
    if ( $data =~ m/^CLEAR/i ) {
        return $this->clearAllData();
    }
    
    ## GETINTERNALSTATS command
    if ( $data =~ m/^GETINTERNALSTATS/i ) {
        return $this->getStats();
    }

    return "_UNKNOWNCOMMAND";
}

####### StatsDaemon functions

## stats data managment

sub createElement {
	my $this    = shift;
    my $element = shift;
    
    my $data_ = $this->{data_};
    
	if ( !defined( $data_->{$element} ) ) {

        $data_->{$element} = &share( {} );
        $data_->{$element}->{'stable'}      = 0;
        $data_->{$element}->{'value'}       = 0;
        $data_->{$element}->{'last_stable'} = time();
        $data_->{$element}->{'stable_id'}   = 0;
        $data_->{$element}->{'last_access'} = time();
	}
}
sub setElementValue {
    my $this    = shift;
    my $element = shift;
    my $key     = shift;
    my $value   = shift;

    lock($element);
    $element->{$key} = $value;
}

sub setElementValueByName {
	my $this    = shift;
	my $element = shift;
	my $key     = shift;
    my $value   = shift;
    
    my $data_ = $this->{data_};
    $this->setElementValue($data_->{$element}, $key, $value);
}

sub getElementValueByName {
	my $this     = shift;
	my $element  = shift;
	my $key      = shift;
	
	my $data_ = $this->{data_};
	return $data_->{$element}->{$key};
}

sub addElementValue {
    my $this    = shift;
    my $element = shift;
    my $value   = shift;

    lock($element);
    $element->{'value'} += $value;
}

sub accessFlatElement {
    my $this    = shift;
    my $element = shift;

    my $data_ = $this->{data_};

    if ( !defined( $data_->{$element} ) ) {

        $this->createElement($element);
        lock(%{$data_->{$element}});

        ## try to load data from backend
        my $value = $this->{backend_object}->accessFlatElement($element);
        if ($value =~ /[^0-9.]/) {
           $this->doLog('element '.$element. ' could not be fetched from backend, return is: ' .$value. '. Setting value to 0.',
                 'statsdaemon', 'error');
           $value = 0;
        }
        $this->setElementValue($data_->{$element},'value', $value);

    }
    $this->setElementValue($data_->{$element},'last_access', time());
    return $data_->{$element};
}

sub checkForStabilization {
    my $this    = shift;
    my $element = shift;

    my $data_ = $this->{data_};
    
    my $time = time();
    if (defined($data_->{$element}->{'last_stable'})) {
      if ( $time - $data_->{$element}->{'last_stable'} >
          $this->{'max_unstable_time'} )
      {
          $this->stabilizeFlatElement($element);
          return 1;
      }
    }
    return 0;
}

sub stabilizeFlatElement {
    my $this    = shift;
    my $element = shift;
   
    foreach my $unwantedkey ( @{ $this->{history_avoid_keys_a} } ) {
        if ($element =~ m/\:$unwantedkey$/) {
            return 'UNWANTEDKEY';
        }
    }
    my $data_ = $this->{data_};
    
    if ($this->getLongReadCount() > 0 ) {
        $this->doLog('Delaying stabilization because long read is running', 'statsdaemon');
        return '_LONGREADRUNNING';
    }
    $this->addStat( 'stabilize_element', 1 );

    if ( defined($data_->{$element}->{'stable'}) && $data_->{$element}->{'stable'} > 0 && !$changing_day_) {
    	$this->setElementValue( $data_->{$element}, 'stable_id',      1 );
        $this->setElementValue( $data_->{$element}, 'last_stable', time() );
        $this->setElementValue( $data_->{$element}, 'last_access', time() );

        $this->doLog(
            'not stabilizing value for element ' . $element
              . ' in backend because already stable',
            'statsdaemon', 'debug'
        );
        return 'ALREADYSTABLE';
    }

    my $stret = $this->{backend_object}->stabilizeFlatElement($element);

    if ($stret =~ /^_/) {
    	return $stret;
    }
    
    $this->setElementValue( $data_->{$element}, 'stable',      1 );
    $this->setElementValue( $data_->{$element}, 'last_stable', time() );
    $this->setElementValue( $data_->{$element}, 'last_access', time() );

    return $stret;
}

sub stabilizeFlatAll {
    my $this = shift;

    my $data_ = $this->{data_};
    my $start_time = [gettimeofday];
    if ( $backend_infos_{'stabilizing'} > 0 ) {
        return 'ALREADYSTABILIZING';
    }
    
    if ($this->getLongReadCount() > 0 ) {
        $this->doLog('Delaying stabilization because long read is running', 'statsdaemon', 'debug');
        return '_LONGREADRUNNING';
    }
    
    $backend_infos_{'stabilizing'} = 1;
    $this->addStat( 'stabilize_all', 1 );
    my $more = '';
    if ($changing_day_ > 0) {
      $more = ' (changing day)';
    }
    $this->doLog( 'Started stabilization of all data'.$more.'...',
        'statsdaemon' );
    
    my $stcount = 0;
    my $unwantedcount = 0;
    my $errorcount = 0;
    my $purgedcount = 0;
    my $elcount = 0;
    my $stablecount = 0;
    while( my ($el, $value) = each(%{$data_})) {
        $elcount++;
        $this->doLog( 'Testing element ' . $el, 'statsdaemon', 'debug' );
        if ( !defined($data_->{$el}->{'stable'}) || $changing_day_ 
              || (defined($data_->{$el}->{'stable'}) && !$data_->{$el}->{'stable'} )) {
            my $stret = $this->stabilizeFlatElement($el);
            if ($stret eq 'STABILIZED') {
              $stcount++;
            } elsif ($stret eq 'ALREADYSTABLE') {
              $stablecount++;
            } elsif ($stret eq 'UNWANTED') {
               $unwantedcount++;
            } elsif ($stret =~ /^_/) {
               $errorcount++;
            }
        } else {
           $stablecount++;
           if (defined($data_->{$el}->{'last_access'})) {
               lock(%{$data_->{$el}});
               my $delta = time() - $data_->{$el}->{'last_access'};
               if ($this->{purge_limit} && $delta > $this->{purge_limit} && $data_->{$el}->{'stable'}) {
                   $this->doLog('Purging element '.$el, 'statsdaemon', 'debug');
                   my $stret = $this->stabilizeFlatElement($el);
                   if ($stret !~ /^_/) {
                       my $fret = $this->freeElement($el);
                       if ($fret eq 'OK') {
                           $purgedcount++;
                       } else {
                           $errorcount++;
                       }
                   }
               }
           }
        }
    }
   
    my $interval = tv_interval($start_time);
    my $sttime = ( int( $interval * 10000 ) / 10000 ); 
    $this->doLog( 'Finished stabilization of all data ('.$elcount.' elements, '.$stablecount.' stable, '.$stcount.' stabilized, '.$unwantedcount.' unwanted, '.$errorcount.' errors, '.$purgedcount.' purged in '.$sttime.' s.)',
        'statsdaemon' );
    $last_stable_ = time();
    $backend_infos_{'stabilizing'} = 0;
    return 'ALLSTABILIZED';
}

sub clearAllData {
    my $this = shift;
    if ( $clearing_ > 0 ) {
        return;
    }
    
    my $data_ = $this->{data_};
    
    $this->doLog( 'Started clearing of all data...', 'statsdaemon' );
    $clearing_ = 1;
    lock %{$data_};
    while( my ($el, $value) = each(%{$data_})) {
        $data_->{$el}->{'value'}  = 0;
        $data_->{$el}->{'stable'} = 0;
    }
    $clearing_ = 0;
    $this->doLog( 'Finished clearing of all data.', 'statsdaemon' );
    return 'CLEARED';
}

sub freeElement {
    my $this = shift;
    my $el = shift;
    
    my $data_ = $this->{data_};
    
    if (!defined($data_->{$el})) {
       return '_UNDEF';
    }

    lock %{$data_->{$el}};
    foreach my $key (keys %{$data_->{$el}}) {
    	undef($data_->{$el}->{$key});
    	delete($data_->{$el}->{$key});
    }
    lock %{$data_};
    $data_->{$el} = ();

    undef($data_->{$el});
    delete($data_->{$el});
    return 'OK';
}

sub dumpData {
	my $this = shift;
	
	my $data_ = $this->{data_};
	
	while( my ($el, $value) = each(%{$data_})) {
      $this->doLog(' - '.$el, 'statsdaemon');
    }
}
sub calcStats {
    my $this  = shift;
    my $what  = shift;
    my $begin = shift;
    my $end   = shift;

    if ( !defined($what) || $what !~ m/^[a-zA-Z0-9@._\-,*:]+$/ ) {
        return '_BADUSAGEWHAT';
    }
    if ( !defined($begin) || $begin !~ m/^[+-]?\d{1,8}$/ ) {
        return '_BADUSAGEBEGIN';
    }
    if ( !defined($end) || $end !~ /^[+-]?\d{1,8}$/ ) {
        return '_BADUSAGEEND';
    }

    my %data;

## compute start and end dates
    my $start = `date +%Y%m%d`;
    my $stop  = $start;
    my $today = $start;
    chomp($start);
    chomp($stop);

    if ( $begin =~ /^(\d{8})/ ) {
        $start = $1;
    }
    if ( $end =~ /^(\d{8})/ ) {
        $stop = $1;
    }
    if ( $begin =~ /^([+-]\d+)/ ) {
        $start = addDate( $stop, $1 );
    }
    if ( $end =~ /^([+-]\d+)/ ) {
        $stop = addDate( $start, $1 );
    }

    if ( int($start) gt int($stop) ) {
        my $tmp = $start;
        $start = $stop;
        $stop  = $tmp;
    }
    if ( $start !~ /(\d{4})(\d{2})(\d{2})/ ) {
        return '_BADSTARTDATE';
    }
    if ( $stop !~ /(\d{4})(\d{2})(\d{2})/ ) {
        return '_BADSTOPDATE';
    }
    
    ## if we need today's stats, stabilize all before querying
    if ( $stop >= $today ) {
        $this->stabilizeFlatAll();
    }
    
    my $ret = $this->{backend_object}->getStats($start, $stop, $what, \%data);
    if ($ret ne 'OK') {
    	return $ret;
    }

## return results
   if (keys %data < 1) {
   	 return '_NODATA';
   }
    my $res = '';
    foreach my $sub ( keys %data ) {
        $res .= "$sub\n";
        foreach my $key ( keys %{ $data{$sub} } ) {
            $res .= " $key: " . $data{$sub}{$key} . "\n";
        }
    }
    return $res;

}

sub increaseLongRead {
    my $this = shift;
    
    lock %backend_infos_;
    $backend_infos_{'long_read'}++;
    return;
}
sub decreaseLongRead {
    my $this = shift;
    
    lock %backend_infos_;
    $backend_infos_{'long_read'}--;
    return;
}
sub getLongReadCount {
    my $this = shift;
    
    return $backend_infos_{'long_read'};
}
                
sub addDate {
    my $in  = shift;
    my $add = shift;

    if ( $in !~ m/^(\d{4})(\d{2})(\d{2})$/ ) {
        return $in;
    }
    my ( $sy, $sm, $sd ) = ( $1, $2, $3 );

    if ( $add !~ m/^([\-\+])(\d+)$/ ) {
        return $in;
    }
    my $op    = $1;
    my $delta = $2;

    my ( $fy, $fm, $fd ) = Add_Delta_Days( $sy, $sm, $sd, $op . $delta );
    my $enddate = sprintf '%.4u%.2u%.2u', $fy, $fm, $fd;
    return $enddate;
}

sub getCurrentDate {
	my $this = shift;
	
	return \%current_date_;
}

sub isChangingDay {
	return $changing_day_;
}
####### Internal stats managment
sub addStat {
    my $this   = shift;
    my $what   = shift;
    my $amount = shift;

    lock %stats_;
    if ( !defined( $stats_{$what} ) ) {
        $stats_{$what} = 0;
    }
    $stats_{$what} += $amount;
    return 1;
}

sub getStats {
    my $this = shift;

    lock %stats_;
    
    my $data_ = $this->{data_};
    
    my $res = '  Total number of elements in memory: ' . keys( %{$data_} )."\n";
    $res .= '  Current data size: '.$this->getDataSize()."\n";
    $res .= '  Total GET queries: ' . $stats_{'queries_get'} ."\n";
    $res .= '  Total ADD queries: ' . $stats_{'queries_add'} ."\n";
    $res .= '  Total element stabilizations: ' . $stats_{'stabilize_element'}."\n";
    $res .= '  Total all stabilizations: ' . $stats_{'stabilize_all'}."\n";
    $res .= '  Total backend read: ' . $stats_{'backend_read'}."\n";
    $res .= '  Total backend write: ' . $stats_{'backend_write'}."\n";
    $res .= '  Current long read running: '.$backend_infos_{'long_read'}."\n";

    return $res;
}

sub logStats {
    my $this = shift;

    return $this->getStats();
}

sub getDataSize {
  my $this = shift;

  my $data_ = $this->{data_};
  my $size = 0;
  while( my ($el, $value) = each(%{$data_})) {
      $size += total_size($el);
  }

  return $size;
}

1;
