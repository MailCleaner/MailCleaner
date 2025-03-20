#!/usr/bin/env perl
#
#    Mailcleaner - SMTP Antivirus/Antispam Gateway
#    Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
#    Copyright (C) 2025 John Mertz <git@john.me.tz>
#
#    This program is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; either version 2 of the License, or
#    (at your option) any later version.
#
#    This program is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this program; if not, write to the Free Software
#    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA    02111-1307    USA
#
#    This module will dump a configuration file based on template

use v5.36;
use strict;
use warnings;
use utf8;

package ConfigTemplate;
require Exporter;
require ReadConfig;

our @ISA = qw(Exporter);
our @EXPORT = qw(create dumpFile);
our $VERSION = 1.0;

our $conf = ReadConfig::getInstance();
our $SRCDIR = $conf->getOption('SRCDIR');
our $VARDIR = $conf->getOption('VARDIR');

###
# create the dumper
# @param    $template      string    base template file
# @param    $targetfile    string    target config file
# @return                            this
###
sub create($templatefile,$targetfile)
{
    $templatefile =~ s/__SRCDIR__/$SRCDIR/g;
    $templatefile =~ s/__VARDIR__/$VARDIR/g;
    if ($templatefile =~ m/^[^\/]/) {
        $templatefile = $conf->getOption('SRCDIR')."/".$templatefile;
    }
    $targetfile =~ s/__SRCDIR__/$SRCDIR/g;
    $targetfile =~ s/__VARDIR__/$VARDIR/g;
    if ($targetfile =~ m/^[^\/]/) {
        $targetfile = $conf->getOption('SRCDIR')."/".$targetfile;
    }

    my %replacements = ();
    my %subtemplates = ();
    my %conditions = ();

    my $self = {
        templatefile => $templatefile,
        targetfile => $targetfile,
        %replacements => (),
        %subtemplates => (),
        %conditions => ()
    };

    bless $self, "ConfigTemplate";

    $self->preParseTemplate();
    return $self;
}

###
# preparse template and variables
# @return   boolean     true on success, false on failure
###
sub preParseTemplate($self)
{
    my $in_template = "";
    return 0 if (!open(my $FILE, '<', $self->{templatefile}));
    while (<$FILE>) {
        my $line = $_;

        if ($line =~ /\_\_TMPL\_([A-Z0-9]+)\_START\_\_/) {
            $in_template = $1;
            $self->{subtemplates}{$in_template} = "";
            next;
        }
        if ($line =~ /\_\_TMPL\_([A-Z0-9]+)\_STOP\_\_/) {
            $in_template = "";
            next;
        }
        if ($in_template !~ /^$/) {
            $self->{subtemplates}{$in_template} .= $line;
            next;
        }
    }
    close $FILE;
    return 1;
}

sub getSubTemplate($self,$tmplname)
{
    if (defined($self->{subtemplates}{$tmplname})) {
        return $self->{subtemplates}{$tmplname};
    }
    return "";
}

###
# set the tag replacement values
# @param    replace     array_h    handle of array of rplacements with tag as keys
# @return               boolean    true on success, false on failure
###
sub setReplacements($self,$replace_h)
{
    my %replace = %{$replace_h};

    foreach my $tag (keys %replace) {
        $self->{replacements}{$tag} = $replace{$tag};
    }
    return 1;
}

###
# dump to destination file
###
sub dump($self)
{
    return 0 if (!open(my $FILE, '<', $self->{templatefile}));

    my $ret;
    my $in_hidden = 0;
    my $ev_hidden = 0;
    my @if_hist = ();
    my $if_hidden = 0;
    my $lc = 0;
    while (<$FILE>) {
        my $line = $_;
        $lc++;

	next if ($line =~ /^\s*#/);

        if ($line =~ /__IF__\s+(\S+)/) {
            if ($self->getCondition($1)) {
                push @if_hist, $1;
            } else {
                push @if_hist, "!".$1;
                $if_hidden++;
            }
            next;
        }

        # __IF__ condition True, do nothing until __FI__
        if ($line =~ /__ELSE__\s+(\S+)/) {
            unless (scalar(@if_hist)) {
                die "__ELSE__ $1 without preceeding __IF__ (".$self->{templatefile}.":$lc)\n";
            }
            if ($if_hist[scalar(@if_hist)-1] eq $1) {
                $if_hist[scalar(@if_hist)-1] = '!' . $if_hist[scalar(@if_hist)-1];
                $if_hidden++;
            } elsif ($if_hist[scalar(@if_hist)-1] eq "!".$1) {
                $if_hist[scalar(@if_hist)-1] =~ s/^!//;
                $if_hidden--;
            } else {
                die "__ELSE__ tag $1 without preceeding __IF__ (".$self->{templatefile}.":$lc)\n";
            }
            next;
        }

        if ($line =~/__FI__/) {
            unless (scalar(@if_hist)) {
                die "__FI__ without preceeding __IF__ (".$self->{templatefile}.":$lc)\n";
            }
            if ($if_hist[scalar(@if_hist)-1] =~ /^!/) {
                $if_hidden--;
            }
            pop @if_hist;
            next;
        }

	# TODO: Unsafe use of `eval`. See PerlCritic severity 5
	# Currently we don't use any EVAL blocks in the default config, so this is a "Use at your
	# own risk" advanced feature for admins.
        if ($line =~    /__EVAL__\s+(.*)$/) {
            if (! eval "$1") {
                $ev_hidden = 1;
            } else {
                $ev_hidden = 0;
            }
            next;
        }
        if ($line =~/__LAVE__/) {
            $ev_hidden = 0;
            next;
        }

        # Includes a file in the exim configuration
        # First looks for a equivalent customised file
        if ($line =~/__INCLUDE__ *(.*)/) {
            next if ($if_hidden );
            my $inc_file = $1;
            my $path_file;
            $inc_file =~ s/_template$//;
            # Version using .include_if_exists
            if ( -f "$SRCDIR/etc/exim/custom/$inc_file" ) {
                $path_file = "$SRCDIR/etc/exim/custom/$inc_file";
            } elsif ( -f "$SRCDIR/etc/exim/$inc_file" ) {
                $path_file = "$SRCDIR/etc/exim/$inc_file";
            } else {
		print STDERR "__INCLUDE__ $SRCDIR/etc/exim/$inc_file does not exist\n";
		next;
	    }

            open(my $PATHFILE, '<', $path_file);
            my @contains = <$PATHFILE>;
            close($PATHFILE);
            chomp(@contains);
            foreach (@contains) {
                $ret .= "$_\n";
            }

            next;
        }
        if ($line =~ /\_\_TMPL\_([A-Z0-9]+)\_START\_\_/) {
            $in_hidden = 1;
            next;
        }
        if ($line =~ /\_\_TMPL\_([A-Z0-9]+)\_STOP\_\_/) {
            $in_hidden = 0;
            next;
        }

        if (!$in_hidden && !$if_hidden && !$ev_hidden) {
            $ret .= $line;
        }
    }
    close $FILE;

    ## do the replacements

    ## replace well known tags
    my $conf = ReadConfig::getInstance();

    my %wellknown = (
        '__SRCDIR__' => $conf->getOption('SRCDIR'),
        '__VARDIR__' => $conf->getOption('VARDIR'),
    );

    ## replace given tags
    foreach my $tag (keys %{$self->{replacements}}) {
        if (!defined($self->{replacements}{$tag})) {
            $self->{replacements}{$tag} = "";
        }
        if ( defined ($ret) ) {
            $ret =~ s/$tag/$self->{replacements}{$tag}/g;
        }
    }

    foreach my $tag ( keys %wellknown ) {
        if ( defined ($ret) ) {
            $ret =~ s/$tag/$wellknown{$tag}/g;
        }
    }

    if ( defined ($ret) ) {
        return 0 if (!open(my $TARGET, '>', $self->{targetfile}));
        print $TARGET $ret;
        close $TARGET;
    }
    my $uid = getpwnam( 'mailcleaner' );
    my $gid = getgrnam( 'mailcleaner' );
    chown $uid, $gid, $self->{targetfile};
    return 1;
}

sub setCondition($self,$condition,$value)
{
    $self->{conditions}{$condition} = $value;
}

sub getCondition($self,$condition)
{
    if (defined($self->{conditions}{$condition})) {
        return $self->{conditions}{$condition};
    }
    return 0;
}

1;
