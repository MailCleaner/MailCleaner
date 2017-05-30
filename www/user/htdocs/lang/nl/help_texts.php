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
<h1>Gebruikershandleiding &ndash; Versie 1.0 &ndash; Juni 2008</h1>
<h2>Download de volledige handleiding</h2>
<p class="download"><a href="__MANUAL_FULL_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_FULL_NAME__" /></a> <a href="__MANUAL_FULL_LINK__">Download</a> (__MANUAL_FULL_SIZE__)</p>
<h2>Bekijk specifieke hoofdstukken</h2>

<h3>Snelle Gids</h3>
<p>De basis snel beheersen.</p>
<p class="download"><a href="__MANUAL_FIRSTCONTACT_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_FIRSTCONTACT_NAME__" /></a> <a href="__MANUAL_FIRSTCONTACT_LINK__">Download Hoofdstuk</a> (__MANUAL_FIRSTCONTACT_SIZE__)</p>

<h3>Algemene principes</h3>
<p>Begrijp de principes en technieken gebruikt de MailCleaner.</p>
<p class="download"><a href="__MANUAL_GENERICCONCEPT_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_GENERICCONCEPT_NAME__" /></a> <a href="__MANUAL_GENERICCONCEPT_LINK__">Download Hoofdstuk</a> (__MANUAL_GENERICCONCEPT_SIZE__)</p>

<h3>Beheer Centrum</h3>
<p>Beheers het Beheer Centrum.</p>
<p class="download"><a href="__MANUAL_GUI_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_GUI_NAME__" /></a> <a href="__MANUAL_GUI_LINK__">Download Hoofdstuk</a> (__MANUAL_GUI_SIZE__)</p>

<h3>Quarantine</h3>
<p>Leer om je quarantaine efficient te beheren.</p>
<p class="download"><a href="__MANUAL_QUARANTINE_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_QUARANTINE_NAME__" /></a> <a href="__MANUAL_QUARANTINE_LINK__">Download Hoofdstuk</a> (__MANUAL_QUARANTINE_SIZE__)</p>

<h3>Statistieken</h3>
<p>Ken de aard van je mail.</p>
<p class="download"><a href="__MANUAL_STATS_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_STATS_NAME__" /></a> <a href="__MANUAL_STATS_LINK__">Download Hoofdstuk</a> (__MANUAL_STATS_SIZE__)</p>

<h3>Configuratie</h3>
<p>Pas MailCleaner aan zodat het past voor je noden en gewoonten.</p>
<p class="download"><a href="__MANUAL_CONFIGURATION_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_CONFIGURATION_NAME__" /></a> <a href="__MANUAL_CONFIGURATION_LINK__">Download Hoofdstuk</a> (__MANUAL_CONFIGURATION_SIZE__)</p>

<h3>Filter onnauwkeurigheden</h3>
<p>Bepaal wat te doen wanneer een filter een foute beslissing maakt.</p>
<p class="download"><a href="__MANUAL_ERRORS_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_ERRORS_NAME__" /></a> <a href="__MANUAL_ERRORS_LINK__">Download Hoofdstuk</a> (__MANUAL_ERRORS_SIZE__)</p>
';

$htxt['FAQ'] = '

<h1>Eenvoudige antwoorden op veel voorkomende vragen.</h1>
<h2>Beheer Centrum</h2>
<h3>Waar krijg ik mijn gebruikersnaam en paswoord?</h3>
<p>Voor elk, door MailCleaner, gefilterd adres, zijn je gebruikersnaam en paswoord dezelfde als deze die gebruikt worden voor je e-mail account in je standaard e-mail programma.</p>
<h2>Spam en quarantine</h2>
<h3>Wat is een quarantine?</h3>
<p>Een quarantine is een geïsoleerde zone, buiten je mailbox, die de berichten, geïdentificeerd als spam, apart houdt.</p>
<h4>Filter aanpassing</h4>
<h3>Wat is een filter aanpassing?</h3>
<p>Een filter aanpassing valt voor op een verzoek dat je maakt wanneer het gebeurt wanneer een geldige mail verkeerdelijk in quarantaine geplaatst wordt, 
of als een spam bericht verkeerdelijk in je mailbox geleverd wordt. In het eerste geval zal MailCleaner een regel versoepelen om toleranter te zijn naar een bepaalde zender of 
 formaat. In het tweede geval wordt MailCleaner strikter.</p>
<h3>Wat gebeurt er wanneer ik een filter aanpassing vraag?</h3>
<p>Een kopie van het bericht werd naar het <em>MailCleaner Analysis Center</em> verzonden. Dat bericht wordt onderzocht door MailCleaner ingenieurs en de filter kan worden aangepast.</p> 
<h4>Berichten in quarantine</h4>
<h3>Een geldige mail werd verkeerdelijk in qurantaine geplaatst. Mat moet ik doen?</h3>
<p>Laat het bericht los om het toch naar je mailbox te laten brengen en vraag een filter, voor in de quarantine aanpassing, aan.</p>
<h3>Waarom plaatste MailCleaner een bericht in de quarantine, terwijl ik deze had moeten ontvangen?</h3>
<p>Zo een situtatie kan gebeuren als het bericht werd doorgegeven door een mail server, die tijdelijk een slechte reputatie had, veroorzaakt door bijvoorbeeld spammers die het gebruiken als een
spam relay. Of een specifiek, ongebruikelijk format van het bericht, kan een anti-spam regel geactiveerd hebben. 
Het betekent niet dat MailCleaner defect is, maar gewoonweg dat het systeem voorzichtig reageert wanneer het ongebruikelijke eigenschappen van een e-mail bericht tegenkomt en deze
niet correct geïnterpreteerd kunnen worden door een eenvoudige scan van de bericht inhoud.</p>
<h3>Hoe laat ik een bericht los?</h3>
<p>Klik op de pijl, op de zelfde lijn als het bericht, om het los te laten. Dit kan zowel in het quarantaine rapport als binnen het Management Center.</p>
<h3>Ik heb een bericht losgelaten, maat wordt nog altijd getoond in de quarantine. Is dat normaal?</h3>
<p>Een losgelaten bericht wordt in quarantine gehouden voor in het geval je het weer los moet lanten in de toekomst. Het wordt schuin gedrukt weergegeven om aan te geven dat het werd losgelaten.</p>
<h3>Wat doe ik om geen quarantaine rapporten meer te krijgen?</h3>
<p>Je kan de frequentie aanpassen voor met welke frequentie quarantaine rapporten verstuurd moeten worden, of je kan deze optie ook uitschakelen in het Configuratie deel van het Management Center. 
Als je kiest om de rapportage uit te schakelen, moet je het Management Center bezoeken om je quarantine te consulteren.</p>
<h4>Ongeblokkeerde spam</h4>
<h3>Een spam werd niet gefilterd. Wat doe ik?</h3>
<p>Gebruik je e-mail programma om een Filter Aanpassing, die de filter regels zal versterken, aan te vragen.</p>
<h3>Waarom liet MailCleaner spam in mijn mailbox komen?</h3>
<p>Sommige spam berichten spelen het klaar om door de filter te glippen omdat geen enkele mathematische analyses, die op dat moment van kracht waren, in staat waren om deze de differentiëren van een geldige e-mail. 
Daarom is het zeer belangrijk dat je deze fout meldt aan het <em>MailCleaner Analysis Center</em>, die dan de gepaste filter regels zullen versterken. 
In grensgevallen kiest MailCleaner om het bericht in je mailbox af te leveren, omdat het beter is om spam te ontvangen in uitzonderlijke gevallen, dan een mogelijk belangrijk bericht te missen.</p>
<h2>Virussen en gevaarlijke berichten</h2>
<h3>Hoe gaat MailCleaner om met virussen?</h3>
<p>Virussen worden zonder pardon verwijderd.</p>
<h3>Wat bedoel je met gevaarlijke inhoud?</h3>
<p>Gevaarlijke inhoud is het type van inhoud dat je e-mail administrator moet filteren als een preventieve maatregel. Voorbeelden zijn bijlagen met uitvoerbare bestanden (.exe) 
of liken naar verdachte websites. MailCleaner verwijdert gevaarlijke inhoud en levert de rest van het bericht naar je mailbox, samen met een uitleg hoe je de administrators
kan vragen om het volledige bericht toch te kunnen ontvangen.</p>
<h3>Hoe weet ik dat een bericht gevaarlijke inhoud bevat?</h3>
<p>Het onderwerp van zo een bericht bevat een sleutelwoord&mdash;usually "{DANGEROUS CONTENT}"&mdash; en ook een bijlage met een uitleg over hoe het te moeten loslaten.</p>
<h3>Hoe krijg ik deze gevaarlijke inhoud van mijn my e-mail administrator?</h3>
<p>Volg de instructies in het bericht. Om de weerhouden inhoud te verkrijgen, heb je de ID van het geblokkeerde bericht nodig. 
Als je administrator gelooft dat de oorspronkelijke bijlage een reële bedreiging vormt, kan hij/zij weigeren om het naar jou door te sturen.</p>
';

$htxt['GLOSSARY'] = '
<h1>Definities van de meeste gebruikte woorden.</h1>
<h3>Authenticatie</h3>
<p>Een proces dat de echte identiteit van een persoon vergelijkt met de identiteit die deze persoon beweert te hebben. Succesvolle authenticatie in MailCleaner is noodzakelijk voor een gebruiker voor zijn/haar quarantaine toegang.</p>
<h3>Analyse Center</h3>
<p>Een team van gespecialiseerde ingenieurs, die op het MailCleaner hoofdkwartier werken, garanderen dat de hoogste kwaliteit van filteren behaald wordt, tegen het wereldwijde spam verkeer, virus activiteit en door het aanpassen van aanvragen door Mailcleaner gebruikers wereldwijd.</p>
<h3>Gevaarlijke inhoud</h3>
<p>In een bericht gevonden verdachte inhoud wordt gefilterd als preventieve maatregel door je ISP of e-mail administrator.</p>
<h3>Domeinen in bescherming</h3> 
<p>Alle Internet-domeinen behandeld door dezelfde instantie van MailCleaner (voorbeelden: company.com, enterprise.com).</p>
<h3>Vals negatief</h3>
<p>Spam dat niet als dusdaning geïdentificeerd werd door de MailCleaner filter. Alle valse negatieven moeten gemeld worden aan het Analysis Center om de gepaste actie te ondernemen.</p>
<h3>Vals postief</h3>
<p>Een geldig bericht beschouwd als spam door Malcleaner. Alle valse positieven moeten gemeld worden door middel van een filter aanpassingsvraag.</p>
<h3>Fastnet SA</h3>
<p>De makers van MailCleaner. Zij zijn het tegenovergestelde van spammers. Fastnet hoofdkwartier bevindt zich in St-Sulpice, Zwitserland.</p>
<h3>Filter Aanpassing Aanvraag</h3>
<p>Een vrijwillge actie van de gebruiker wanneer een geldig bericht geblokkeerd wordt of wanneer spam wordt afgeleverd in je mailbox. In het eerste geval zal een filter aanpassing ervoor zorgen dat het toleranter is naar een bepaalde verzender of formaat. In het tweede geval zal Mailcleaner de filter verbeteren. Filter aanpassing Aanvragen worden behandeld door het <em>MailCleaner Analysis Center</em>.</p>
<h3>Filter regel</h3>
<p>Een wiskundige en statistische analyse van specifieke tekens van een bericht om te bepalen of het moet gezien worden als spam.</p>
<h3>ISP</h3>
<p>Internet Service Provider, een bedrijf dat toegang geeft tot Internet en e-mail diensten aanbiedt.</p>
<h3>Management Center</h3>
<p>Een privé Internet zone waar je binnenkomende, in quarantaine geplaatste, berichten kan onderzoeken en verzschillende MailCleaner opties kan configureren.</p>
<h3>Plug-in</h3>
<p>Een extensie die kan toegeveogd worden aan een reeds bestaande software toepassing. De MailCleaner plug-in voor Microsoft Outlook vereenvoudigt het proces van valse negatieve te melden.</p>
<h3>Quarantine</h3>
<p>Een isolatie plaats, buiten je mailbox, om als spam geïdentificeerde berichten te bawaren.</p>
<h3>Quarantine rapport</h3>
<p>Een automatisch gegenereerd periodiek rapport dat een sopsomming geeft van alle geblokkeerde berichten en die hulpmiddelen aanbiedt om de inhoud ervan te bekijken en indien nodig berichten uit de quarantaine te verwijderen en ze toch naar de inbox te laten sturen.</p>
<h3>RBL</h3>
<p>Realtime Blackhole List. RBLs houden lijsten van servers in real time bij, die bekend zijn voor het sturen van spam. Het gebruik van RBLs is qua principe zeer eenvoudig: Als een binnenkomende mail verzonden in door een, door RBL op de lijst gezette, server, wordt deze a priori beschouwd als spam. De moeilijkheid bij het gebruik van RBLs is de nood om het continu na te gaan of deze nog accuraat zijn.</p>
<h3>Een bericht loslaten</h3>
<p>Een actie van de gebruiker die een bericht in quarantaine loslaat zodat deze toch in de mailbox van de ontvanger terecht komt.</p>
<h3>Bewaringstermijn</h3>
<p>Tijdsperiode waarin een bericht in quarantaine kan geconsulteerd worden. Wanneer deze periode gedaan is, wordt het bericht automatisch verwijderd.</p>
<h3>Score</h3>
<p>Een quarantine indicator die een bepaalde numerieke schatting geeft of een bericht al dan niet spam is.</p>
<h3>SMTP</h3>
<p>Simple Mail Transfer Protocol. Een protocol dat gebruikt wordt om electronische mail te versturen.</p>
<h3>Spam</h3>
<p>Een electronsich bericht dat ongewenst is voor de ontvanger, maar zonder enige gevaarlijke inhoud. Ook wel "Junk mail" genoemd.</p>
<h3>Spoofing</h3>
<p>E-mail adres spoofing is a spammer\'s strategie waar de zender van een bericht wordt vervalst in een poging om spam te vermommen als een legitiem bericht van een andere afzender.</p>
<h3>Zwitserland</h3>
<p>Land van oorsprong van MailCleaner, waar spam uitgeroeid word met de precisie en kwaliteit van een horlogemaker.</p>
<h3>Virus</h3>
<p>Een ongewenste software entiteit, soms als bijlage, dat de integriteit van je pc kan veranderen.</p>
<h3>Waarschuw lijst</h3>
<p>Een lijst met e-mail adressen die betrouwbaar zijn en normaal gezien geen spam zouden mogen genereren. Je zal een waarschuwing krijgen van Mailcleaner wanneer een bericht, verzonden van een waarschuw lijst adres, geblokkeerd wordt.</p>
<h3>Witte lijst</h3>
<p>Een lijst van e-mail adressen die volledig betrouwbaar zijn. Berichten verzonden van witte lijst adressen zullen nooit geblokkeerd worden door Mailcleaner.</p>
<h3>Wauw.</h3> 
<p>Wat we hopen dat jullie zullen zeggen als een MailCleaner gebruiker.</p>
';

$htxt['PLUGIN'] = '
<h1>Beheer niet gefilterde spam vanuit je mailbox.</h1>
<h6>Een plug-in kan toegevoegd worden aan Microsoft Outlook voor Windows om automatisch MailCleaner te melden dat spam werd ontvangen.
Deze plug-in installeert een knop in de menu balk die het MailCleaner logo weergeeft en de titel "Ongewenst".</h6>
<p>Elke aanpassing aanvraag wordt bekeken tijdens het continu lopende filter aanpassing proces.</p>
<p class="note"><strong>Note:</strong> Je systeembeheerder kan de installatie van Outlook plug-ins op je computer geblokkeerd hebben en zou moeten gecontacteerd worden in dat geval.</p>
<h2>Download MailCleaner plug-in voor Outlook</h2>
<p>Voor Microsoft Outlook 2003: <a href="__PLUGIN_OU2003_LINK__">Download</a>
 (Version 1.0.3 &ndash; __PLUGIN_OU2003_SIZE__) </p>
<p>Voor Microsoft Outlook 2007: <a href="__PLUGIN_OU2007_LINK__">Download</a>
 (Version 1.0.3 &ndash; __PLUGIN_OU2007_SIZE__)</p>
<h2>De MailCleaner plug-in voor Outlook installeren</h2>
<p>Om de MailCleaner plug-in voor Outlook voor Windows te installeren:</p>
<ul>
<li>Download de laatste versie van de bovenste link.</li>
<li>Sluit de Outlook toepassing als deze reeds gestart.</li>
<li>Dubbelklik op het installeer icoon.</li>
<li>Volg de instructies.</li>
<li>Een bericht bevestigt de succesvolle installatie van de plug-in.</li>
<li>Herstart Outlook.</li>
</ul>
<p>Een nieuwe knop zou zichtbaar moeten zijn in je Outlook taakbalk.</p>
<h2>Beheren van valse negatieven met Microsoft Outlook voor Windows</h2>
<ul>
<li>Selecteer de ontvangen spam in de lijst met berichten.</li>
<li>Klik op de "Ongewenst" knop in de taakbalk.</li>
<li>Een filter aanpassing aanvraag werd verstuurd naar het Analyse Centrum, samen met een kopie van het ongewenste bericht.</li>
<li>Daarna mag je de spam verwijderen.</li>
</ul>
<p>Er wordt geen bevestiging gestuurd naar jou, maar je aanvraag zal behandeld worden tijdens het continu lopende filter aanpassingsproces.</p>
<h2>Bekijk het ook in de gebruikershandleiding</h2>
<h3>Filter onnauwkeurigheden </h3>
<p>Beslis wat te doen wanneer de filter een foute beslissing maakt.</p>
<p><a href="__MANUAL_ERRORS_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_ERRORS_NAME__" /></a> <a href="__MANUAL_ERRORS_LINK__">Download hoofdstuk</a> (__MANUAL_ERRORS_SIZE__)</p>
';

$htxt['ANALYSE'] = '
<h1>Filter aanpassing aanvraag</h1>
<h6>Als je Outlook met de __LINKHELP_plugin__MailCleaner extension__LINK__ niet gebruikt, zullen alle filter aanpassing aanvragen, die komen vanwege ongewenste spam, manueel verzonden worden naar een specifiek e-mail adres naar het <em>MailCleaner Analysis Center</em>.</h6>
<p>De spam zal geanalyseerd worden en verder behandeld worden in het continu doorlopende filter aanpassingsproces.</p>
<h2>Adres om spam te melden</h2>
<p>Het algemene e-mail adres om spam te melden is:</p><p>__SPAM_EMAIL__</p>
<h2>Hoe spam melden(valse negatieve)</h2>
<p>Om spam te melden, moet je het bericht versturen naar het bovenstaanden e-mail adres door de "terugzenden" functie&mdash;of zijn equivalente&mdash;van je e-amil software.</p>
<p>Stuur de inhoud van het spam bericht niet door door middel van de kopieer/plak functie. 
Dit voorkomt de transmissie van de lange hoofding in het originele bericht en deze is nodig om de spam correct te analyseren.</p>
<h3>Beheer valse negatieven met Netscape, Mozilla of Thunderbird</h3>
<ul>
 <li>Selecteer de ontvangen spam in de lijst met berichten.</li>
 <li>Selecteer <em>Bericht</em>, <em>Stuur door als Bijlage</em> in het menu.</li>
 <li>Vul, in het ontvanger veld, het e-mail adres in voor <em>Filter Aanpassing Aanvragen</em>.</li>
 <li>Een <em>filter aanpassingsaanvraag</em> werd verstuurd, samen met een kopie van het ongewenste bericht.</li>
 <li>Daarna mag je de spam verwijderen.</li>
 <li>Er wordt geen bevestiging naar jou gestuurd, maar je aanvraag zal behandeld worden in het continue lopende filter aanpassingproces.</li>
</ul>
<h3>Beheer valse negatieven met Microsoft Entourage (Apple computers)</h3>
<ul>
 <li>Selecteer de ontvangen spam in de lijst van berichten.</li>
 <li>Selecteer <em>Bericht</em>, <em>Opnieuw verzenden</em> in het menu.</li>
 <li>Vul, in het ontvanger veld, het e-mail adres in voor <em>Filter Aanpassing Aanvragen</em>.</li>
 <li>Een <em>filter aanpassingsaanvraag</em> werd verstuurd, samen met een kopie van het ongewenste bericht.</li>
 <li>Daarna mag je de spam verwijderen.</li>
 <li>Er wordt geen bevestiging naar jou gestuurd, maar je aanvraag zal behandeld worden in het continue lopende filter aanpassingproces.</li>
</ul>
<h3>Beheer valse negatieven met Mail (Apple computers)</h3>
<ul>
 <li>Selecteer de ontvangen spam in de lijst met berichten.</li>
 <li>Selecteer <em>Bericht</em>, <em>Stuur door als Bijlage</em> in het menu.</li>
 <li>Vul, in het ontvanger veld, het e-mail adres in voor <em>Filter Aanpassing Aanvragen</em>.</li>
 <li>Een <em>filter aanpassingsaanvraag</em> werd verstuurd, samen met een kopie van het ongewenste bericht.</li>
 <li>Daarna mag je de spam verwijderen.</li>
 <li>Er wordt geen bevestiging naar jou gestuurd, maar je aanvraag zal behandeld worden in het continue lopende filter aanpassingproces.</li>
</ul>
<h2>Bekijk dit ook in de gebruikershandleiding</h2>
<h3>Filter onnauwkeurigheden </h3>
<p>Beslis wat te doen wanneer de filter een verkeerde beslissing neemt.</p>
<p><a href="__MANUAL_ERRORS_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_ERRORS_NAME__" /></a> <a href="__MANUAL_ERRORS_LINK__">Download hoofdstuk</a> (__MANUAL_ERRORS_SIZE__)</p>
';

$htxt['SUPPORT'] = '
<h1>Ondersteuning en hulp</h1>
<h6>Onze support en commerciële diensten zijn beschikbaar tijdens de werkuren, van maandag tot vrijdag.</h6>
<h2>In geval van problemen</h2>
<p>__SUPPORT_EMAIL__</p>
<p>Vooraleer je de ondersteuningsdienst contacteert, zorg ervoor dat je probleem niet voorkomt in de __LINKHELP_usermanual__user manual__LINK__ 
of in de __LINKHELP_faq__frequently asked questions__LINK__.</p>
<h2>Voor commerciële vragen</h2>
<p>__SALES_EMAIL__</p>
';
?>
