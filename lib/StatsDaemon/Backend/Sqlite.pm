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

package StatsDaemon::Backend::Sqlite;

require Exporter;
use strict;
use threads;
use threads::shared;
use File::Copy;
use DBD::SQLite;
require ReadConfig;
use File::Path qw(mkpath);
use Fcntl qw(:flock SEEK_END);

our @ISA        = qw(Exporter);
our @EXPORT     = qw(new threadInit accessFlatElement stabilizeFlatElement getStats announceMonthChange announceDayChange);
our $VERSION    = 1.0;

my $shema = "
CREATE TABLE stat (
  date       int,
  key        varchar(100),
  value      int,
  UNIQUE (date, key)
);
";

sub new {
    my $class = shift;
    my $daemon = shift;
 
    my $conf = ReadConfig::getInstance();
     
    my $this = {    
        'class' => $class,
        'daemon' => $daemon,
        'data' => undef,
        'basepath' => $conf->getOption('VARDIR') . '/spool/mailcleaner/stats',
        'dbfilename' => 'stats.sqlite',
        'history_avoid_keys' => '',
        'history_avoid_keys_a' => [],
        'template_database' => $conf->getOption('SRCDIR').'/lib/StatsDaemon/Backend/data/stat_template.sqlite'
    };
    
    bless $this, $class;
    
    foreach my $option (keys %{ $this->{daemon} }) {
    	if (defined($this->{$option})) {
    		$this->{$option} = $this->{daemon}->{$option};
    	}
    }
    foreach my $o (split(/\s*,\s*/, $this->{history_avoid_keys})) {
    	push @{$this->{history_avoid_keys_a}}, $o;
    }
    if (! -d $this->{basepath}) {
    	mkpath($this->{basepath});
    	$this->doLog("base path created: ".$this->{basepath});
    }
    $this->doLog("backend loaded", 'statsdaemon');
    
    $this->{data} = $StatsDaemon::data_;
    return $this;
}

sub threadInit {
    my $this = shift;
    
    $this->doLog("backend thread initialization", 'statsdaemon');
}

sub accessFlatElement {	
	my $this = shift;
    my $element = shift;

    my $value = 0;
    
    my ($path, $file, $base, $el_key) = $this->getPathFileBaseAndKeyFromElement($element);
    if (! -f $file) {
    	return $value;
    }
    my $dbh = $this->connectToDB($file);
    if (defined($dbh)) {
    	my $current_date = sprintf( '%.4d%.2d%.2d', 
    	                       $this->{daemon}->getCurrentDate()->{'year'}, 
    	                       $this->{daemon}->getCurrentDate()->{'month'}, 
    	                       $this->{daemon}->getCurrentDate()->{'day'});
    	my $query = 'SELECT value FROM stat WHERE date='.$current_date.' AND key=\''.$el_key.'\'';
    	my $res = $dbh->selectrow_hashref($query);
    	$this->{daemon}->addStat( 'backend_read', 1 );
    	if (defined($res) && defined($res->{'value'})) {
    		$value = $res->{'value'};
    	}
    	$dbh->disconnect;
    } else {
    	$this->doLog( "Cannot connect to database: " . $file, 'statsdaemon',
            'error' );
    }
    return $value;
}

sub stabilizeFlatElement {
    my $this = shift;
    my $element = shift;
    
    my ($path, $file, $base, $el_key) = $this->getPathFileBaseAndKeyFromElement($element);
    foreach my $unwantedkey ( @{ $this->{history_avoid_keys_a} } ) {
        if ($el_key eq $unwantedkey) {
            return 'UNWANTEDKEY';
        }
    }
    
    if (! -d $path) {
        mkpath($path);
    }
    
    my $dbh = $this->connectToDB($file);
    if (defined($dbh)) {
    	my $current_date = sprintf( '%.4d%.2d%.2d', 
                               $this->{daemon}->getCurrentDate()->{'year'}, 
                               $this->{daemon}->getCurrentDate()->{'month'}, 
                               $this->{daemon}->getCurrentDate()->{'day'});
        my $query = 'REPLACE INTO stat (date,key,value) VALUES(?,?,?)';
        my $nbrows =  $dbh->do($query, undef, $current_date, $el_key, $this->{daemon}->getElementValueByName($element, 'value'));
        if (!defined($nbrows)) {
        	$this->doLog( "Could not update database: " . $query, 'statsdaemon', 'error' );
        }
        $dbh->disconnect;
        $this->{daemon}->addStat( 'backend_write', 1 );
    } else {
        $this->doLog( "Cannot connect to database: " . $file, 'statsdaemon', 'error' );
        return '_CANNOTCONNECTDB';
    }
    
    return 'STABILIZED';
}

sub getStats {
    my $this = shift;
    my $start = shift;
    my $stop = shift;
    my $what = shift;
    my $data = shift;
    
    return 'OK';
}

sub announceMonthChange {
    my $this = shift;	
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

##
sub getPathFileBaseAndKeyFromElement {
	my $this = shift;
	my $element = shift;
	
	my @els = split(/:/, $element);
    my $key = pop @els;
    
    my $path = $this->{basepath}.'/'.join('/',@els);
    my $file = $path.'/'.$this->{dbfilename};
    my $base = join(':', @els);
    return (lc($path), lc($file), lc($base), lc($key));
}

sub connectToDB {
	my $this = shift;
	my $file = shift;
	
	#my $create = 0;
	if (! -f $file) {
		copy($this->{template_database}, $file);
	#	$create = 1;
	}
	
	my $dbh = DBI->connect("dbi:SQLite:dbname=".$file,"","");
	if (!$dbh) {
		$this->doLog( "Cannot create database: " . $file, 'statsdaemon',
            'error' );
		return undef;
	}

    #if ($create) {
    #	$dbh->do($shema);
    #    $this->doLog( "Table created in $file", 'debug' );
    #}
	
	return $dbh;
}
1;
