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
#   This module will create a mail based on template and send it
#

package          MailTemplate;
require          Exporter;
require          ReadConfig;
require			  DB;
require          Email;
use Net::SMTP;
use MIME::QuotedPrint;
use Encode;
use strict;

our @ISA        = qw(Exporter);
our @EXPORT     = qw(create getSubTemplate setReplacements send);
our $VERSION    = 1.0;

###
# create the mail template, and get the definitive path
# @param  $directory   string  main base template directory
# @param  $filename    string  mail file name
# @param  $template    string  template preference
# @param  $destination Email   destination mail address
# @return              this
###
sub create {
  my ($directory, $filename,$template,$destination_h,$language,$type ) = @_;
  my ($path, %subtemplates, $lang, $to, $from, %replacements, %headers, $sup_part, %values, %attachedpicts);
  
  my $email = ${$destination_h};
  return if (!$email || !$email->can('getPref'));
  if (!defined($language)) {
    $lang = $email->getPref('language');
  	$lang = $language;
  } else {
  	$lang = $language;
  }
  # If user pref langage is not currently translated for the template type,
  # use english by default
  my $conf = ReadConfig::getInstance();
  if (! -d $conf->getOption('SRCDIR')."/templates/$directory/$template/$lang/${filename}_parts" &&
	! -f $conf->getOption('SRCDIR')."/templates/$directory/$template/$lang/${filename}.txt")
  {
        $lang = 'en';
  }

  ## summary_type not yet available as user preference
  if ($type !~ /(html|text)/) {
    $type = 'html';
  }
  
  $to = $email->getAddress();
  my $sysconf = SystemPref::getInstance();
  $from = $sysconf->getPref('summary_from');
  my $domain = $email->getDomainObject();
  if ($domain && $domain->can('getPref')) {
  	my $dm = $domain->getPref('systemsender');
  	if (! $dm eq "" && $dm !~ /NOTFOUND/ && $dm !~ /^_/) {
  	  $from = $dm;
  	}
  }
  $path = $conf->getOption('SRCDIR')."/templates/$directory/$template/$lang/$filename";
 # print "testing path: $path\n";
  if (! -d $path."_parts" && ! -f $path.".txt") {
    $path = $conf->getOption('SRCDIR')."/templates/$directory/default/$lang/$filename";
  }
 # print "using path: $path\n";
  my $this = {
         path => $path,
         to => $to,
         from => $from,
         type => $type,
         language => $lang,
         %subtemplates => (),
         %replacements => (),
         %headers => (),
         sup_part => '',
         %values => (),
         %attachedpicts => ()
         };
         
  bless $this, "MailTemplate";

  # first read main text part (also included in html version)
  $this->preParseTemplate($path.".txt");
  $this->{headers}->{Subject} =~ s/\?\?ADDRESS/$to/;
  # then parse other parte if needed
  if ($type eq 'html' && -d $path."_parts") {
  	if ( opendir(DIR, $path."_parts")) {
  	  while (defined(my $file = readdir(DIR))) {
  	  	chomp($file);
  	  	next if ($file !~ /\.(txt|html)$/);
  		$this->preParseTemplate($path."_parts/".$file);
  	  } 
  	close(DIR);
  	}
  } else {
  	$this->{type} = 'text';
  }
  
  #foreach my $tmpl (keys %{$this->{subtemplates}}) {
  #	print "Template: $tmpl\n=============\n".$this->{subtemplates}{$tmpl}."=============\n";
  #}
  return $this;
}

###
# preparse body texts for templates and variables
# @param   file  string    filename to preparse
# @return        boolean   true on success, false on failure
###
sub preParseTemplate {
  my $this = shift;	
  my $file = shift;
  my $in_headers = 1;
  my $in_template = "";
  return 0 if (!open(FILE, $file));
  while (<FILE>) {
  	my $line = $_;
  	if ($in_headers) {
      if ($line =~ /^(\S+)\:\ (.*)$/) {
  	    $this->{headers}{$1} = $2;
  	    next;
  	  } else {
  	    $in_headers = 0;
  	  }
  	} else {
    	$line = decode_qp($_);
  	}
  		
  	if ($line =~ /__DEFAULT__ ([A-Z0-9]+) (.*)/) {
  	  $this->{values}{$1} = $2;
  	  next;
  	}
  	if ($line =~ /\?\?START\_([A-Z0-9]+)/ ||  $line =~ /\_\_TMPL\_([A-Z0-9]+)\_START\_\_/) {
      $in_template = $1;
      $this->{subtemplates}{$in_template} = "";
      next;
  	}
  	if ($line =~ /\?\?END\_([A-Z0-9]+)/ ||  $line =~ /\_\_TMPL\_([A-Z0-9]+)\_STOP\_\_/) {
      $in_template = "";
      next;
  	}
  	if ($in_template !~ /^$/) {
  	  $this->{subtemplates}{$in_template} .= $line;
  	  next;
  	}
  }  
  close FILE;
  return 1;
}

###
# return the test string of a sub template
# @param  $name  string  name of the sub template
# @return        string  value of the sub template
###
sub getSubTemplate {
  my $this = shift;	
  my $name = shift;
  
  if (defined($this->{subtemplates}{$name})) {
  	return $this->{subtemplates}{$name};
  }
  return "";
}

###
# set the tag replacement values
# @param  replace   array_h  handle of array of rplacements with tag as keys
# @return           boolean  true on success, false on failure
###
sub setReplacements {
  my $this = shift;
  my $replace_h = shift;
  my %replace = %{$replace_h};
  
  foreach my $tag (keys %replace) {
  	$this->{replacements}{$tag} = $replace{$tag};
  }
  return 1;
}

sub setDestination {
  my $this = shift;
  my $destination = shift;
  
  $this->{to} = $destination;
  return 1;
}

sub setLanguage {
  my $this = shift;
  my $lang = shift;
  
  $this->{language} = $lang;
  return 1;
}

sub addAttachement {
  my $this = shift;
  my $type = shift;
  my $parth = shift;

  $this->{sup_part} = $$parth;
  return 1;
}
###
# eehh.. guess what ? .. this one sends the message
# @param    destination  if set, will override the default destination address
# @return   boolean   true on success, false on failure
###
sub send {
  my $this = shift;
  my $dest = shift;
  my $retries = shift;
  # set retries to 1 if it is not defined or inferior to 1
  $retries = 1 if ( (! defined($retries)) || ($retries < 1) );
  
  my $to = $this->{to};
  if (defined($dest) && $dest =~ /^\S+\@\S+$/) {
  	$to = $dest;
  }
  
#  require MIME::Lite;
  require MIME::Entity;
#  MIME::Lite->send("smtp", 'localhost:2525', Debug => 0, Timeout => 30);

    
  ## first add the main text part
  my $subject = "";
  if (defined($this->{headers}{Subject})) {
  	$subject = $this->{headers}{Subject};
  }
  my $main_text_part = $this->getMainTextPart();
  my $from = $this->{from};
  my $mime_msg;
  my $txt = "";
  my %headers;
  
  if ($this->{type} eq 'text') {
     
    if ($this->{sup_part} eq "") {
  
  	  $txt = $main_text_part;
          $txt = encode("utf8", $txt);
          $txt = encode_qp($txt);

  	  %headers = (
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset="utf-8"',
        'Content-Transfer-Encoding' => 'quoted-printable',
        'To' => $to,
        'From' => $from,
        'Subject' => $subject,
        'X-Auto-Response-Suppress' => 'DR, NDR, RN, NRN, OOF, AutoReply'
       );
     } else {
        $mime_msg = MIME::Entity->build(
                                      From    => $from,
                                      To      => $to,
                                      Subject =>$subject,
                                      Data    => $main_text_part,
                                      Type => 'text/html',
                                      Charset => 'utf-8',
                                      Encoding    => "quoted-printable",
                    ) or return 0;
       if (! $this->{sup_part} eq "") {
        $mime_msg->attach(
                       Type => 'application/text',
                       Filename => 'message.txt',
                       Data => $this->{sup_part}
	            );
       }   
       $mime_msg->replace('X-Mailer', 'MailCleaner');
       $txt = $mime_msg->stringify();
     }
  } else {
  	$mime_msg = MIME::Entity->build(
  	   From => $from,
       To    => $to,
       Subject =>$subject,
       Type    =>'multipart/related',
    ) or return 0;
    
    my @parts = $this->getUseableParts();
    foreach my $part (@parts) {
      if ($part =~ /\.(txt|text)$/i) {
      	## add text part
      	my $body = $this->parseTemplate($this->{path}."_parts/$part");
      	$mime_msg->attach(
      	        Type => 'TEXT',
      	        Data => $body );
      } elsif ($part =~ /\.(html|htm)$/i) {
      	## add html part
      	my $body = $this->parseTemplate($this->{path}."_parts/$part");
      	$mime_msg->attach(
      	        Type => 'text/html',
                Charset => 'utf-8',
                Encoding    => "quoted-printable",
      	        Data => $body
                 );
      } elsif ($part =~ /\.gif$/i) {
      	## add gif part
      	if (defined($this->{attachedpicts}{$part})) {
        	$mime_msg->attach(
      	        Type => 'image/gif',
      	        Id   => '<'.$part.'>',
      	        Path => $this->{path}."_parts/$part");
      	}
      } elsif ($part =~ /\.(jpg|jpeg)$/i) {
      	## add jpeg part
      	if (defined($this->{attachedpicts}{$part})) {
        	$mime_msg->attach(
      	        Type => 'image/jpeg',
      	        Id   => '<'.$part.'>',
      	        Path => $this->{path}."_parts/$part");
      	}
      } elsif ($part =~ /\.png$/i) {
        # add png part
        if (defined($this->{attachedpicts}{$part})) {
             $mime_msg->attach(
	             Type => 'image/png',
	             Id   => '<'.$part.'>',
	             Path => $this->{path}."_parts/$part");
        }
      }
      if (! $this->{sup_part} eq "") {
      $mime_msg->attach(
		Type => 'application/text',
		Filename => 'message.txt',
        Data => $this->{sup_part}
	   )
    } 

    } 
    
    $mime_msg->replace('X-Mailer', 'MailCleaner');
    $txt = $mime_msg->stringify();
  }
 
  my $smtp;
  while ($retries > 0) {
    last if ($smtp = Net::SMTP->new('localhost:2525'));
    $retries--;
    if ($retries == 0) {
      print "cannot connect to outgoing smtp server !\n";
      return 0;
    }
    sleep 60;
  }
  $smtp->mail($from);
  $smtp->to($to);
  my $err = $smtp->code();
  if ($err == 550) {
    print "NOSUCHADDR ".$to."\n";
    return 0;
  }
  if ($err >= 500) {
    print $err;
    my $errmsg = $smtp->message();
    chop $errmsg;
    print "ERRORSENDING ".$to." (".$errmsg.")\n";
    return 0;
  }
  $smtp->data();
  $smtp->datasend("Date:".`date -R`);
  foreach my $header (keys %headers) {
    $smtp->datasend("$header: ".$headers{$header}."\n");
  }
 
  $smtp->datasend($txt);
  $smtp->dataend();
  $err = $smtp->code();
  if ( $err < 200 || $err >= 500 ) {
      ## smtpError
      return;
  }
  my $returnmessage = $smtp->message();
  my $id = 0;
  if ($returnmessage =~ m/id=(\S+)/) {
      $id = $1;
  }
  
  return $id;
}

###
# get the main text part body
# @return  string  part body
###
sub getMainTextPart {
  my $this = shift;
  
  my $file = $this->{path}.".txt";
  return $this->parseTemplate($file);
}

sub getDefaultValue {
  my $this = shift;
  my $value = shift;
  
  if (defined($this->{values}{$value})) {
  	return $this->{values}{$value};
  }
  return "";
}
###
# get the parts
# @return   array   array of useable parts files
###
sub getUseableParts {
  my $this = shift;
  
  my @ret = (); 
  # first add html parts
  if ( opendir(DIR, $this->{path}."_parts")) {
  	while (defined(my $file = readdir(DIR))) {
  	 chomp($file);
  	 next if ($file !~ /\.(htm|html)$/i);
  	 	push @ret, $file;
  	}
   close(DIR);
  }
  # next add text part
  if ( opendir(DIR, $this->{path}."_parts")) {
  	while (defined(my $file = readdir(DIR))) {
  	 chomp($file);
  	 next if ($file !~ /\.(txt|text)$/i);
  	 	push @ret, $file;
  	}
    close(DIR);
  }
  # finally add pictures
  if ( opendir(DIR, $this->{path}."_parts")) {
  	while (defined(my $file = readdir(DIR))) {
  	 chomp($file);
  	 next if ($file !~ /\.(gif|jpg|jpeg|png)$/i);
  	 #print "pushing: $file\n";
  	 push @ret, $file;
  	} 
    close(DIR);
  }
  return @ret;
}

###
# parse a template and return body text
# @param   $template  string   template file to use
# @return             string   body text
###
sub parseTemplate {
  my $this = shift;
  my $template = shift;
  
  return "" if (!open(FILE, $template));
  
  my $ret;
  my $in_hidden = 0;
  my $in_headers = 1;
  while (<FILE>) {
  	my $line = decode_qp($_);
  	
  	if ($in_headers) {
     next if ($line =~ /^(\S+)\:\ (.*)$/);
  	 $in_headers = 0;
  	}
  	if ($line =~ /\?\?START\_([A-Z0-9]+)/ ||  $line =~ /\_\_TMPL\_([A-Z0-9]+)\_START\_\_/) {
      $in_hidden = 1;
      next;
  	}
  	if ($line =~ /\_\_TMPL\_([A-Z0-9]+)\_STOP\_\_/) {
      $in_hidden = 0;
      next;
  	}
  	if ($line =~ /\?\?END\_([A-Z0-9]+)/) {
  	  $in_hidden = 0;
  	}
  	if ($line =~ /__DEFAULT__/) {
  	  next;
  	}
  	
  	if (!$in_hidden) {
      $ret .= $line;
  	}
  }
  
  ## do the replacements
  
  ## replace well known tags
  my $sys = SystemPref::getInstance();
  my $conf = ReadConfig::getInstance();
  
  my $http = "http://";
  if ( $sys->getPref('use_ssl') =~ /true/i ) {
		$http = "https://";
  }
  my $baseurl = $http.$sys->getPref('servername');
  my %wellknown = (
    '__BASEURL__' => $baseurl,
    '__WEBBASEURL__' => $baseurl,
    '__MAILCLEANERURL__' => $baseurl,
    '__FORCEURL__' => $baseurl,
    '__STOREID__' => $conf->getOption('HOSTID'),
    '__ADDRESS__' => $this->{to},
    '__LANGUAGE__' => $this->{language},
    '__SPAMNBDAYS__' => $sys->getPref('days_to_keep_spams'),
  );
	
  ## replace given tags
  foreach my $tag (keys %{$this->{replacements}}) {
  	$ret =~ s/$tag/$this->{replacements}{$tag}/g;
  	if ($tag =~ /\_\_(\S+)\_\_/) {
  	  my $tag2 = "\\?\\?$1";
  	  $ret =~ s/$tag2/$this->{replacements}{$tag}/g;
  	}
  }
  
  foreach my $tag ( keys %wellknown ) {
    $ret =~ s/$tag/$wellknown{$tag}/g;
    if ($tag =~ /\_\_(\S+)\_\_/) {
  	  my $tag2 = "\\?\\?$1";
  	  $ret =~ s/$tag2/$wellknown{$tag}/g;
  	}
  }
  
  my @lines = split('\n', $ret);
  foreach my $rline (@lines) {
   while ($rline =~ /cid:(\S+.(jpe?g|gif|png))(.*)/ ) {
       $rline = $3;
       $this->{attachedpicts}{$1} = 1;
   }
  }
      
  return $ret;
}

sub uniqid() {
 my $sessionId  ="";
 my $length=16;

 for(my $i=0 ; $i< $length ;) {
    my $j = chr(int(rand(127)));

	if($j =~ /[0-9]/) {
      $sessionId .=$j;
      $i++;
	}
  }
  return $sessionId;
}

1;
