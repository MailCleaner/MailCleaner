<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
$htxt['EINLEITUNG'] = '
<h1>Willkommen in der Welt der Nachrichten,... die Sie wirklich erhalten m&ouml;chten.</h1>
<p>MailCleaner ist ein m&auml;chtiges Antivirus- und Antispam-Programm.</p>
<p>Dieser Filter der letzten Generation wird nicht auf Ihrem Computer installiert, sondern ist der Lieferkette der Meldungen vorgeschaltet, an der Spitze der technischen Infrastruktur Ihres Unternehmens, Ihrer Einrichtung oder Ihres Webhosters. Er wendet ausgekl&uuml;gelte Regeln an, die t&auml;glich durch die Ingenieure des Analysezentrums von MailCleaner aktualisiert werden, je nach den Strategien der Spammer und dem Erscheinen neuer Viren.  Dank diesem Grundsatz der st&auml;ndigen &Uuml;berwachung verf&uuml;gen Sie 24 Stunden am Tag &uuml;ber die besten Tr&uuml;mpfe zum Schutz gegen Virenangriffe, gef&auml;hrliche Inhalte und unerw&uuml;nschte Nachrichten.</p>
<p>Dieses Handbuch dient dazu, Ihnen das Funktionieren von MailCleaner, seine Integration in Ihr E-Mail Programm und die verschiedenen bestehenden M&ouml;glichkeiten der benutzerdefinierten Gestaltung zu erkl&auml;ren.</p>
';

$htxt['SCHNELLSTART'] = '
<h1>Nehmen Sie sich ein paar Minuten Zeit, um zu entdecken, wie sich MailCleaner in Ihre E-Mails integriert.</h1>
<p>Die in diesem Kapitel enthaltenen Anweisungen erm&ouml;glichen es Ihnen, in wenigen Minuten Ihr neues Antivirus- und Antispamprogramm zu beherrschen. Sie beruhen auf der Standardkonfiguration des Filters, die Ihnen sogleich einen maximalen Schutz bietet.</p>
<p>MailCleaner erfordert nur wenig Beachtung durch Sie: es merzt die Viren aus, behandelt die gef&auml;hrlichen Inhalte und entfernt die Spams aus Ihren E-Mails, jederzeit und ohne Ihr Zutun. Im Sinne der Transparenz informiert es Sie &uuml;ber seine Aktionen durch Quarant&auml;neberichte, die Sie regelm&auml;ssig in Ihrem Postfach finden.</p>

<h2>Die Quarant&auml;neberichte</h2>
<p>Ein Mal pro Tag, Woche oder Monat &mdash; je nach der Konfiguration durch Ihren Administrator oder Provider &mdash; erhalten Sie einen Bericht &uuml;ber jede Ihrer pers&ouml;nlichen Adressen, die von MailCleaner gefiltert wurde. In dieser Liste finden Sie alle E-Mails, die in der letzten Periode als Spam betrachtet und folglich in Quarant&auml;ne behalten wurden, d. h. in einer besonderen Isolationszone ausserhalb Ihres E-Mail Programms.</p>
<p>Kontrollieren Sie in den ersten Wochen der Verwendung diese Quarant&auml;neberichte sehr aufmerksam, um sich zu vergewissern, dass &mdash; was sehr selten ist &mdash; kein erw&uuml;nschtes E-Mail, d. h. keines, das Sie h&auml;tte erreichen sollen, irrt&uuml;mlich blockiert worden ist.</p>
<p>Anschliessend k&ouml;nnen Sie den Empfang der Berichte deaktivieren oder sie weiter beziehen und periodisch &uuml;ber die Wirkungen des Filters informiert werden.</p>

<h2>Was tun wenn ein E-Mail irrt&uuml;mlich blokiert wurde</h2>
<p>In sehr seltenen F&auml;llen kann es vorkommen, dass eine erw&uuml;nschte Nachricht blockiert und in Quarant&auml;ne gesetzt wurde. Dies kann verschiedene Gr&uuml;nde haben, darunter die Formatierung des E-Mails oder der schlechte Ruf des Servers, von dem aus das E-Mail versandt worden ist. Es handelt sich also nicht um eine Fehlfunktion von MailCleaner, sondern um eine Vorsichtsmassnahme angesichts der Charakteristika eines bestimmten E-Mails, die beim blossen Durchlesen des Inhalts nicht zu erkennen sein m&ouml;gen.</p>
<p>Gegebenfalls sind zwei Handlungen m&ouml;glich:</p>
<ul>
<li><i>Freigabe des E-Mails</i> aus der Quarant&auml;ne,damit es zu Ihrem Postfach gelangen kann ;</li>
<li>Mitteilung dieses Fehlers an das <i>Analysezentrum</i> von MailCleaner, damit der Filter diesem Absender gegen&uuml;ber toleranter wird. Bei MailCleaner nennen wir dies eine <i>Anpassung des Filters </i>.</li>
</ul> 
<p>Wenn Sie Zweifel &uuml;ber die Art des E-Mails haben, k&ouml;nnen Sie es selbstverst&auml;ndlich zuerst ansehen, bevor Sie &uuml;ber seine Freigabe entscheiden.</p>
<p class="note"><strong>Hinweis:</strong> das <i>Analysezentrum</i> von MailCleaner befindet sich am Sitz des Software-Herausgebers und besteht aus spezialisierten Ingenieuren, die jederzeit die hohe Qualit&auml;t des Filters angesichts des weltweiten Spamverkehrs, der Virenaktivit&auml;t und der Anpassungsbegehren der Benutzer aus aller Welt garantieren.</p>
<h3>Den Inhalt eines E-Mails ansehen </h3>
<ul>
 <li>Klicken Sie das Datum, den Betreff oder das Symbol Voransicht an ;</li> 
 <li>der Inhalt des E-Mails wir in einem neuen Fenster angezeigt. </li>
</ul>
<h3>Das E-Mail aus der Quarant&auml;ne freigeben </h3>
<ul>
<li>Klicken Sie auf das Symbol Freigabe, das sich links von der E-Mail Zeile befindet ;</li>
<li>das blockierte E-Mail gelangt zu Ihrem E-Mail Programm.</li>
</ul>

<h3>Eine Anpassung des Filters verlangen</h3>
<ul>
 <li>Klicken Sie auf das Symbol Anpassung, das sich ganz links in der E-Mail Zeile befindet ;</li>
 <li>Sie werden aufgefordert, Ihr Begehren zu best&auml;tigen ;</li>
 <li>ein <i>Begehren um Anpassung des Filters</i> wird zusammen mit einer Kopie des E-Mails an das <i>Analysezentrum</i> versandt ;</li>
 <li>Sie erhalten bis zum n&auml;chsten Werktag Informationen &uuml;ber die Massnahmen, die das Analysezentrum von MailCleaner zur Korrektur des Filters getroffen hat.</li>
</ul>
<p class="note"><strong>Hinweis:</strong> die Verwendung eines dieser Tools von einem Quarant&auml;nebericht aus bewirkt die &Ouml;ffnung einer Seite oder eines Best&auml;tigungsdialogs in Ihrem Internetbrowser.</p>

<h2>Was tun, wenn ein Spam nicht blokiert wurde</h2>
<p>Wenn ein Spam durch die Maschen des Netzes f&auml;llt und den Weg in Ihr Postfach findet, so bedeutet dies, dass der Unterschied zu einem erw&uuml;nschten E-Mail sehr gering ist. In diesem Fall nimmt MailCleaner die Zustellung in der Annahme vor, dass es weniger schlimm ist, ein Spam zu erhalten als eine m&ouml;glicherweise wichtige Meldung nicht zu bekommen.</p>
<p>Wenn Sie feststellen, dass es sich in der Tat um Spam handelt, m&uuml;ssen Sie eine Anpassung des Filters verlangen, um die Regeln der Spamerkennung zu verfeinern.</p>
<h3>Spam mit Microsoft Outlook nicht blockiert</h3>
<p>Sie k&ouml;nnen dem E-Mail Programm Microsoft Outlook f&uuml;r Windows eine Erweiterung (ein Plug-in) hinzuf&uuml;gen, damit von Ihrem E-Mail Programm aus ein nicht gefiltertes Spam einfach mitgeteilt werden kann. Es wird in der Men&uuml;leiste eine Schaltfl&auml;che mit dem Logo von MailCleaner und dem Text „Unerw&uuml;nscht“ installiert.</p> 

<p>Um ein Spam mit der MailCleaner Erweiterung mitzuteilen:</p>
<ul>
 <li>w&auml;hlen Sie das Spam aus der E-Mail Liste ;</li>
 <li>klicken Sie auf die Schaltfl&auml;che <i>Unerw&uuml;nscht</i> in der Werkzeugleiste ;</li>
 <li>ein <i>Begehren um Anpassung des Filters</i> wird zusammen mit einer Kopie des E-Mails an das <i>Analysezentrum</i> versandt ;</li>
 <li>l&ouml;schen Sie anschliessend das Spam, falls Sie dies w&uuml;nschen ; Sie erhalten zwar keine Best&auml;tigung, aber Ihre Mitteilung wird im st&auml;ndigen Verfahren der Korrektur des Filters ber&uuml;cksichtigt ;</li>
</ul>
<p class="note"><strong>Hinweis:</strong> wenn diese Erweiterung in Outlook nicht vorhanden ist, wenden Sie sich an den E-Mail Administrator oder befolgen Sie die Installationsanweisungen in diesem Handbuch.</p>
<h3>Nicht blockiertes Spam mit einem anderen E-Mail Programm</h3>
<p>Wenn Sie nicht Microsoft Outlook mit MailCleaner verwenden, so ist ein Begehren um Anpassung des Filters wegen eines nicht gefilterten Spams nur durch ein manuelles E-Mail an das Analysezentrum von MailCleaner m&ouml;glich.</p>
<p><strong>Diese Adresse kann in diesem Dokument nicht angegeben werden, da sie von der Konfiguration abh&auml;ngt, die von Ihrem E-Mail Administrator oder Ihrem Provider durchgef&uuml;hrt wurde.</strong> Um sie kennen zu lernen, konsultieren Sie im Abschnitt Hilfe des Verwaltungsbereiches die Rubrik Begehren um Anpassung des Filters. Notieren Sie diese Adresse: Sie werden sie in den folgenden Schritten ben&ouml;tigen.</p>
<p>UmSpam manuell zu melden:</p>
<ul>
 <li>w&auml;hlen Sie das Spam aus der E-Mail Liste ;</li>
 <li>leiten Sie das Mail mit der entsprechenden Funktion in Ihrem E-Mail Programm weiter ;</li>
 <li>geben Sie als Empf&auml;nger die Adresse des <i>Analysezentrums</i> von MailCleaner an, die Sie zuvor notiert haben ;</li>
 <li>ein <i>Begehren um Anpassung des Filters</i> wird zusammen mit einer Kopie des E-Mails versandt ;</li>
 <li>l&ouml;schen Sie anschliessend das Spam, falls Sie dies w&uuml;nschen ;</li>
 <li>Sie erhalten zwar keine Best&auml;tigung, aber Ihre Mitteilung wird im st&auml;ndigen Verfahren der Korrektur des Filters ber&uuml;cksichtigt.</li>
</ul>

<p class="important"><strong>Wichtig:</strong> Sie d&uuml;rfen keinesfalls das Spam durch Kopieren und Einsetzen weiterleiten, sonst gehen die urspr&uuml;nglichen langen Kopfzeilen verloren, die f&uuml;r die Analyse des E-Mails unerl&auml;sslich sind. Unabh&auml;ngig davon, ob Sie einen PC oder einen Mac haben und welches Ihr E-Mail Programm ist, ben&uuml;tzen Sie unbedingt die Funktion <i>Weiterleiten</i> oder &auml;hnlich.</>

<h2>Ihre Kenntnisse &uuml;ber MailCleaner vertiefen</h2>
<p>Wenn Sie diese paar Grundregeln gelernt haben, m&ouml;chten Sie bestimmt mehr &uuml;ber die M&ouml;glichkeiten einer benutzerdefinierten Einstellung von MailCleaner erfahren.</p>
<p>In diesem Handbuch finden Sie stets die ben&ouml;tigten Antworten.</p>
<p>Wir w&uuml;nschen Ihnen einen reibungslosen Gebrauch von MailCleaner.</p>
';

$htxt['MANUAL_FULL_NAME'] = 'mailcleaner_benutzerhandbuch.pdf';
$htxt['MANUAL_FIRSTCONTACT_NAME'] = 'mailcleaner_schnellstart.pdf';
$htxt['MANUAL_GENERICCONCEPT_NAME'] = 'mailcleaner_allgemeine_grundsaetze.pdf';
$htxt['MANUAL_GUI_NAME'] = 'mailcleaner_verwaltungsbereich.pdf';
$htxt['MANUAL_QUARANTINE_NAME'] = 'mailcleaner_abschnitt_quarantaene.pdf';
$htxt['MANUAL_STATS_NAME'] = 'mailcleaner_abschnitt_statistiken.pdf';
$htxt['MANUAL_CONFIGURATION_NAME'] = 'mailcleaner_abschnitt_konfiguration.pdf';
$htxt['MANUAL_ERRORS_NAME'] = 'mailcleaner_ungenauigkeiten_der_filterung.pdf';

$htxt['USERMANUAL'] = '
<h1>Benutzerhandbuch &ndash; Version 1.0 &ndash; Juni 2008</h1>
<h2>Gesamtes Benutzerhandbuch herunterladen</h2>
<p class="download"><a href="__MANUAL_FULL_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_FULL_NAME__" /></a> <a href="__MANUAL_FULL_LINK__">Herunterladen</a> (__MANUAL_FULL_SIZE__)</p>
<h2>Bestimmte Kapitel nachschlagen</h2>

<h3>Schnellstart</h3>
<p>So meistern Sie in wenigen Minuten die Grundfunktionen.</p>
<p class="download"><a href="__MANUAL_FIRSTCONTACT_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_FIRSTCONTACT_NAME__" /></a> <a href="__MANUAL_FIRSTCONTACT_LINK__">Kapitel herunterladen</a> (__MANUAL_FIRSTCONTACT_SIZE__)</p>

<h3>Allgemeine Grunds&auml;tze</h3>
<p>Um Strategien zur Vorbeugung und Abwehr zu verstehen, die sich im Kern des MailCleaners befinden.</p>
<p class="download"><a href="__MANUAL_GENERICCONCEPT_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_GENERICCONCEPT_NAME__" /></a> <a href="__MANUAL_GENERICCONCEPT_LINK__">Kapitel herunterladen</a>  (__MANUAL_GENERICCONCEPT_SIZE__)</p>

<h3>Verwaltungsbereich</h3>
<p>Hier erfahren Sie alles &uuml;ber den Verwaltungsbereich, in dem Sie sich gerade befinden.</p>
<p class="download"><a href="__MANUAL_GUI_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_GUI_NAME__" /></a> <a href="__MANUAL_GUI_LINK__">Kapitel herunterladen</a> (__MANUAL_GUI_SIZE__)</p>

<h3>Quarant&auml;ne</h3>
<p>Um Ihre Quarant&auml;ne effizient verwalten zu lernen.</p>
<p class="download"><a href="__MANUAL_QUARANTINE_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_QUARANTINE_NAME__" /></a> <a href="__MANUAL_QUARANTINE_LINK__">Kapitel herunterladen</a> (__MANUAL_QUARANTINE_SIZE__)</p>

<h3>Statistiken</h3>
<p>Um die Art der Meldungen zu erkennen, die Sie erhalten.</p>
<p class="download"><a href="__MANUAL_STATS_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_STATS_NAME__" /></a> <a href="__MANUAL_STATS_LINK__">Kapitel herunterladen</a> (__MANUAL_STATS_SIZE__)</p>

<h3>Konfiguration</h3>
<p>Um MailCleaner Ihren Bed&uuml;rfnissen entsprechend zu personnalisieren.</p>
<p class="download"><a href="__MANUAL_CONFIGURATION_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_CONFIGURATION_NAME__" /></a> <a href="__MANUAL_CONFIGURATION_LINK__">Kapitel herunterladen</a> (__MANUAL_CONFIGURATION_SIZE__)</p>

<h3>Ungenauigkeiten der Filterung</h3>
<p>Um Massnahmen zu ergreifen, wenn eine Meldung nicht richtig gefiltert wurde.</p>
<p class="download"><a href="__MANUAL_ERRORS_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_ERRORS_NAME__" /></a> <a href="__MANUAL_ERRORS_LINK__">Kapitel herunterladen</a> (__MANUAL_ERRORS_SIZE__)</p>
';

$htxt['FAQ'] = '
<h1>Einfache und direkte Antworten auf die h&auml;ufigsten Fragen.</h1>
<h2>Verwaltungsbereich</h2>
<h3>Wo finde ich meinen Benutzername und mein Passwort ?</h3>
<p>F&uuml;r eine bestimmte von MailCleaner gefilterte Adresse sind Ihr Benutzername und Ihr Passwort dieselben wie in Ihrem E-Mail Programm.</p>
<h2>Spams und Quarant&auml;ne</h2>
<h3>Was ist die Quarant&auml;ne ?</h3>
<p>Eine Isolationszone ausserhalb Ihres E-Mail Programms, welche die als Spams identifizierten E-Mails blockiert.</p>
<h4>Anpassungen des Filters</h4>
<h3>Was ist eine Anpassung des Filters ?</h3>
<p>Eine freiwillige Aktion von Ihnen in der Folge der Blockade einer legitimen Nachricht in der Quarant&auml;ne oder der Ankunft eines Spams in Ihrem Postfach. Im ersten Fall macht die Anpassung des Filters MailCleaner gegen&uuml;ber einem bestimmten Absender toleranter. Im zweiten Fall wird MailCleaner aggressiver.</p>
<h3>Was geschieht bei einem Begehren um Anpassung des Filters ?</h3>
<p>Eine Kopie des E-Mails wird an das Analysezentrum von MailCleaner gesandt. Die Ingenieure &uuml;berpr&uuml;fen die Lage und nehmen eventuell eine &Auml;nderung des Filters vor. Die vom Analysezentrum getroffenen Massnahmen werden Ihnen innerhalb eines Werktags mitgeteilt.</p>
<h4>In der Quarant&auml;ne blockierte E-Mails</h4>
<h3>Ein legitimes E-Mail wurde irrt&uuml;mlich in der Quarant&auml;ne blockiert. Was muss ich tun ?</h3>
<p>Sie m&uuml;ssen es freigeben, damit es Ihr E-Mail Programm erreichen darf und dann aus der Quarant&auml;ne heraus <i>ein Begehren um Anpassung des Filters</i> stellen.</p>
<h3>Warum blockiert MailCleaner ein E-Mail, das mich h&auml;tte erreichen sollen ?</h3>
<p>Weil die fragliche Nachricht &uuml;ber einen Server mit zurzeit zweifelhaftem Ruf versandt wurde (er wurde vielleicht von Piraten als Relais f&uuml;r Spams verwendet) und/oder eine spezifische Formatierung enth&auml;lt, die eine Spamregel ausgel&ouml;st hat. Es handelt sich also nicht um einen Fehler von MailCleaner, sondern um eine Vorsichtsmassnahme angesichts der Charakteristika eines bestimmten E-Mails, die beim blossen Durchlesen des Inhalts nicht zu erkennen sein m&ouml;gen.</p>
<h3>Wie geben Sie ein E-Mail frei ?</h3>
<p>Klicken Sie auf das Pfeilsymbol, das auf der entsprechenden Zeile der Meldung in einem Quarant&auml;nebericht oder im Verwaltungsbereich zu finden ist.</p>
<h3>Ich habe eine Meldung freigegeben, aber sie erscheint noch immer in der Quarant&auml;ne. Ist das normal&nbsp;?</h3>
<p>Eine freigegebene Nachricht verbleibt in der Quarant&auml;ne, damit Sie sie allenfalls erneut freigeben k&ouml;nnen. Sie erscheint jedoch in Kursivschrift, damit Sie sehen, dass sie bereits freigegeben wurde.</p>
<h3>Was m&uuml;ssen Sie tun, um keine Quarant&auml;neberichte mehr zu erhalten ?</h3>
<p>Sie k&ouml;nnen die im Abschnitt <i>Konfiguration</i> des Verwaltungsbereichs die Periodizit&auml;t der Berichte &auml;ndern oder auf ihren Versand verzichten. Dann m&uuml;ssen Sie aber jedes Mal auf den Verwaltungsbereich zugreifen, um Ihre Quarant&auml;ne zu konsultieren.</p>
<h4>Nicht zur&uuml;ckbehaltene Spams</h4>
<h3>Ein Spam ist nicht gefiltert worden. Was muss ich tun ?</h3>
<p>Sie m&uuml;ssen aus Ihrem E-Mail Programm heraus ein Begehren um <i>Anpassung des Filters</i> stellen, damit die Filterregeln verst&auml;rkt werden.</p>
<h3>Warum l&auml;sst MailCleaner Spams zu ?</h3>
<p>Gewisse Spams fallen durch die Maschen des Netzes, weil sich in der mathematischen Analyse kein Unterschied zu einer legitimen Meldung gezeigt hat. Aus diesem Grund m&uuml;ssen Sie diesen Fehler unbedingt dem <i>Analysezentrum</i> mitteilen, damit gewisse Regeln verst&auml;rkt werden k&ouml;nnen. Im Zweifelsfall nimmt MailCleaner die Zustellung vor, <strong>da es weniger gravierend f&uuml;r Sie ist, ein Spam zu erhalten als eine legitime Nachricht nicht zu erhalten</strong>.</p>
<h2>Viren und gef&auml;hrliche E-Mails</h2>
<h3>Wie behandelt MailCleaner die Viren ?</h3>
<p>Die Viren werden ausgemerzt. Sie erhalten keine Meldung dar&uuml;ber.</p>
<h3>Was ist ein gef&auml;hrlicher Inhalt ?</h3>
<p>Es handelt sich um Informationen, die Ihr E-Mail Administrator vorsorglich filtern wollte, z. B. ausf&uuml;hrbare Scripts (.exe) in Attachments oder Links zu verd&auml;chtigen Internetseiten. Ein E-Mail mit gef&auml;hrlichen Inhalten wird an Ihr Postfach weitergeleitet, nachdem die gef&auml;hrlichen Elemente entfernt worden sind. Sie werden durch einen Hinweis ersetzt, wie Sie Ihren Administrator bitten k&ouml;nnen, Ihnen das vollst&auml;ndige E-Mail zuzustellen.</p>
<h3>Wie erkennen Sie, dass ein E-Mail einen gef&auml;hrlichen Inhalt hat ?</h3>
<p>Es umfasst ein Schl&uuml;sselwort in der Betreffzeile &ndash; gew&ouml;hnlich "{GEF&Auml;HRLICHER INHALT}" &ndash; und Anweisungen zur Freigabe in der Anlage.</p>
<h3>Wie verlange ich von meinem Administrator, dass er mir die gef&auml;hrlichen Inhalte nachsendet ?</h3>
<p>Befolgen Sie die als Anlage erhaltenen Anweisungen. Sie m&uuml;ssen Ihrem Administrator das numerische Kennzeichen der blockierten Meldung angeben. Wenn Ihr Administrator zum Schluss kommt, dass es sich wirklich um eine Gefahr handelt (was meist zutrifft), so kann er die &Uuml;bermittlung verweigern.</p>
';

$htxt['GLOSSARY'] = '
<h1>Die Definitionen, die am h&auml;ufigsten verwendet werden.</h1>
<h3>Analysezentrum</h3>
<p>Das Analysezentrum von MailCleaner befindet sich am Sitz des Herausgebers von MailCleaner und wird jederzeit die hohe Qualit&auml;t des Filters angesichts des weltweiten Spamverkehrs, der Virenaktivit&auml;t und der Anpassungsbegehren der Benutzer aus aller Welt garantieren.</p>
<h3>Aufbewahrungsfrist</h3>
<p>Dauer, w&auml;hrend der ein in der Quarant&auml;ne zur&uuml;ckbehaltenes E-Mail konsultiert werden kann. Nach Ablauf dieser Frist wird es automatisch gel&ouml;scht.</p>
<h3>Begehren um Anpassung des Filters</h3>
<p>Eine freiwillige Aktion von Ihnen in der Folge der Blockade einer legitimen Nachricht in der Quarant&auml;ne oder der Ankunft eines Spams in Ihrem Postfach. Im ersten Fall macht die Anpassung des Filters MailCleaner gegen&uuml;ber einem bestimmten Absender toleranter. Im zweiten Fall wird MailCleaner aggressiver. Die Begehren werden vom Analysezentrum von MailCleaner behandelt.</p>
<h3>Ein E-Mail freigeben</h3>
<p>Eine Freiwillige Aktion, um ein in der Quarant&auml;ne zur&uuml;ckbehaltenes E-Mail an das E-Mail Programm weiterzuleiten.</p> 
<h3>Falsche Positivmeldung</h3>
<p>Legitimes E-Mail, das von MailCleaner als Spam betrachtet wurde. Jede falsche Positivmeldung muss Gegenstand eines Begehrens um Anpassung des Filters bilden.</p>
<h3>Falsche Negativmeldung</h3>
<p>Spam, der nicht durch den Filter als solcher erkannt wurde. Jede falsche Negativmeldung muss dem Analysezentrum gemeldet werden, damit der Filter insk&uuml;nftig dieser Ausnahme Rechnung tragen kann.</p>
<h3>Fastnet SA</h3>
<p>Der freundliche Herausgeber von MailCleaner. Das reine Gegenteil der Spammer. Sitz in St-Sulpice, Schweiz.</p>
<h3>Filterungsregel</h3>
<p>Mathematische und statistische &Uuml;berpr&uuml;fung der Charakteristika eines E-Mails, um festzustellen, ob es sich um Spam handelt.</p>
<h3>Gef&auml;hrlicher Inhalt</h3>
<p>Verd&auml;chtige Informationen, die in einem E-Mail enthalten sind, zuvor durch Ihren Administrator oder Ihren Provider gefiltert.</p>
<h3>Login</h3>
<p>Verfahren, das in der Feststellung besteht, ob eine Person wirklich diejenige ist, die sie zu sein vorgibt. Bei MailCleaner gestattet ein erfolgreiches Login dem Benutzer den Zugriff auf seine Quarant&auml;ne.</p>
<h3>Plug-in</h3>
<p>Englische Bezeichnung f&uuml;r eine Erweiterung, die in einer Anwendung zu installieren ist. Die Erweiterung MailCleaner f&uuml;r Microsoft Outlook vereinfacht die Mitteilungen &uuml;ber falsche Negativmeldungen.</p> 
<h3>Provider</h3>
<p>Unternehmen, das Internet- und E-Mail-Dienstleistungen anbietet. Auf Englisch: ISP (Internet Service Provider).</p>
<h3>Quarant&auml;ne</h3>
<p>Eine Isolationszone ausserhalb Ihres E-Mail Programms, welche die als Spams betrachteten E-Mails zur&uuml;ckh&auml;lt.</p>
<h3>Quarant&auml;nebericht</h3>
<p>Automatisch versandter periodischer Bericht, worin alle in der Quarant&auml;ne zur&uuml;ckbehaltenen Meldungen aufgef&uuml;hrt werden und &uuml;ber Werkzeuge verf&uuml;gt, um die E-Mails anzusehen und freizugeben.</p>
<h3>RBL</h3>
<p>Realtime Blackhole List. Die RBL haben zum Ziel, in Echtzeit eine Liste der Server zu f&uuml;hren, die einen schlechten Ruf haben, weil sie Spam verschicken. Der Grundsatz der Verwendung ist sehr einfach: wenn das eingetroffene E-Mail von einem solchen Server stammt, wird es grunds&auml;tzlich als Spam behandelt.  Die Schwierigkeit bei der Verwendung von RBL besteht darin, st&auml;ndig ihre Vertrauensw&uuml;rdigkeit zu &uuml;berp&uuml;fen.</p>
<h3>Schweiz</h3>
<p>Ursprungsland von MailCleaner. Die Spams werden mit der Qualit&auml;t und Pr&auml;zision eines Uhrmachers ausgemerzt.</p>
<h3>Score</h3>
<p>Quarant&auml;neindikator, der eine ziffernm&auml;ssige und gewichtete Bewertung der Spamindizen zeigt.</p>
<h3>SMTP</h3>
<p>Simple Mail Transfer Protocol. F&uuml;r den Versand elektronischer Post verwendetes Protokoll.</p>
<h3>Spam</h3>
<p>Vom Benutzer nicht erw&uuml;nschte Nachricht, die aber keine gef&auml;hrlichen Inhalte enth&auml;lt. Manchmal auch "Junkmail" genannt.</p>
<h3>&Uuml;berwachte Domain</h3>
<p>Gesamtheit der durch dieselbe Einrichtung von MailCleaner &uuml;berwachten Internetdomains (z. B.: @meyer.com, @mueller.com).</p>
<h3>Verwaltungsbereich</h3>
<p>Privater Internetbereich, wo Sie die in Quarant&auml;ne zur&uuml;ckbehaltenen E-Mails konsultieren und die benutzerdefinierte Konfiguration der Parameter vornehmen k&ouml;nnen.</p>
<h3>Virus</h3>
<p>Softwareelement, das als Anlage (attachment) in Ihren Computer eindringt und m&ouml;glicherweise dessen Integrit&auml;t beeintr&auml;chtigt.</p>
<h3>Warnliste</h3>
<p>Liste von Absendern Ihres Vertrauens, sodass Sie f&uuml;r jedes ihrer in der Quarant&auml;ne zur&uuml;ckbehaltene E-Mail eine Warnung erhalten. Auf Englisch: Warn List.</p>
<h3>Weisse Liste</h3>
<p>Liste von Absendern Ihres Vertrauens, sodass deren E-Mails nicht in der Quarant&auml;ne zur&uuml;ckbehalten werden. Auf Englisch: White List.</p>
<h3>Wow</h3> 
<p>Das sagen Sie jetzt hoffentlich.</p>
';

$htxt['PLUGIN'] = '
<h1>Um den nicht gefilterten Spam aus Ihrem E-Mail Programm effizient zu verwalten.</h1>
<h6>Ein Erweiterungsprogramm (Plug-in) kann dem E-Mail Programm Mocrosoft Outlook f&uuml;r Windows zugef&uuml;gt werden. Die notification eines nicht gefiltertes Spam an das Analysezentrum wird vereinfacht.</h6>
<p>Es wird in der Men&uuml;leiste eine Schaltfl&auml;che mit dem Logo von MailCleaner und dem Text "Unerw&uuml;nscht" installiert.</p>
<p>Alle Meldungen, die an das Analysezentrum &uuml;bertragen werden, werden gepr&uuml;ft, um eine eventuelle Anpassung des Filters zu festzustellen.</p>
<p class="note">Hinweiss: Ihr Systemadministrator hat vielleicht die Installation von Outlook Erweiterungen auf Ihrem Computer untersagt. Nehmen Sie gegebenenfalls mit ihm Kontakt auf.</p>
<h2>Aufladung der MailCleaner Erweiterung f&uuml;r Outlook f&uuml;r Windows</h2>
<p>F&uuml;r Microsoft Outlook 2003: <a href="__PLUGIN_OU2003_LINK__">Herunterladen</a>
 (Version 1.0.3 &ndash; __PLUGIN_OU2003_SIZE__) </p>
<p>F&uuml;r Microsoft Outlook 2007: <a href="__PLUGIN_OU2007_LINK__">Herunterladen</a>
 (Version 1.0.3 &ndash; __PLUGIN_OU2007_SIZE__)</p>
<h2>Installation der MailCleaner Erweiterung f&uuml;r Outlook f&uuml;r Windows</h2>
<p>Um die Erweiterung f&uuml;r Outlook f&uuml;r Windows zu installieren:</p>
<ul>
<li>laden Sie die neuste Version mit den nachfolgenden Link herunter ;</li>
<li>verlassen Sie Outlook, wenn es aktiv ist ;</li>
<li>Doppelklicken Sie auf das Installationsprogramm ;</li>
<li>befolgen Sie die Anweisungen (Es ist m&ouml;glich, dass das Hinzuf&uuml;gen von "Visual Studio 2005 Tools for Office" oder eines anderen Komponenten eines Dritten gefordet wird) ;</li>
<li>eine Best&auml;tigungsmeldung zeigt Ihnen das Ende der Installation an ;</li>
<li>starten Sie Outlook erneut.</li>
</ul>
<p>Sie finden eine neue Schaltfl&auml;che in Ihrer Werkzeugleiste.</p>
<h2>Um ein Spam mit der MailCleaner Erweiterung f&uuml;r Outlook f&uuml;r Windows zu melden</h2>
<ul>
<li>w&auml;hlen Sie das Spam aus der E-Mail Liste ;</li>
<li>klicken Sie auf die Schaltfl&auml;che <i>Unerw&uuml;nscht</i> in der Werkzeugleiste ;</li>
<li>ein Begehren um Anpassung des Filters wird zusammen mit einer Kopie des E-Mails an das <i>Analysezentrum</i> versandt ;</li>
<li>l&ouml;schen Sie anschliessend das Spam, falls Sie dies w&uuml;nschen.</li>
</ul>
<p>Sie erhalten zwar keine Best&auml;tigung, aber Ihre Mitteilung wird im st&auml;ndigen Verfahren der Korrektur des Filters ber&uuml;cksichtigt.</p>
<h2>Siehe auch das Benutzerhandbuch</h2>
<h3>Ungenauigkeiten der Filterung</h3>
<p>Um Massnahmen zu ergreifen, wenn eine Meldung nicht richtig gefiltert wurde.</p>
<p><a href="__MANUAL_ERRORS_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_ERRORS_NAME__" /></a> <a href="__MANUAL_ERRORS_LINK__">Kapitel herunterladen</a> (__MANUAL_ERRORS_SIZE__)</p>
';

$htxt['ANALYSE'] = '
<h1>Begehren um Anpassung des Filters</h1>
<h6>Wenn Sie nicht Microsoft Outlook mit __LINKHELP_plugin__ MailCleaner Erweiterung__LINK__, die Kennzeichnung eines Spam fordert, dass Sie die Meldung manuell an das Analysezentrum leiten.</h6>
<p>Das Spam wird gepr&uuml;ft, um die Filterungsmassnahmen zu verst&auml;rken.</p>
<h2>Adresse, um ein Spam mitzuteilen</h2>
<p>Die Adresse, um ein Spam mitzuteilen ist:</p><p>__SPAM_EMAIL__</p>
<h2>Um ein Spam mitzuteilen, das die Kontrolle umgangen hat (falsche Negativmeldung)</h2>
<p>Um ein Spam mitzuteilen, leiten Sie die Meldung mit der entsprechenden Funktion in Ihrem E-Mail Programm weiter an die vorher ern&auml;hnte Adresse.</p>
<p>Sie d&uuml;rfen keinesfalls das Spam durch Kopieren und Einsetzen weiterleiten, sonst gehen die urspr&uuml;nglichen langen Kopfzeilen verloren, die f&uuml;r die Analyse des E-Mails unerl&auml;sslich sind.</p>
<h3>Verwaltung der falschen Negativmeldung mit Netscape, Mozilla, Thunderbird</h3>
<ul>
 <li>W&auml;hlen Sie das Spam aus der E-Mail Liste ;</li>
 <li>W&auml;hlen Sie das Men&uuml; <em>Message</em>, dann das Untermen&uuml; <em>Forward as...</em>, dann den Punkt <em>Attachement</em> ;</li>
 <li>geben Sie als Empf&auml;nger die Adresse des <em>Analysezentrums</em> von MailCleaner ein, die zuvor angegeben wurde ;</li>
 <li>ein <em>Begehren um Anpassung des Filters</em> wird zusammen mit einer Kopie des E-Mails versandt ;</li>
 <li>l&ouml;schen Sie anschliessend das Spam, falls Sie dies w&uuml;nschen ;</li>
 <li>Sie erhalten zwar keine Best&auml;tigung, aber Ihre Mitteilung wird im st&auml;ndigen Verfahren der Korrektur des Filters ber&uuml;cksichtigt.</li>
</ul>
<h3>Verwaltung der falschen Negativmeldungen mit Microsoft Entourage (Apple Computer)</h3>
<ul>
 <li>W&auml;hlen Sie das Spam aus der E-Mail Liste ;</li>
 <li>W&auml;hlen Sie das Men&uuml; <em>Message</em>, dann den Punkt <em>Weiterleiten</em> ;</li>
 <li>geben Sie als Empf&auml;nger die Adresse des <em>Analysezentrums</em> von MailCleaner ein, die zuvor angegeben wurde ;</li> 
 <li>ein <em>Begehren um Anpassung des Filters</em> wird zusammen mit einer Kopie des E-Mails versandt ;</li>
 <li>l&ouml;schen Sie anschliessend das Spam, falls Sie dies w&uuml;nschen ;</li>
 <li>Sie erhalten zwar keine Best&auml;tigung, aber Ihre Mitteilung wird im st&auml;ndigen Verfahren der Korrektur des Filters ber&uuml;cksichtigt.</li>
</ul>
<h3>Verwaltung der falschen Negativmeldungen mit Mail (Apple Computer)</h3>
<ul>
 <li>W&auml;hlen Sie das Spam aus der E-Mail Liste ;</li>
 <li>W&auml;hlen Sie das Men&uuml; <em>Message</em>, dann den Punkt <em>Weiterleiten als Anlage</em> ;</li>
 <li>geben Sie als Empf&auml;nger die Adresse des <em>Analysezentrums</em> von MailCleaner ein, die zuvor angegeben wurde ;</li> 
 <li>ein <em>Begehren um Anpassung des Filters</em> wird zusammen mit einer Kopie des E-Mails versandt ;</li>
 <li>l&ouml;schen Sie anschliessend das Spam, falls Sie dies w&uuml;nschen ;</li>
 <li>Sie erhalten zwar keine Best&auml;tigung, aber Ihre Mitteilung wird im st&auml;ndigen Verfahren der Korrektur des Filters ber&uuml;cksichtigt.</li>
</ul>
<h2>Siehe auch das Benutzerhandbuch</h2>
<h3>Ungenauigkeiten der Filterung</h3>
<p>Um Massnahmen zu ergreifen, wenn eine Meldung nicht richtig gefiltert wurde.</p>
<p><a href="__MANUAL_ERRORS_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_ERRORS_NAME__" /></a> <a href="__MANUAL_ERRORS_LINK__">Kapitel herunterladen</a> (__MANUAL_ERRORS_SIZE__)</p>
';

$htxt['SUPPORT'] = '
<h1>Support</h1>
<h6>Unser Supportdienst und unsere Verkaufsabteilung stehen Ihnen w&auml;hrend der B&uuml;rozeiten von Montag bis Freitag zur Verf&uuml;gung.</h6>
<h2>Im Falle von Schwierigkeiten</h2>
<p>__SUPPORT_EMAIL__</p>
<p>Vor der Kontaktaufnahme mit unserem Supportdienst, bitten wir Sie, sicherzustellen, ob Ihr Problem nicht im __LINKHELP_usermanual__Benutzerhandbuch__LINK__ 
oder im Abschnitt __LINKHELP_faq__H&auml;ufig gestellte Fragen__LINK__ behandelt wird.</p>
<h2>F&uuml;r alle kommerziellen Fragen</h2>
<p>__SALES_EMAIL__</p>
';
?>
