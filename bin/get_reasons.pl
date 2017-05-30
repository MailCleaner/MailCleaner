#!/usr/bin/perl
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
#   This script will fetch the scores and rules given by SpamAssassin.
#
#   Usage:
#           get_reasons.pl msg_id destination language
#   where msg_id is the id of the message
#   destination is the email address of the original recipient
#   and language is the language of the output (rules description)

use strict;

my %config = readConfig("/etc/mailcleaner.conf");

my $msg_id = shift;
my $for = shift;
my $lang = shift;

if (! $lang) {  
        $lang = 'en';
}


if ( (!$msg_id) || !($msg_id =~ /^[a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{2}$/)) {
	print "INCORRECTMSGID\n";
        exit 0;
}

if ( (!$for) || !($for =~ /^(\S+)\@(\S+)$/)) {
	print "INCORRECTMSGDEST\n";
	exit 0;
}
 
my $for_local = $1;
my $for_domain = $2;

my $msg_file = $config{'VARDIR'}."/spam/".$for_domain."/".$for."/".$msg_id;

print $msg_file."\n";

if ( open(MSG, $msg_file)) {
	my $keep_in = 1;
	my $in_it = 0;
	my @hits;
        my $hit;
        my $score_line;
        my $score;
        my $cmd;
        my $describe_line;
	my $line = "";
	while (($line=<MSG>) && ($keep_in > 0)) {
		if ( $line =~ /^X-MailCleaner-SpamCheck:.*\(.*score=([\-]?[0-9\.]*)\,.*$/) {
                        print "TOTAL_SCORE::$1::\n";
                        $in_it = 1;
                }
		elsif ( $line =~ /^X-MailCleaner-SpamScore:.*/) {
                                $keep_in = 0;
                                $in_it = 0;
                }
		elsif ($line =~ /^\s+$/) {      # headers are finished
                                exit;
                }
		else {
			if ($in_it) {
				if ($line =~ /^\s/) {
                                        chomp($line);
                                        @hits = split(/,/, $line);
                                        foreach $hit (@hits) {
                                                chomp($hit);
                                                $hit =~ s/^\s+//;
                                                $hit =~ s/\s+$//;       
                                                if ($hit =~ /^([0-9_A-Za-z]*)\s+([\-]?[0-9\.]*)/) {
                                                        $hit = $1;
							if ($hit =~ /required/) {
       							  next;
							}
                                                        print $hit."::".$2."::";

						 	my $textfile;
							if ($lang =~ /en/) {
								$textfile = "/usr/local/share/spamassassin/*.cf";
							} else {
								$textfile = "/usr/local/share/spamassassin/30_text_$lang.cf";
							}	

                                                        #$cmd = "grep \'describe $hit\' /usr/local/share/spamassassin/*";
							$cmd = "grep \'describe $hit\' $textfile";
                                                        $describe_line=`$cmd`;
                                                        if ($describe_line =~ /.*describe\s+$hit\s+(.*)\s+(.*)/) {
                                                                print $1."\n";
                                                        } else {
                                                                print "$hit\n";
                                                        }
                                                }
                                        }
				}
			}
		}
        }
	close(MSGFILE);
} 
else {
	print "MSGFILENOTFOUND\n";
}


exit 1;

##########################################
sub readConfig
{       # Reads configuration file given as argument.
        my $configfile = shift;
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
                $config{$var} = $value;
        }
        close CONFIG;
        return %config;
}

############################################
sub print_usage
{
        print "bad usage...\n";
        print "Usage: get_reasons.pl message_id destination\@adresse\n";
        exit 0;
}
