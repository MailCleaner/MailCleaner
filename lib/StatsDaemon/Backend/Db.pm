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

package StatsDaemon::Backend::Db;

require Exporter;
use strict;
use threads;
use threads::shared;
require DB;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(new threadInit accessFlatElement stabilizeFlatElement getStats announceMonthChange announceDayChange);
our $VERSION    = 1.0;

my $_current_table : shared = '';
my $_current_table_exists : shared = 0;
my $_current_table_creating : shared = 0;

sub new {
    my $class = shift;
    my $daemon = shift;
    
    my $this = {	
    	'class' => $class,
    	'daemon' => $daemon
    };
    bless $this, $class;
    
   foreach my $option (keys %{ $this->{daemon} }) {
       if (defined($this->{$option})) {
           $this->{$option} = $this->{daemon}->{$option};
       }
    }

    $this->doLog("backend loaded", 'statsdaemon');
    
    $this->{data} = $StatsDaemon::data_;
    return $this;
}

sub threadInit {
	my $this = shift;
	
	$this->doLog("backend thread initialization", 'statsdaemon');
	$this->connectBackend();
}

sub accessFlatElement {
	my $this = shift;
	my $element = shift;
	
	my $value = 0;
	
	if ( $_current_table_exists ) {
            my $query =
                "SELECT s.id as id, d.value as value FROM "
              . $_current_table
              . " d, stats_subject s WHERE s.id=d.subject AND s.subject='"
              . $element
              . "' AND d.day="
              . $this->{daemon}->getCurrentDate()->{'day'};
            $this->{daemon}->addStat( 'backend_read', 1 );
            return '_NOBACKEND' if ( !$this->connectBackend() );
            my %res = $this->{db}->getHashRow($query);
            $this->doLog( 'Query executed: '.$query, 'statsdaemon', 'debug');
            if ( $res{'id'} && $res{'value'} ) {
            	
                $this->{data}->{$element}->{'stable_id'} = $res{'id'};
                
                $value = $res{'value'};
            }
            $this->doLog( 'loaded data for ' . $element . ' from backend',
                'statsdaemon', 'debug' );
     }
     return $value;
}

sub stabilizeFlatElement {
	my $this = shift;
	my $element = shift;
        
	my $table = '';
    if ( $_current_table_exists ) {
        $table = $_current_table;
    }
    else {
        if ( $this->createCurrentTable() ) {
            $table = $_current_table;
        }
    }
    if ( $table eq '' ) {
        $this->doLog(
            "Error: Current table cannot be found (probably being created)",
            'statsdaemon', 'error' );
        return '_CANNOTSTABILIZE';
    }
    my $day = $this->{daemon}->getCurrentDate()->{'day'};

    ## find out if element already is registered, register it if not.
    if (!defined($this->{data}->{$element}) || 
         !defined($this->{data}->{$element}->{'stable_id'}) ||
         (defined($this->{data}->{$element}->{'stable_id'}) && !$this->{data}->{$element}->{'stable_id'})
       ) {
        my $query =
          "SELECT id FROM stats_subject WHERE subject='" . $element . "'";
        $this->{daemon}->addStat( 'backend_read', 1 );
        return '_NOBACKEND' if ( !$this->connectBackend() );
        my %res = $this->{db}->getHashRow($query);
        if ( defined( $res{'id'} ) ) {
            $this->{daemon}->setElementValueByName( $element, 'stable_id',
                $res{'id'} );
        }
        else {
            $query = "INSERT INTO stats_subject SET subject='" . $element . "'";
            return '_NOBACKEND' if ( !$this->connectBackend() );
            if ( $this->{db}->execute($query) ) {
                my $id = $this->{db}->getLastID();
                $this->{daemon}->addStat( 'backend_write', 1 );
                if ($id) {
                    $this->{daemon}->setElementValueByName( $element, 'stable_id',
                        $id );
                    $this->doLog(
                        'registered new element ' . $element
                          . ' in backend with id '
                          . $id,
                        'statsdaemon', 'debug'
                    );
                }
                else {
                    $this->doLog(
                        "Could not get subject ID for element: $element",
                        'statsdaemon', 'error' );
                }
            }
            else {
                ## maybe inserted meanwhile, so search again...
                my $query =
                    "SELECT id FROM stats_subject WHERE subject='" . $element
                  . "'";
                $this->{daemon}->addStat( 'backend_read', 1 );
                return '_NOBACKEND' if ( !$this->connectBackend() );
                my %res = $this->{db}->getHashRow($query);
                if ( defined( $res{'id'} ) ) {
                    $this->{daemon}->setElementValueByName( $element, 'stable_id',
                        $res{'id'} );
                }
                else {
                    $this->doLog(
                        "Could not insert subject for element: $element",
                        'statsdaemon', 'error' );
                }
            }
        }
    }

    ## update or insert the value
    my $query =
        "INSERT INTO " . $table
      . " SET day="
      . $day
      . ", subject="
      . $this->{daemon}->getElementValueByName($element, 'stable_id')
      . ", value="
      . $this->{daemon}->getElementValueByName($element, 'value')
      . " ON DUPLICATE KEY UPDATE value="
      . $this->{daemon}->getElementValueByName($element, 'value');
    return '_NOBACKEND' if ( !$this->connectBackend() );
    if ( !$this->{db}->execute($query) ) {
        $this->doLog( "Could not stabilize statistic with query: '$query'",
            'statsdaemon', 'error' );
        return '_CANNOTSTABILIZE';
    }
    $this->{daemon}->addStat( 'backend_write', 1 );
    $this->doLog( 'stabilized value for element ' . $element . ' in backend',
        'statsdaemon', 'debug' );
    return 'STABILIZED';
}

sub getStats {
	my $this = shift;
	my $start = shift;
	my $stop = shift;
	my $what = shift;
	my $data = shift;
	
	## defs
    my $base_subject = '---';
	
	if ( $start !~ /(\d{4})(\d{2})(\d{2})/ ) {
        return '_BADSTARTDATE';
    }
    my $start_table = $1 . $2;
    my $start_y     = $1;
    my $start_m     = $2;
    my $start_day   = $3;
    if ( $stop !~ /(\d{4})(\d{2})(\d{2})/ ) {
        return '_BADSTOPDATE';
    }
    my $stop_table = $1 . $2;
    my $stop_day   = $3;

    my $day_table_y = $start_y;
    my $day_table_m = $start_m;

## get backend table to use
    my $day_table = sprintf '%.4u%.2u', $day_table_y, $day_table_m;
    my @tables;
    while ( $day_table <= $stop_table ) {
        $day_table_m++;
        push @tables, $day_table;

        if ( $day_table_m > 12 ) {
            $day_table_m = 1;
            $day_table_y++;
        }
        $day_table = sprintf '%.4u%.2u', $day_table_y, $day_table_m;
    }

## get subjects to work on
    my @subjects;
    foreach my $what ( split /,/, $what ) {
        my %sub;
        if ( $what !~ /\*/ ) {
            if ( $what =~ /^(\S+)@(\S+)/ ) {
            #    $sub{'sub'} = $base_subject . ':' . $2 . ':' . $1 . ':%';
            #    $sub{'neg'} = $sub{'sub'} . ':%';
                 $sub{'sub'} = 'user:'.$2.':'.$1.':%';
            }
            elsif ( $what eq "_global" ) {
            #    $sub{'sub'} = $base_subject . ":%";
            #    $sub{'neg'} = $sub{'sub'} . ':%';
                 $sub{'sub'} ='global:%';
            }
            else {
            #    $sub{'sub'} = $base_subject . ':' . $what . ":%";
            #    $sub{'neg'} = $sub{'sub'} . ':%';
                 $sub{'sub'} = 'domain:'.$what.":%";
            }
        }
        else {

            # find all subjects for * queries
            if ( $what =~ /^\*@(\S+)/ ) {
                my $dom = $1;
                ## push domain itself
                my %dsub;
                #$dsub{'sub'} = $base_subject . ':' . $dom . ':%';
                #$dsub{'neg'} = $dsub{'sub'} . ':%';
                $dsub{'sub'} = 'domain:'.$dom.':%';

                push @subjects, \%dsub;

                ## then subject
                #$sub{'sub'} = $base_subject . ':' . $dom . ':%:%';
                #$sub{'neg'} = $sub{'sub'} . ':%';
                $sub{'sub'} = 'user:'.$dom.":%";
            }
            else {
                ## push global
                my %gsub;
                #$gsub{'sub'} = $base_subject . ":%";
                #$gsub{'neg'} = $gsub{'sub'} . ':%';
                $gsub{'sub'} = 'global:%';
                push @subjects, \%gsub;

                ## then subject
                #$sub{'sub'} = $base_subject . ':%:%';
                #$sub{'neg'} = $sub{'sub'} . ':%';
                $sub{'sub'} = 'domain:%';
            }
        }
        push @subjects, \%sub;
    }

## query each subject through each table and compute stats
    foreach my $subh (@subjects) {

        my %sub       = %{$subh};
        my $table_idx = 0;
        foreach my $table (@tables) {
            my $day_where = '';
            if ( $table_idx == 0 ) {
                $day_where = " AND d.day >= " . $start_day;
            }
            $table_idx++;
            if ( $table_idx == @tables ) {
                $day_where = " AND d.day <= " . $stop_day;
            }
            if ( @tables == 1 ) {
                $day_where =
                  " AND d.day >= " . $start_day . " AND d.day <= " . $stop_day;
            }

            foreach my $func ( 'SUM', 'MAX' ) {
                my $query =
                    "SELECT s.subject, " . $func
                  . "(d.value) sm FROM stats_subject s LEFT JOIN stats_$table d ON ";
                $query .= "d.subject=s.id ";
                $query .= $day_where
                  ;    ## thanks Raf for this one ! Major speed improvement
                #$query .= " WHERE ( s.subject NOT LIKE '" . $sub{'neg'} . "' ";
                #$query .= "AND s.subject LIKE '" . $sub{'sub'} . "' ";
                #if ( $func eq 'SUM' ) {
                #    $query .=
#"AND s.subject NOT LIKE '%domain' AND s.subject NOT LIKE '%user' ) ";
#                }
#                else {
#                    $query .=
#"AND ( s.subject LIKE '%domain' OR s.subject LIKE '%user' ) ) ";
#                }

                $query .= " WHERE s.subject LIKE '" . $sub{'sub'} . "' ";
                if ( $func eq 'SUM' ) {
                     $query .= 	"AND s.subject NOT LIKE '%domain' AND s.subject NOT LIKE '%user' ";
                } else {
                	 $query .=  "AND ( s.subject LIKE '%domain' OR s.subject LIKE '%user' ) ";
                }
                
                $query .= " group by d.subject";
                $this->doLog( 'Using query: "'.$query.'"', 'statsdaemon', 'debug' );
                $this->{daemon}->increaseLongRead();
                my @results = $this->{db}->getListOfHash( $query, 1 );
                $this->{daemon}->decreaseLongRead();
                foreach my $res (@results) {
                    if ( !$res->{'sm'} ) {
                        $res->{'sm'} = 0;
                    }
                    my @subject_path = split( ':', $res->{'subject'} );
                    my $value_key    = pop @subject_path;
                    my $subject_key  = join( ':', @subject_path );
                    if ( !defined( $data->{$subject_key}{$value_key} ) ) {
                        $data->{$subject_key}{$value_key} = 0;
                    }
                    if ( $func eq 'MAX' ) {
                        if ( $res->{'sm'} > $data->{$subject_key}{$value_key} ) {
                            $data->{$subject_key}{$value_key} = $res->{'sm'};
                        }
                    }
                    else {
                        $data->{$subject_key}{$value_key} += $res->{'sm'};
                    }
                }
            }
        }
    }
    return 'OK';
}

sub announceMonthChange {
	my $this = shift;
	
	$_current_table_exists = 0;
}

sub announceDayChange {
	my $this = shift;
	
}

## Database managment
sub connectBackend {
    my $this = shift;

    return 1 if ( defined( $this->{db} ) && $this->{db}->ping() );

    $this->{db} = DB::connect( 'slave', 'mc_stats', 0 );
    if ( !$this->{db}->ping() ) {
        $this->doLog( "WARNING, could not connect to statistics database",
            'statsdaemon', 'error' );
        return 0;
    }
    $this->doLog( "Connected to statistics database", 'statsdaemon' );

    if ( $_current_table_exists == 0 ) {
        $this->createCurrentTable();
    }

    my $query = "SET SESSION TRANSACTION ISOLATION LEVEL READ UNCOMMITTED";
    $this->{db}->execute($query);

    return 1;
}

sub createCurrentTable() {
    my $this = shift;

    if ( $_current_table_creating == 1 ) {
        return 0;
    }
    $_current_table_creating = 1;

    my $query =
        "CREATE TABLE IF NOT EXISTS `stats_subject` ("
      . "`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,"
      . "`subject` varchar(250) DEFAULT NULL,"
      . "UNIQUE KEY `subject` (`subject`),"
      . "KEY `id` (`id`)"
      . ") ENGINE=MyISAM";
    if ( !$this->{db}->execute($query) ) {
        $this->doLog( "Cannot create subject table", 'statsdaemon', 'error' );
        $_current_table_creating = 0;
        return 0;
    }

    my ( $sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst ) =
      localtime time;
    %{$this->{daemon}->getCurrentDate()} =
      ( 'day' => $mday, 'month' => $mon + 1, 'year' => $year + 1900 );
    my $table = "stats_"
      . sprintf( '%.4d%.2d', $this->{daemon}->getCurrentDate()->{'year'}, $this->{daemon}->getCurrentDate()->{'month'} );

    $query =
        "CREATE TABLE IF NOT EXISTS `$table` ("
      . "`day` tinyint(4) UNSIGNED NOT NULL, "
      . "`subject` int(11) UNSIGNED NOT NULL, "
      . "`value` BIGINT UNSIGNED NOT NULL DEFAULT '0', "
      . "PRIMARY KEY `day` (`day`,`subject`), "
      . "KEY `subject_idx` (`subject`) "
      . ") ENGINE=MyISAM";
    $this->{daemon}->addStat( 'backend_write', 1 );
    if ( !$this->{db}->execute($query) ) {
        $this->doLog( "Cannot create table: " . $table, 'statsdaemon',
            'error' );
        $_current_table_creating = 0;
        return 0;
    }
    else {
        $this->doLog( 'Table ' . $table . " created", 'statsdaemon' );
    }
    $_current_table_exists = 1;
    $_current_table = $table;
    $_current_table_creating = 0;
    return 1;
}

sub doLog {
	my $this = shift;
    my $message   = shift;
    my $given_set = shift;
    my $priority  = shift;
	
    my $msg = $this->{class}." ".$message;
    if ($this->{daemon}) {
        $this->{daemon}->doLog($msg, $given_set, $priority); 
    }
}

1;
