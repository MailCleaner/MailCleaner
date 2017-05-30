# <@LICENSE>
# Licensed to the Apache Software Foundation (ASF) under one or more
# contributor license agreements.  See the NOTICE file distributed with
# this work for additional information regarding copyright ownership.
# The ASF licenses this file to you under the Apache License, Version 2.0
# (the "License"); you may not use this file except in compliance with
# the License.  You may obtain a copy of the License at:
# 
#     http://www.apache.org/licenses/LICENSE-2.0
# 
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
# </@LICENSE>

package OscarOcr::Logging;

use base 'Exporter';
our @EXPORT_OK = qw(debuglog errorlog infolog warnlog logfile salog);

use Mail::SpamAssassin::Logger qw(log_message would_log);
use FileHandle;
use Fcntl ':flock';
use POSIX qw(strftime);

sub logfile {
    my $conf = OscarOcr::Config::get_config();
    my $logtext = $_[0];
    my $time = strftime("%Y-%m-%d %H:%M:%S",localtime(time));
    $logtext =~ s/\n/\n                      /g;

    unless ( open LOGFILE, ">>", $conf->{oscar_logfile} ) {
       warn "Can't open $conf->{oscar_logfile} for writing, check permissions";
       return;
    }
    flock( LOGFILE, LOCK_EX );
    seek( LOGFILE, 0, 2 );
    print LOGFILE "$time [$$] $logtext\n";
    close LOGFILE;
}

sub _not_debug {
    return $Mail::SpamAssassin::Logger::LOG_SA{level} != 3;
}
sub _log {
    my $conf = OscarOcr::Config::get_config();
    my $type  = $_[0];
    my @lines = split('\n',$_[1]);
    foreach (@lines) { log_message($type,"Oscar: $_"); }
}
    
sub errorlog {
    my $conf = OscarOcr::Config::get_config();
    _log("error",$_[0]) if $conf->{oscar_log_stderr};
    if (defined $conf->{oscar_logfile}) {
        logfile($_[0]);
    }
}

sub warnlog {
    my $conf = OscarOcr::Config::get_config();
    _log("warn",$_[0]) if $conf->{oscar_log_stderr};
    if (defined $conf->{oscar_logfile} and ($conf->{oscar_verbose} >= 1)) {
        logfile($_[0]);
    }
}

sub infolog {
    my $conf = OscarOcr::Config::get_config();
    #unless (_not_debug()) {
        _log("info",$_[0]) if $conf->{oscar_log_stderr};
    #}
    if (defined $conf->{oscar_logfile} and ($conf->{oscar_verbose} >= 2)) {
        logfile($_[0]);
    }
}

sub debuglog {
    my $conf = OscarOcr::Config::get_config();
    unless (_not_debug()) {
        _log("dbg",$_[0]) if $conf->{oscar_log_stderr};
    }
    if (defined $conf->{oscar_logfile} and ($conf->{oscar_verbose} >= 3)) {
        logfile($_[0]);
    }
}

sub salog {
	_log("info",$_[0]);
	if (defined $conf->{oscar_logfile}) {
	  logfile($_[0]);
	}
}

1;
