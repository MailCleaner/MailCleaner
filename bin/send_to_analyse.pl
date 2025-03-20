#!/usr/bin/perl
#
#   Mailcleaner - SMTP Antivirus/Antispam Gateway
#   Copyright (C) 2004 Olivier Diserens <olivier@diserens.ch>
#   Copyright (C) 2021 John Mertz <git@john.me.tz>
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
#   This script will send an analysis request to the Analysis Center
#
#   Usage:
#           send_to_analyse.pl msg_id destination
#   where msg_id is the message id
#   and destination is the email address of the original recipient

use strict;
use DBI();
use Net::SMTP;
require MIME::Lite;

my $msg_id = shift;
my $for = shift;

if ( (!$msg_id) || !($msg_id =~ /^[a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{6,11}-[a-z,A-Z,0-9]{2,4}$/)) {
        print "INCORRECTMSGID\n";
        exit 0;
}

if ( (!$for) || !($for =~ /^(\S+)\@(\S+)$/)) {
        print "INCORRECTMSGDEST\n";
        exit 0;
}
my $for_local = $1;
my $for_domain = $2;

my %config = readConfig("/etc/mailcleaner.conf");
my %system_conf = get_system_config();
my %domain_conf = get_domain_config($for_domain);

my $msg_file = $config{'VARDIR'}."/spam/".$for_domain."/".$for."/".$msg_id;

if (defined($domain_conf{'falsepos_to'}) && $domain_conf{'falsepos_to'} =~ m/\S+\@\S+/) {
    $system_conf{'analyse_to'} = $domain_conf{'falsepos_to'};
}
if (defined($domain_conf{'systemsender'}) && $domain_conf{'systemsender'} =~ m/\S+\@\S+/) {
    $system_conf{'summary_from'} = $domain_conf{'systemsender'};
}

send_message($msg_file);

exit 0;


##########################################
sub get_system_config {

    my %default = (days_to_keep_spams => 30, sysadmin => 'support@localhost', summary_subject => 'Mailcleaner analysis request', summary_from => 'support@localhost', servername => 'localhost', analyse_to => 'analyse@localhost');
        
        my $dbh = DBI->connect("DBI:mysql:database=mc_config;mysql_socket=$config{VARDIR}/run/mysql_slave/mysqld.sock",
                                "mailcleaner", "$config{MYMAILCLEANERPWD}", {RaiseError => 0, PrintError => 0})
                                        or die "cannot connect to database | get_system_config() |";
        
        my $sth =  $dbh->prepare("SELECT s.days_to_keep_spams, s.sysadmin, s.summary_subject, s.summary_from, h.servername, s.analyse_to, s.falsepos_to FROM system_conf s, httpd_config h")
                                                or die "cannot prepare query | get_system_config() |";
        $sth->execute() or die "cannot execute query | get_system_config() |";
        if ($sth->rows < 1) {
        $sth->finish();
        $dbh->disconnect();
        return %default;
    }

    my $ref = $sth->fetchrow_hashref() or die "cannot get query results | get_system_config() |";
        $sth->finish();
        %default = (days_to_keep_spams => $ref->{'days_to_keep_spams'}, sysadmin => $ref->{'sysadmin'}, summary_subject => $ref->{'summary_subject'}, summary_from => $ref->{'summary_from'}, servername => $ref->{'servername'}, analyse_to => $ref->{'falsepos_to'});
        $dbh->disconnect();
        
        return %default;    
}

##########################################
sub get_domain_config {
    my $d = shift;
    my %default = (language => 'en', support_email => '');

    my $dbh = DBI->connect(
        "DBI:mysql:database=mc_config;mysql_socket=$config{VARDIR}/run/mysql_slave/mysqld.sock",
        "mailcleaner", 
        "$config{MYMAILCLEANERPWD}",
        { RaiseError => 0, PrintError => 0 }
    ) or die "cannot connect to database | get_domain_config() |";

    my $sth =  $dbh->prepare(
        "SELECT dp.language, dp.support_email, dp.falsepos_to, dp.systemsender FROM domain_pref dp, domain d WHERE d.prefs=dp.id AND (d.name='$d' or d.name='*') order by name DESC LIMIT 1"
    ) or die "cannot prepare query | get_domain_config() |";
    $sth->execute() or die "cannot execute query | get_domain_config() |";

    if ($sth->rows < 1) {
        $sth->finish();
        $dbh->disconnect();
        return %default;
    }

    my $ref = $sth->fetchrow_hashref() or die "cannot get query results | get_domain_config() |";
    $sth->finish();
    %default = (language => $ref->{'language'}, support_email => $ref->{'support_email'}, falsepos_to => $ref->{'falsepos_to'}, systemsender => $ref->{'systemsender'});
    $dbh->disconnect();

    return %default;
}

##########################################
sub send_message {
    my $msg_file = shift;
    my $for = $system_conf{'analyse_to'};
    #if ($domain_conf{'support_email'} =~ /^(\S+)\@(\S+)$/) { $for = $domain_conf{'support_email'};};
    my $from = $system_conf{'summary_from'};
    my $subject = "Analysis request";

    MIME::Lite->send("smtp", 'localhost:2525', Debug => 0, Timeout => 30);

    my $mime_msg = MIME::Lite->new(
        From => $from,
        To   => $for,
        Subject => $subject,
        Type => 'TEXT',
        Data => "Analysis request for message: \n\n  Id:\t\t $msg_id\n  Server:\t $system_conf{'servername'}\n  Host ID:\t $config{'HOSTID'}\n  Address:\t $for_local\@$for_domain\n\n"
    ) or die "ERRORSENDING $for\n";

    $mime_msg->attach(
        Type => 'application/text',
        Path => $msg_file,
        Filename => 'message.txt'
    ) or die "ERRORSENDING $for\n";    
    
    my $message_body = $mime_msg->body_as_string();

    if ($mime_msg->send()) {
        print("SENTTOANALYSE\n");
        return 1;
    } else {
        print "ERRORSENDING $for\n";
        return 0;
    }
}

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
