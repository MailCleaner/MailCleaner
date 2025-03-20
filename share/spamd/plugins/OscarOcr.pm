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

# FuzzyOcr plugin, version 3.6
#
# written by Christian Holler (decoder_at_own-hero_dot_net)
#   and Jorge Valdes (jorge_at_joval_dot_info)

package OscarOcr;

use strict;
use warnings;
use POSIX;
use Fcntl ':flock';
use Mail::SpamAssassin;
use Mail::SpamAssassin::Logger;
use Mail::SpamAssassin::Util;
use Mail::SpamAssassin::Timeout;
use Mail::SpamAssassin::Plugin;
use oscar;

use Time::HiRes qw( gettimeofday tv_interval );
use String::Approx 'adistr';
use FileHandle;

BEGIN { 
  my $CONFIGFILE = "/etc/mailcleaner.conf";
  my %config = readConfig();
  my $plugindir=$config{'SRCDIR'}."/share/spamassassin/plugins";	
  unshift(@INC, $plugindir) ;
  
  sub readConfig {
    my $configfile = $CONFIGFILE;

    my %config;
        my ($var, $value);

    open CONFIG, $configfile or die "Cannot open $configfile: $!\n";
        while (<CONFIG>) {
                chomp;                  # no newline
                s/#.*$//;                # no comments
                s/^\*.*$//;             # no comments
                s/;.*$//;                # no comments
                s/^\s+//;               # no leading white
                s/\s+$//;               # no trailing white
                next unless length;     # anything left?
                my ($var, $value) = split(/\s*=\s*/, $_, 2);
                if ($value =~ m/^(.*)$/) {
                   $config{$var} = $1;
                }
        }
        close CONFIG;
      return %config;
   }
}

use OscarOcr::Logging qw(debuglog errorlog warnlog infolog salog);
use OscarOcr::Config qw(kill_pid
    get_tmpdir
    set_tmpdir
    get_all_tmpdirs
    get_pms
    save_pms
    get_timeout
    get_mysql_ddb
    get_scansets
    get_wordlist
    get_wordweights
    set_config
    get_config
    parse_config
    finish_parsing_end
    read_words);
use OscarOcr::Hashing qw(check_image_hash_db add_image_hash_db calc_image_hash);
use OscarOcr::Deanimate qw(deanimate);
use OscarOcr::Scoring qw(wrong_ctype wrong_extension corrupt_img known_img_hash);
use OscarOcr::Misc qw(max removedir removedirs save_execute);

our @ISA = qw(Mail::SpamAssassin::Plugin);

# constructor: register the eval rule
sub new {
    my ( $class, $mailsa ) = @_;
    $class = ref($class) || $class;
    my $self = $class->SUPER::new($mailsa);
    bless( $self, $class );
    $self->register_eval_rule("oscar_check");
    $self->register_eval_rule("dummy_check");
    $self->set_config($mailsa->{conf});
    $ENV{'TESSDATA_PREFIX'} = '/opt/impact/';
    return $self;
}

sub dummy_check {
    return 0;
}

sub oscar_check {
    my ( $self, $pms ) = @_;
    my $conf = get_config();

    save_pms($pms);

    my $end;
    my $begin = [gettimeofday];
    if ($conf->{oscar_global_timeout}) {
        my $t = get_timeout();
        debuglog("Global Timeout set at ".$conf->{oscar_timeout}." sec.");
        $t->run(sub {
            $end = oscar_do( $self, $conf, $pms );
        });
        if ($t->timed_out()) {
            infolog("Scan timed out after $conf->{oscar_timeout} seconds.");
            infolog("Killing possibly running pid...");
            my ($ret, $pid) = kill_pid();
            if ($ret > 0) {
                    infolog("Successfully killed PID $pid");
            } elsif ($ret < 0) {
                infolog("No processes left... exiting");
            } else {
                infolog("Failed to kill PID $pid, stale process!");
            }
            infolog("Removing possibly leftover tempdirs...");
            removedirs(get_all_tmpdirs());
            return 0;
        }
    } else {
        $end = fuoscar_do( $self, $conf, $pms );
    }
    debuglog("Processed in ".
        sprintf("%.6f",tv_interval($begin, [gettimeofday]))
        ." sec.");
    return $end;
}

sub oscar_do {
    my ( $self, $conf, $pms ) = @_;

    my $internal_score = 0;
    my $current_score = $pms->get_score();
    my $score = $conf->{oscar_autodisable_score} || 100;

    if ( $current_score > $score ) {
        infolog("Scan canceled, message has already more than $score points ($current_score).");
        return 0;
    }

    my $nscore = $conf->{oscar_autodisable_negative_score} || -100;
    if ( $current_score < $nscore ) {
        infolog("Scan canceled, message has less than $nscore points ($current_score).");
        return 0;
    }

    my $imgdir;
    my %imgfiles = ();
    my @found    = ();
    my @hashes   = ();
    my $cnt      = 0;
    my $imgerr   = 0;
    my $main     = $self->{main};

    debuglog("Starting Oscar...");
    
    #Show PMS info if asked to
    if ($conf->{oscar_log_pmsinfo}) {
        my $msgid = $pms->get('Message-Id') ? $pms->get('Message-Id') : "<no messageid>";
        my $from = $pms->get('From') ? $pms->get('From') : "<no sender>";
        my $to = $pms->get('To') ? $pms->get('To') : "<no recipients>";
        chomp($from, $to, $msgid);
        infolog("Processing Message with ID \"$msgid\" ($from -> $to)");
    }

    foreach my $p (
        $pms->{msg}->find_parts(qr(^image\b)i),
        $pms->{msg}->find_parts(qr(Application/Octet-Stream)i),
	$pms->{msg}->find_parts(qr(application/pdf)i)
    ) {
        my $ctype = $p->{'type'};
        my $fname = $p->{'name'} || 'unknown';
        if (($fname eq 'unknown') and
            (defined $p->{'headers'}->{'content-id'})
            ){
            $fname = join('',@{$p->{'headers'}->{'content-id'}});
            $fname =~ s/[<>]//g;
            $fname =~ tr/\@\$\%\&/_/s;
        }

        my $filename = $fname; $filename =~ tr{a-zA-Z0-9\-.}{_}cs;
        debuglog("fname: \"$fname\" => \"$filename\"");
        my $pdata = $p->decode();
        my $pdatalen = length($pdata);
        my $w = 0; my $h = 0;

        if ( substr($pdata,0,3) eq "\x47\x49\x46" ) {
            ## GIF File
            $imgfiles{$filename}{ftype} = 1; 
            ($w,$h) = unpack("vv",substr($pdata,6,4));
            infolog("GIF: [${h}x${w}] $filename ($pdatalen)");
            $imgfiles{$filename}{width}  = $w;
            $imgfiles{$filename}{height} = $h;
        } elsif ( substr($pdata,0,2) eq "\xff\xd8" ) {
            ## JPEG File
            my @Markers = (0xC0,0xC1,0xC2,0xC3,0xC5,0xC6,0xC7,0xC9,0xCA,0xCB,0xCD,0xCE,0xCF);
            my $pos = 2;
            while ($pos < $pdatalen) {
                my ($b,$m) = unpack("CC",substr($pdata,$pos,2)); $pos += 2;
                if ($b != 0xff) {
                   infolog("Invalid JPEG image");
                   $pos = $pdatalen + 1;
                   last;
                }
                my $skip = 0;
                foreach my $mm (@Markers) {
                    if ($mm == $m) {
                        $skip++; last;
                    }
                }
                last if ($skip);
                $pos += unpack("n",substr($pdata,$pos,2));
            }
            if ($pos > $pdatalen) {
                errorlog("Cannot find image dimensions");
            } else {
                ($h,$w) = unpack("nn",substr($pdata,$pos+3,4));
                infolog("JPEG: [${h}x${w}] $filename ($pdatalen)");
                $imgfiles{$filename}{ftype} = 2;
                $imgfiles{$filename}{height} = $h;
                $imgfiles{$filename}{width}  = $w;
            }
        } elsif ( substr($pdata,0,4) eq "\x89\x50\x4e\x47" ) {
            # PNG File
            ($w,$h) = unpack("NN",substr($pdata,16,8));
            $imgfiles{$filename}{ftype}  = 3;
            $imgfiles{$filename}{width}  = $w;
            $imgfiles{$filename}{height} = $h;
            infolog("PNG: [${h}x${w}] $filename ($pdatalen)");
        } elsif ( substr($pdata,0,2) eq "BM" ) {
            ## BMP File
            ($w,$h) = unpack("VV",substr($pdata,18,8));
            $imgfiles{$filename}{ftype}  = 4;
            $imgfiles{$filename}{width}  = $w;
            $imgfiles{$filename}{height} = $h;
            infolog("BMP: [${h}x${w}] $filename ($pdatalen)");
        } elsif (
            ## TIFF File
            (substr($pdata,0,4) eq "\x4d\x4d\x00\x2a") or
            (substr($pdata,0,4) eq "\x49\x49\x2a\x00")
                ) {
            my $worder = (substr($pdata,0,2) eq "\x4d\x4d") ? 0 : 1;
            my $offset = unpack($worder?"V":"N",substr($pdata,4,4));
            my $number = unpack($worder?"v":"n",substr($pdata,$offset,2)) - 1;
            foreach my $n (0 .. $number) {
                my $add = 2 + ($n * 12);
                my ($id,$tag,$cnt,$val)  = unpack($worder?"vvVV":"nnNN",substr($pdata,$offset+$add,12));
                $h = $val if ($id == 256);
                $w = $val if ($id == 257);
                last if ($h != 0 and $w != 0);
            }
            infolog("TIFF: [${h}x${w}] $filename ($pdatalen) ($worder)");
            infolog("Cannot determine size of TIFF image, setting to '1x1'") if ($h == 0 and $w == 0);
            $imgfiles{$filename}{ftype}  = 5;
            $imgfiles{$filename}{width}  = $w ? $w : 1;
            $imgfiles{$filename}{height} = $h ? $h : 1;
        } elsif (substr($pdata,0,5) eq "\x25\x50\x44\x46\x2d") {
        	if ($conf->{oscar_scan_pdfs}) {
        	    my $version = substr($pdata,5,3);
               infolog("PDF: [version $version] $filename ($pdatalen)");
	           $imgfiles{$filename}{ftype} = 6;
	           $imgfiles{$filename}{version} = $version;
               $imgfiles{$filename}{width}  = 0;
               $imgfiles{$filename}{height} = 0;
        	}
	}

        #Skip unless we found the right header
        unless (defined $imgfiles{$filename}{ftype}) {
            infolog("Skipping file with content-type=\"$ctype\" name=\"$fname\"");
            delete $imgfiles{$filename};
            next;
        }
	if ($imgfiles{$filename}{ftype} == 6) {
		unless ($conf->{oscar_scan_pdfs}) {
			infolog("Skipping PDF file: PDF Scanning was disabled in config");
			next;
		}
	} else {
		#Skip images that cannot contain text
		if ($imgfiles{$filename}{height} < $conf->{oscar_min_height}) {
		    infolog("Skipping image: height < $conf->{oscar_min_height}");
		    delete $imgfiles{$filename};
		    next;
		}

		#Skip images that cannot contain text
		if ($imgfiles{$filename}{width} < $conf->{oscar_min_width}) {
		    infolog("Skipping image: width < $conf->{oscarmin_width}");
		    delete $imgfiles{$filename};
		    next;
		}

		#Skip too big images, screenshots etc
		if ($imgfiles{$filename}{height} > $conf->{oscar_max_height}) {
		    infolog("Skipping image: height > $conf->{oscar_max_height}");
		    delete $imgfiles{$filename};
		    next;
		}

		#Skip too big images, screenshots etc
		if ($imgfiles{$filename}{width} > $conf->{oscar_max_width}) {
		    infolog("Skipping image: width > $conf->{oscar_max_width}");
		    delete $imgfiles{$filename};
		    next;
		}
	}
        #Found Image!! Get a temporary dir to save image
        $imgdir = Mail::SpamAssassin::Util::secure_tmpdir();
        unless ($imgdir) {
            errorlog("Scan canceled, cannot create Image TMPDIR.");
            return 0;
        }
        set_tmpdir($imgdir);

        #Generate unique filename to store image
        my $imgfilename = Mail::SpamAssassin::Util::untaint_file_path(
            $imgdir . "/" . $filename
        );
        my $unique = 0;
        while (-e $imgfilename) {
            $imgfilename = Mail::SpamAssassin::Util::untaint_file_path(
                $imgdir . "/" . chr(65+$unique) . "." . $filename
            );
            $unique++;
        }

        #Save important constants
        $imgfiles{$filename}{fname} = $fname;
        $imgfiles{$filename}{ctype} = $ctype;
        $imgfiles{$filename}{fsize} = $pdatalen;
        $imgfiles{$filename}{fpath} = $imgfilename;

        #Save Image to disk.
        unless (open PICT, ">$imgfilename") {
            errorlog("Cannot write \"$imgfilename\", skipping...");
            delete $imgfiles{$filename};
            removedir($imgdir);
            next;
        }
        binmode PICT;
        print PICT $pdata;
        close PICT;
        debuglog("Saved: $imgfilename");

        #Increment valid image file counter
        $cnt++;

        #keep raw email for debugging later
        my $rawfilename = $imgdir . "/raw.eml";
        if (open RAW, ">$rawfilename") {
            print RAW $pms->{msg}->get_pristine();
            close RAW;
            debuglog("Saved: $rawfilename");
        }

    }

    if ($cnt == 0) {
        debuglog("Skipping OCR, no image files found...");
        return 0;
    }
    infolog("Found: $cnt images"); $cnt = 0;
    if ($conf->{oscar_enable_image_hashing} == 3) {
        $conf->{oscar_mysql_ddb} = get_mysql_ddb();
    }

    my $haserr;
    foreach my $filename (keys %imgfiles) {
        my $pic = $imgfiles{$filename};
        #infolog("Analyzing file with content-type=\"$$pic{ctype}\"");
        salog("Working on file: $filename");
        my @used_scansets = ();
        my $corrupt = 0;
        my $suffix = 0;
        my $generic_ctype = 0;
        my $digest;
        $$pic{fpath} =~ m/^(\S+)$/;
        my $file  = $1;
        my $tfile = $file;
        my $pfile = $file . ".pnm";
        my $efile = $file . ".err";
        debuglog("pfile => $pfile");
        debuglog("efile => $efile");

        #Open ERRORLOG
        $haserr = $Mail::SpamAssassin::Logger::LOG_SA{level} == 3;

        if ($haserr) {
            $haserr = open RAWERR, ">$imgdir/raw.err";
            debuglog("Errors to: $imgdir/raw.err") if ($haserr>0);
        }

        my $mimetype = $$pic{ctype};
        if($mimetype =~ m'application/octet-stream'i) {
            $generic_ctype = 1;
        }

        if($$pic{fname} =~ /\.([\w-]+)$/) {
            $suffix = $1;
        }
        if ($suffix) {
            debuglog("File has Content-Type \"$mimetype\" and File Extension \"$suffix\"");
        } else {
            debuglog("File has Content-Type \"$mimetype\" and no File Extension");
        }

        if ( $$pic{ftype} == 1 ) {
            infolog("Found GIF header name=\"$$pic{fname}\"");
            if ($conf->{oscar_skip_gif}) {
                infolog("Skipping image check");
                next;
            }
            if (defined($conf->{oscar_max_size_gif}) and ($$pic{fsize} > $conf->{oscar_max_size_gif})) {
                infolog("GIF file size ($$pic{fsize}) exceeds maximum file size for this format, skipping...");
                next;
            }

            if ( ($$pic{ctype} !~ /gif/i) and not $generic_ctype) {
                wrong_ctype( "GIF", $$pic{ctype} );
                $internal_score += $conf->{'oscar_wrongctype_score'};
            }

            if ( $suffix and $suffix !~ /gif/i) {
                wrong_extension( "GIF", $suffix);
                $internal_score += $conf->{'oscar_wrongext_score'};
            }

            my $interlaced_gif = 0;
            my $image_count = 0;

            foreach my $a (qw/gifsicle giftext giffix gifinter giftopnm pnmtopng/) {
                unless (defined $conf->{"oscar_bin_$a"}) {
                    errorlog("Cannot exec $a, skipping image");
                    next;
                }
            }

            my @stderr_data;
            my ($retcode, @stdout_data) = save_execute(
                "$conf->{oscar_bin_giftext} $file",
                undef,
                ">$imgdir/giftext.info",
                ">>$imgdir/giftext.err", 1);
                
            if ($retcode<0) { # only care if we timed out
                chomp $retcode;
                errorlog("$conf->{oscar_bin_giftext} Timed out [$retcode], skipping...");
                ++$imgerr if $conf->{oscar_keep_bad_images}>0; next;
            }

            foreach (@stdout_data) {
                unless ($interlaced_gif) {
                    if ( $_ =~ /Image is Interlaced/i ) {
                        $interlaced_gif = 1;
                    }
                }
                if ( $_ =~ /^Image #/ ) {
                    $image_count++;
                }
            }
            if ($interlaced_gif or ($image_count > 1)) {
                infolog("Image is interlaced or animated...");
            }
            else {
                infolog("Image is single non-interlaced...");
                $tfile .= "-fixed.gif";
                printf RAWERR "## $conf->{oscar_bin_giffix} $file >$tfile 2>>$efile\n" if ($haserr>0);

                $retcode = save_execute("$conf->{oscar_bin_giffix} $file", undef, ">$tfile", ">>$efile");

                if ($retcode<0) { # only care if we timed out
                    chomp $retcode;
                    errorlog("$conf->{oscar_bin_giffix}: Timed out [$retcode], skipping...");
                    printf RAWERR "?? Timed out > $retcode\n" if ($haserr>0);
                    ++$imgerr if $conf->{oscar_keep_bad_images}>0; next;
                }

                if (open ERR, $efile) {
                    @stderr_data = <ERR>;
                    close ERR;
                    foreach (@stderr_data) {
                        if ( $_ =~ /GIF-LIB error/i ) {
                            $corrupt = $_;
                            last;
                        }
                    }
                }
            }
            my $fixedsize = (stat($tfile))[7];
            if (defined($conf->{oscar_max_size_gif}) and ($fixedsize > $conf->{oscar_max_size_gif})) {
                infolog("Fixed GIF file size ($fixedsize) exceeds maximum file size for this format, skipping...");
                next;
            }

            if ($corrupt) {
                if ($interlaced_gif or ($image_count > 1)) {
                    infolog("Skipping corrupted interlaced image...");
                    corrupt_img($conf->{oscar_corrupt_unfixable_score}, $corrupt);
                    $internal_score += $conf->{oscar_corrupt_unfixable_score};
                    next;
                }
                if (-z $tfile) {
                    infolog("Uncorrectable corruption detected, skipping non-interlaced image...");
                    corrupt_img($conf->{oscar_corrupt_unfixable_score}, $corrupt);
                    $internal_score += $conf->{oscar_corrupt_unfixable_score};
                    next;
                }
                infolog("Image is corrupt, but seems fixable, continuing...");
                corrupt_img($conf->{oscar_corrupt_score}, $corrupt);
                $internal_score += $conf->{oscar_corrupt_score};
            }

            if ($image_count > 1) {
                infolog("File contains <$image_count> images, deanimating...");
                $tfile = deanimate($tfile);
            }

            if ($interlaced_gif) {
                infolog("Processing interlaced_gif $tfile...");
                my $cfile = $tfile;
                if ($tfile =~ m/\.gif$/i) {
                    $tfile =~ s/\.gif$/-fixed.gif/i;
                } else {
                    $tfile .= ".gif";
                }
                printf RAWERR qq(## $conf->{oscar_bin_gifinter} $cfile >$tfile 2>>$efile\n) if ($haserr>0);
	
                $retcode = save_execute("$conf->{oscar_bin_gifinter} $cfile", undef, ">$tfile", ">>$efile");

                if ($retcode<0) {
                    chomp $retcode;
                    printf RAWERR "?? Timed out > $retcode\n" if ($haserr>0);
                    errorlog("$conf->{oscar_bin_gifinter}: Timed out [$retcode], skipping...");
                    ++$imgerr if $conf->{oscar_keep_bad_images}>0; next;
                } elsif ($retcode>0) {
                    chomp $retcode;
                    printf RAWERR "?? [$retcode] returned from $conf->{oscar_bin_gifinter}\n" if ($haserr>0);
                    errorlog("$conf->{oscar_bin_gifinter}: Returned [$retcode], skipping...");
                    ++$imgerr if $conf->{oscar_keep_bad_images}>0; next;
                }
            }

            printf RAWERR qq(## $conf->{oscar_bin_giftopnm} $tfile >$pfile 2>>$efile\n) if ($haserr>0);

            $retcode = save_execute("$conf->{oscar_bin_giftopnm} $tfile", undef, ">$pfile", ">>$efile");

            if ($retcode<0) {
                chomp $retcode;
                printf RAWERR "?? Timed out > $retcode\n" if ($haserr>0);
                errorlog("$conf->{oscar_bin_giftopnm}: Timed out [$retcode], skipping...");
                ++$imgerr if $conf->{oscar_keep_bad_images}>0; next;
            } elsif ($retcode>0) {
                chomp $retcode;
                printf RAWERR "?? [$retcode] returned from $conf->{oscar_bin_giftopnm}\n" if ($haserr>0);
                errorlog("$conf->{oscar_bin_giftopnm}: Returned [$retcode], skipping...");
                ++$imgerr if $conf->{oscar_keep_bad_images}>0; next;
            }
        }
        elsif ( $$pic{ftype} == 2 ) {
            infolog("Found JPEG header name=\"$$pic{fname}\"");
            if ($conf->{oscar_skip_jpeg}) {
                infolog("Skipping image check");
                next;
            }

            if (defined($conf->{oscar_max_size_jpeg}) and ($$pic{fsize} > $conf->{oscar_max_size_jpeg})) {
                infolog("JPEG file size ($$pic{fsize}) exceeds maximum file size for this format, skipping...");
                next;
            }
            if ( ($$pic{ctype} !~ /(jpeg|jpg)/i) and not $generic_ctype) {
                wrong_ctype( "JPEG", $$pic{ctype} );
                $internal_score += $conf->{'oscar_wrongctype_score'};
            }

            if ( $suffix and $suffix !~ /(jpeg|jpg|jfif)/i) {
                wrong_extension( "JPEG", $suffix);
                $internal_score += $conf->{'oscar_wrongext_score'};
            }

            foreach my $a (qw/jpegtopnm/) {
                unless (defined $conf->{"oscar_bin_$a"}) {
                    errorlog("Cannot exec $a, skipping image");
                    next;
                }
            }
            printf RAWERR qq(## $conf->{oscar_bin_jpegtopnm} $file >$pfile 2>>$efile\n) if ($haserr>0);
            my $retcode = save_execute("$conf->{oscar_bin_jpegtopnm} $file", undef, ">$pfile", ">>$efile");

            if ($retcode<0) {
                chomp $retcode;
                printf RAWERR "?? Timed out > $retcode\n" if ($haserr>0);
                errorlog("$conf->{oscar_bin_jpegtopnm}: Timed out [$retcode], skipping...");
                ++$imgerr if $conf->{oscar_keep_bad_images}>0; next;
            } elsif ($retcode>0) {
                chomp $retcode;
                printf RAWERR "?? [$retcode] returned from $conf->{oscar_bin_jpegtopnm}\n" if ($haserr>0);
                errorlog("$conf->{oscar_bin_jpegtopnm}: Returned [$retcode], skipping...");
                ++$imgerr if $conf->{oscar_keep_bad_images}>0; next;
            }
        }
        elsif ( $$pic{ftype} == 3 ) {
            infolog("Found PNG header name=\"$$pic{fname}\"");
            if ($conf->{oscar_skip_png}) {
                infolog("Skipping image check");
                next;
            }
            if (defined($conf->{oscar_max_size_png}) and ($$pic{fsize} > $conf->{oscar_max_size_png})) {
                infolog("PNG file size ($$pic{fsize}) exceeds maximum file size for this format, skipping...");
                next;
            }
            if ( ($$pic{ctype} !~ /png/i) and not $generic_ctype) {
                wrong_ctype( "PNG", $$pic{ctype} );
                $internal_score += $conf->{'oscar_wrongctype_score'};
            }
            if ( $suffix and $suffix !~ /(png)/i) {
                wrong_extension( "PNG", $suffix);
                $internal_score += $conf->{'oscar_wrongext_score'};
            }
            foreach my $a (qw/pngtopnm pnmtopng/) {
                unless (defined $conf->{"oscar_bin_$a"}) {
                    errorlog("Cannot exec $a, skipping image");
                    next;
                }
            }

            printf RAWERR qq(## $conf->{oscar_bin_pngtopnm} $file >$pfile 2>>$efile\n) if ($haserr>0);
            my $retcode = save_execute("$conf->{oscar_bin_pngtopnm} $file", undef, ">$pfile", ">>$efile");

            if ($retcode<0) {
                chomp $retcode;
                printf RAWERR "?? Timed out > $retcode\n" if ($haserr>0);
                errorlog("$conf->{oscar_bin_pngtopnm}: Timed out [$retcode], skipping...");
                ++$imgerr if $conf->{oscar_keep_bad_images}>0; next;
            } elsif ($retcode>0) {
                chomp $retcode;
                printf RAWERR "?? [$retcode] returned from $conf->{oscar_bin_pngtopnm}\n" if ($haserr>0);
                errorlog("$conf->{oscar_bin_pngtopnm}: Returned [$retcode], skipping...");
                ++$imgerr if $conf->{oscar_keep_bad_images}>0; next;
            }
        }
        elsif ( $$pic{ftype} == 4 ) {
            infolog("Found BMP header name=\"$$pic{fname}\"");
            if ($conf->{oscar_skip_bmp}) {
                infolog("Skipping image check");
                next;
            }
            if (defined($conf->{oscar_max_size_bmp}) and ($$pic{fsize} > $conf->{oscar_max_size_bmp})) {
                infolog("BMP file size ($$pic{fsize}) exceeds maximum file size for this format, skipping...");
                next;
            }
            if ( ($$pic{ctype} !~ /bmp/i) and not $generic_ctype) {
                wrong_ctype( "BMP", $$pic{ctype} );
                $internal_score += $conf->{'oscar_wrongctype_score'};
            }
            if ( $suffix and $suffix !~ /(bmp)/i) {
                wrong_extension( "BMP", $suffix);
                $internal_score += $conf->{'oscar_wrongext_score'};
            }
            foreach my $a (qw/bmptopnm pnmtopng/) {
                unless (defined $conf->{"oscar_bin_$a"}) {
                    errorlog("Cannot exec $a, skipping image");
                    next;
                }
            }
            printf RAWERR qq(## $conf->{oscar_bin_bmptopnm} $file >$pfile 2>>$efile\n) if ($haserr>0);

            my $retcode = save_execute("$conf->{oscar_bin_bmptopnm} $file", undef, ">$pfile", ">>$efile");
            if ($retcode<0) {
                chomp $retcode;
                printf RAWERR "?? Timed out > $retcode\n" if ($haserr>0);
                errorlog("$conf->{oscar_bin_bmptopnm}: Timed out [$retcode], skipping...");
                ++$imgerr if $conf->{oscar_keep_bad_images}>0; next;
            } elsif ($retcode>0) {
                chomp $retcode;
                printf RAWERR "?? [$retcode] returned from $conf->{oscar_bin_bmptopnm}\n" if ($haserr>0);
                errorlog("$conf->{oscar_bin_bmptopnm}: Returned [$retcode], skipping...");
                ++$imgerr if $conf->{oscar_keep_bad_images}>0; next;
            }
        }
        elsif ( $$pic{ftype} == 5 ) {
            infolog("Found TIFF header name=\"$$pic{fname}\"");
            if ($conf->{oscar_skip_tiff}) {
                infolog("Skipping image check");
                next;
            }
            if (defined($conf->{oscar_max_size_tiff}) and ($$pic{fsize} > $conf->{oscar_max_size_tiff})) {
                infolog("TIFF file size ($$pic{fsize}) exceeds maximum file size for this format, skipping...");
                next;
            }
            if ( ($$pic{ctype} !~ /tif/i) and not $generic_ctype) {
                wrong_ctype( "TIFF", $$pic{ctype} );
                $internal_score += $conf->{'oscar_wrongctype_score'};
            }
            if ( $suffix and $suffix !~ /tif/i) {
                wrong_extension( "TIFF", $suffix);
                $internal_score += $conf->{'oscar_wrongext_score'};
            }

            foreach my $a (qw/tifftopnm/) {
                unless (defined $conf->{"oscar_bin_$a"}) {
                    errorlog("Cannot exec $a, skipping image");
                    next;
                }
            }
            printf RAWERR qq(## $conf->{oscar_bin_tifftopnm} $file >$pfile 2>>$efile\n) if ($haserr>0);
            my $retcode = save_execute("$conf->{oscar_bin_tifftopnm} $file", undef, ">$pfile", ">>$efile");

            if ($retcode<0) {
                chomp $retcode;
                printf RAWERR "?? Timed out > $retcode\n" if ($haserr>0);
                errorlog("$conf->{oscar_bin_tifftopnm}: Timed out [$retcode], skipping...");
                ++$imgerr if $conf->{oscar_keep_bad_images}>0; next;
            } elsif ($retcode>0) {
                chomp $retcode;
                printf RAWERR "?? [$retcode] returned from $conf->{oscar_bin_tifftopnm}\n" if ($haserr>0);
                errorlog("$conf->{oscar_bin_tifftopnm}: Returned [$retcode], skipping...");
                ++$imgerr if $conf->{oscar_keep_bad_images}>0; next;
            }
        } elsif ($$pic{ftype} == 6) {
            infolog("Found PDF header name=\"$$pic{fname}\"");

            my $missing_bin = 0;
            foreach my $a (qw/pdftops pstopnm pdfinfo/) {
                unless (defined $conf->{"oscar_bin_$a"}) {
		    $missing_bin = 1;
                    errorlog("Cannot exec $a, skipping image");
                    next;
                }
            }

	    if ($missing_bin) {
	    	next;
	    }

            my @stderr_data;
            my ($retcode, @stdout_data) = save_execute(
                "$conf->{oscar_bin_pdfinfo} $file",
                undef,
                ">$imgdir/pdfinfo.info",
                ">>$imgdir/pdfinfo.err", 1);
            
            foreach (@stdout_data) {
                if ($_ =~ /^Pages:\s*([0-9]+)/) {
			$$pic{pages} = $1;
		}
            }
            
            unless ($$pic{pages}) {
                infolog("Can't determine page count of PDF Document\n");
            }

            if ($$pic{pages} > $conf->{oscar_pdf_maxpages}) {
                infolog("PDF has too many pages, skipping this file...\n");
                next;
            }
            
            if ( ($$pic{ctype} !~ /pdf/i) and not $generic_ctype) {
                wrong_ctype( "Application/PDF", $$pic{ctype} );
                $internal_score += $conf->{'oscar_wrongctype_score'};
            }

            $retcode = save_execute("$conf->{oscar_bin_pdftops} $file -", undef, ">$file.ps", ">>$efile");

            if ($retcode<0) {
                chomp $retcode;
                printf RAWERR "?? Timed out > $retcode\n" if ($haserr>0);
                errorlog("$conf->{oscar_bin_pdftops}: Timed out [$retcode], skipping...");
                ++$imgerr if $conf->{oscar_keep_bad_images}>0; next;
            } elsif ($retcode>0) {
                chomp $retcode;
                printf RAWERR "?? [$retcode] returned from $conf->{oscar_bin_pdftops}\n" if ($haserr>0);
                errorlog("$conf->{oscar_bin_pdftops}: Returned [$retcode], skipping...");
                ++$imgerr if $conf->{oscar_keep_bad_images}>0; next;
            }

            $retcode = save_execute("$conf->{oscar_bin_pstopnm} -stdout -xsize=1000 $file.ps", undef, ">$pfile", ">>$efile");

            if ($retcode<0) {
                chomp $retcode;
                printf RAWERR "?? Timed out > $retcode\n" if ($haserr>0);
                errorlog("$conf->{oscar_bin_pstopnm}: Timed out [$retcode], skipping...");
                ++$imgerr if $conf->{oscar_keep_bad_images}>0; next;
            } elsif ($retcode>0) {
                chomp $retcode;
                printf RAWERR "?? [$retcode] returned from $conf->{oscar_bin_pstopnm}\n" if ($haserr>0);
                errorlog("$conf->{oscar_bin_pstopnm}: Returned [$retcode], skipping...");
                ++$imgerr if $conf->{oscar_keep_bad_images}>0; next;
            }
	}
        else {
            errorlog("Image type not recognized, unknown format. Skipping this image...");
            next;
        }

        if($conf->{oscar_enable_image_hashing}) {
            infolog("Calculating image hash for: $pfile");
            ($corrupt, $digest) = calc_image_hash($pfile,$pic);
            if ($corrupt) {
                infolog("Error calculating the image hash, skipping hash check...");
            } else {
                my ($score, $dinfo, $whash);
                $whash = $conf->{oscar_enable_image_hashing} == 3
                    ? $conf->{oscar_mysql_hash} 
                    : $conf->{oscar_db_hash};
                ($score,$dinfo) = check_image_hash_db($digest, $whash, $$pic{fname}, $$pic{ctype}, $$pic{ftype});
                if ($score > 0) {
                    known_img_hash($score,$dinfo);
                    infolog("Message is SPAM. $dinfo") if ($conf->{oscar_enable_image_hashing} < 3);
                    removedirs(get_all_tmpdirs());
                    return 0;
                }
                $whash = $conf->{oscar_enable_image_hashing} == 3
                    ? $conf->{oscar_mysql_safe} 
                    : $conf->{oscar_db_safe};
                ($score,$dinfo) = check_image_hash_db($digest, $whash, $$pic{fname}, $$pic{ctype}, $$pic{ftype});
                if ($score > 0) {
                    infolog("Image in KNOWN_GOOD. Skipping OCR checks...");
                    next;
                }
            }
            if ($digest eq '') {
                infolog("Empty Hash, skipping...");
                next;
            }
        } else {
            infolog("Image hashing disabled in configuration, skipping...");
        }

        # Note: $current_score is here the score that the message had at the beginning
        # and $score is the autodisable_score defined in the config
        # $internal_score describes the score that the message got by FuzzyOcr so far.
        if ($internal_score + $current_score > $score) {
            my $total = $internal_score + $current_score;
            infolog("Oscar stopped, message got $internal_score points by other Oscar tests ($total>$score).");
            return 0;
        }

        my @ocr_results = ();
        
        my $mcnt = 0;
        my $modus = 0;
        my $modus_match = 0;
        my $wref = get_wordlist();
        my %words = %$wref;
        my $wwref = get_wordweights();
        my %wweights = %$wwref;
        
        my $cmcnt = 0;
        debuglog("Oscar: will work on file: $pfile");
        
        foreach my $a (qw/pnmtopng/) {
          unless (defined $conf->{"oscar_bin_$a"}) {
               errorlog("Cannot exec $a, skipping image");
               next;
          }
        }
        my $ffile = $pfile.".png";
        printf RAWERR qq(## $conf->{oscar_bin_pnmtopng} $pfile >$ffile 2>>$efile\n) if ($haserr>0);
        my $retcode = save_execute("$conf->{oscar_bin_pnmtopng} $pfile", undef, ">$ffile", ">>$efile");

        if ($retcode<0) {
          chomp $retcode;
          printf RAWERR "?? Timed out > $retcode\n" if ($haserr>0);
          errorlog("$conf->{oscar_bin_pnmtopng}: Timed out [$retcode], skipping...");
          ++$imgerr if $conf->{oscar_keep_bad_images}>0; next;
        } elsif ($retcode>0) {
          chomp $retcode;
          printf RAWERR "?? [$retcode] returned from $conf->{oscar_bin_pnmtopng}\n" if ($haserr>0);
          errorlog("$conf->{oscar_bin_pnmtopng}: Returned [$retcode], skipping...");
          ++$imgerr if $conf->{oscar_keep_bad_images}>0; next;
        }


        my $locations = new oscar::StringVector(0
        );            
        my $ocr = oscar::Impact::getInstance();
        $ocr->loadImage($ffile);
        $ocr->getTextLocations($locations);
        
        my $image_score = 0;
        my $processed_locations = 0;
        for(my $reg_id = 0; $reg_id<$locations->size();$reg_id++){
          last if ($image_score >= $conf->{oscar_counts_required});
          my $loc = $locations->get($reg_id);
          $processed_locations++;
          my $texts  = new oscar::StringVector(0);
          my $scores = new oscar::FloatVector(0);
          $ocr->extractTextContent($loc, $texts, $scores);
          my %linematched = ();
          for(my $text_id = 0; $text_id<$texts->size();$text_id++){
             last if ($image_score >= $conf->{oscar_counts_required});
          	 my $line = lc($texts->get($text_id));
             debuglog(" -- ".$line);
             foreach $modus (0 .. 1) {
                last if ($image_score >= $conf->{oscar_counts_required});
               	if ($modus) {
                   $line =~ s/ //g;
                }
                if ($conf->{oscar_strip_numbers}) {
                  $line =~tr/!;|(0815/iiicoals/;
                  $line =~ s/[0-9]//g;
                } else {
                  $line =~ tr/!;|(/iiic/;
                }
                $line =~ s/[^a-z0-9 ]//g;
                
                foreach my $ww (keys %words) {
                   last if ($image_score >= $conf->{oscar_counts_required});
                   my $w = lc $ww;
                   $w =~ s/[^a-z0-9 ]//g;
                   if ($modus) {
                     $w =~ s/ //g;
                   }
                   if ($conf->{oscar_strip_numbers}) {
                     $w =~ s/[0-9]//g;
                   }
                   
                   my $matched = abs(adistr( $w, $line ));
                   if ( $matched < $words{$ww} ) {
                   	  if (defined($linematched{$w})) {
                   	  	debuglog("word \"$w\" already found in line, skipping...");
                   	  	next;
                   	  }
                   	  $linematched{$w} = 1;
                   	  $image_score += $wweights{$w};
                   	  salog(
                         " found word \"$w\" with fuzz of "
                           . sprintf("%0.4f",$matched)
                           ." and weight of ".$wweights{$w}
                           . "\nline: \"$line\""
                      );
                   }
                }
             }
             
          }	
        }

       infolog("Oscar analyzing finished, $processed_locations locations processed");
       salog("Oscar analyzing finished, $processed_locations locations processed, score is: $image_score (".$conf->{oscar_counts_required}." required)");
       if ($conf->{oscar_enable_image_hashing}) {
           my $info = join('::',$mcnt,$$pic{fname},$$pic{ctype},$$pic{ftype},$digest);
           push(@hashes, $info);
       }

       my $score = '0.000';
       if ($image_score == 0) {
         if ($conf->{oscar_enable_image_hashing} > 1 and @hashes) {
            infolog("Message is ham, saving...");
            foreach my $h (@hashes) {
                my ($mcnt,$fname,$ctype,$ftype,$digest) = split('::',$h,5);
                next if $mcnt;
                my $whash = $conf->{oscar_enable_image_hashing} == 3
                    ? $conf->{oscar_mysql_safe} 
                    : $conf->{oscar_db_safe};
                add_image_hash_db($digest,0,$whash,$fname,$ctype,$ftype);
            }
         }
       } else {
       	 if ($image_score >= $conf->{oscar_counts_required}) {
       	   $score = $conf->{oscar_base_score};
       	   if ($conf->{oscar_enable_image_hashing} and
             $conf->{oscar_hashing_learn_scanned}) {
             foreach my $h (@hashes) {
                my ($mcnt,$fname,$ctype,$ftype,$digest) = split('::',$h,5);
                next unless $mcnt;
                my $whash = $conf->{oscar_enable_image_hashing} == 3
                              ? $conf->{oscar_mysql_hash} 
                             : $conf->{oscar_db_hash};
                add_image_hash_db($digest,$score,$whash,$fname,$ctype,$ftype,'');
              }
            }

       	 } else {
       	   #$score = $conf->{oscar_base_score} * $image_score;
       	 }
       }

        for my $set ( 0 .. 3 ) {
            $pms->{conf}->{scoreset}->[$set]->{"OSCAR"} = $score;
        }

        if ($score > 0) {
           $pms->_handle_hit( "OSCAR", $score, "BODY: ", "BODY", 
              $pms->{conf}->get_description_for_rule("OSCAR"));
        }
    }
    if ($imgerr == 0 and $conf->{oscar_keep_bad_images}<2) {
        removedirs(get_all_tmpdirs());
    }
    if ($conf->{oscar_enable_image_hashing} == 3) {
        if (defined $conf->{oscar_mysql_ddb}) {
            $conf->{oscar_mysql_ddb}->disconnect;
        }
    }
    debuglog("OSCAR ending successfully...");
    return 0;
}

1;
#vim: et ts=4 sw=4
