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

use strict;
package OscarOcr::Config;

use lib qw(..);
use OscarOcr::Logging qw(debuglog infolog warnlog errorlog);
use Mail::SpamAssassin::Logger;

use Fcntl qw(O_RDWR O_CREAT);

use base 'Exporter';
our @EXPORT_OK = qw/
    parse_config
    finish_parsing_end
    get_config 
    set_config 
    set_pid
    unset_pid
    kill_pid
    set_tmpdir
    get_tmpdir
    get_all_tmpdirs
    get_pms
    save_pms 
    get_timeout
    get_scansets 
    get_preprocessor 
    get_thresholds 
    get_wordlist 
    get_wordweights
    get_mysql_ddb
    get_db_ref
    set_db_ref
    read_words 
    /;

use constant HAS_DBI => eval { require DBI; };
use constant HAS_DBD_MYSQL => eval { require DBD::mysql; };
use constant HAS_MLDBM => eval { require MLDBM; require MLDBM::Sync;};
use constant HAS_DB_FILE => eval { require DB_File; };
use constant HAS_STORABLE => eval { require Storable; };

#Defines the defaults and reads the configuration and wordlists

our %Threshold = ();
our %words = ();
our %wweights = ();
our $conf;
our $pms;
our $timeout;
our $pid;
our $tmpdir;
our @tmpdirs;
our $dbref;

# State of the plugin, already initialized?
our $initialized = 0;

our @bin_utils = qw/gifsicle
    giffix
    giftext 
    gifinter 
    giftopnm
    jpegtopnm 
    pngtopnm 
    bmptopnm 
    tifftopnm 
    ppmhist 
    pamfile 
    ocrad
    gocr/; 

our @paths = qw(/usr/local/netpbm/bin /usr/local/bin /usr/bin);

my @img_types = qw/gif png jpeg bmp tiff/;

sub get_timeout {
    unless (defined $timeout) {
        $timeout = Mail::SpamAssassin::Timeout->new({ secs => $conf->{oscar_timeout} });
    }
    return $timeout;
}

sub set_pid {
    $pid = shift;
    debuglog("Saved pid: $pid");
}

sub unset_pid {
    $pid = 0;
}

sub kill_pid {
    if ($pid) {
        infolog("Sending SIGTERM to pid: $pid",2);
        my $ret = kill POSIX::SIGTERM, $pid;
        # Wait for zombie process if the process is a zombie (i.e. SIGTERM didn't work)
        wait();
        return ($ret, $pid);
    } else {
        return (-1, 0);
    }
}

sub set_tmpdir {
    $tmpdir = shift;
    push(@tmpdirs, $tmpdir);
}

sub get_tmpdir {
    return $tmpdir;
}

sub get_all_tmpdirs {
    return @tmpdirs;
}

sub save_pms {
    $pms = shift;
}

sub get_pms {
    return $pms;
}

sub get_config {
    return $conf;
}

sub get_wordlist {
    return \%words;
}

sub get_wordweights {
	return \%wweights;
}

sub get_thresholds {
    return \%Threshold;
}

sub set_db_ref {
    $dbref = shift;
}

sub get_db_ref {
    return $dbref;
}

sub get_mysql_ddb {
    return undef unless (HAS_DBI and HAS_DBD_MYSQL);

    my $conf = get_config();
    my %dopts = ( AutoCommit => 1 );
    my $dsn = "dbi:mysql:database=".$conf->{oscar_mysql_db};
    if (defined($conf->{oscar_mysql_socket})) {
        $dsn .= ";mysql_socket=".$conf->{oscar_mysql_socket};
    } else {
        $dsn .= ";host=".$conf->{oscar_mysql_host};
    $dsn .= ";port=".$conf->{oscar_mysql_port} if $conf->{oscar_mysql_port} != 3306;
    }
    debuglog("Connecting to: $dsn");
    my $ddb = DBI->connect($dsn,
        $conf->{oscar_mysql_user},
        $conf->{oscar_mysql_pass},
        \%dopts);
    return $ddb;
}

sub set_config {
    my($self, $conf) = @_;
    my @cmds = ();

    foreach my $t (qw/s h w cn/) {
        push (@cmds, {
            setting => 'oscar_threshold_'.$t,
            default => 0.01,
            type => $Mail::SpamAssassin::Conf::CONF_TYPE_NUMERIC
        });
    }
    foreach my $t (qw/c max_hash/) {
        push (@cmds, {
            setting => 'oscar_threshold_'.$t,
            default => 5,
            type => $Mail::SpamAssassin::Conf::CONF_TYPE_NUMERIC
        });
    }
    foreach my $t (qw/height width/) {
        push (@cmds, {
            setting => 'oscar_min_'.$t,
            default => 4,
            type => $Mail::SpamAssassin::Conf::CONF_TYPE_NUMERIC
        });
        push (@cmds, {
            setting => 'oscar_max_'.$t,
            default => 800,
            type => $Mail::SpamAssassin::Conf::CONF_TYPE_NUMERIC
        });
    }
    push (@cmds, {
        setting => 'oscar_threshold',
        default => 0.25,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_NUMERIC
    });

    push (@cmds, {
        setting => 'oscar_counts_required',
        default => 2,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_NUMERIC
    });

    push (@cmds, {
        setting => 'oscar_verbose',
        default => 1,
        code => sub {
            my ($self, $key, $value, $line) = @_;
            unless (defined $value && $value !~ m/^$/) {
                return $Mail::SpamAssassin::Conf::MISSING_REQUIRED_VALUE;
            }
            unless ($value =~ m/^[0-9]+$/) {
                return $Mail::SpamAssassin::Conf::INVALID_VALUE;
            }
            $self->{oscar_verbose} = $value+0;
        }
    });

    push (@cmds, {
        setting => 'oscar_timeout',
        default => 10,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_NUMERIC
    });

    push (@cmds, {
        setting => 'oscar_global_timeout',
        default => 0,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_BOOL
    });

    push (@cmds, {
        setting => 'oscar_logfile',
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_STRING
    });

    push (@cmds, {
        setting => 'oscar_log_stderr',
        default => 1,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_BOOL
    });

    push (@cmds, {
        setting => 'oscar_log_pmsinfo',
        default => 1,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_BOOL
    });

    push (@cmds, {
        setting => 'oscar_enable_image_hashing',
        default => 0,
        code => sub {
            my ($self, $key, $value, $line) = @_;
            unless (defined $value && $value !~ m/^$/) {
                return $Mail::SpamAssassin::Conf::MISSING_REQUIRED_VALUE;
            }
            unless ($value =~ m/^[0123]$/) {
                return $Mail::SpamAssassin::Conf::INVALID_VALUE;
            }
            $self->{oscar_enable_image_hashing} = $value+0;
        }
    });

    push (@cmds, {
        setting => 'oscar_hashing_learn_scanned',
        default => 1,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_BOOL
    });

    push (@cmds, {
        setting => 'oscar_skip_updates',
        default => 0,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_BOOL
    });

    push (@cmds, {
        setting => 'oscar_digest_db',
        default => "/etc/mail/spamassassin/OScar.hashdb",
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_STRING
    });

    push (@cmds, {
        setting => 'oscar_global_wordlist',
        default => "/etc/mail/spamassassin/Oscar.words",
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_STRING
    });

    push (@cmds, {
        setting => 'oscar_personal_wordlist',
        default => "__userstate__/Oscar.words",
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_STRING
    });
    push (@cmds, {
        setting => 'oscar_no_homedirs',
        default => 0,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_BOOL
    });
    push (@cmds, {
        setting => 'oscar_db_hash',
        default => "/etc/mail/spamassassin/Oscar.db",
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_STRING
    });

    push (@cmds, {
        setting => 'oscar_db_safe',
        default => "/etc/mail/spamassassin/Oscar.safe.db",
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_STRING
    });

    push (@cmds, {
        setting => 'oscar_db_max_days',
        default => 35,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_NUMERIC
    });

    push (@cmds, {
        setting => 'oscar_keep_bad_images',
        default => 0,
        code => sub {
            my ($self, $key, $value, $line) = @_;
            unless (defined $value && $value !~ m/^$/) {
                return $Mail::SpamAssassin::Conf::MISSING_REQUIRED_VALUE;
            }
            unless ($value =~ m/^[012]$/) {
                return $Mail::SpamAssassin::Conf::INVALID_VALUE;
            }
            $self->{oscar_keep_bad_images} = $value+0;
        }
    });

    push (@cmds, {
        setting => 'oscar_strip_numbers',
        default => 1,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_NUMERIC
    });

    push (@cmds, {
        setting => 'oscar_twopass_scoring_factor',
        default => 1.5,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_NUMERIC
    });
    push (@cmds, {
        setting => 'oscar_unique_matches',
        default => 0,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_BOOL
    });

    push (@cmds, {
        setting => 'oscar_score_ham',
        default => 0,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_BOOL
    });

    push (@cmds, {
        setting => 'oscar_base_score',
        default => 5,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_NUMERIC
    });

    push (@cmds, {
        setting => 'oscar_add_score',
        default => 1,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_NUMERIC
    });

    push (@cmds, {
        setting => 'oscar_corrupt_score',
        default => 2.5,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_NUMERIC
    });

    push (@cmds, {
        setting => 'oscar_corrupt_unfixable_score',
        default => 5,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_NUMERIC
    });

    push (@cmds, {
        setting => 'oscar_wrongctype_score',
        default => 1.5,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_NUMERIC
    });

    push (@cmds, {
        setting => 'oscar_wrongext_score',
        default => 1.5,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_NUMERIC
    });

    push (@cmds, {
        setting => 'oscar_autodisable_score',
        default => 10,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_NUMERIC
    });

    push (@cmds, {
        setting => 'oscar_autodisable_negative_score',
        default => -5,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_NUMERIC
    });

    push (@cmds, {
        setting => 'oscar_path_bin',
        default => '/usr/local/netpbm/bin:/usr/local/bin:/usr/bin',
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_STRING
    });

    foreach (@bin_utils) {
        push (@cmds, {
            setting => 'oscar_bin_'.$_,
            type => $Mail::SpamAssassin::Conf::CONF_TYPE_STRING
        });
    }

    foreach (@img_types) {
        push (@cmds, {
            setting => 'oscar_skip_'.$_,
            default => 0,
            type => $Mail::SpamAssassin::Conf::CONF_TYPE_BOOL
        });
        push (@cmds, {
            setting => 'oscar_max_size_'.$_,
            type => $Mail::SpamAssassin::Conf::CONF_TYPE_NUMERIC
        });
    }

    push (@cmds, {
        setting => 'oscar_scan_pdfs',
        default => 0,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_BOOL
    });
    push (@cmds, {
        setting => 'oscar_pdf_maxpages',
        default => 1,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_NUMERIC
    });

    push (@cmds, {
        setting => 'oscar_autosort_buffer',
        default => 10,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_NUMERIC
    });
    push (@cmds, {
        setting => 'oscar_mysql_host',
        default => 'localhost',
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_STRING
    });

    push (@cmds, {
        setting => 'oscar_mysql_port',
        default => 3306,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_NUMERIC
    });
    push (@cmds, {
        setting => 'oscar_mysql_socket',
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_STRING
    });
    push (@cmds, {
        setting => 'oscar_mysql_db',
        default => 'FuzzyOcr',
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_STRING
    });
    push (@cmds, {
        setting => 'oscar_mysql_hash',
        default => 'Hash',
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_STRING
    });
    push (@cmds, {
        setting => 'oscar_mysql_safe',
        default => 'Safe',
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_STRING
    });
    push (@cmds, {
        setting => 'oscar_mysql_update_hash',
        default => 0,
        type => $Mail::SpamAssassin::Conf::CONF_TYPE_BOOL
    });
    foreach (qw/user pass/) {
        push (@cmds, {
           setting => 'oscar_mysql_'.$_,
            default => 'fuzzyocr',
            type =>  $Mail::SpamAssassin::Conf::CONF_TYPE_STRING
        });
    }

    $conf->{parser}->register_commands(\@cmds);
}

sub parse_config {
    my ($self, $opts) = @_;

    # Don't parse a config twice
    if ($initialized) { return 1; }

    if ($opts->{key} eq 'oscar_end_config') {
        $conf = $opts->{conf};
        my $main = $self->{main};
        my $retcode;

        return 1;
    } elsif ($opts->{key} eq 'oscar_bin_helper') {
        my @cmd; $conf = $opts->{conf};
        my $val = $opts->{value}; $val =~ s/[\s]*//g;
        debuglog("oscar_bin_helper: '$val'");
        foreach my $bin (split(',',$val)) {
            unless (grep {m/$bin/} @bin_utils) {
                push @bin_utils, $bin;
                push (@cmd, {
                    setting => 'oscar_bin_'.$bin,
                    type => $Mail::SpamAssassin::Conf::CONF_TYPE_STRING
                });
            } else {
                warnlog("$bin is already defined, skipping...");
            }
        }
        if (scalar(@cmd)>0) {
            infolog("Adding <".scalar(@cmd)."> new helper apps");
            $conf->{parser}->register_commands(\@cmd)
        }
        return 1;
    }
    return 0;
}

sub finish_parsing_end {
    my ($self, $opts) = @_;

    # Don't call this function twice
    if ($initialized) { return 1; }

    my $main = $self->{main};
    $conf = $opts->{conf};

    # find external binaries
    @paths = split(/:/, $conf->{oscar_path_bin});
    infolog("Searching in: $_") foreach @paths;
    foreach my $a (@bin_utils) {
        my $b = "oscar_bin_$a";
        if (defined $conf->{$b} and ! -x $conf->{$b}) {
            infolog("cannot exec $a, removing...");
            delete $conf->{$b};
        } 
        if (defined $conf->{$b}) {
        	if ($conf->{$b} =~ m/^(\S+)$/) {
        		$conf->{$b} = $1;
                debuglog("Using $a => $conf->{$b}");
        	}
        } else {
            foreach my $p (@paths) {
                my $f = "$p/$a";
                next unless -x $f;
                $conf->{$b} = $f;
                last;
            }
            if (defined $conf->{$b}) {
                infolog("Using $a => $conf->{$b}");
            } else {
                warnlog("Cannot find executable for $a");
            }
        }
    }

    # Allow scanning if in debug mode?
    $conf->{oscar_autodisable_score} = 1000
        if $Mail::SpamAssassin::Logger::LOG_SA{level} == 3;

    # Extract Thresholds
    foreach my $k (keys %{$conf}) {
        if ($k =~ m/^oscar_threshold_(\S+)/) {
            $Threshold{$1} = $conf->{$k};
            debuglog("Threshold[$1] => $conf->{$k}");
        }
    }
    # Display All Options
    foreach my $k (sort keys %{$conf}) {
        next unless $k =~ m/^oscar_/;
        next if $k =~ m/^oscar_bin_/;
        next if $k =~ m/^oscar_mysql_pass/;
        next if $k =~ m/^oscar_threshold_/;
        debuglog(" $k => ".$conf->{$k});
    }

    if ($conf->{oscar_enable_image_hashing} == 3) {
        unless (HAS_DBI and HAS_DBD_MYSQL) {
            $conf->{oscar_enable_image_hashing} = 0;
            errorlog("Disable Image Hashing");
            errorlog("Missing DBI") unless HAS_DBI;
            errorlog("Missing DBD::mysql") unless HAS_DBD_MYSQL;
        }

        # Warn if MLDBM databases are present, but can't be imported
        unless (HAS_MLDBM and HAS_DB_FILE and HAS_STORABLE and (-r $conf->{oscar_db_hash} or -r $conf->{oscar_db_safe})) {
            infolog("Importing for MLDBM databases not available (dependencies missing)");
        }
    }
    if ($conf->{oscar_enable_image_hashing} == 2) {
        unless (HAS_MLDBM and HAS_DB_FILE and HAS_STORABLE) {
            $conf->{oscar_enable_image_hashing} = 0;
            errorlog("Disable Image Hashing");
            errorlog("Missing MLDBM and/or MLDBM::Sync") unless HAS_MLDBM;
            errorlog("Missing DB_File") unless HAS_DB_FILE;
            errorlog("Missing Storable") unless HAS_STORABLE;
        }
    }
    unless ($conf->{oscar_skip_updates}) {
        if ($conf->{oscar_enable_image_hashing} == 2 and -r $conf->{oscar_digest_db}) {
            import MLDBM qw(DB_File Storable);
            my %DB; my $dbm; my $sdbm; my $err = 0;
            my $now = time - ($conf->{oscar_db_max_days}*86400);
            $sdbm = tie %DB, 'MLDBM::Sync', $conf->{oscar_db_hash} or $err++;
            if ($err) {
                errorlog("Could not open \"$conf->{oscar_db_hash}\"");
            } else {
                $sdbm->Lock;
                my $hash = 0;
                infolog("Expiring records prior to: ".scalar(localtime($now)));
                foreach my $k (keys %DB) {
                    my $db = $DB{$k};
                    if ($db->{check} < $now) {
                        infolog("Expire: <$k> Reason: $db->{check} < $now");
                        delete $DB{$k}; $hash++;
                    }
                }
                infolog("Expired <$hash> Image Hashes after $conf->{oscar_db_max_days} day(s)")
                    if ($hash>0);
                $hash = 0;
                open HASH, $conf->{oscar_digest_db};
                while (<HASH>) {
                    chomp;
                    my($score,$basic,$key) = split('::',$_,3);
                    next if (defined $DB{$key});
                    $dbm = $DB{$key};
                    $dbm->{score} = $score;
                    $dbm->{basic} = $basic;
                    $dbm->{input} =
                    $dbm->{check} = time;
                    $dbm->{match} = 1;
                    $DB{$key} = $dbm;
                    $hash++;
                }
                close HASH;
                infolog("Imported <$hash> Image Hashes from \"$conf->{oscar_digest_db}\"")
                    if ($hash>0);
                $hash = scalar(keys %DB);
                infolog("<$hash> Known BAD Image Hashes Available");
                $sdbm->UnLock;
                undef $sdbm;
                untie %DB;
            }
            $err = 0;
            $sdbm = tie %DB, 'MLDBM::Sync', $conf->{oscar_db_safe} or $err++;
            if ($err) {
                errorlog("Could not open \"$conf->{oscar_db_safe}\"");
            } else {
                $sdbm->Lock;
                my $hash = 0;
                foreach my $k (keys %DB) {
                    my $db = $DB{$k};
                    if ($db->{check} < $now) {
                        infolog("Expire: <$k> Reason: $db->{check} < $now");
                        delete $DB{$k}; $hash++;
                    }
                }
                infolog("Expired <$hash> Image Hashes after $conf->{oscar_db_max_days} day(s)")
                    if ($hash>0);
                $hash = scalar(keys %DB);
                infolog("<$hash> Known GOOD Image Hashes Available");
                $sdbm->UnLock;
                undef $sdbm;
                untie %DB;
            }
        }
        if ($conf->{oscar_enable_image_hashing} == 3 and defined (my $ddb = get_mysql_ddb())
            and (-r $conf->{oscar_db_hash} or -r $conf->{oscar_db_safe})
            and HAS_MLDBM and HAS_DB_FILE and HAS_STORABLE) {

            import MLDBM qw(DB_File Storable);
            my $db   = $conf->{oscar_mysql_db};
            my $tab  = $conf->{oscar_mysql_hash};
            my $file = $conf->{oscar_db_hash};
            my %DB; my $dbm; my $sdbm; my $err = 0;
            $sdbm = tie %DB, 'MLDBM::Sync', $file or $err++;
            if ($err) {
                errorlog("Could not open \"$file\"");
            } else {
                $sdbm->ReadLock;
                foreach my $k (keys %DB) {
                    my $dbm = $DB{$k};
                    my $sql = qq(select score from $db.$tab where $tab.key='$k');
                    my @data = $ddb->selectrow_array($sql);
                    unless (scalar(@data)>0) {
                        $sql  = "insert into $db.$tab values ('$k'";
                        foreach my $y (qw/basic fname ctype/) {
                            my $val = defined($dbm->{$y}) ? $dbm->{$y} : '';
                            $sql .= ",'$val'";
                        }
                           if ($dbm->{ctype} =~ m/gif/i)      { $sql .= ",'1'"; }
                        elsif ($dbm->{ctype} =~ m/jpg|jpeg/i) { $sql .= ",'2'"; }
                        elsif ($dbm->{ctype} =~ m/png/i)      { $sql .= ",'3'"; }
                        elsif ($dbm->{ctype} =~ m/bmp/i)      { $sql .= ",'4'"; }
                        elsif ($dbm->{ctype} =~ m/tiff/i)     { $sql .= ",'5'"; }
                        else                                  { $sql .= ",'0'"; }
                        foreach my $y (qw/match input check score dinfo/) {
                            my $val = defined($dbm->{$y}) ? $dbm->{$y} : '';
                            $sql .= ",'$val'";
                        }
                        $sql .= ")";
                        debuglog($sql);
                        $ddb->do($sql); $err++;
                    }
                }
                $sdbm->UnLock;
                undef $sdbm;
                untie %DB;
                infolog("Stored [$err] Hashes in $db.$tab") if $err>0;
            }
            $tab  = $conf->{oscar_mysql_safe};
            $file = $conf->{oscar_db_safe};
            $err  = 0;
            $sdbm = tie %DB, 'MLDBM::Sync', $file or $err++;
            if ($err) {
                errorlog("Could not open \"$file\"");
            } else {
                $sdbm->ReadLock;
                foreach my $k (keys %DB) {
                    my $dbm = $DB{$k};
                    my $sql = qq(select score from $db.$tab where $tab.key='$k');
                    my @data = $ddb->selectrow_array($sql);
                    unless (scalar(@data)>0) {
                        $sql  = "insert into $db.$tab values ('$k'";
                        foreach my $y (qw/basic fname ctype/) {
                            my $val = defined($dbm->{$y}) ? $dbm->{$y} : '';
                            $sql .= ",'$val'";
                        }
                           if ($dbm->{ctype} =~ m/gif/i)      { $sql .= ",'1'"; }
                        elsif ($dbm->{ctype} =~ m/jpg|jpeg/i) { $sql .= ",'2'"; }
                        elsif ($dbm->{ctype} =~ m/png/i)      { $sql .= ",'3'"; }
                        elsif ($dbm->{ctype} =~ m/bmp/i)      { $sql .= ",'4'"; }
                        elsif ($dbm->{ctype} =~ m/tiff/i)     { $sql .= ",'5'"; }
                        else                                  { $sql .= ",'0'"; }
                        foreach my $y (qw/match input check score dinfo/) {
                            my $val = defined($dbm->{$y}) ? $dbm->{$y} : '';
                            $sql .= ",'$val'";
                        }
                        $sql .= ")";
                        debuglog($sql);
                        $ddb->do($sql); $err++;
                    }
                }
                $sdbm->UnLock;
                undef $sdbm;
                untie %DB;
                infolog("Stored [$err] Hashes in $db.$tab") if $err>0;
            }
            debuglog("done updating MySQL database");
            $ddb->disconnect;
        }
    }
    read_words( $conf->{oscar_global_wordlist} , 'Global');
    1;

    # Important: We parsed the config now and did all post config parsing stuff
    # don't do it again (for amavisd and other 3rd party applications using the SA API directly)
    $initialized = 1;
}

sub read_words {
    my $wfile = $_[0];
    return unless ( -e $wfile );
    my $tfile = $_[1] || 'Personal';
    unless ( -r $wfile ) {
        warnlog("Cannot read $tfile wordlist: \"$wfile\"\n Please check file path and permissions are correct.");
        return;
    }
    my $cnt = 0;
    open WORDLIST, "<$wfile";
    while(my $w = <WORDLIST>) {
        chomp($w);
        $w =~ s/\s*//;
        $w =~ s/#(.*)//;
        next unless $w;
        my $wt = $conf->{oscar_threshold};
        my $weight = 1;
        $wt = $conf->{oscar_threshold};
        $wt *= 0.750 if length($w) == 5;
        $wt *= 0.500 if length($w) == 4;
        $wt *= 0.250 if length($w)  < 4;
        if ($w =~ /\/\/(0\.\d+)/) {
        	$weight = $1;
        }
        if ($w =~ /::(0\.\d+)/) {
            $wt = $1;
        }
        $w =~ s/[\/:]{2}(0\.\d+)//g;
        
        $words{$w} = $wt;
        $wweights{$w} = $weight;
        $cnt++;
    }
    close WORDLIST;
    infolog("Added <$cnt> words from \"$wfile\"") if ($cnt>0);
}

1;
