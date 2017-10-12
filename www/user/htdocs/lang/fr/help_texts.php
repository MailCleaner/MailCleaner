<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
$htxt['INTRODUCTION'] = '
<h1>Bienvenue dans le monde des messages
que vous voulez vraiment recevoir.</h1>
<p>MailCleaner est un puissant dispositif antivirus et antispam.</p>
<p>Ce filtre de derni&egrave;re g&eacute;n&eacute;ration n\'est pas install&eacute; sur votre ordinateur, mais agit en amont de la cha&icirc;ne de 
livraison des messages, au plus haut de l\'infrastructure technique de votre entreprise, institution ou 
h&eacute;bergeur. Il met en &oelig;uvre des r&egrave;gles sophistiqu&eacute;es qui sont quotidiennement actualis&eacute;es par les 
ing&eacute;nieurs du <em>centre d\'analyse de MailCleaner</em>, en fonction des strat&eacute;gies des spammeurs et de 
l\'apparition de nouveaux virus. Gr&acirc;ce &agrave; ce principe permanent de veille, vous disposez 24 heures sur 24 
des meilleurs atouts pour vous pr&eacute;munir contre les attaques virales, les contenus dangereux et les 
messages ind&eacute;sirables.</p>
<p>Le r&ocirc;le de ce manuel est de vous expliquer le fonctionnement de MailCleaner, son int&eacute;gration avec votre 
messagerie et les diff&eacute;rentes possibilit&eacute;s de personnalisation qui vous sont offertes.</p>
';
$htxt['FIRSTCONTACT'] = '
<h1>Prenez quelques minutes pour d&eacute;couvrir comment 
MailCleaner s'int&egrave;gre dans la gestion de vos messages. </h1>
<p>Les instructions contenues dans ce chapitre vous donnent les moyens de ma&icirc;triser en quelques minutes 
votre nouveau dispositif antivirus et antispam. Elles reposent sur la configuration par d&eacute;faut du filtre qui 
vous apporte imm&eacute;diatement une protection maximale.</p>
<p>MailCleaner n'exige qu'un minimum d'attention de votre part : il &eacute;radique les virus, traite les contenus 
dangereux et &eacute;carte les spams de votre messagerie 24 heures sur 24, en toute autonomie. Par souci de 
transparence, il vous informe de son action avec des rapports de quarantaine que vous recevez 
r&eacute;guli&egrave;rement dans votre bo&icirc;te aux lettres.</p>
<h2>Les rapports de quarantaine</h2>
<p>Une fois par jour, par semaine ou par mois &mdash; selon la configuration voulue par votre administrateur de 
messagerie ou votre h&eacute;bergeur &mdash;, un rapport vous est adress&eacute; pour chacune de vos adresses 
personnelles filtr&eacute;es par MailCleaner. Ce rapport liste tous les messages qui ont &eacute;t&eacute; consid&eacute;r&eacute;s comme 
des spams au cours de la derni&egrave;re p&eacute;riode et qui, en cons&eacute;quence, ont &eacute;t&eacute; retenus dans une quarantaine, 
c'est-&agrave;-dire une zone d'isolation situ&eacute;e hors de votre messagerie.</p>
<p>Durant les premi&egrave;res semaines d'utilisation, examinez attentivement ces rapports de quarantaine afin de 
vous assurer &mdash; ce qui est fort rare &mdash; qu'aucun courrier l&eacute;gitime, c'est-&agrave;-dire qui aurait d&ucirc; vous parvenir, n'a 
&eacute;t&eacute; bloqu&eacute; par erreur.</p>
<p>Ensuite, vous pourrez d&eacute;sactiver la r&eacute;ception des rapports ou continuer &agrave; les recevoir pour prendre 
p&eacute;riodiquement connaissance de l'action du filtre.</p>

<h2>Que faire si un message est bloqu&eacute; par erreur</h2>
<p>Dans de tr&egrave;s rares cas, il peut arriver qu'un message que vous souhaitiez recevoir soit bloqu&eacute; en 
quarantaine. Plusieurs raisons peuvent conduire &agrave; une telle situation, telles que le formatage du message 
ou la r&eacute;putation du serveur utilis&eacute; par l'exp&eacute;diteur lors de son envoi. Il ne s'agit donc pas d'un 
dysfonctionnement de MailCleaner, mais bien d'une r&eacute;action prudente face &agrave; des caract&eacute;ristiques 
sp&eacute;cifiques d'un message, lesquelles peuvent ne pas &ecirc;tre discernables &agrave; travers la simple lecture du 
contenu.</p>
<p>Le cas &eacute;ch&eacute;ant, vous devez proc&eacute;der &agrave; deux actions :</p>
<ul>
<li><i>lib&eacute;rer le message</i> de la quarantaine pour l'autoriser &agrave; atteindre votre messagerie ;</li>
<li>notifier cette erreur <i>au centre d'analyse</i> de MailCleaner afin que le filtre soit rendu plus tol&eacute;rant pour 
cet exp&eacute;diteur. Dans le langage de MailCleaner, cela s'appelle un <i>ajustement du filtre</i>.</li>
</ul> 
<p>Si vous avez des doutes sur la nature d'un message, il vous est bien entendu possible d'en voir le contenu 
avant de d&eacute;cider de sa lib&eacute;ration.</p>
<p class="note"><strong>Note :</strong> le centre d'analyse de MailCleaner, situ&eacute; au si&egrave;ge de l'&eacute;diteur, est compos&eacute; d'ing&eacute;nieurs 
sp&eacute;cialis&eacute;s dont le r&ocirc;le est de garantir en permanence une haute qualit&eacute; de filtrage, en fonction du 
trafic mondial du spam, de l'activit&eacute; des virus et des demandes d'ajustements effectu&eacute;es par les 
utilisateurs du monde entier. </p>
<h3>Voir le contenu d'un message </h3>
<ul>
 <li>Cliquez &agrave; choix sur la date, l'objet du message ou l'ic&ocirc;ne de pr&eacute;visualisation ;</li> 
 <li>le contenu du message s'affiche dans une nouvelle fen&ecirc;tre. </li>
</ul>
<h3>Lib&eacute;rer le message de la quarantaine </h3>
<ul>
<li>Cliquez sur l'ic&ocirc;ne de lib&eacute;ration, situ&eacute;e tout &agrave; gauche de la ligne du message ;</li>
<li>le message bloqu&eacute; est alors autoris&eacute; &agrave; atteindre votre messagerie.</li>
</ul>

<h3>Demander un ajustement du filtre</h3>
<ul>
 <li>Cliquez sur l'ic&ocirc;ne d'ajustement, situ&eacute;e tout &agrave; gauche de la ligne du message ;</li>
 <li>il vous est demand&eacute; de confirmer votre demande ;</li>
 <li>une demande d'ajustement du filtre est envoy&eacute;e au centre d'analyse avec une copie du message ;</li>
prises par le centre d'analyse de MailCleaner.</li>
</ul>
<p class="note"><strong>Note :</strong> l'utilisation de l'un de ces outils depuis un rapport de quarantaine provoquera l'ouverture d'une 
page ou d'un dialogue de confirmation dans votre navigateur Internet.</p>
<h2>Que faire si un spam n'a pas &eacute;t&eacute; bloqu&eacute;</h2>
<p>Lorsqu'un spam passe entre les mailles du filet et parvient dans votre bo&icirc;te aux lettres, cela signifie que 
les diff&eacute;rences qui le s&eacute;parent d'un message l&eacute;gitime sont tr&egrave;s fines. Dans un tel cas, MailCleaner 
achemine l'envoi, estimant qu'il est moins grave pour vous de recevoir un spam que d'&ecirc;tre priv&eacute; d'un 
message potentiellement important. </p>
<p>Si vous constatez qu'il s'agit bel et bien d'un spam, il est n&eacute;cessaire de demander un ajustement du filtre 
pour que les r&egrave;gles de d&eacute;tection soient renforc&eacute;es. </p>
<h3>Spam non bloqu&eacute; avec Microsoft Outlook</h3>
<p>Une extension (plug-in) peut &ecirc;tre ajout&eacute;e au logiciel de courrier Microsoft Outlook pour Windows afin de 
faciliter la notification d'un spam non filtr&eacute; depuis votre messagerie. Elle installe dans la barre de menu un 
bouton portant le logo MailCleaner et la mention "Ind&eacute;sirable". </p> 

<p>Pour notifier un spam avec l'extension MailCleaner :</p>
<ul>
 <li>s&eacute;lectionnez le spam dans la liste des messages ;</li>
 <li>cliquez sur le bouton Ind&eacute;sirable dans la barre d'outils ;</li>
 <li>une demande d'ajustement du filtre est envoy&eacute;e au centre d'analyse avec une copie du message ;</li>
 <li>supprimez ensuite le spam si vous le souhaitez ;</li>
</ul>
<p class="note"><strong>Note :</strong> si cette extension n'est pas pr&eacute;sente dans Outlook, demandez son installation &agrave; votre 
administrateur de messagerie ou consultez les instructions d'installation du pr&eacute;sent manuel.</p>
<h3>Spam non bloqu&eacute; avec un autre logiciel de messagerie</h3>
<p>Si vous n'utilisez pas Microsoft Outlook avec l'extension MailCleaner, une demande d'ajustement du filtre 
cons&eacute;cutive &agrave; un spam non filtr&eacute; suppose que vous redirigiez manuellement le message &agrave; l'adresse 
&eacute;lectronique du centre d'analyse de MailCleaner.</p>
<p><strong>Cette adresse ne peut vous &ecirc;tre indiqu&eacute;e dans ce document, car elle d&eacute;pend de la configuration 
effectu&eacute;e par votre administrateur de messagerie ou votre h&eacute;bergeur.</strong> Pour la conna&icirc;tre, consultez la 
section Aide de l'espace de gestion, puis la rubrique Demande d'ajustement du filtre. Notez cette adresse, 
car vous en aurez besoin dans les &eacute;tapes suivantes. </p>
<p>Pour notifier un spam manuellement :</p>
<ul>
 <li>s&eacute;lectionnez le spam dans la liste des messages ;</li>
 <li>redirigez le message au moyen de la fonction pr&eacute;vue &agrave; cet effet dans votre logiciel de messagerie ;</li>
 <li>indiquez comme destinaire l'adresse du centre d'analyse de MailCleaner que vous avez 
pr&eacute;c&eacute;demment not&eacute;e ;</li>
 <li>une demande d'ajustement du filtre est envoy&eacute;e avec une copie du message ;</li>
 <li>supprimez ensuite le spam si vous le souhaitez ;</li>
</ul>

<p class="important"><strong>Important :</strong> il ne faut surtout pas que vous fassiez suivre le spam par copier-coller, ce qui 
supprimerait l'en-t&ecirc;te long original, indispensable &agrave; l'analyse du message. Quel que soit votre logiciel 
de messagerie, sur PC comme sur Mac, acheminez toujours le message par la fonction Rediriger ou 
&eacute;quivalente.</>
<h2>Parfaire votre ma&icirc;trise de MailCleaner</h2>
<p>Une fois que vous aurez acquis ces quelques principes de base, vous aurez tr&egrave;s vite envie d'en savoir 
plus sur les capacit&eacute;s de personnalisation de MailCleaner.</p>
<p>Ce manuel saura en tout temps vous apporter les r&eacute;ponses que vous attendez.</p>
<p>Nous vous souhaitons une agr&eacute;able utilisation de MailCleaner.</p>
';
$htxt['MANUAL_FULL_NAME'] = 'mailcleaner_manuel_utilisateur.pdf';
$htxt['MANUAL_FIRSTCONTACT_NAME'] = 'mailcleaner_prise_en_main_rapide.pdf';
$htxt['MANUAL_GENERICCONCEPT_NAME'] = 'mailcleaner_principes_generaux.pdf';
$htxt['MANUAL_GUI_NAME'] = 'mailcleaner_espace_de_gestion.pdf';
$htxt['MANUAL_QUARANTINE_NAME'] = 'mailcleaner_section_quarantaine.pdf';
$htxt['MANUAL_STATS_NAME'] = 'mailcleaner_section_statistiques.pdf';
$htxt['MANUAL_CONFIGURATION_NAME'] = 'mailcleaner_section_configuration.pdf';
$htxt['MANUAL_ERRORS_NAME'] = 'mailcleaner_imprecisions_de_filtrage.pdf';
$htxt['USERMANUAL'] = '
<h1>Manuel utilisateur &ndash; Version 1.0 &ndash; Juin 2008</h1>
<h2>T&eacute;l&eacute;charger le manuel complet</h2>
<p class="download"><a href="__MANUAL_FULL_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_FULL_NAME__" /></a> <a href="__MANUAL_FULL_LINK__">T&eacute;l&eacute;charger</a> (__MANUAL_FULL_SIZE__)</p>
<h2>Consulter des chapitres sp&eacute;cifiques</h2>

<h3>Prise en main rapide</h3>
<p>Pour ma&icirc;triser en quelques minutes les fonctions de base.</p>
<p class="download"><a href="__MANUAL_FIRSTCONTACT_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_FIRSTCONTACT_NAME__" /></a> <a href="__MANUAL_FIRSTCONTACT_LINK__">T&eacute;l&eacute;charger le chapitre</a> (__MANUAL_FIRSTCONTACT_SIZE__)</p>

<h3>Principes g&eacute;n&eacute;raux</h3>
<p>Pour comprendre les strat&eacute;gies de pr&eacute;vention et de d&eacute;fense qui sont au c&oelig;ur de MailCleaner.</p>
<p class="download"><a href="__MANUAL_GENERICCONCEPT_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_GENERICCONCEPT_NAME__" /></a> <a href="__MANUAL_GENERICCONCEPT_LINK__">T&eacute;l&eacute;charger le chapitre</a>  (__MANUAL_GENERICCONCEPT_SIZE__)</p>

<h3>Espace de gestion</h3>
<p>Pour tout savoir sur l'espace de gestion au sein duquel vous naviguez actuellement.</p>
<p class="download"><a href="__MANUAL_GUI_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_GUI_NAME__" /></a> <a href="__MANUAL_GUI_LINK__">T&eacute;l&eacute;charger le chapitre</a> (__MANUAL_GUI_SIZE__)</p>

<h3>Quarantaine</h3>
<p>Pour apprendre &agrave; g&eacute;rer efficacement votre quarantaine.</p>
<p class="download"><a href="__MANUAL_QUARANTINE_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_QUARANTINE_NAME__" /></a> <a href="__MANUAL_QUARANTINE_LINK__">T&eacute;l&eacute;charger le chapitre</a> (__MANUAL_QUARANTINE_SIZE__)</p>

<h3>Statistiques</h3>
<p>Pour conna&icirc;tre la nature des messages que vous recevez.</p>
<p class="download"><a href="__MANUAL_STATS_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_STATS_NAME__" /></a> <a href="__MANUAL_STATS_LINK__">T&eacute;l&eacute;charger le chapitre</a> (__MANUAL_STATS_SIZE__)</p>

<h3>Configuration</h3>
<p>Pour personnaliser MailCleaner selon vos habitudes et vos besoins.</p>
<p class="download"><a href="__MANUAL_CONFIGURATION_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_CONFIGURATION_NAME__" /></a> <a href="__MANUAL_CONFIGURATION_LINK__">T&eacute;l&eacute;charger le chapitre</a> (__MANUAL_CONFIGURATION_SIZE__)</p>

<h3>Impr&eacute;cisions de filtrage</h3>
<p>Pour d&eacute;cider des mesures &agrave; prendre lorsqu'un message n'a pas &eacute;t&eacute; correctement filtr&eacute;.</p>
<p class="download"><a href="__MANUAL_ERRORS_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_ERRORS_NAME__" /></a> <a href="__MANUAL_ERRORS_LINK__">T&eacute;l&eacute;charger le chapitre</a> (__MANUAL_ERRORS_SIZE__)</p>
';
$htxt['FAQ'] = '
<h1>Des r&eacute;ponses simples et directes aux  interrogations les plus fr&eacute;quentes.</h1>
<h2>Espace de gestion</h2>
<h3>O&ugrave; puis-je trouver mon nom d'utilisateur et mon mot de passe ?</h3>
<p>Pour une adresse donn&eacute;e filtr&eacute;e par MailCleaner, votre nom d'utilisateur et votre mot de passe sont ceux que vous utilisez pour cette adresse dans votre logiciel de messagerie habituel.</p>
<h2>Spams et quarantaine</h2>
<h3>Qu'est-ce que la quarantaine ?</h3>
<p>Une zone d'isolation situ&eacute;e hors de votre messagerie qui bloque les messages identifi&eacute;s comme des spams.</p>
<h4>Demandes d'ajustement du filtre</h4>
<h3>Qu'est-ce qu'un ajustement du filtre ?</h3>
<p>Une action volontaire de votre part, cons&eacute;cutive au blocage d'un message l&eacute;gitime dans la quarantaine ou 
&agrave; l'arriv&eacute;e d'un spam dans votre bo&icirc;te. Dans le premier cas, l'ajustement du filtre permet &agrave; MailCleaner 
d'&ecirc;tre plus tol&eacute;rant avec un exp&eacute;diteur particulier. Dans le second, MailCleaner sera rendu plus agressif.</p>
<h3>Que se passe-t-il lors d'une demande d'ajustement de filtre ?</h3>
<p>Une copie du message est envoy&eacute;e au centre d'analyse de MailCleaner. Apr&egrave;s examen, une correction du 
filtre est &eacute;ventuellement effectu&eacute;e par les ing&eacute;nieurs. </p>
<h4>Messages bloqu&eacute;s en quarantaine</h4>
<h3>Un message l&eacute;gitime a &eacute;t&eacute; bloqu&eacute; par erreur en quarantaine. Que dois-je faire ?</h3>
<p>Vous devez le lib&eacute;rer pour l'autoriser &agrave; atteindre votre messagerie puis effectuer une demande d'ajustement du filtre depuis la quarantaine.</p>
<h3>Pourquoi MailCleaner bloque-t-il un message qui aurait d&ucirc; me parvenir ?</h3>
<p>Parce que le message en question a transit&eacute; par un serveur d'envoi dont la r&eacute;putation est provisoirement 
douteuse (il a peut-&ecirc;tre &eacute;t&eacute; utilis&eacute; comme relais de spams par des pirates) et/ou comporte un formatage 
sp&eacute;cifique qui ont d&eacute;clench&eacute; une r&egrave;gle de spam. Il ne s'agit donc pas d'une erreur de MailCleaner, mais 
d'une r&eacute;action prudente du filtre face &agrave; des caract&eacute;ristiques sp&eacute;cifiques d'un message, lesquelles peuvent 
ne pas &ecirc;tre discernables &agrave; travers la simple lecture du contenu.</p>
<h3>Comment lib&eacute;rer un message ?</h3>
<p>Cliquez sur l'ic&ocirc;ne identifi&eacute;e par une fl&egrave;che, situ&eacute;e sur la ligne correspondante du message, dans un 
rapport de quarantaine ou au sein de l'espace de gestion.</p>
<h3>J'ai lib&eacute;r&eacute; un message, mais il appara&icirc;t toujours dans la quarantaine. Est-ce normal ?</h3>
<p>Un message lib&eacute;r&eacute; demeure dans la quarantaine afin que vous puissiez &eacute;ventuellement le lib&eacute;rer &agrave; 
nouveau. Il appara&icirc;t toutefois en italiques afin de vous indiquer qu'il a d&eacute;j&agrave; &eacute;t&eacute; lib&eacute;r&eacute;.</p>
<h3>Comment ne plus recevoir de rapports de quarantaine ?</h3>
<p>Vous pouvez modifier la p&eacute;riodicit&eacute; des rapports ou supprimer leur envoi dans la section Configuration de 
l'espace de gestion. Pour consulter votre quarantaine, il vous sera alors &agrave; chaque fois n&eacute;cessaire 
d'acc&eacute;der &agrave; l'espace de gestion.</p>
<h4>Spams non retenus</h4>
<h3>Un spam n'a pas &eacute;t&eacute; filtr&eacute;. Que dois-je faire ?</h3>
<p>Vous devez effectuer une demande d'ajustement du filtre depuis votre messagerie, afin que les r&egrave;gles de 
filtrage soient renforc&eacute;es.</p>
<h3>Pourquoi MailCleaner laisse-t-il passer des spams ?</h3>
<p>Certains spams passent entre les mailles du filet parce qu'aucune analyse math&eacute;matique n'a su mettre en 
&eacute;vidence une diff&eacute;rence avec un message l&eacute;gitime. C'est la raison pour laquelle vous devez 
imp&eacute;rativement d&eacute;clarer cette erreur au centre d'analyse afin que certaines r&egrave;gles puissent &ecirc;tre 
renforc&eacute;es. A noter qu'en cas de doute, MailCleaner achemine le message, car il est moins grave pour 
vous de recevoir un spam que d'&ecirc;tre priv&eacute; d'un message l&eacute;gitime.</p>
<h2>Virus et messages dangereux</h2>
<h3>Comment MailCleaner traite-t-il les virus ?</h3>
<p>Les virus sont &eacute;radiqu&eacute;s. Aucune notification ne vous est transmise.</p>
<h3>Qu'est-ce qu'un contenu dangereux ?</h3>
<p>Des informations que votre administrateur de messagerie a pr&eacute;ventivement voulu filtrer, par exemple des 
scripts ex&eacute;cutables (.exe) en pi&egrave;ces jointes ou des liens vers des sites Internet suspects. Un message qui 
en contient est achemin&eacute; expurg&eacute; des &eacute;l&eacute;ments dangereux. Ils sont remplac&eacute;s par une notice qui vous 
indique comment demander &agrave; votre administrateur de vous faire parvenir le message dans son int&eacute;gralit&eacute;.</p>
<h3>Comment reconna&icirc;tre un message avec contenu dangereux ?</h3>
<p>Il comporte un mot-cl&eacute; dans son objet  &ndash; usuellement "{CONTENU DANGEREUX}"  &ndash; et des instructions 
de lib&eacute;ration en pi&egrave;ce jointe.</p>
<h3>Comment demander &agrave; mon administrateur de me faire suivre les contenus dangereux ?</h3>
<p>Suivez les instructions attach&eacute;es en pi&egrave;ce jointe. Vous devez indiquer &agrave; votre administrateur l'identifiant 
num&eacute;rique du message bloqu&eacute;. Si votre administrateur juge que les pi&egrave;ces jointes posent v&eacute;ritablement 
un danger, ce qui est vrai la plupart du temps, il peut refuser de vous les transmettre.</p>
';
$htxt['GLOSSARY'] = '
<h1>Les d&eacute;finitions des mots les plus couramment utilis&eacute;s.</h1>
<h3>Authentification</h3>
<p>Processus qui consiste &agrave; v&eacute;rifier qu'une personne est bien celle qu'elle pr&eacute;tend &ecirc;tre. Au sein de
MailCleaner, une authentification r&eacute;ussie autorise un utilisateur &agrave; acc&eacute;der &agrave; sa quarantaine.</p>
<h3>Centre d'analyse</h3>
<p>Equipe d'ing&eacute;nieurs sp&eacute;cialis&eacute;s, travaillant au si&egrave;ge de l'&eacute;diteur de MailCleaner, dont le r&ocirc;le est de 
garantir en permanence une haute qualit&eacute; de filtrage, en fonction du trafic mondial de spams, de 
l'activit&eacute; des virus et des demandes d'ajustements effectu&eacute;es par les utilisateurs du monde entier.</p>
<h3>Contenu dangereux</h3>
<p>Information suspecte contenue dans un message, pr&eacute;ventivement filtr&eacute;es par votre administrateur de 
messagerie ou votre h&eacute;bergeur.</p>
<h3>Demande d'ajustement du filtre</h3>
<p>Action volontaire cons&eacute;cutive au blocage d'un message l&eacute;gitime dans la quarantaine ou &agrave; l'arriv&eacute;e d'un 
spam dans votre bo&icirc;te. Dans le premier cas, l'ajustement du filtre permet &agrave; MailCleaner d'&ecirc;tre plus 
tol&eacute;rant avec un exp&eacute;diteur particulier. Dans le second, MailCleaner sera rendu plus agressif. C'est <em>le 
centre d'analyse</em> de MailCleaner qui traite les demandes.</p>
<h3>Domaines surveill&eacute;s</h3>
<p>Ensemble des domaines Internet examin&eacute;s par le m&ecirc;me dispositif MailCleaner (exemples : @durand.com, @dupont.com).</p>
<h3>Dur&eacute;e de r&eacute;tention</h3>
<p>Dur&eacute;e pendant laquelle un message retenu en quarantaine demeure consultable. Au terme de cette 
p&eacute;riode, il est automatiquement supprim&eacute;.</p>
<h3>Espace de gestion</h3>
<p>Zone Internet privative dans laquelle vous pouvez consulter les messages retenus en quarantaine et 
proc&eacute;der &agrave; la configuration personnalis&eacute;e de param&egrave;tres.</p>
<h3>Fastnet SA</h3>
<p>Gentil &eacute;diteur de MailCleaner. Tout le contraire des spammeurs. Si&egrave;ge &agrave; St-Sulpice, Suisse.</p>
<h3>Faux-n&eacute;gatif</h3>
<p>Spam n'ayant pas &eacute;t&eacute; analys&eacute; comme tel par le filtre. Tout faux-n&eacute;gatif doit &ecirc;tre notifi&eacute; au <em>centre 
d'analyse</em> afin que le filtre puisse tenir compte de cette exception dans le futur.</p>
<h3>Faux-positif</h3>
<p>Message l&eacute;gitime consid&eacute;r&eacute; comme un spam par MailCleaner. Tout faux-positif doit faire l'objet d'une 
<em>demande d'ajustement du filtre</em>.</p>
<h3>H&eacute;bergeur</h3>
<p>Entreprise qui propose des services de connexion Internet et de messagerie &eacute;lectronique. En anglais : 
ISP pour Internet Service Provider.</p>
<h3>Lib&eacute;rer un message</h3>
<p>Action volontaire qui autorise un message retenu en quarantaine &agrave; poursuivre son chemin vers votre 
messagerie.</p>
<h3>Liste blanche</h3>
<p>Liste comportant des adresses d'exp&eacute;diteurs de confiance, pour lesquelles aucune retenue en 
quarantaine ne sera effectu&eacute;e. En anglais : White List.</p>
<h3>Liste d'avertissement</h3>
<p>Liste comportant des adresses d'exp&eacute;diteurs de confiance, pour lesquelles tout message bloqu&eacute; en 
quarantaine vous sera notifi&eacute; par un avertissement. En anglais : Warn List.</p>
<h3>Plug-in</h3>
<p>Nom donn&eacute; en anglais &agrave; une extension &agrave; installer dans une application. L'extension MailCleaner pour 
Microsoft Oulook simplifie la notification des faux-n&eacute;gatifs.</p>
<h3>Quarantaine</h3>
<p>Zone d'isolation qui retient hors de votre messagerie les messages consid&eacute;r&eacute;s comme des spams.</p>
<h3>Rapport de quarantaine</h3>
<p>Rapport p&eacute;riodique envoy&eacute; automatiquement qui liste les messages retenus en quarantaine et dispose 
d'outils de consultation et de lib&eacute;ration.</p>
<h3>RBL</h3>
<p>Realtime Blackhole List. Les RBLs ont pour mission de maintenir en temps r&eacute;el une liste des serveurs 
r&eacute;put&eacute;s pour envoyer du spam. Le principe d'utilisation est tr&egrave;s simple : si le message re&ccedil;u provient 
d'un tel serveur, il sera consid&eacute;r&eacute;, a priori, comme un spam. La difficult&eacute; avec l'utilisation des RBL est 
de v&eacute;rifier en permanence leur fiabilit&eacute;.</p>
<h3>R&egrave;gle de filtrage</h3> 
<p>Examen math&eacute;matique et statistique portant sur les caract&eacute;ristiques d'un message et permettant de 
savoir s'il s'agit d'un spam.</p>
<h3>Score</h3>
<p>Indicateur de quarantaine qui apporte une &eacute;valuation chiffr&eacute;e et pond&eacute;r&eacute;e des indices de spam.</p>
<h3>SMTP</h3>
<p>Simple Mail Transfer Protocol. Protocole utilis&eacute; pour envoyer du courrier &eacute;lectronique.</p>
<h3>Spam</h3>
<p>Message non souhait&eacute; par l'utilisateur mais qui ne comporte aucun contenu dangereux. Appel&eacute; parfois 
"pourriel".</p>
<h3>Suisse</h3>
<p>Pays d'origine de MailCleaner. Les spams sont &eacute;radiqu&eacute;s avec une qualit&eacute; et une pr&eacute;cision horlog&egrave;re.</p>
<h3>Virus</h3>
<p>El&eacute;ment logiciel intrusif port&eacute; en annexe d'un message, susceptible d'alt&eacute;rer l'int&eacute;grit&eacute; de votre 
ordinateur.</p>
<h3>Waow.</h3>
<p>Ce que nous esp&eacute;rons que vous direz.</p>
';
$htxt['PLUGIN'] = '<h1>Plus supporté.</h1>';
$htxt['ANALYSE'] = '
<h1>Demande d'ajustement du filtre</h1>
<h6>Si vous n'utilisez pas Microsoft Outlook avec __LINKHELP_plugin__l'extension MailCleaner__LINK__, la signalisation d'un spam non filtr&eacute; demande que vous redirigiez manuellement le message au centre d'analyse de MailCleaner.</h6>
<p>Le spam y sera examin&eacute; afin que les mesures de filtrage puissent &ecirc;tre renforc&eacute;es.</p>
<h2>Adresse pour le signalement d'un spam</h2>
<p>L'adresse d'envoi pour signaler un spam est :</p><p>__SPAM_EMAIL__</p>
<h2>Comment signaler un spam non filtr&eacute; (faux n&eacute;gatif)</h2>
<p>Pour signaler un spam, vous devez faire suivre le message &agrave; l'adresse mentionn&eacute;e ci-dessus en utilisant imp&eacute;rativement la fonction  "Rediriger" &ndash; ou &eacute;quivalente &ndash; de votre logiciel de messagerie.</p>
<p>Il ne faut surtout pas proc&eacute;der par "copier-coller", ce qui supprimerait l'en-t&ecirc;te long original, indispensable &agrave; l'analyse du message.</p>
<h3>Signalisation des faux n&eacute;gatifs avec Netscape, Mozilla, Thunderbird</h3>
<ul>
 <li>S&eacute;lectionnez le spam dans la liste des messages ;</li>
 <li>choisissez le menu <em>Message</em>, puis le sous-menu <em>Forward as...</em>, puis l'article <em>Attachement</em> ;</li>
 <li>indiquez comme destinataire l'adresse du <em>centre d'analyse</em> de MailCleaner pr&eacute;c&eacute;demment indiqu&eacute;e ;</li>
 <li>une <em>demande d'ajustement du filtre</em> est envoy&eacute;e avec une copie du message ;</li>
 <li>supprimez ensuite le spam si vous le souhaitez ;</li>
 <li>vous ne recevrez pas de confirmation, mais il sera tenu compte de votre notification dans les processus permanents de correction du filtre.</li>
</ul>
<h3>Signalisation des faux n&eacute;gatifs avec Microsoft Entourage (ordinateurs Apple)</h3>
<ul>
 <li>S&eacute;lectionnez le spam dans la liste des messages ;</li>
 <li>choisissez le menu <em>Message</em>, puis l'article <em>R&eacute;acheminer</em> ;</li>
 <li>indiquez comme destinataire l'adresse du <em>centre d'analyse</em> de MailCleaner pr&eacute;c&eacute;demment indiqu&eacute;e ;</li> 
 <li>une <em>demande d'ajustement du filtre</em> est envoy&eacute;e avec une copie du message ;</li>
 <li>supprimez ensuite le spam si vous le souhaitez ;</li>
 <li>vous ne recevrez pas de confirmation, mais il sera tenu compte de votre notification dans les processus permanents de correction du filre.</li>
</ul>
<h3>Signalisation des faux n&eacute;gatifs avec Mail (ordinateurs Apple)</h3>
<ul>
 <li>S&eacute;lectionnez le spam dans la liste des messages ;</li>
 <li>choisissez le menu <em>Message</em>, puis l'article <em>R&eacute;acheminer en tant que pi&egrave;ce jointe</em> ;</li>
 <li>indiquez comme destinataire l'adresse du <em>centre d'analyse</em> de MailCleaner pr&eacute;c&eacute;demment indiqu&eacute;e ;</li>
 <li>une <em>demande d'ajustement du filtre</em> est envoy&eacute;e avec une copie du message ;</li>
 <li>supprimez ensuite le spam si vous le souhaitez ;</li>
 <li>vous ne recevrez pas de confirmation, mais il sera tenu compte de votre notification dans les processus permanents de correction du filtre.</li>
</ul>
<h2>A voir aussi dans le manuel utilisateur</h2>
<h3>Impr&eacute;cisions de filtrage</h3>
<p>Pour d&eacute;cider des mesures &agrave; prendre lorsqu'un message n'a pas &eacute;t&eacute; correctement filtr&eacute;.</p>
<p><a href="__MANUAL_ERRORS_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_ERRORS_NAME__" /></a> <a href="__MANUAL_ERRORS_LINK__">T&eacute;l&eacute;charger le chapitre</a> (__MANUAL_ERRORS_SIZE__)</p>
';
$htxt['SUPPORT'] = '
<h1>Support et assistance</h1>
<h6>Notre service de support et notre service commercial sont &agrave; votre disposition pendant les heures de bureau, du lundi au vendredi.</h6>
<h2>En cas de probl&egrave;me</h2>
<p>__SUPPORT_EMAIL__</p>
<p>Avant de prendre contact avec le service de support, nous vous prions de vous assurer que votre probl&egrave;me n'est pas trait&eacute; dans le __LINKHELP_usermanual__manuel de l'utilisateur__LINK__ 
ou dans la section des __LINKHELP_faq__questions fr&eacute;quentes__LINK__.</p>
<h2>Pour toute question commerciale</h2>
<p>__SALES_EMAIL__</p>
';
