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
package OscarOcr::Hashing;

use base 'Exporter';
our @EXPORT_OK = qw(check_image_hash_db
    add_image_hash_db 
    calc_image_hash);

use lib qw(..);
use OscarOcr::Config qw(get_thresholds get_config set_config get_tmpdir get_mysql_ddb);
use OscarOcr::Misc qw(save_execute);
use OscarOcr::Logging qw(debuglog errorlog warnlog infolog);
use Fcntl;
use Fcntl ':flock';

#Implements all functions related to Image Hashing

sub within_threshold {
    my $thresref = get_thresholds();
    my %Threshold = %$thresref;

    my $digest = shift;
    my $record = shift;

    my ($dimg,$dkey) = split('::',$digest);
    my ($rimg,$rkey) = split('::',$record);
    my ($ds, $dh, $dw, $dcn) = split(':',$dimg);
    my ($rs, $rh, $rw, $rcn) = split(':',$rimg);
    return(0) unless $rs;
    return(0) unless $rh;
    return(0) unless $rw;
    return(0) unless $rcn;
    return(0) unless $rkey;
    return(0) if ((abs($ds  - $rs ) / $rs ) > $Threshold{s});
    return(0) if ((abs($dh  - $rh ) / $rh ) > $Threshold{h});
    return(0) if ((abs($dw  - $rw ) / $rw ) > $Threshold{w});
    return(0) if ((abs($dcn - $rcn) / $rcn) > $Threshold{cn}); 
            
    my @rcf = split('::',$rkey);
    my @dcf = split('::',$dkey);

    my (@dcfs, @rcfs);
    foreach (@dcf) { push @dcfs,split(':',$_); }
    foreach (@rcf) { push @rcfs,split(':',$_); }

    my $total = scalar(@rcfs);
    if ($total == scalar(@dcfs)) {
        my $match = 0;
        foreach (0 .. ($total-1)) {
            $match++ if (abs($dcfs[$_] - $rcfs[$_]) <= $Threshold{c});
        }
        infolog("image matched <$match> of <$total> colors");
        return(1) if ($match == $total);
    }
    return(0);
}

sub check_image_hash_db {
    my $conf = get_config();
    if ($conf->{oscar_enable_image_hashing} == 0) {
        warnlog("Image Hashing is disabled");
        return (0,'');
    }
    my $digest = $_[0];
    my $dbfile = $_[1] || $conf->{oscar_db_hash};
    my $fname  = $_[2];
    my $ctype  = $_[3];
    my $ftype  = $_[4] || 0;
    my ($img, $key) = split('::', $digest,2);
    return (0,'') unless defined $key;
    my $now = time;
    my $hash = $digest;
    my $ret = 0; my $txt = 'Exact';
    my $dinfo;

    if ($conf->{oscar_enable_image_hashing} == 3) {
        unless (defined $conf->{oscar_mysql_ddb}) {
            warnlog("Connection to MySQL server unavailable");
            return (0,'');
        }
        my $ddb   = $conf->{oscar_mysql_ddb};
        my $db    = $conf->{oscar_mysql_db};
        my $sql   = qq(select * from $db.$dbfile where $dbfile.key='$key');
        my @data  = $ddb->selectrow_array($sql);
        my $next  = 0;
        my $when  = 0;
        my $match = 0;
        if ((scalar(@data)>0) and ($img eq $data[1])) {
            $match++;
            infolog("Found[$dbfile]: Score='$data[8]' Info: '$data[9]'");
            $next  = $data[5]; $next++;
            $when  = $data[7]; $data[8] += 0;
            $ret   = $data[8] == 0 ? 0.001 : $data[8];
            $dinfo = $data[9] || '';
            if ($data[2] eq '') {
                infolog("Updating $txt info File-Name:'$fname'");
                $ddb->do(qq(update $db.$dbfile set $dbfile.fname=? where $dbfile.key='$key'),undef,$fname);
            }
            if ($data[3] eq '') {
                infolog("Updating $txt info Content-Type:'$ctype'");
                $ddb->do(qq(update $db.$dbfile set $dbfile.ctype=? where $dbfile.key='$key'),undef,$ctype);
            }
            if ($data[4] != $ftype) {
                infolog("Updating $txt info File-Type:'$ftype'");
                $ddb->do(qq(update $db.$dbfile set $dbfile.ftype=? where $dbfile.key='$key'),undef,$ftype);
            }
        }
        unless ($match) {
            my $then = time - ($conf->{oscar_db_max_days}*86400);
            $sql = qq(select * from $db.$dbfile order by $dbfile.check);
            my $sth  = $ddb->prepare($sql); $sth->execute;
            while (my @row = $sth->fetchrow_array) {
                my $hash2 = $row[1] || "0:0:0:0";
                $hash2 .= "::$row[0]";
                if (within_threshold($digest,$hash2)) {
                    $txt   = 'Approx';
                    $key   = $row[0];
                    $next  = $row[5] + 1;
                    $when  = $row[7] || $now;
                    $ret   = $dbfile eq $conf->{oscar_mysql_hash} ? $row[8] : $row[5];
                    $dinfo = $row[9] || '';
                    infolog("Found[$dbfile]: Score='$row[8]' Info: '$row[9]'");
                    last;
                }
            }
            # Expire old records...
            $sql = qq(delete from $db.$dbfile where $dbfile.check < $then);
            debuglog($sql,2);
            $ddb->do($sql);
        }
        if ($ret > 0) {
            if ($dbfile eq $conf->{oscar_mysql_hash}) {
                infolog("Found Score <$ret> for $txt Image Hash");
            }
            infolog("Matched [$next] time(s). Prev match: ".fmt_time($now - $when));
            $sql = qq(update $db.$dbfile set $dbfile.match='$next',$dbfile.check='$now' where $dbfile.key='$key');
            $ddb->do($sql);
            debuglog($sql);
        }
        return ($ret,$dinfo);
    }
    elsif ($conf->{oscar_enable_image_hashing} == 2) {
        import MLDBM qw(DB_File Storable);
        my %DB = (); my $dbm; my $sdbm;
        $sdbm = tie %DB, 'MLDBM::Sync', $dbfile, O_CREAT|O_RDWR or $ret++;
        if ($ret>0) {
            warnlog("No Image Hash database found at \"$dbfile\", or permissions wrong.");
            return (0,'');
        }
        $sdbm->Lock;
        if (defined $DB{$key}) {
            $dbm = $DB{$key};
            if ($img eq $dbm->{basic}) {
                $ret = $dbm->{score} || 0.001;
                $dinfo = $dbm->{dinfo} || '';
                $dbm->{fname} = $fname;
                $dbm->{ctype} = $ctype;
                infolog("Updating $txt info File:'$fname' Type:'$ctype'");
                $DB{$key} = $dbm;
            }
        }
        if ($ret == 0) {
            my $then = time - ($conf->{oscar_db_max_days}*86400);
            foreach my $k (keys %DB) {
                $dbm  = $DB{$k};
                $hash = $dbm->{basic} ? $dbm->{basic} : "0:0:0:0::$k";
                if (within_threshold($digest,$hash)) {
                    $ret  = $dbfile eq $conf->{oscar_db_hash} ? $dbm->{score} : $dbm->{match};
                    $txt  = 'Approx'; $dinfo = $dbm->{dinfo} || '';
                    infolog("Found in: <$dbfile>");
                    last;
                }
                # Has the record expired??
                $dbm->{check} = $now - 1 unless defined $dbm->{check};
                if ($dbm->{check} < $then) {
                    infolog("Expiring <$k> older than $conf->{oscar_db_max_days} days");
                    delete $DB{$k};
                }
            }
        }
        if ($ret>0) {
            $dbm->{match}++;
            if ($dbfile eq $conf->{oscar_db_hash}) {
                $ret = sprintf("%0.3f",$dbm->{score});
                infolog("Found Score <$ret> for $txt Image Hash");
            }
            infolog("Matched [$dbm->{match}] time(s). Prev match: ".fmt_time(time - $dbm->{check}));
            $dbm->{check} = time;
            $DB{$key} = $dbm;
        }
        $sdbm->UnLock;
        undef $sdbm;
        untie %DB;
        return ($ret,$dinfo);
    }
    elsif ($conf->{oscar_enable_image_hashing} == 1) {
        $ret = open HASH, $conf->{oscar_digest_db};
        unless($ret) {
            warnlog("No Image Hash database found at \"$conf->{oscar_digest_db}\", or permissions wrong.");
            return (0,'');
        }
        while (<HASH>) {
            chomp;
            ($ret,$hash) = split('::',$_,2);
            if (within_threshold($digest,$hash)) {
                infolog("Found Score <$ret> for Hash <$hash>");
                return ($ret,'');
            }
        }
        close HASH;
        return (0,'');
    }
}

sub add_image_hash_db {
    my $conf = get_config();
    return if ($conf->{oscar_enable_image_hashing} == 0);
    my $digest = $_[0];
    my $score  = $_[1];
    my $ret = 0;

    if ($conf->{oscar_enable_image_hashing} == 3) {
        unless (defined $conf->{oscar_mysql_ddb}) {
            warnlog("Connection to MySQL server unavailable");
            return;
        }
        my $db    = $conf->{oscar_mysql_db};
        my $ddb   = $conf->{oscar_mysql_ddb};
        my $table = $_[2] || $conf->{oscar_mysql_hash};
        my $fname = $_[3] || '';
        my $ctype = $_[4] || 'image';
        my $ftype = $_[5] || 0;
        my $dinfo = $_[6] || '';
        infolog("Adding Hash to table: \"$db.$table\" with score \"$score\"");
        my $sql;
        my ($img,$key) = split('::',$digest,2);
        if (defined $key) {
            $sql = "select basic from $db.$table where $table.key='$key'";
            my @data = $ddb->selectrow_array($sql);
            if (scalar(@data)>0) {
                if ($conf->{oscar_mysql_update_hash}) {
                    infolog("Hash already in $db.$table updating...");
                    $sql  = "update $db.$table set ";
                    my @params;
                    unless ($data[1] eq $img) {
                        $sql .= "basic=?,"; push @params,$img;
                    }
                    unless ($data[2] eq $fname) {
                        $sql .= "fname=?,"; push @params,$fname;
                    }
                    unless ($data[3] eq $ctype) {
                        $sql .= "ctype=?,"; push @params,$ctype;
                    }
                    unless ($data[4] == $ftype) {
                        $sql .= "ftype=?,"; push @params,$ftype;
                    }
                    unless ($data[8] == $score) {
                        $sql .= "score=?,"; push @params,$score;
                    }
                    unless ($data[9] == $dinfo) {
                        $sql .= "dinfo=?,"; push @params,$dinfo;
                    }
                    $sql  =~ s/,$//;
                    $sql .= " where $table.key='$key'";
                    $ddb->do($sql,undef,@params);
                    foreach my $p (@params) { $sql =~ s/\?/$p/; }
                    debuglog($sql);
                } else {
                    infolog("Hash already in $db.$table skipping...");
                }
            } else {
                my @params = (
                    $key, $img, $fname, $ctype, $ftype,
                    ($table eq $conf->{oscar_mysql_hash} ? 0 : 1),
                    time, time, $score, $dinfo);
                $sql = "insert into $db.$table values (?,?,?,?,?,?,?,?,?,?)";
                $ddb->do($sql,undef,@params);
                foreach my $p (@params) { $sql =~ s/\?/$p/; }
                debuglog($sql);
            }
        }
    }
    elsif ($conf->{oscar_enable_image_hashing} == 2) {
        import MLDBM qw(DB_File Storable);
        my $dbfile = $_[2] || $conf->{oscar_db_hash};
        my %DB = (); my $sdbm;
        $sdbm = tie %DB, 'MLDBM::Sync', $dbfile, O_CREAT|O_RDWR or $ret++;
        if ($ret>0) {
            warnlog("Unable to open/create Image Hash database at \"$dbfile\", check permissions.");
            return;
        }
        $sdbm->Lock;
        infolog("Adding Hash to \"$dbfile\" with score \"$score\"");
        my ($img,$key) = split('::',$digest,2);
        if (defined $key) {
            my $dbm = $DB{$key};
            $dbm->{fname} = $_[3];
            $dbm->{ctype} = $_[4];
            $dbm->{ftype} = $_[5];
            $dbm->{dinfo} = $_[6];
            $dbm->{basic} = $img;
            $dbm->{score} = $score;
            $dbm->{input} = 
            $dbm->{check} = time;
            $dbm->{match} = $dbfile eq $conf->{oscar_db_hash} ? 0 : 1;
            $DB{$key} = $dbm;
        }
        $sdbm->UnLock;
        undef $sdbm;
        untie %DB;
    }
    elsif ($conf->{oscar_enable_image_hashing} == 1) {
        if (-e $conf->{oscar_digest_db}) {
            $ret = open DB, ">>$conf->{oscar_digest_db}";
        } else {
            $ret = open DB,  ">$conf->{oscar_digest_db}";
        }
        unless ($ret) {
            warnlog("Unable to open/create Image Hash database at \"$conf->{oscar_digest_db}\", check permissions.");
            return;
        }
        infolog("Adding Hash to \"$conf->{oscar_digest_db}\"");
        flock( DB, LOCK_EX );
        seek( DB, 0, 2 );
        print DB "${score}::${digest}\n";
        close(DB);
    }
    debuglog("Digest: $digest");
}

sub calc_image_hash {
    my $conf = get_config();
    my $imgdir = get_tmpdir();
    my $thresref = get_thresholds();
    my %Threshold = %$thresref;
    my $pfile = $_[0];
    my $pic   = $_[1];
    my $hash;

    foreach my $a (qw/ppmhist/) { #pamfile
        unless (defined $conf->{"oscar_bin_$a"}) {
            errorlog("calc_image_hash cannot exec $a");
            return (1, '');
        }
    }
    
    unless (-r $pfile) {
        errorlog("Cannot read $pfile");
        return(1, '');
    }

    my ($r, @stdout_data) = save_execute(
        "$conf->{oscar_bin_ppmhist} -noheader $pfile", undef,
        ">$imgdir/ppmhist.info",
        ">/dev/null", 1);

    if ($r) {
        chomp $r;
        errorlog("$conf->{oscar_bin_ppmhist}: ".
            ($r<0) ? 'Timed out' : 'Error'
            ." [$r], skipping...");
        return (1, '');
    }

    my $cnt = 0;
    my $c = scalar(@stdout_data);
    my $s = (stat($pfile))[7] || 0;
    $hash = sprintf "%d:%d:%d:%d",$s,
        defined $pic->{height} ? $pic->{height} : 0,
        defined $pic->{width}  ? $pic->{width}  : 0,
        $c;
    if ($Threshold{max_hash}) {
        foreach (@stdout_data) {
            $_ =~ s/ +/ /g;
            my(@d) = split(' ', $_);
            $hash .= sprintf("::%d:%d:%d:%d:%d",@d);
            if ($cnt++ ge $Threshold{max_hash}) {
                last;
            }
        }
    }
    debuglog("Got: <$hash>");
    return(0, $hash);
}

sub fmt_time {
    my $when = $_[0];
    my $ret;

    if ($when>86400) {
        my $d = int($when/86400);
        $when -= $d*86400;
        $ret = "$d days,";
    }
    if ($when>3600) {
        my $h = int($when/3600);
        $when -= $h*3600;
        $ret .= " $h hrs.";
    }
    if ($when>60) {
        my $m = int($when/60);
        $when -= $m*60;
        $ret .= " $m min.";
    }
    if ($when>0) {
        $ret .= " $when sec.";
    }
    $ret .= " ago";
    return $ret;
}

1;
