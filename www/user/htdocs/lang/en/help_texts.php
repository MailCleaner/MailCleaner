<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
$htxt['INTRODUCTION'] = '
<h1>Welcome to a world where the e-mail you get is the e-mail you want.</h1>
<p>MailCleaner is an extremely powerful antivirus and anti-spam system.</p>
<p>Based on the latest generation of filtering technology, MailCleaner does not need to be installed on your computer. 
Instead, it acts before e-mail messages reach your mailbox, at the highest level of the network infrastructure of your company, organization or ISP. 
MailCleaner relies on sophisticated rules that are updated daily by the engineers of the <em>MailCleaner Analysis Center</em> in response to spammers\' 
ever-changing strategies and the appearance of new viruses. Thanks to MailCleaner\'s unblinking surveillance, you can be assured 24 hours a day 
that you have the best tool to prevent viral attacks, intrusion of dangerous files and undesirable e-mail messages.</p>
';

$htxt['FIRSTCONTACT'] = '
<h1>Take a couple of minutes to discover how MailCleaner works with your e-mail software.</h1>
<p>This chapter contains all the necessary information to help you master your new antivirus and anti-spam system in just a few minutes. 
The default configuration of the MailCleaner filter immediately provides you with maximum protection, so you can rely on MailCleaner from the very start.</p>
<p>MailCleaner requires only minimal attention on your part. It works autonomously to eradicate viruses, identify dangerous content and remove spam from your e-mail 24 hours a day. 
MailCleaner works transparently, and you will receive regular quarantine reports that will tell you everything about its activity.</p>
<h2>Quarantine reports </h2>
<p>As a MailCleaner user, you will be receiving a quarantine report for each e-mail address that is protected by the system. Quarantine reports may be sent to you daily, 
weekly, or monthly, depending on the configuration chosen by your e-mail administrator or your Internet Service Provider (ISP). A quarantine report lists all the messages 
received in a given period that are identified as spam. These messages are quarantined, meaning that they are retained in a special zone outside of your e-mail system.</p>
<p>During the first few weeks as a new MailCleaner user, you should examine your quarantine reports carefully to make sure that the system does not inadvertently block any 
legitimate e-mail message that should have been delivered to your mailbox. Such instances are very rare, but not impossible.</p>
<p>After a test period, you will have the option to disable the delivery of quarantine reports, depending on whether or not you are interested in reviewing 
the activity of the filtering system.</p>

<h2>What to do if a message is blocked inadvertently</h2>
<p>It is possible, although extremely rare, that a message that you want to receive is blocked by MailCleaner.  This may be caused by different factors, such as a non-standard 
format of the e-mail message or a compromised reputation of the mail server used to send the message. It does not mean that MailCleaner is malfunctioning, but simply that the 
system acts cautiously when encountering an e-mail message with unusual characteristics that cannot be correctly interpreted by a simple scan of the message contents.</p>
<p>If you encounter such a situation, there are two things you should do:</p>
<ul>
<li><em>Release the message</em> from the quarantine to allow it to reach your mailbox.</li>
<li>Notify the <em>MailCleaner Analysis Center</em> so that the engineers may render the filter more tolerant towards 
  the sender or format of the blocked message. This is referred to as a <em>filter adjustment</em> in the MailCleaner vocabulary.</li>
</ul> 
<p>If you are unsure about the nature of the message that has been blocked, you can view its contents before deciding whether it should be released 
  or not via the MailCleaner Management Center.</p>
<p class="note"><strong>Note:</strong> The <em>MailCleaner Analysis Center</em>, located on our premises, is composed of a team of highly specialized engineers whose role 
is to guarantee the highest possible quality of filtering in response to global spam traffic, emergence of viruses and filter adjustment requests emanating from users worldwide.</p>
<h3>Viewing the contents of a message</h3>
<ul>
 <li>To view a message, click on the date, the subject or the preview icon.</li> 
 <li>The contents of the message are displayed in a new window.</li>
</ul>
<h3>Releasing a quarantined message</h3>
<ul>
<li>Click on the message release icon next to the message of interest.</li>
<li>A copy of the blocked message is sent to your mailbox.</li>
</ul>

<h3>Filter adjustment request</h3>
<ul>
 <li>Click on the filter adjustment request icon next to the message of interest.</li>
 <li>Confirm your request;</li>
 <li>A filter adjustment request is sent to the Analysis Center along with a copy of the blocked message.</li>
</ul>
<p class="note"><strong>Note:</strong> If you use any of the preceding tools available in the quarantine report, your browser will open a new window or a confirmation dialog box.</p>
<h2>What to do if a spam has not been blocked</h2>
<p>If a spam slips undetected through the MailCleaner system, the differences between this spam and a legitimate e-mail message are likely very small. 
In such a situation, MailCleaner chooses to deliver the message to your mailbox. It is better to receive spam on an exceptional basis than to miss a potentially 
important legitimate message.</p>
<p>If you receive a spam message, you should request a filter adjustment to fine tune MailCleaner detection rules.</p>
<h3>Spam received by Microsoft Outlook</h3>
<p>A plug-in may be added to Microsoft Outlook for Windows to automatically notify MailCleaner that unfiltered spam has been received. 
This plug-in installs a button in the menu bar that displays the MailCleaner logo and the caption <em>Unwanted</em>.</p> 

<p>To notify the <em>MailCleaner Analysis Center</em> of received spam using the MailCleaner plug-in:</p>
<ul>
 <li>Select the received spam in the list of messages.</li>
 <li>Click on the Unwanted button in the Tools bar.</li>
 <li>A filter adjustment request is sent to the Analysis Center along with a copy of the unwanted message.</li>
 <li>You may then delete the spam.</li>
 <li>No confirmation will be sent to you, but your request will be taken into account in the continuous filter adjustment process.</li>
</ul>
<p class="note"><strong>Note:</strong> If the MailCleaner plug-in is missing in Outlook, ask your e-mail administrator to install it 
for you or consult the installation instructions in this manual.</p>
<h3>Spam received by another e-mail program</h3>
<p>All filter adjustment requests resulting from unwanted spam must be sent manually to a specific e-mail address at the <em>MailCleaner Analysis Center</em>.</p>
<p><strong>This address is defined by your e-mail administrator or ISP. It cannot be found in this manual.</strong> 
To obtain this address, click on the Help section of the Management Center and then on Filter Adjustment Request. 
Copy the e-mail address, as it will be needed in the subsequent steps.</p>
<p>To manually notify the <em>MailCleaner Analysis Center</em> of received spam:</p>
<ul>
 <li>Select the received spam in the list of messages.</li>
 <li>Resend the message using the resend function of your e-mail software.</li>
 <li>Type in or paste the e-mail address for filter adjustment requests obtained previously.</li>
 <li>A filter adjustment request is sent along with a copy of the unwanted message.</li>
 <li>You may then delete the spam.</li>
 <li>No confirmation will be sent to you, but your request will be taken into account in the continuous filter adjustment process.</li>
</ul>

<p class="important"><strong>Important:</strong> Do not forward the contents of the spam message using the copy and paste function. 
Doing so will prevent the transmission of the long header in the original message which is needed to properly analyze the spam. 
Regardless of your e-mail software or operating system (PC or Mac), you should only use the Resend function (or its equivalent) to send your filter adjustment request. </p>
<h2>Improving your knowledge of MailCleaner</h2>
<p>After mastering the basics, you will undoubtedly want to learn more about customizing MailCleaner to precisely fit your needs.</p>
';

$htxt['MANUAL_FULL_NAME'] = 'mailcleaner_user_manual.pdf';
$htxt['MANUAL_FIRSTCONTACT_NAME'] = 'mailcleaner_quick_guide.pdf';
$htxt['MANUAL_GENERICCONCEPT_NAME'] = 'mailcleaner_general_principles.pdf';
$htxt['MANUAL_GUI_NAME'] = 'mailcleaner_management_center.pdf';
$htxt['MANUAL_QUARANTINE_NAME'] = 'mailcleaner_quarantine.pdf';
$htxt['MANUAL_STATS_NAME'] = 'mailcleaner_statistics.pdf';
$htxt['MANUAL_CONFIGURATION_NAME'] = 'mailcleaner_configuration.pdf';
$htxt['MANUAL_ERRORS_NAME'] = 'mailcleaner_filter_inaccuracies.pdf';

$htxt['USERMANUAL'] = '
<h1>User manual &ndash; Version 1.0 &ndash; June 2008</h1>
<h2>Download the full manual</h2>
<p class="download"><a href="__MANUAL_FULL_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_FULL_NAME__" /></a> <a href="__MANUAL_FULL_LINK__">Download</a> (__MANUAL_FULL_SIZE__)</p>
<h2>View specific chapters</h2>

<h3>Quick guide</h3>
<p>Quickly master the basics.</p>
<p class="download"><a href="__MANUAL_FIRSTCONTACT_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_FIRSTCONTACT_NAME__" /></a> <a href="__MANUAL_FIRSTCONTACT_LINK__">Download chapter</a> (__MANUAL_FIRSTCONTACT_SIZE__)</p>

<h3>General principles</h3>
<p>Understand the principles and techniques used by MailCleaner.</p>
<p class="download"><a href="__MANUAL_GENERICCONCEPT_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_GENERICCONCEPT_NAME__" /></a> <a href="__MANUAL_GENERICCONCEPT_LINK__">Download chapter</a> (__MANUAL_GENERICCONCEPT_SIZE__)</p>

<h3>Management Center</h3>
<p>Master the Management Center you currently are browsing.</p>
<p class="download"><a href="__MANUAL_GUI_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_GUI_NAME__" /></a> <a href="__MANUAL_GUI_LINK__">Download chapter</a> (__MANUAL_GUI_SIZE__)</p>

<h3>Quarantine</h3>
<p>Learn how to efficiently manage your quarantine.</p>
<p class="download"><a href="__MANUAL_QUARANTINE_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_QUARANTINE_NAME__" /></a> <a href="__MANUAL_QUARANTINE_LINK__">Download chapter</a> (__MANUAL_QUARANTINE_SIZE__)</p>

<h3>Statistics</h3>
<p>Know the nature of your mail.</p>
<p class="download"><a href="__MANUAL_STATS_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_STATS_NAME__" /></a> <a href="__MANUAL_STATS_LINK__">Download chapter</a> (__MANUAL_STATS_SIZE__)</p>

<h3>Configuration</h3>
<p>Customize MailCleaner to fit your needs and habits.</p>
<p class="download"><a href="__MANUAL_CONFIGURATION_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_CONFIGURATION_NAME__" /></a> <a href="__MANUAL_CONFIGURATION_LINK__">Download chapter</a> (__MANUAL_CONFIGURATION_SIZE__)</p>

<h3>Filter inaccuracies</h3>
<p>Decide what to do when the filter makes a wrong decision.</p>
<p class="download"><a href="__MANUAL_ERRORS_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_ERRORS_NAME__" /></a> <a href="__MANUAL_ERRORS_LINK__">Download chapter</a> (__MANUAL_ERRORS_SIZE__)</p>
';

$htxt['FAQ'] = '

<h1>Simple answers to common questions.</h1>
<h2>Management Center</h2>
<h3>Where can I get my user name and password?</h3>
<p>For any given address filtered by MailCleaner, your user name and password are the same as those used for your e-mail account in your standard e-mail software.</p>
<h2>Spam and quarantine</h2>
<h3>What is a quarantine?</h3>
<p>A quarantine is an isolated zone outside of your mailbox which retains messages identified as spam.</p>
<h4>Filter adjustment</h4>
<h3>What is a filter adjustment?</h3>
<p>A filter adjustment results from a request that you decide to make when you encounter a legitimate mail that is wrongfully quarantined, 
or a spam message that is wrongfully delivered to your mailbox. In the first case, MailCleaner will relax a rule to be more tolerant towards 
a specific sender or format. In the second case, MailCleaner becomes more strict.</p>
<h3>What happens when I request a filter adjustment?</h3>
<p>A copy of the message is sent to the <em>MailCleaner Analysis Center</em>. The message is examined by MailCleaner engineers and the filter may be corrected.</p> 
<h4>Quarantined messages</h4>
<h3>A legitimate message has been mistakenly quarantined. What should I do?</h3>
<p>Release the message to allow its delivery to your mailbox and request a filter adjustment from within the quarantine.</p>
<h3>Why did MailCleaner quarantine a message that I was supposed to receive?</h3>
<p>Such a situation may occur if the message was relayed by a mail server that may temporarily have a compromised reputation, caused for example by spammers 
that use it as a spam relay. Alternatively, a specific, unusual format of the message may have triggered an anti-spam rule. 
It does not mean that MailCleaner has malfunctioned, but simply that the system acts cautiously when encountering unusual characteristics of an e-mail message 
that cannot be correctly interpreted by a simple scan of the message contents.</p>
<h3>How do I release a message?</h3>
<p>Click on the arrow icon located on the same line as the message to release it. This can be done either in the quarantine report or within the Management Center.</p>
<h3>I have released a message, but it is still displayed in the quarantine. Is this normal?</h3>
<p>A released message is kept in the quarantine in case you need to release it again in the future. It is displayed in italics to indicate that it has been released.</p>
<h3>What do I do to stop receiving quarantine reports?</h3>
<p>You may change the frequency at which quarantine reports are sent, or disable this option, in the Configuration section of the Management Center. 
If you choose to disable reporting, you will need to visit your Management Center to consult your quarantine.</p>
<h4>Unblocked spam</h4>
<h3>A spam has not been filtered. What should I do?</h3>
<p>Use your e-mail software to request a Filter Adjustment which will reinforce the filter rules.</p>
<h3>Why did MailCleaner let a spam reach my mailbox?</h3>
<p>Some spam messages manage to slip through the filter because none of the mathematical analyses carried out at the time were able to differentiate it from legitimate e-mail. 
It is therefore very important that you report this error to the <em>MailCleaner Analysis Center</em>, which will then reinforce the relevant filter rules. 
In borderline situations, MailCleaner chooses to deliver the message to your mailbox, since it is better to receive spam on an exceptional basis 
than to miss a potentially important message.</p>
<h2>Viruses and dangerous messages</h2>
<h3>How does MailCleaner handle viruses?</h3>
<p>Viruses are eliminated without any notification.</p>
<h3>What do you mean by dangerous content?</h3>
<p>Dangerous content is the type of content that your e-mail administrator must filter as a preventive measure. Examples include attachments with executable scripts (.exe) 
or links to suspicious web sites. MailCleaner removes dangerous content and delivers the remainder of the message to your mailbox along with a note explaining how to ask 
your administrator to send you the complete message.</p>
<h3>How do I know that a message included dangerous content?</h3>
<p>The subject of such a message includes a keyword&mdash;usually "{DANGEROUS CONTENT}"&mdash;as well as an attached document explaining how to release it.</p>
<h3>How do I obtain this dangerous content from my e-mail administrator?</h3>
<p>Follow the instructions in the message attachment. To receive the retained content, you must provide the ID of the message that was blocked. 
If your administrator believes that the original attachment poses a genuine threat, he or she may refuse to forward it to you.</p>
';

$htxt['GLOSSARY'] = '
<h1>Definitions of the most commonly used words.</h1>
<h3>Authentication</h3>
<p>A process that verifies that the true identity of a person matches the identity claimed by this person. Successful authentication in MailCleaner is necessary for a user to access his or her quarantine.</p>
<h3>Analysis Center</h3>
<p>A team of specialized engineers working at the MailCleaner headquarters whose role is to guarantee at all times the best possible quality of filtering in response to global spam traffic, virus activity and adjustment requests from MailCleaner users worldwide.</p>
<h3>Dangerous content</h3>
<p>Suspicious content found in a message that has been filtered out as a preventive measure by your ISP or e-mail administrator.</p>
<h3>Domains under protection</h3> 
<p>All of the Internet domains examined by the same instance of MailCleaner (examples: company.com, enterprise.com).</p>
<h3>False negative</h3>
<p>Spam that has not been identified as such by the MailCleaner filter. All false negatives should be reported to the Analysis Center to take corrective action.</p>
<h3>False positive</h3>
<p>A legitimate message considered as spam by MailCleaner. All false positives should be reported via a filter adjustment request.</p>
<h3>Fastnet SA</h3>
<p>The nice folks who are the authors of MailCleaner. They are just the opposite of spammers. Fastnet headquarters are in St-Sulpice, Switzerland.</p>
<h3>Filter Adjustment Request</h3>
<p>A voluntary action on the part of a user in case a legitimate message is blocked or spam is delivered to your mailbox. In the first case, filter adjustment will render MailCleaner more tolerant towards a particular sender or format. In the second case, MailCleaner will reinforce its filter. Filter adjustment requests are handled by the <em>MailCleaner Analysis Center</em>.</p>
<h3>Filter rule</h3>
<p>A mathematical and statistical analysis of specific characteristics of a message in order to determine whether it should be considered as spam.</p>
<h3>ISP</h3>
<p>Internet Service Provider, a company providing access to the Internet and offering e-mail services.</p>
<h3>Management Center</h3>
<p>A private Internet zone where you can inspect incoming quarantined messages and configure different MailCleaner options.</p>
<h3>Plug-in</h3>
<p>An extension that can be added to a preexisting software application. The MailCleaner plug-in for Microsoft Outlook simplifies the process of notifying of false negatives.</p>
<h3>Quarantine</h3>
<p>An isolated area outside of your mailbox for storing messages identified as spam.</p>
<h3>Quarantine report</h3>
<p>An automatically generated periodic report which lists all blocked messages and which provides tools to inspect their content and to release quarantined messages if necessary.</p>
<h3>RBL</h3>
<p>Real-time Blackhole List. RBLs maintain lists of servers in real time known to send spam. Using RBLs is very simple in principle: If an incoming message is sent by an RBL-listed server it is considered a priori as spam. The difficulty in using RBLs is the need to continuously verify that they are reliable.</p>
<h3>Releasing a message</h3>
<p>User action that releases a quarantined message so that it can reach the recipient\'s mailbox.</p>
<h3>Retention period</h3>
<p>Period of time during which a quarantined message may be consulted. When the retention period expires, the message is automatically deleted.</p>
<h3>Score</h3>
<p>A quarantine indicator that offers a weighted, numerical estimation that a message is spam or not.</p>
<h3>SMTP</h3>
<p>Simple Mail Transfer Protocol. A protocol used to send electronic mail.</p>
<h3>Spam</h3>
<p>An electronic message that is unwanted by the recipient, but without any dangerous content. Also called "junk mail".</p>
<h3>Spoofing</h3>
<p>E-mail address spoofing is a spammer\'s strategy where the sender of a message is forged in an attempt to disguise spam as a legitimate message from another sender.</p>
<h3>Switzerland</h3>
<p>Country of origin of MailCleaner, where spam is eradicated with watchmaker\'s quality and precision.</p>
<h3>Virus</h3>
<p>An intrusive software entity, sometimes included as an attachment to a message, which may alter the integrity of your computer.</p>
<h3>Warn list</h3>
<p>A list of e-mail addresses that are trustworthy and should not generate spam. You will receive a warning if a message sent from a warn list address is blocked by MailCleaner.</p>
<h3>White list</h3>
<p>A list of e-mail addresses that are fully trustworthy. Messages sent from white list addresses will never be blocked by MailCleaner.</p>
<h3>Wow.</h3> 
<p>What we hope you will say as a MailCleaner user.</p>
';

$htxt['PLUGIN'] = '<h1>Not supported anymore.</h1>';
/*$htxt['PLUGIN'] = '
*<h1>Manage non-filtered spams from within your mailbox.</h1>
*<h6>A plug-in may be added to Microsoft Outlook for Windows to automatically notify MailCleaner that spam has been received.
*This plug-in installs a button in the menu bar that displays the MailCleaner logo and the caption "Unwanted".</h6>
*<p>Every adjustment request will be taken into account in the continuous filter adjustment process.</p>
*<p class="note"><strong>Note:</strong> Your system administrator may have blocked the installation of Outlook plug-ins on your computer and should be contacted in such a case.</p>
*<h2>Download MailCleaner plug-in for Outlook</h2>
*<p>For Microsoft Outlook 2003: <a href="__PLUGIN_OU2003_LINK__">Download</a>
* (Version 1.0.3 &ndash; __PLUGIN_OU2003_SIZE__) </p>
*<p>For Microsoft Outlook 2007: <a href="__PLUGIN_OU2007_LINK__">Download</a>
* (Version 1.0.3 &ndash; __PLUGIN_OU2007_SIZE__)</p>
*<h2>Installing the MailCleaner plug-in for Outlook</h2>
*<p>To install the MailCleaner plug-in for Outlook for Windows:</p>
*<ul>
*<li>Download the latest version from the link above.</li>
*<li>Close the Outlook application if it has been started.</li>
*<li>Double-click on the installer icon.</li>
*<li>Follow the instructions.</li>
*<li>A message confirms the successful installation of the plug-in.</li>
*<li>Restart Outlook.</li>
*</ul>
*<p>A new button should be present in your Outlook toolbar.</p>
*<h2>Managing false negatives with Microsoft Outlook for Windows</h2>
*<ul>
*<li>Select the received spam in the list of messages.</li>
*<li>Click on the "Unwanted" button in the toolbar.</li>
*<li>A filter adjustment request is sent to the Analysis Center along with a copy of the unwanted message.</li>
*<li>You may then delete the spam.</li>
*</ul>
*<p>No confirmation will be sent to you, but your request will be taken into account in the continuous filter adjustment process.</p>
*<h2>See also in the user manual</h2>
*<h3>Filter inaccuracies </h3>
*<p>Decide what to do when the filter makes a wrong decision.</p>
*<p><a href="__MANUAL_ERRORS_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_ERRORS_NAME__" /></a> <a href="__MANUAL_ERRORS_LINK__">Download chapter</a> (__MANUAL_ERRORS_SIZE__)</p>
';*/

$htxt['ANALYSE'] = '
<h1>Filter adjustment request</h1>
<h6>If you do not use Microsoft Outlook with the __LINKHELP_plugin__MailCleaner extension__LINK__, all filter adjustment requests resulting from unwanted spam must 
be sent manually to a specific e-mail address at the <em>MailCleaner Analysis Center</em>.</h6>
<p>The spam will be analyzed and taken into account in the continuous filter adjustment process.</p>
<h2>Address to report spam</h2>
<p>The generic e-mail address to report spam is:</p><p>__SPAM_EMAIL__</p>
<h2>How to report a spam (false negative)</h2>
<p>To report a spam, you must transfer the message to the e-mail address above by using the "resend" function&mdash;or its equivalent&mdash;of your e-mail software.</p>
<p>Do not forward the contents of the spam message using the copy and paste function. 
Doing so will prevent the transmission of the long header in the original message which is needed to properly analyze the spam.</p>
<h3>Managing false negatives with Netscape, Mozilla or Thunderbird</h3>
<ul>
 <li>Select the received spam in the list of messages.</li>
 <li>Select <em>Message</em>, <em>Forward as Attachment</em> in the menu.</li>
 <li>Enter in the recipient field the e-mail address for <em>Filter Adjustment Requests</em> obtained previously.</li>
 <li>A <em>filter adjustment request</em> is sent along with a copy of the unwanted message.</li>
 <li>You may then delete the spam.</li>
 <li>No confirmation will be sent to you, but your request will be taken into account in the continuous filter adjustment process.</li>
</ul>
<h3>Managing false negatives with Microsoft Entourage (Apple computers)</h3>
<ul>
 <li>Select the received spam in the list of messages.</li>
 <li>Select <em>Message</em>, <em>Resend</em> in the menu.</li>
 <li>Enter in the recipient field the e-mail address for <em>Filter Adjustment Requests</em> obtained previously.</li>
 <li>A <em>filter adjustment request</em> is sent along with a copy of the unwanted message.</li>
 <li>You may then delete the spam.</li>
 <li>No confirmation will be sent to you, but your request will be taken into account in the continuous filter adjustment process.</li>
</ul>
<h3>Managing false negatives with Mail (Apple computers)</h3>
<ul>
 <li>Select the received spam in the list of messages.</li>
 <li>Select <em>Message</em>, <em>Forward as attachment</em> in the menu.</li>
 <li>Enter in the recipient field the e-mail address for <em>Filter Adjustment Requests</em> obtained previously.</li>
 <li>A <em>filter adjustment request</em> is sent along with a copy of the unwanted message.</li>
 <li>You may then delete the spam.</li>
 <li>No confirmation will be sent to you, but your request will be taken into account in the continuous filter adjustment process.</li>
</ul>
<h2>See also in the user manual</h2>
<h3>Filter inaccuracies </h3>
<p>Decide what to do when the filter makes a wrong decision.</p>
<p><a href="__MANUAL_ERRORS_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_ERRORS_NAME__" /></a> <a href="__MANUAL_ERRORS_LINK__">Download chapter</a> (__MANUAL_ERRORS_SIZE__)</p>
';

$htxt['SUPPORT'] = '
<h1>Support and help</h1>
<h6>Our support and commercial services are available during working hours, from Monday to Friday.</h6>
<h2>In case of problems</h2>
<p>__SUPPORT_EMAIL__</p>
<p>Before contacting the support service, please make sure your problem is not already handled in the __LINKHELP_usermanual__user manual__LINK__ 
or in the __LINKHELP_faq__frequently asked questions__LINK__.</p>
<h2>For commercial questions</h2>
<p>__SALES_EMAIL__</p>
';
?>
