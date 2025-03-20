package FuzzyOcr::Logging;

use base 'Exporter';
our @EXPORT_OK = qw(debuglog errorlog infolog warnlog logfile);

use Mail::SpamAssassin::Logger qw(log_message would_log);
use FileHandle;
use Fcntl ':flock';
use POSIX qw(strftime);

sub logfile {
    my $conf = FuzzyOcr::Config::get_config();
    my $logtext = $_[0];
    my $time = strftime("%Y-%m-%d %H:%M:%S",localtime(time));
    $logtext =~ s/\n/\n                      /g;

    unless ( open LOGFILE, ">>", $conf->{focr_logfile} ) {
       warn "Can't open $conf->{focr_logfile} for writing, check permissions";
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
    my $conf = FuzzyOcr::Config::get_config();
    my $type  = $_[0];
    my @lines = split('\n',$_[1]);
    foreach (@lines) { log_message($type,"FuzzyOcr: $_"); }
}
    
sub errorlog {
    my $conf = FuzzyOcr::Config::get_config();
    _log("error",$_[0]) if $conf->{focr_log_stderr};
    if (defined $conf->{focr_logfile}) {
        logfile($_[0]);
    }
}

sub warnlog {
    my $conf = FuzzyOcr::Config::get_config();
    _log("warn",$_[0]) if $conf->{focr_log_stderr};
    if (defined $conf->{focr_logfile} and ($conf->{focr_verbose} >= 1)) {
        logfile($_[0]);
    }
}

sub infolog {
    my $conf = FuzzyOcr::Config::get_config();
    unless (_not_debug()) {
        _log("info",$_[0]) if $conf->{focr_log_stderr};
    }
    if (defined $conf->{focr_logfile} and ($conf->{focr_verbose} >= 2)) {
        logfile($_[0]);
    }
}

sub debuglog {
    my $conf = FuzzyOcr::Config::get_config();
    unless (_not_debug()) {
        _log("dbg",$_[0]) if $conf->{focr_log_stderr};
    }
    if (defined $conf->{focr_logfile} and ($conf->{focr_verbose} >= 3)) {
        logfile($_[0]);
    }
}

1;
