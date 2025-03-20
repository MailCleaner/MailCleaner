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
package OscarOcr::Deanimate;

use base 'Exporter';
our @EXPORT_OK = qw(deanimate);

use lib qw(..);
use OscarOcr::Config qw(get_config set_config get_tmpdir);
use OscarOcr::Misc qw(save_execute);
use OscarOcr::Logging qw(errorlog warnlog infolog);

# Provide functions to deanimate gifs

sub deanimate {
    my $conf = get_config();
    my $imgdir = get_tmpdir();
    my $tfile = shift;
    my $efile = $tfile . ".err";
    my $tfile2 = $tfile;
    my $tfile3 = $tfile;

    if ($tfile2 =~ m/\.gif$/i) {
        $tfile2 =~ s/\.gif$/-multi.gif/i;
        $tfile3 =~ s/\.gif$/-reduced.gif/i;
    } else {
        $tfile2 .= ".gif";
        $tfile3 .= "-reduced.gif";
    }

    my $info = gif_info($tfile);
    my $index = find_dominant_image($info);

    # Pick frame number $index from the animation and spew to stdout:
    if ($info->{'has_local_color_table'}) {
        infolog("deanimate: Image has local_color_table, reducing to 255 colors");
        my $retcode = save_execute(
            "$conf->{oscar_bin_gifsicle} --colors=255 $tfile",
	    undef,
            ">$tfile3",
            ">>$efile");
        if ($retcode == 0) {
            $tfile = $tfile3;
        } else {
            warnlog("$conf->{'oscar_bin_gifsicle'}: ".
                ($retcode<0) ? 'Timed out' : 'Error'
                ." [$retcode], image not reduced!");
        }
    }
    my $retcode = save_execute(
        "$conf->{oscar_bin_gifsicle} -o $tfile2 --unoptimize $tfile #$index",
	undef,
        undef,
        ">>$efile");
    return $tfile2 if ($retcode == 0);
    warnlog("$conf->{oscar_bin_gifsicle}: cannot extract image#${index}");
    return $tfile;
}

sub gif_info {
    my $conf = get_config();
    my $imgdir = get_tmpdir();
    my $giffile = $_[0];
    
    my $fd = new IO::Handle;
    
    my $retcode;
    my @stdout_data;
    my @stderr_data;

    my %info = (
        'error' => 0,
        'loop' => 0,
        'loop_count' => 0,
        'delays' => [],
        'has_local_color_table' => 0
    );

    ($retcode, @stdout_data) = save_execute(
        "$conf->{oscar_bin_gifsicle} --info $giffile",
        undef,
        ">$imgdir/gifsicle.info",
        ">>$imgdir/gifsicle.err", 1);

    if ($retcode) {
        errorlog("$conf->{'oscar_bin_gifsicle'}: ".
            ($retcode<0) ? 'Timed out' : 'Error'
            ." [$retcode], data unavailable...");
        return \%info;
    }

    my $output = join("", @stdout_data);
    my ($globalinfo, @frameinfo)
        = split /^ \s+ \+ \s+ (?=image \s+ \#\d+)/mx, $output;

    if ($globalinfo =~ /^ \s* loop \s+ (forever|count \s+ (\d+))/mx) {
        $info{'loop'} = 1;
        $info{'loop_count'} = $2 ? ($2 + 0) : 0;
    }

    my $frameno = 0;
    foreach my $info (@frameinfo) {
        # We could just match the delays, but we'll also check the image#'s
        # as a sanity check.
        my ($n, $delay) = $info =~ m{ image \s+ \#(\d+)
                                  (?: .* \b delay \s+ (\d+(?:\.\d+)?) s)?
                                  }sx;
        if ($n != $frameno) {
            warnlog ( "Trouble parsing 'gifsicle --info' output.\n"
                     . "  Expected 'image \#$frameno', found 'image \#$n', skipping..." );
            $info{'error'}++;
        } else {
            $info{'delays'}->[$frameno++] = $delay ? $delay + 0.0 : 0.0;
            $info{'has_local_color_table'} ||= $output =~ /local\s+color\s+table/;
        }
    }

    return \%info;
}

sub find_dominant_image ($) {
    my ($info) = @_;
    my ($loop, $loop_count, $delays) = @$info{qw(loop loop_count delays)};

    # Pick out the frame with the longest delay.
    my $maxdelay = -1e6;
    my $maxn = @$delays - 1;
    for (my $n = 0; $n < @$delays; $n++) {
        $delays->[$n] > $maxdelay
            and ($maxn, $maxdelay) = ($n, $delays->[$n]);
    }

    if ($maxdelay < 15.0 && !$loop) {
        # In non-looped (or finitely-looped) images, the last frame
        # gets displayed forever at the end of the animation.
        # Therefore the last frame is the dominant frame.
        return @$delays - 1;
    }
    return $maxn;
}
