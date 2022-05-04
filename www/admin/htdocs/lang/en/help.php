<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
/**************************
 ** TO DO
 **   Delete $grafix
 **   All h2 headings should be consistent: colons or not? separate line or not?
 **   Remove unused tags
 **   To tags that are subheadings: Add the name of the page to the title (e.g., "Antispam settings: Network checks")
 **/
$help['NOSUBJECT'] = "help for this topic is not available";

/**********************
$grafix = 
"analyse.gif    	<img src=\"images/analyse.gif\" ><br/>
apply.gif      	<img src=\"images/apply.gif\" ><br/>
asc.gif        	<img src=\"images/asc.gif\" ><br/>
desc.gif       	<img src=\"images/desc.gif\" ><br/>
erase.gif      	<img src=\"images/erase.gif\" ><br/>
fastnetlogo.gif	<img src=\"images/fastnetlogo.gif\" ><br/>
force.gif      	<img src=\"images/force.gif\" ><br/>
forced.gif     	<img src=\"images/forced.gif\" ><br/>
help.gif       	<img src=\"images/help.gif\" ><br/>
info.gif       	<img src=\"images/info.gif\" ><br/>
logout.gif     	<img src=\"images/logout.gif\" ><br/>
mclogo.gif     	<img src=\"images/mclogo.gif\" ><br/>
mctitle.gif    	<img src=\"images/mctitle.gif\" ><br/>
minus.gif      	<img src=\"images/minus.gif\" ><br/>
pencil.gif     	<img src=\"images/pencil.gif\" ><br/>
plus.gif       	<img src=\"images/plus.gif\" ><br/>
reasons.gif    	<img src=\"images/reasons.gif\" ><br/>
search.gif     	<img src=\"images/search.gif\" ><br/>
top_back.gif   	<img src=\"images/top_back.gif   \" ><br/>
";
**********************/


/****************************
 * View and Manage Domains
 ****************************/

### Verified 
$help['DOMAINLISTTITLE'] = "<h1>List of filtered domains</h1>
This is the list of the domain names accepted and forwarded by MailCleaner.<br/><br/>
The first column, Domain Names, lists the currently filtered domain names.<br/>
The following column, Destination Server, indicates the final destination mail
server to which messages for this domain will be forwarded after being
processed by the filter. Usually, this server name corresponds to the former MX
record before MailCleaner was installed for this domain.<br/><br/>
In this list, you can edit (<img src=\"images/pencil.gif\" border=\"0\">) or
delete (<img src=\"images/erase.gif\">) domains.
";


### Verified
$help['DOMAINNAME'] = 
"<h1>Domain name</h1>
This is the name of the domain you wish to be filtered by MailCleaner.<br/><br/>
You can add as many domains as you wish, each one with its own behavior and settings.<br/>
MailCleaner can act as a full mail gateway and mail routing system.<br/><br/>
Without at least one domain configured, MailCleaner will not accept any mail.
Therefore, a minimum of one domain is required.<br/><br/>
Enter \"*\" as the domain name if you wish to filter any domain names, but DO
NOT do so unless you aware of the potential consequences! This will set your
system as an open relay; extremely restrictive firewall settings would be in
order to assure that MailCleaner's TCP port 25 (SMTP) is not available from the
Internet.
";


### NOT USED?!
$help['DOMAINSETTINGS'] =
"<h1>Domain settings</h1>
The settings in this section allow you to configure how MailCleaner handles
messages for this domain.<br/><br/>
Each domain may be configured differently from all others, so MailCleaner can
act as a complete multi-domain gateway and mail router.<br/><br/>
Some of these settings (e.g., Filtering and Preference settings) may be
overridden by user preferences; these will be the default settings for the users
of this domain.
";


### Verified
$help['DOMAINDELIVERY'] = 
"<h1>Delivery settings</h1>
These settings define how messages will be handled once they have been
processed by MailCleaner.<br/><br/>
<h2>Destination server:</h2>
This is the server to which messages for this domain will be forwarded once
they have been processed.<br/> Generally, this is either the final mail server
for the domain or another gateway. <br/><br/> You can enter a (fully qualified)
hostname or an IP address.<br/> For multiple destination servers (when load
balancing), you can separate the different hosts with \":\". (i.e.,
host1.domain.com:host2.domain.com).
<h2>Use MX record:</h2>
If enabled, MailCleaner will use the DNS MX records for routing messages to the
destination mail server rather than using the \"Destination server\" setting.
<br/>
<b>Warning:</b> This option should <b>only</b> be used if e-mail addresses are
rewritten during delivery, or if your MailCleaner server uses a local,
nonpublic DNS server that has been customized for this purpose.
<h2>Action on spam:</h2>
This will define how to handle messages that are detected as spam.<br/>
This setting can be overridden by user preferences.
<ul>
<li><b>tag:</b> spam will be delivered to the destination mail server, but will
include a tag in the subject. This may be useful if you wish to create an
incoming rule on your final mail server to drop these messages into a specific
folder.<br/><br/>
<li><b>quarantine:</b> spam will not be delivered to the destination mail
server, but will be stored in the MailCleaner quarantine. Users may view the
content of the quarantine in real time using the web interface, or through the
periodic e-mail summaries.
<br/>This is the most common setting.<br/><br/>
<li><b>drop:</b> spam will not be delivered to the destination mail server and
will be dropped. There is no means of retrieving these messages, and no log
will be generated. Used in rare circumstances.
</ul>
<h2>Enable SMTP callout:</h2>
In principle, MailCleaner has no means of knowing which e-mail addresses are
configured on your destination mail server. Therefore, by default, it accepts
all messages destined for the configured domains, regardless of the name part
of the address. This leads to a great deal of unnecessary load on the
MailCleaner server, since it must process messages for nonexistent recipients
only to have these messages rejected afterward by the destination mail server.<p> 
If you enable the SMTP callout option, MailCleaner will first check the validity of the
recipient address by doing an SMTP callout to your destination mail server.<br/> This
may considerably reduce the load on your system if you are prone to dictionary
spam attacks.<br/> The downside to using this option is a slight increase in
network traffic between MailCleaner and your destination mail server(s) due to
callout requests. But MailCleaner keeps the results of such requests in cache,
thereby minimizing such traffic as much as possible.<br/> This option is not
recommended if your destination mail server acts as a gateway and does not
refuse nonexistent addresses during the SMTP dialog (i.e.  MS Exchange Server <=
5.5) 

<h2>Enable LDAP/AD callout:</h2>
This option is similar to SMTP callout, except that it uses LDAP/Active
Directory to validate addresses. <br/>To use this option, you must configure
\"LDAP/AD callout configuration\" on the SMTP Configuration page.
<p><b>Do not enable both SMTP callout AND LDAP/AD callout.</b>
";


### Verified 
$help['DOMAINFILTERING'] = 
"<h1>Email filtering</h1>
These settings allow the administrator to enable or disable the fundamental
MailCleaner components. They also allow the administrator to define the default
subject tags.
<br/><br/>
<h2>Antivirus/Content protection: </h2>
Enable or disable MailCleaner's antivirus and dangerous content protection.
<h2>Virus tag: </h2>
This is the tag that will prefix the subject of the message if a virus has been
detected and removed from the message. You may set this as a blank string to
avoid appending tags altogether. (In this case, you would still be able to
filter against the X-MailCleaner headers in the final mail server or mail
client if need be.)
<h2>Dangerous content tag:</h2>
Similar to Virus tags, this is the tag that will prefix the subject of the
message if dangerous content has been detected and removed from the message. 
<h2>Antispam:</h2>
Enable or disable the spam detection engine of MailCleaner.
<h2>Tag:</h2>
This is the tag that will prefix the subject if the message has been detected
as spam. You may set this as a blank string to avoid appending tags altogether.
(In this case, you would still be able to filter against the X-MailCleaner
headers in the final mail server or mail client if need be.) This setting can
be overridden by users in their preference settings.
";


### Verified 
$help['DOMAINPREFERENCES'] = 
"<h1>Domain preferences</h1>
These are the default values for user preferences. They can be overridden by
individuals via the user web interface.
<br/><br/>
<h2>Language:</h2>
Set the default language for the domain. This will impact the web user
interface and e-mail summaries. Even if a user modifies this setting in the
user interface, the initial logon page for the user interface will remain in
this language.<br/>
<h2>Summary frequency:</h2>
Set the default frequency of the e-mail spam reports that list the messages
currently found in the spam quarantine. Each day, week, or month, the users
will receive an e-mail containing the list of all spam that have been blocked
for his/her address(es).<br/>
Using links found in these periodic reports, users can release any message
found in the quarantine.
<h2>Support email:</h2>
This is the technical support address that will be found in MailCleaner
summaries and virus/dangerous content warnings. This is also the address which
will be alerted if a user requests that a message held in quarantine be
analyzed.
";


### Verified 
$help['USERAUTHENTICATION'] = 
"<h1>User authentication</h1>
MailCleaner can authenticate users in a variety of ways.<br/><br/>
Instead of storing user credentials locally on MailCleaner, you can use the different mechanisms (connectors) in order to authenticate against your existing credential database.  <br/>
This allows your users to access the user interface to consult their quarantine
and set their preferences without the superfluous overhead of yet another
password database. 
<h2>Connector: </h2>
This is the mechanism used for authentication.
<ul>
<li><b>local</b>: authenticate against users local to the machine.
<li><b>mysql</b>: authenticate against a mysql database.
<li><b>imap</b>: authenticate against an imap server.
<li><b>pop3</b>: authenticate against a pop3 server.
<li><b>ldap</b>: authenticate against a ldap or active directory server.
<li><b>radius</b>: authenticate against a radius server.
</ul>
<h2>Server and Port: </h2>
This is the name or IP address of the server against which you want MailCleaner
to do the authentication. The field after the \":\" is the port used for the
authentication. (The standard IANA-assigned ports are 3306 for mysql, 143 for
imap, 110 for pop3, 389 for ldap, and 1812 for radius.)
<h2>Use SSL:</h2>
If enabled, the sessions established for user authentication will be encrypted.
<h2>Username format: </h2>
This is the username format that your authentication server requires. Often,
multi-domain mail servers expect the domain to be appended to the username in
order to differentiate accounts of different domains.
<ul>
<li><b>username@domain: </b> MailCleaner will append an \"@\" and the domain to
the username (if not already present in the username) before authentication.
<li><b>username%domain: </b> MailCleaner will append a \"%\" and the domain to
the username (if not already present in the username) before authentication.
<li><b>username: </b> no change is made to the username.
</ul>
<h2>Address format: </h2>
MailCleaner organizes its quarantine by e-mail address, not by username.
Therefore, when a user logs on to MailCleaner for the first time, if the
username isn't the same as the e-mail address, a mechanism is necessary to
allow MailCleaner to correspond the quarantine (e-mail address) to the
username.  <br/>
The Address format is the means by which MailCleaner will bind the correct
e-mail address(es) to the authenticated user. Using this format template,
MailCleaner can deduce the e-mail address based on the username.
<ul>
<li><b>local lookup: </b>This option can be used if the Connector is <b>local</b> or if no others apply. In the latter case, you will need to manually create users and their addresses via the \"<b>Manage by users</b>\" interface.
<li><b>username@domain: </b>the address will be constructed with the username and the domain name.
<li><b>ldap lookup: </b>the address(es) will be fetched from the ldap/active directory.
<li><b>text file lookup: </b>the address(es) will be fetched from a local file.
<li><b>mysql lookup: </b>the address(es) will be fetched from a mysql server.
</ul>

<h2><b>LDAP SUPPLEMENTARY FIELDS</b></h2>
The following fields are available if you have chosen ldap as your connector.
<h2>Use SSL (ldaps): </h2>Enable this if your LDAP uses SSL (LDAPS).
<h2>Protocol version: </h2>Version of the LDAP server protocol. For example, Windows Server 2003 uses version 3.
<h2>Base DN:</h2>The basic DN of the LDAP server.
<h2>User attribute:</h2>The LDAP attribute that contains the user name.
<h2>Bind user (optional):</h2>
If the LDAP server does not allow anonymous access (e.g., AD 2003), this is the
username to connect the LDAP directory.
<h2>Bind password (optional):</h2>The password for the Bind User.
";


$help['WHITEWARNLIST'] =
"<h1>Whitelists and warnlists</h1>
<b>Whitelists</b> let users set up a list of sender that will never be flagged as spam or put in quarantine. 
Although this option may be convenient, it is also very dangerous as spammers tend to forge the sender address to fake someone you know.
<br>With many entries in whitelists, your filter may become less efficient and being open to smart spam campains.
<br><br>
<b>Warnlists</b> are a less dangerous method because it forces the user to be more attentive to the list he sets.<br> Instead of simply let the message go through when the sender is listed,
 the system will send a warning message to the final recipient noticing that is has been put in quarantine. This way, the user is aware that something went wrong with the filter
 and can eventually forward the problem to the administrator.<br>Moreover, the user who receives a warning for a real spam will know where the error lies, and not simply believe that the filter has let it go through.
<br><br>
Althoug it is always better to avoid any kind of list, it is advised for the administrator who really needs it, to only enable warnlists first.
<br>Enabling both is possible, but may not be very usefull.
<br>Now if you really want to enable whitelists for the whole domain, you have been warned !
 ";

### Verified 
$help['DOMAINTEMPLATES'] =
"<h1>Templates</h1>
<h2>User web interface</h2>If there is more than one user interface template installed, you can choose the template here that determines the look of the user web interface.
<h2>Summaries</h2>This is the default language used to send MailCleaner summary reports for this domain.
<h2>Warnings</h2>This is the default language used to send virus/dangerous content warning messages for this domain.
";


/****************************
 *  Manage by e-mail
 ****************************/

### Added
$help['EMAILLISTTITLE'] = 
"<h1>Administration by e-mail address</h1>
MailCleaner handles address and user administration separately, even though these two entities are tightly related. This is because a single user can have many e-mail addresses; However, an e-mail address is not necessarily associated with a user.<p>
That being said, the majority of interesting options are found under address administration.
<h2>Search: </h2>
In order to choose the address to edit, first select the domain. Then type the name part of the address. If you wish, you can type just a portion of the name part of the address and click on Search (<img src=\"images/search.gif\" border=\"0\">).<p>
If you would like to view the list of all addresses for the domain, simply click on Search when the email field is empty.<p>
Click on the desire address to edit its properties.
";


### Verified
$help['EMAILSETTINGS'] = "
<h1>Settings for an email address</h1>
The user can define how to handle spam for each individual address under his
account. Using this interface, you can modify settings on behalf of the
user.<br/><br/>
<h2>Action on spam: </h2>
There are three different ways that MailCleaner can handle messages determined to be spam:
	<ul>
		<li><b>tag: </b>Route the message as usual, but insert a tag at
		the beginning of the subject line to make the message readily
		identifiable as spam
		<li><b>quarantine: </b>Put the spam in quarantine, where the
		user can force its release at a later time if desired
		<li><b>drop: </b>Delete the message altogether
	</ul>
<h2>Tag: </h2>
This is the tag that would be inserted in the subject line if \"tag\" is selected as the Action to take, above.
<h2>Quarantine postmaster errors: </h2>
If enabled, all non-delivery reports (NDRs) sent to this address are tagged or
quarantined, depending on the \"Action on spam\" setting.  This is useful when
a user's address has been used as the sender's address in a spam or virus
campaign; in this case, the user could receive hundreds of NDRs in the period
of a few days.<br/>
While this function is useful on a short-term basis, we advise you to disable
it a few weeks after enabling it, as it also quarantines legitimate NDRs (which
would be sent, for example, if the user misspells an e-mail address).
<h2>Summary frequency: </h2>
The frequency at which the user of this address automatically receives a Spam Summary Report for this address.
<h2>Language: </h2>
The language of Spam summary reports and any other automated messages sent by MailCleaner.
<h2>Access to quarantine: </h2>
Clicking on this link will bring you directly to the list of contents of the user's quarantine.
<h2>delete email settings: </h2>
Restores the current address's settings to the domain default settings.
";


/****************************
 *  Manage by user
 ****************************/

### Verified
$help['USERLISTTITLE'] = 
"<h1>Registered users list</h1>
This is the list of users known by MailCleaner for the selected domain. This list is based on users who have logged on at least once--unless LDAP is used, in which case the list can be based on LDAP lookup.<br/><br/>
<h2>Search: </h2>
In order to choose the user to edit, first select the domain. Then type all or part of the username and click on Search (<img src=\"images/search.gif\" border=\"0\">).<p>
If you would like to view the list of all users for the domain, simply click on Search when the email field is empty.<p>
Click on the desired address to edit its properties.
";


### Verified
$help['USERSETTINGS'] = 
"<h1>User Settings</h1>
<h2>Language: </h2>
Set the language for the user interface for this user. Note that the login page will remain the default language of the MailCleaner installation.
<h2>Addresses: </h2>This is a pull-down menu containing a list of all addresses that have been associated with this user, including the \"main\" address for the user<br/>
To make an address the main address for the user select the address and click the <img src=\"images/analyse.gif\"> icon.<br/>
To delete an address from the user's account, select the address to remove and click the <img src=\"images/erase.gif\"> icon. Note that remove an address from a user account does not delete the address itself from MailCleaner.<br/>
<h2>Add address: </h2>To add an address to this user profile, type the address here and click on the apply button.
<h2>Delete user settings: </h2>
This will delete the user profile, including all associations with e-mail addresses (but not the information concerning the e-mail addresses themselves).
";


/****************************
 *  Spam quarantine
 ****************************/

### Added
$help['SPAMFILTERTITLE'] = 
"<h1>Spam quarantine</h1>
This interface gives you a bird's eye view of the spam quarantine for the entire MailCleaner installation. It is intended to allow you to investigate when a user reports unusual or erroneous behavior by the mail system. From the listing, you can :
<ul>
<li>force a message to be delivered (<img src=\"images/force.gif\" border=\"0\">) to the final user, 
<li>view the list of criteria that were used to identify the message as spam (<img src=\"images/info.gif\" border=\"0\">), or 
<li>forward the message to the Analysis Center (<img src=\"images/analyse.gif\" border=\"0\">). 
</ul>
The fields to filter the results of a search are self-evident, with two exceptions:
<h2>Mask forced</h2>
If enabled, the results do not include messages whose delivery has been forced by the user (or administrator).
<h2>Mask bounces</h2>
If enabled, the results do not include Non-Delivery Reports (NDRs) sent automatically by mail servers to alert the user that a message has not arrived at its destination. Large number of NDRs are generated when a user's address has been used as the sender's address by a virus or spammer.
";


/****************************
 *  Content quarantine
 ****************************/

### Added
$help['CONTENTFILTERTITLE'] = 
"<h1>Content quarantine</h1>
This interface allows you to view or force the
delivery of a message that was quarantined because
of dangerous content. The form is broken in two
parts:
<h2>Content ID</h2>
When a user requests that a message with dangerous
content be released, The Content ID can be found
in the request. It is of the format
20051230/1EZSXP-0006ld-Mv. To find the message,
simply paste this ID in the Content ID field and
click on the search button (<img
src=\"images/search.gif\" border=\"0\">).
<h2>Filtered search</h2>
If you do not have the ID of the message, or if
you would like to peruse through the Dangerous
Content quarantine, you can use the advanced
filter dialog. For MailCleaner installations with
more than one server, please be aware that you can
view the Dangerous Content quarantine on one
server (host) at a time. If you are looking for a
particular message, it may be necessary to try
your search on a number of different servers
before finding it.
";


/****************************
 *      Defaults
 ****************************/

### Added
$help['DEFAULTSTITLE'] = 
"<h1>Configuration of default values</h1>
These defaults are system-wide default values for all domains in the MailCleaner installation.
";


### Added
### Incomplete - still waiting on "Default Domain" info
$help['DEFAULTSDOMAINS'] = 
"<h1>Configuration of default values</h1>
<h2>Language: </h2>
This is the default language for the user interface logon screen as well as all automated e-mails.
";
### <h2>Default domain: </h2>


### Added
$help['DEFAULTSADDRESSES'] = 
"<h1>Addresses</h1>
<h2>Report spam address: </h2>This address is the recipient when users request an analysis of a message in quarantine.
<h2>System sender address</h2>This is the Sender address used with all outgoing automated messages.
";


### Added
$help['DEFAULTSQUARANTINES'] = 
"<h1>Configuration of quarantine defaults</h1>
<h2>Days to keep spam: </h2>Spam will be deleted after being held in quarantine for this number of days. A default value of 60 is suggested.
<h2>Days to keep viruses/content: </h2>Quarantined viruses and dangerous content will be deleted after being held in quarantine for this number of days.
";


### Added
$help['DEFAULTSTASKS'] = 
"<h1>Configuration of periodic task defaults</h1>
<h2>Daily tasks run at: </h2>At the selected time, all scheduled daily maintenance will be executed. Daily maintenance includes sending daily spam reports, deleting expired spam, viruses, and dangerous content from the quarantine (see <a href=\"help.php?s=DEFAULTSQUARANTINES\">Quarantine defaults</a>), and rotating logs.
<h2>Weekly tasks run on: </h2>On this day of the week, at the time given in the Daily Tasks field, scheduled weekly maintenance will be executed. This includes sending weekly spam reports.
<h2>Monthly tasks run on day: </h2>This is the day of month upon which monthly maintenance will be executed, at the given time. This includes sending monthly spam reports.
";


/****************************
 *      Base System
 ****************************/

### Added
### ?? dns and search list space-delimited?
$help['BASENETCONFIG'] = 
"<h1>Network configuration</h1>
<b>Warning:</b> Please be certain of what you are doing before changing these settings. An error could disable the MailCleaner server's network connectivity!
<h2>Network interface: </h2>If the server has more than one network interface installed, you can choose which interface to use. <tt>eth0</tt> is the first interface, and the one used by default.
<h2>IP address, network mask, Gateway: </h2>These IP parameters should be provided by your network administrator.
<h2>DNS servers: </h2>The DNS servers that you would like MailCleaner to use. If there is more than one, delimit the names or addresses with commas.
<h2>Search domain: </h2> The search list is normally determined from the
local domain name; by default, it contains only the local domain name.
This may be changed by listing the desired domain search path following
the search keyword with spaces or tabs separating the names. Resolver
queries that do not include a domain name will be attempted using each
component of the search path in turn until a match is found. If there is more than one, delimit the names with commas.
";


### Added
$help['BASEDATETIMECONFIG'] = 
"<h1>Date and time configuration</h1>
If your network security policy permits it, the MailCleaner servers can have their time set automatically via NTP. To use NTP, enable the \"<b>Use network time server</b>\" option and enter one or more time servers in the pertaining field. Multiple servers should be separated by a comma.<p>
If you do not enable NTP, please verify that the given date and time are correct.
";


### Added
$help['BASEPROXIESCONFIG'] = 
"<h1>Proxy configuration</h1>
If your network topology does not allow your MailCleaner installation direct access to the Internet on ports 80 and 443, it is necessary to provide a web proxy for MailCleaner updates.<br/>
Likewise, if MailCleaner must send external mail (e.g., Non Delivery Reports or similar routing errors), and it cannot directly access port 25 on the Internet, you must provide a valid SMTP gateway.
";


### Added
$help['BASEROOTPASS'] = 
"<h1>Root password</h1>
The root account is the main administrator account for the MailCleaner server. You are advised to change the root password to a long, secure password here.
";


/****************************
 *         SMTP
 ****************************/

### Added
$help['SMTPACCESS'] = 
"<h1>SMTP access</h1>
<h2>Allow relay from hosts: </h2> Machines listed here are permitted to use MailCleaner as a relay to send outbound mail. Note: These hosts must also be included in \"Allow connection from hosts\".
<h2>Allow connection from hosts: </h2>MailCleaner will only accept SMTP connections from these hosts. By default, the asterisk allows open access.
<h2>Reject these hosts: </h2>All messages sent by these hosts are refused with 550 errors during the SMTP dialog. For multiple entries, put each entry on a separate line.
<h2>Reject these senders: </h2>All messages sent from these addresses are refused with 550 errors during the SMTP dialog. For multiple entries, put each entry on a separate line.
<h2>Verify sender: </h2>If enabled, a lookup for the sender domain is executed in order to validate the validating sender lookup domain.
<h2>Enable TLS: </h2>If enabled, MailCleaner will accept encrypted connections. You can define another custom certificate file if needed.
";


### Added
$help['SMTPLDAPCALLOUT'] = 
"<h1>LDAP/AD callout configuration</h1>
These settings are global to the MailCleaner installation. They apply to all domains which have <a href=\"help.php?s=DOMAINDELIVERY\">\"Enable LDAP/AD callout\"</a> enabled. 
";

### Added
$help['GREYLISTCONFIG'] = 
"<h1>Greylisting  daemon configuration</h1>
These settings are global to the MailCleaner installation. They apply to all domains which have <a href=\"help.php?s=DOMAINDELIVERY\">\"Enable greylisting\"</a> enabled. 
";

### Added
$help['SMTPADVANCEDCONFIG'] = 
"<h1>Advanced Configuration</h1>

<h2>Stage:</h2>
In MailCleaner, messages pass through three general stages: 
<nl>
<li> A <i>pre-filtering</i> MTA process receives all messages to be analyzed and places them in a queue. 
<li> The messages are filtered for viruses and spam.
<li> Clean messages are passed to an outgoing queue, which are then delivered by a <i>post-filtering</i> MTA process.
</nl>
You can choose to view/alter the settings below for the pre-filtering (incoming) or post-filtering (outgoing) stage.<br/>

<h2>Connection timeout: </h2>This sets a timeout value for SMTP reception. If a line of input (either an SMTP command or a data line) is not received within this time, the SMTP connection is dropped and the message is abandoned. 

<h2>Maximum simultaneous connections: </h2>
This option specifies the maximum number of simultaneous incoming SMTP calls that the MTA process will accept. If the value is set to zero, no limit is applied.

<h2>Maximum connections per host: </h2>
This option restricts the number of simultaneous IP connections from a single host (strictly, from a single IP address) to the MTA process. Once the limit is reached, additional connection attempts from the same host are rejected with error code 421. The default value of zero imposes no limit.

<h2>Ignore bounces after: </h2> [2d]
This option affects the processing of bounce messages that cannot be delivered, that is, those that suffer a permanent delivery failure. (Bounce messages that suffer temporary delivery failures are of course retried in the usual way.)

<h2>Timeout frozen after: </h2> [7d]
If this value is set to a time greater than zero, a frozen message of any kind that has been on the queue for longer than the given time is automatically cancelled at the next queue run. If it is a bounce message, it is just discarded; otherwise, a bounce is sent to the sender.

<h2>Header: </h2>
This string defines the contents of the Received: message header that is added
to each message, except for the timestamp, which is automatically added on at
the end (preceded by a semicolon). Please see <a
href=\"http://www.exim.org/exim-html-4.50/doc/html/spec_14.html#IX1351\"
target=\"_blank\">The exim documentation</a> for more information.
";


/****************************
 *    Administrators
 ****************************/

### Added
$help['ADMINTITLE'] = 
"<h1>Administration</h1>
This is the list of MailCleaner administrators. These administrators are able to log on to the MailCleaner administrative web interface.<p>
You can edit (<img src=\"images/pencil.gif\" border=\"0\">)each administrator's rights or delete (<img src=\"images/erase.gif\" border=\"0\">) the administrator altogether.<br/>
To add an administrator, click on <img src=\"images/plus.gif\" border=\"0\">.
";


### Added
$help['ADMINACCESS'] = 
"<h1>Access</h1>
Set this administrator's web interface password.
";


### Added
$help['ADMINAUTHORIZATIONS'] = 
"<h1>Authorizations</h1>
<h2>Manageable domains: </h2>These are the domains that this administrator is authorized to manage. For all domains, enter an asterisk. For multiple domains names, enter a comma-separated list.
<h2>Can manage domains: </h2>If enabled, the administrator can modify top-level settings for the domains listed in the previous field.
<h2>Can manage users: </h2>If enabled, the administrator can add, remove, and modify all users and addresses in the permitted domains.
<h2>Can configure system: </h2>If enabled, the administrator can modify global, system-wide settings, as well as add and delete domains.
<h2>Can view statistics: </h2>If enabled, the administrator can view all statistics.
";


/****************************
 *      Antispam
 ****************************/

### Added
$help['ANTISPAMSETTINGS'] = 
"<h1>Antispam settings</h1>
This panel give you control over generic settings of the antispam engine.<br/><br/>
<h2>Friendly languages:</h2>These are the languages that are most likely to be
spoken by your users. Messages in other languages will be slightly penalized
(i.e., more likely to be considered spam)
<h2>Trusted IPs/Networks:</h2>Put here the network or IP addresses (separated by spaces) of any host in your infrastructure that could eventually handle mail before MailCleaner.
Don't hesitate to also put here the public address of the MailCleaner. This setting will let MailCleaner know how to find the first external host that addresses the messages. This is important for some antispam checks.
<h2>Enable whitelists:</h2>This is the main switch to enable/disable whitelists. Whitelisting can be dangerous and may be exploited by spammers. Use this option with caution.
<h2>Enable blacklists:</h2>This is the main switch to enable/disable blacklists. 
<h2>Enable warnlists:</h2>This is the main switch to enable/disable warnlists. Warnlists are less dangerous than whitelists. Using it before enabling whitelists is generally a good idea.
<h2>Use Syslog logging:</h2>If activated and if a syslog server has been set up in 'Defaults' panel, engine logs will be copied and sent to it.
";

$help['ANTISPAMMODULES'] =
"<h1>Modules</h1>
Each module provide a specific filtering engine that can help detect spams or non-spams. It can be set up as decisive for both possibilities allowing messages to skip the rest of the processing.<br/><br/>
<b>NiceBayes: </b> this module provides a rough statistical database that only hits 99% or 100% sure spams. An efficient database can help reduce further processing by up to 70%.<br/><br/>
<b>ClamSpam: </b> this module provides a check against known spams signatures. This is particularly efficient with attachement spams (ie. PDF, ZIP, etc...)<br/><br/>
<b>PreRBLs: </b> this module provides a check against many public RBL's. This one also helps reduce the processing time.<br/><br/>
<b>Spamc: </b> this modules provides a SpamAssassin check. It is the most important, but also the most resource intensive one. Basically, it should always comes last and be both negatively and positively decisive.
";

$help['SALOCAL'] =
"<h1>Antispam settings: Local checks</h1>
Local checks are the spam-filtering mechanisms that use no Internet-based
resources and no external databases.<br/><br/>
<h2>Use SpamAssassin: </h2>If selected, MailCleaner will use the SpamAssassin
detection engine along with its other modules. You must leave this option
enabled if you want antispam functionality.
<h2>SpamAssassin timeout: </h2>If SpamAssassin takes too long to process a
message, it will time out and pass the message on to the next module. (In this
case, the message will not be considered spam under any circumstances.) The
timeout period is defined here, in seconds.
<h2>Use Bayesian: </h2>Enable the Bayesian classifier included in SpamAssassin.
The Bayesian classifier is extremely effective and thus an important part of
MailCleaner, so it is important to leave this option enabled in general.
However, if the server is extremely slow, to the point where it adversely
affects message delivery time, it may be useful to disable the Bayesian
classifier for a brief time.
<h2>Auto-learn: </h2>If enabled, MailCleaner will analyze all incoming messages
to render the Bayesian classifier more effective. Like the Bayesian classifier,
this is something to disable only for a brief time in case of delivery delays.
<h2>Friendly languages: </h2>These are the languages that are most likely to be
spoken by your users. Messages in other languages will be slightly penalized
(i.e., more likely to be considered spam).
<h2>Trusted IPs/Networks:</h2>Put here the network or IP addresses (separated by spaces) of any host in your infrastructure that could eventually handle mail before MailCleaner.
Don't hesitate to also put here the public address of the MailCleaner. This setting will let MailCleaner know how to find the first external host that addresses the messages. This is important for some antispam checks.";


### Added
$help['SANETWORK'] = 
"<h1>Antispam settings: Network checks</h1>
These MailCleaner modules require Internet access. They primarily use
Internet-based databases.<br/><br/>
<h2>Use Real-time Blocking lists: </h2>This server uses the DNS protocol to
query Blacklist databases on the Internet. These databases maintain lists of
known open relays, dynamically allocated IP addresses, and URI blacklists.<br/>
If network access time is prohibitively slow for a brief period, it may be
useful to temporarily disable this feature.
<h2>Use Razor: </h2>Razor is a constantly updated spam catalog used by
MailCleaner. It is advised to disable this feature if you cannot configure your
firewall to let MailCleaner to use TCP port 2703 (outgoing). <i>(<a
href=\"http://razor.sourceforge.net\"
target=\"_blank\">razor.sourceforge.net</a>)</i>
<h2>Use Pyzor: </h2>Pyzor is a constantly updated spam catalog used by
MailCleaner. It is advised to disable this feature if you cannot configure your
firewall to let MailCleaner to use UDP port 24441 (outgoing). <i>(<a
href=\"http://pyzor.sourceforge.net\"
target=\"_blank\">pyzor.sourceforge.net</a>)</i>
<h2>Use DCC (Distributed Checksum Clearinghouse): </h2>DCC uses UDP port 6277
(outgoing). <i>(<a href=\"http://www.rhyolite.com/anti-spam/dcc/\"
target=\"_blank\">www.rhyolite.com/anti-spam/dcc/</a>)</i>
";


/****************************
 *      Antivirus
 ****************************/

### Added
$help['ANTIVIRUSSCANNERS'] = 
"<h1>Antivirus: Scanners</h1>
MailCleaner can work with a variety of different antivirus programs; by default, it is installed with ClamAV. If you choose to use another supported antivirus program, you must enable the corresponding option here. In such a case, it is necessary to confirm that the given path is correct.
";


### Added
### Reread
$help['ANTIVIRUSSETTINGS'] = 
"<h1>Antivirus: Settings</h1>
MailCleaner uses antivirus software to verify that attachments
do not contain known viruses. All the same, even if an
attached file is not found to contain a known virus, it will
be withheld if the contents or filename are deemed
suspicious.<br/>
The settings found here affect how MailCleaner interacts with
the chosen antivirus software (ClamAV by default), as well as
attachment processing in general.<br/><br/>
<h2>Don't warn on known viruses: </h2>If this feature is enabled, MailCleaner
will not issue a virus warning if the attachment in question contains a virus
confirmed by the antivirus module. It will not deliver the infected message to
the recipient. Because most modern viruses forge the sender's address to cover
their tracks, it is generally pointless to issue a virus alert. Users will
still be warned if messages with otherwise potentially dangerous content are
quarantined.
<h2>Maximum message size: </h2>If this value is set, MailCleaner will
quarantine all messages larger than the given size. In this case, a warning
will be sent to the user (unless the user has disabled warning reception); this
warning is much like a <i><a href=\"help.php?s=CONTENTFILTERTITLE\">virus or
dangerous content warning</a></i>, including an identification string in the
same format.<br/> Such withheld messages can be released by the administrator
via the <b>content quarantine</b> page in the admin interface.<br/> A value of 0
(the default value) means that there is no size limit to messages. However, any
size limits imposed by the destination mail server will still apply.
<h2>Maximum attachment size: </h2>If this value is set,
MailCleaner will quarantine all attachments larger than the given size. In this
case, a warning will be sent to the user (unless the user has disabled warning
reception); this warning is much like a <i><a
href=\"help.php?s=CONTENTFILTERTITLE\">virus or dangerous content
warning</a></i>, including an identification string in the same format.<br/>
Such withheld attachments can be released by the administrator via the
<b>content quarantine</b> page in the admin interface.<br/> A value of -1 (the
default value) means that there is no size limit to messages.<br/> A value of 0
means that no attachments are permitted.<br/>
<h2>Maximum archive depth: </h2>Sometimes, archive files
(.zip, .tar.gz, etc.) are re-archived, i.e., a .zip within a
.zip. Generally, this is done by mistake or for dubious
reasons. If desired, a maximum depth of archives within
archives can be defined here.<br/>
A value of 0 (the default value) means that there is no limit
to the depth of archives.
<h2>Expand TNEF: </h2>Microsoft Exchange and Outlook e-mail clients use a
proprietary format called TNEF (Transport Neutral Encapsulation Format) when
sending messages formatted in Rich Text Format (RTF). When Microsoft Exchange
believes that the recipient uses a Microsoft mail client, it extract all
formatting information and encodes it in a special TNEF block. The resulting
message is in two parts: the text message with all formatting removed, and the
TNEF block (which includes the formatting instructions). Unfortunately, if the
recipient does not use a Microsoft mail client, the mail client can do nothing
with the TNEF block. It generally appears as an attachment called
winmail.dat.<br/> MailCleaner is capable of expanding TNEF blocks in order to
analyze formatting data therein. It is preferable to leave this option active.
<h2>Deliver bad TNEF: </h2>In general, it is a bad idea to deliver messages
with improperly formatted TNEF blocks, as it can cause a variety of errors with
Microsoft Exchange Server as well as a number of mail clients. Therefore, it is
best to leave this option unchecked. However, if you regularly receive e-mails
from an organization with improperly formatted messages that are quarantined,
you may wish to experiment with this option.
<h2>Send notices (notices to): </h2>This option allows the administrator to
send a copy of <b>all</b> e-mail notices generated by MailCleaner to a central
administrative address.  Any e-mail notice that would be generated to alert a
user of a spam or virus would also be sent to this address, regardless of
whether or not the user has enabled notice reception in their personal
preferences.
<h2>Virus scanner timeout: </h2>If the antivirus module takes too long to
process a message, it will time out and pass the message on to the next module.
(In this case, the message will not be safely filtered for viruses.) The
timeout period is defined here, in seconds.
<h2>File type control timeout: </h2>Certain files are withheld purely on the
basis of their file type. If this analysis
takes an unusually long time for any reason, the analysis will time out and
pass the message to the next module. The timeout duration can be modified here.
<h2>TNEF expander timeout: </h2>If TNEF expansion takes an unusually long time
for any reason, the expansion will time out and pass the message to the next
module. The timeout duration can be modified here.
";


/****************************
 *  Dangerous content
 ****************************/

### Verified
$help['DANGEROUSTITLE'] = 
"<h1>Dangerous content protection settings</h1>
<i>Dangerous content</i> is content that is not identified as virus-related, but is suspicious in nature. This can include executable or dubiously named files, HTML that includes code which could execute upon the opening of a message, and improperly formatted messages. This page allows you to control in detail the way that MailCleaner handles such messages.
";


### Verified
$help['DANGEROUSHTMLCHECKS'] = 
"<h1>HTML content checks</h1>
These settings cover often-used html code that can be used maliciously. Most of these technologies are used regularly on Internet web sites for legitimate reasons. However, by the nature of being web-based technologies, they were not designed for use in e-mail messages. Therefore, their presence in messages can signal abuse.<br/>
For these options, check the \"Set as Silent\" box to prevent the user from receiving a warning when messages are blocked by each of these criteria.<br/>
<h2>IFrame tags: </h2>This could allow a maliciously written e-mail to download and execute code from an outside source.
<h2>Form tags: </h2>E-mail messages rarely contain forms for legitimate reasons. For example, forms are often used by phishers. (<i>Phishing</i> is the act of trying to dupe users into divulging information regarding their personal bank accounts, credit cards, or passwords.) All the same, forms are occasionally used in legitimate general distributions such as newsletters.
<h2>Script tags: </h2>HTML can contain scripts, for example, Javascript or VBScript. Generally, scripts in e-mail messages should be avoided. All the same, forms are occasionally used in legitimate general distributions such as newsletters.
<h2>CodeBase tags: </h2>The codebase attribute of the &lt;object&gt; tag can be used to insert the URL of a remotely located object (ActiveX component, applet, image map, plug-in, media player, etc.) in a message. This can leave the user unprotected against various Microsoft-specific security vulnerabilities. It is best to leave this option blocked unless your users demand its activation.
<h2>Web Bugs: </h2>Spammers sometimes include images in their messages that are downloaded from a remote server upon opening the message. Often, the URLs for these images are unique to each message; this allows the spammers to know that a particular address is valid, because the image URL will contain enough information for the server delivering the image to identify the recipient's e-mail address.<br/>These uniquely formed URLs--used to validate recipients' e-mail addresses--are called Web Bugs.
MailCleaner can disarm Web Bugs so that the message can be safely opened without sending any compromising information to outside servers.
";


### Recycled
$help['DANGEROUSFORMATCHECKS'] = 
"<h1>Message format checks</h1>
<h2>Password archives: </h2>Archive files (i.e., .ZIP, .RAR) that are protected by a password. This is commonly used to propagate viruses, since antivirus programs cannot decrypt such files in order to verify their contents. The virus writer simply puts the password in plain text in the message body to dupe the user into opening the malicious archive.
<h2>Partial messages: </h2>A Message that is sent as fragments over many messages. The user's mail client could re-assemble the message, including a malicious attachment.
<h2>External bodies: </h2>Messages whose bodies are stored externally, somewhere else on the Internet. This functionality is used by very few mail clients, and should generally be disabled.
<h2>Encrypted messages: </h2>Messages that are encrypted (i.e., PGP, SSL). Allowed by default.
<h2>Unencrypted messages: </h2>Messages that are unencrypted. Allowed by default.
<h2></h2>
";


### Verified
$help['DANGEROUSATTACHCHECKS'] = 
"<h1>Attachment checks</h1>
Today, viruses and worms are the most prevalent form of dangerous file attachment. But antivirus software alone is not enough to deal with dangerous attachments. Antivirus software is ineffective in two areas: in the brief period when a new virus, or a new variant of a known virus, is first released in the wild; and when dealing with malicious code (scripts or embedded Visual Basic code, for example) that specifically targets a user or group of users.<br/> <br/>
In order to complement antivirus software when protecting users from malicious attachments, MailCleaner quarantines attachments that are suspiciously named or of a particularly vulnerable file type (for example, self-executing files). In this case, the administrator must release the file in question. The administrator can customize MailCleaner to be more or less restrictive as need be.<br/><br/>
<h2>Filename control: </h2>This list shows all file types that MailCleaner is configured to handle, as well as the action to take for each file type (allow or deny). 
<h3>Action: </h3>If \"deny\", this file type is automatically quarantined.
<h3>Rule: </h3>The definition that the file name must satisfy in order to fall into this rule. These rules follow <a href=\"http://www.perl.com/doc/manual/html/pod/perlre.html\" target=\"_blank\"><i>Perl Regular Expression</i></a> syntax.
<h3>Name: </h3>A short name for the file extension. This will be used in the log files.
<h3>Description: </h3>A meaningful description for the file extension. This will be used in the virus warning (VirusWarning.txt) that the user receives.
<br/><br/>
In this list, you can edit (<img src=\"images/pencil.gif\" border=\"0\">) or delete (<img src=\"images/erase.gif\">) a file extension.
<h2>Filetype control: </h2>
This list behaves much like the previous list with one significant exception: The administrator can neither add nor delete an entry on this list, because the file types that are identifiable by MailCleaner are determined by the MailCleaner installation itself. This list can only change with MailCleaner software updates.
";


/****************************
 *     External Access
 ****************************/

### Added
### EMPTY
$help['ACCESSTITLE'] = 
"<h1>External access configuration (firewall)</h1>
These settings allow you to limit access to the various Internet-based services that run on MailCleaner.<br/>
Multiple IP addresses or ranges must be separated by colons (:). To open to all addresses, use \"0.0.0.0/0\". <br/>
Leaving a field empty is equivalent to closing all access. However, in multi-server installations, even if a field is empty, all necessary ports remain open between MailCleaner servers.
<h2>Web interface access  (port(s) 80:443, TCP): </h2>
<h2>Database access  (port(s) 3306:3307, TCP): </h2>
If you would like to connect to MailCleaner's database, add the machines of your external MySQL database clients.
<h2>SNMP access  (port(s) 161, UDP): </h2>
If you wish to implement SNMP, specify the IP address(es) of your SNMP client(s) here.
<h2>SSH access  (port(s) 22, TCP): </h2>
SSH is used primarily for support. 
<h2>Mail access  (port(s) 25, TCP): </h2>
Unless MailCleaner is behind a mail gateway, mail access must remain open to everyone in order to properly receive mail.
<h2>Web services  (port(s) 5132, TCP): </h2>
Web services are used primarily for synchronization between servers in a multi-server installation. This field has no relation to the user and admin web interfaces. If you need access to MailCleaner web services, add the address of your web service client.
";


/****************************
 *         Status
 ****************************/

### Added
$help['MONGLOBALHOSTID'] = 
"The status page gives you a bird's eye view of every MailCleaner server in your MailCleaner installation.
<h1>ID</h1>
The ID number of the MailCleaner server. The master server is #1. In a single server installation, you will only see #1.
";


### Added
$help['MONGLOBALHOST'] = 
"<h1>Host</h1>
The name or IP address of the MailCleaner server. This value cannot be changed.
";


### Added
$help['MONGLOBALPROCESSES'] = 
"<h1>Processes</h1>
This list of MailCleaner server processes gives you the current status of each process as well as the possibility to stop and start it. Generally, all processes should be <font color=\"green\">\"RUNNING\"</font>. If technical support, or common sense, advises you to do so, you can stop and restart processes using this interface.
";


### Added
$help['MONGLOBALSPOOLS'] = 
"<h1>Spools</h1>
The spools are the queues of messages that await processing by the main
MailCleaner stages. Every message passes through each spool in turn.<br/>

Messages in the <b>Incoming</b> spool have been received by the incoming
(pre-filtering) MTA process. They are then passed to the <b>Filtering</b> spool
to await spam and antivirus analysis. Clean messages and alerts are then passed
to the <b>Outgoing</b> spool, where the outgoing (post-filtering) MTA process
will deliver them in turn to their designated receiving mail server. <br/>

Clicking on the <img src=\"templates/default/images/eye.gif\" border=\"0\">
icon will produce a list of the messages presently in the spool. On this list,
you can choose to force (<img src=\"images/force.gif\">) any particular message
to the next stage, or click on the \"<b>force all</b>\" link at the top of the
page to force delivery of all messages in the spool.
";


### Added
$help['MONGLOBALLOAD'] = 
"<h1>Load average</h1>
The load average is an indication of the number of active processes--that is, the number of processes waiting for processor time. High load averages generally mean that the system is being used heavily. In general, load averages less than or equal to 3are satisfactory. Higher figures could indicate that the server is too heavily loaded.<br/>
Averages are given for the last five minutes, ten minutes, and fifteen minutes.
";


### Added
$help['MONGLOBALDISKUSAGE'] = 
"<h1>Disk usage</h1>
MailCleaner servers have two disk partitions: One for the system, and one for the data, including all spools, logs, and quarantines. Disk usage informs you of the percentage of space that has been used on each partition.
";


### Added
$help['MONGLOBALMEMORYUSAGE'] = 
"<h1>Memory usage</h1>
\"Total memory\" and \"Free memory\" refer to the physical RAM that is installed on the server. \"Total swap\" and \"Free swap\" refer to the swap memory, or virtual memory, that has been allocated for the server. If the free swap is consistently very small relative to the total swap, this could indicate that the server is too heavily loaded.
";


### Added
$help['MONGLOBALLASTPATCH'] = 
"<h1>Last patch</h1>
This is the name of the latest patch that was downloaded by, and applied to, the server. The name is based on the date of the patch, in the form YYYYMMDDpp, where pp is the patch ID of the given date.
";


### Added
$help['MONGLOBALTODAYSCOUNTS'] = 
"<h1>Today's counts</h1>
This is a quick summary of the number of spam, viruses, and dangerous content that have been filtered since midnight today, as well as a count of the total messages that have been received by the server.
";


/****************************
 *       Statistics
 ****************************/

### Added
$help['STATSTITLE'] = 
"<h1>Statistics</h1>
These graphs give you a visual summary of the performance of each or all of your MailCleaner servers. You can choose to view the statistics graphs of one server or all of them, for one particular type of statistic or all of them, and for a daily, weekly, monthly, or annual view.
";

$help['MESSAGES'] = 
"<h1>Message counts</h1>" .
"This graphic displays the count of the different type of messages detected by MailCleaner.<br> These can be viruses, dangerous contents, spams or clean messages.<br><br>" .
"The priority of types is : virus, dangerous content, spam and clean.<br> That is when a message is detected as both a virus and a spam, it is counted as a virus.".
"<br><br>This is a daily count, so it get reset avery day.";

$help['PMESSAGES'] = 
"<h1>Message type</h1>".
"This graphic displays the nature of the messages that were filtered by MailCleaner.<br>" .
"You will see the percentage of each detected type here: viruses, dangerous contents, spams and cleans.";

$help['SPOOLS'] =
"<h1>Spools counts</h1>".
"This graphics will display the average of the number of messages waiting on the different spools used by MailCleaner.<br>".
"There are three different spools which have different meaning here.<br><br>".
"<b>The incoming spool</b> is where the messages are first received by external hosts. There is two main reasons why messages can hang here: ".
"<br>- The first is if a host is opening too many connections to your MailCleaner in a short time.".
" This will cause MailCleaner to delay these messages in order to temporize the sender and avoid a Denial of service attack. ".
"<br>- The second reason is when you use your MailCleaner as an outgoing relay. As MailCleaner is then responsible for sending out mails, ".
" it will sometimes queue them if remote hosts are not responding or reply with a temporary error.".
"<br>The size of this spool may vary, but will not hit the filtered message throughput." .
"<br><br><b>The filtering spool</b> is where the messages are stored waiting to be analysed. The engine will take messages here every 5 seconds and will process them by batches of maximum 30. ".
"Messages will not be deleted here until they have been fully processed and successfully transferred to the outgoing stage. So it may sometimes seems that messages are not processed as the queue constantly grows, but it doesn't means they are not actually being processed. ".
"To check if MailCleaner is actually analysing messages, you'll have to check the engine logs.".
"The size of this spool may rapidly vary, and may be even quite large on busy system or when a large amount of messages have been delivered to the filter.".
"<br><br><b>The outgoing spool</b> is where messages are being stored whenever they could not be instantly delivered to the final destination server. This may happen when the mailbox server is down, or overloaded.<br>".
"Bounces messages could also be blocked or frozen here when not using callout for destination that doesn't exists with a sender that is also invalid. These messages will be dropped after a few days (4 by default) and stay here for information purpose.".
"<br>Generally, the size of this spool is quite small and do not vary that much. It is a good indication of something's going wrong with the destination mailbox server(s)";

$help['CPU'] =
"<h1>CPUs usage</h1>".
"This graphic will display the average usage of the processor(s). It is a sum percentage of all the processors, so if you have more than one CPU, the maximum value available will \"nb processors*100\".".
"<br>You'll see two important kinds of CPU usage: System and user. <br>The first is what is used by the systems kernel, for tasks like disk I/O's, memory paging, etc....".
"<br> The second is the use of the main MailCleaner processes, such as accepting, delivering, analysing messages.".
"<br><br> What is important here is to monitor the system usage (dark red) as it might indicate that your system is actually swapping memory or slowed down by bad disks accesses. It should normally not go beyond 30% on a busy system.".
"<br>Of course, if the user usage is filling up the rest, you may need to invest in one or more CPU.";

$help['LOAD'] =
"<h1>Load average</h1>".
"This graphic will display the load average of the system. <br>(Very) Basically, the load is the number of simultaneous tasks that are waiting in the kernel queue to be processed by the CPU.".
"<br><br>A load average between 5 and 10 is quite common on normally loaded systems. <br>More than 20 can be seen on heavily loaded system, or might indicate that the system is short in memory and is swapping memory (which is bad for performances).";

$help['MEMORY'] =
"<h1>Memory usage</h1>".
"This graphic will display the RAM usage of your system.".
"<br> Don't be afraid if you see all your memory filled either by the used, buffered or cached areas because Linux has a tendency to appropriate all what it can.".
"<br> The most important here is to check the red line that will indicate if your system is actually swapping, which is very bad for performances. This indicates that you should increase memory as soon as possible.".
"<br> More than CPU speed, the amount of RAM is critical in order for MailCleaner to have optimum work conditions.";

$help['NETWORK'] =
"<h1>Network usage</h1>".
"This graphic will display the average bandwidth used by the MailCleaner host.".
"<br><br>In a normal situation, both incoming and outgoing traffic should be quite identical and follow the same trends.";

$help['DISK'] = 
"<h1>Disk usage</h1>".
"This graphic will display the amount of hard drive storage used by your system.".
"<br><br>The base system partition (\"/\" or root) should not vary. But the data one (\"/var\") may slowly increase the first days of production or whenever your users base increase.".
"<br>If the data partition gets full, your system may be halted, so if this partition is getting too high, you should think of changing disks.";


/****************************
 *          Logs
 ****************************/

### Added
$help['VIEWLOGS'] = 
"<h1>Logs</h1>
This page allows you to drill down to any of the logs from any server for any recent date. The fields are self-explanatory.
";


/****************************
 *      Old stuff
 ****************************/


$help['VIRUSQUARANTINE'] = "
<h1>Virus quarantine: Withheld File Release</h1>
Occasionally, your users will find that a suspicious file attachment has been withheld by MailCleaner. In this situation, the user will forward a message to the administrator requesting that he release the file. Often, upon inspection by the administrator, the withheld file is found to pose no threat.<br/><br/>

In the message that the user forwards to the administrator, you will find an attachment called AttentionVirus.txt. Within this file, please copy the attachment identification string (in the format yyyymmdd/xxxxx-xxxxxx-xx) and paste it in the <b>Id string:</b> box. When you click on \"apply\", MailCleaner will automatically forward the original message, including the attachment in question, to the user.<br/><br/>
<b>Prudence is advised when forwarding executable files!</b>
";


$help['HOSTLISTTITLE'] = "
<h1>Host list</h1>
This is the list of MailCleaner servers in your installation.
The first column indicates the unique Host ID of the MailCleaner server in your installation. The server with a Host ID of 1 is the master server; any others are slaves.<br/><br/>
The second column is the fully qualified domain name of the server.
In this list, you can edit (<img src=\"images/pencil.gif\" border=\"0\">) or delete (<img src=\"images/erase.gif\">) hosts.
";


$help['HOSTSETTINGS'] = "
<h1>Host settings</h1>

<h2>Hostname: </h2>
<h2>Sql port: </h2>
<h2>Password: </h2>
<h2>SSH public key: </h2>
";


$help['MONPROCSTATUS'] = "
<h1>Host Monitoring: Processes</h1>
This section provides basic information about the services that are critical to the MailCleaner host's operation.<br/><br/>
";
### <h2>MTA incoming, MTA filtering, MTA outgoing: </h2>
### <h2>Antispam Engine: </h2>
### <h2>Web interface: </h2>
### <h2>Master database: </h2>
### <h2>Slave database: </h2>


$help['MONSPOOLSCOUNT'] = "
<h1>Host Monitoring: Spools</h1>
This section provides a quick view of the number of messages that the host is currently processing.<br/><br/>
The acceptable number of messages in each spool depends on how the MailCleaner is configured. For example, if your MailCleaner host is configured to be an outgoing SMTP relay, you may find a large number of messages in the incoming queue; in this case, this would not signal a problem.<br/>
In general, if your server is configured by default, all figures will be green when the load is normal. During brief periods when the server is under heavy load, you may find that the figures are yellow or red. If this persists, it may be necessary to intervene.
";
### <h2>Incoming: </h2>
### <h2>Filtering: </h2>
### <h2>Outgoing: </h2>


$help['MONDAILYSTATS'] = "
<h1>Host Monitoring: Statistics</h1>
These general statistics offer the administrator a brief view of the cumulative traffic since midnight.
";


$help['MONDAILYGRAPHS'] = "
<h1>Host Monitoring: Daily graphs</h1>
These graphs offer the administrator a brief view of the server's resources and mail traffic.
";
### <h2>CPU usage: </h2>
### <h2>Network usage: </h2>
### <h2>Messages/spams: </h2>
### <h2>Viruses: </h2>

?>
