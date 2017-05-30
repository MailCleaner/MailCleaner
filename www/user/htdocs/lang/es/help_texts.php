<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
$htxt['INTRODUCTION'] = '
<h1>Bienvenido en el mundo de los mensajes que realmente quiere recibir.</h1>
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

$htxt['MANUAL_FULL_NAME'] = 'mailcleaner_manual_de_usuario.pdf';
$htxt['MANUAL_FIRSTCONTACT_NAME'] = 'mailcleaner_guia_rapida.pdf';
$htxt['MANUAL_GENERICCONCEPT_NAME'] = 'mailcleaner_principios_generales.pdf';
$htxt['MANUAL_GUI_NAME'] = 'mailcleaner_espacio_de_gestion.pdf';
$htxt['MANUAL_QUARANTINE_NAME'] = 'mailcleaner_parte_cuarentena.pdf';
$htxt['MANUAL_STATS_NAME'] = 'mailcleaner_parte_estaditicas.pdf';
$htxt['MANUAL_CONFIGURATION_NAME'] = 'mailcleaner_parte_configuracion.pdf';
$htxt['MANUAL_ERRORS_NAME'] = 'mailcleaner_imprecisiones_de_filtracion.pdf';

$htxt['USERMANUAL'] = '
<h1>Manual de usuario &ndash; Versi&oacute;n 1.0 &ndash; Junio de 2008</h1>
<h2>Descargar el manual entero</h2>
<p class="download"><a href="__MANUAL_FULL_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_FULL_NAME__" /></a> <a href="__MANUAL_FULL_LINK__">Descargar</a> (__MANUAL_FULL_SIZE__)</p>
<h2>Consultar cap&iacute;tulos espec&iacute;ficos</h2>

<h3>Gu&iacute;a r&aacute;pida</h3>
<p>Dominar en algunos minutos las funciones de base.</p>
<p class="download"><a href="__MANUAL_FIRSTCONTACT_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_FIRSTCONTACT_NAME__" /></a> <a href="__MANUAL_FIRSTCONTACT_LINK__">Descargar el cap&iacute;tulo</a> (__MANUAL_FIRSTCONTACT_SIZE__)</p>

<h3>Principios generales</h3>
<p>Entender las estrategias de prevenci&oacute;n y de defensa que utiliza MailCleaner.</p>
<p class="download"><a href="__MANUAL_GENERICCONCEPT_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_GENERICCONCEPT_NAME__" /></a> <a href="__MANUAL_GENERICCONCEPT_LINK__">Descargar el cap&iacute;tulo</a> (__MANUAL_GENERICCONCEPT_SIZE__)</p>

<h3>Espacio de gesti&oacute;n</h3>
<p>Para conocer todo del espacio de gesti&oacute;n en el que est&aacute; navigando.</p>
<p class="download"><a href="__MANUAL_GUI_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_GUI_NAME__" /></a> <a href="__MANUAL_GUI_LINK__">Descargar el cap&iacute;tulo</a> (__MANUAL_GUI_SIZE__)</p>

<h3>Cuarentena</h3>
<p>Para aprender como utilizar su cuarentena de manera eficaz.</p>
<p class="download"><a href="__MANUAL_QUARANTINE_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_QUARANTINE_NAME__" /></a> <a href="__MANUAL_QUARANTINE_LINK__">Descargar el cap&iacute;tulo</a> (__MANUAL_QUARANTINE_SIZE__)</p>

<h3>Estad&iacute;sticas</h3>
<p>Para conocer la naturaleza de los mensajes que recibe.</p>
<p class="download"><a href="__MANUAL_STATS_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_STATS_NAME__" /></a> <a href="__MANUAL_STATS_LINK__">Descargar el cap&iacute;tulo</a> (__MANUAL_STATS_SIZE__)</p>

<h3>Configuraci&oacute;n</h3>
<p>Para personalizar MailCleaner seg&uacute;n sus costumbres y sus necesidades.</p>
<p class="download"><a href="__MANUAL_CONFIGURATION_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_CONFIGURATION_NAME__" /></a> <a href="__MANUAL_CONFIGURATION_LINK__">Descargar el cap&iacute;tulo</a> (__MANUAL_CONFIGURATION_SIZE__)</p>

<h3>Imprecisiones de filtraci&oacute;n</h3>
<p>Para determinar las medidas que tomar cuando un mensaje no fue filtrado correctamente.</p>
<p class="download"><a href="__MANUAL_ERRORS_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_ERRORS_NAME__" /></a> <a href="__MANUAL_ERRORS_LINK__">Descargar el cap&iacute;tulo</a> (__MANUAL_ERRORS_SIZE__)</p>
';

$htxt['FAQ'] = '

<h1>Respuestas simples y directas a las preguntas las m&aacute;s frecuentes.</h1>
<h2>Espacio de gesti&oacute;n</h2>
<h3>&iquest;D&oacute;nde puedo encontrar mi nombre de usuario y mi contrase&ntilde;a?</h3>
<p>Su nombre de usuario y su contrase&ntilde;a son los mismos que utiliza para conectarle a su correo electr&oacute;nico habitual.</p>
<h2>Spam y cuarentena</h2>
<h3>&iquest;Cu&aacute;l es la cuarentena?</h3>
<p>Una zona de aislamiento situada fuera de su correo electr&oacute;nico que bloquea los spams.</p>
<h4>Ajustes del filtro</h4>
<h3>&iquest;Cu&aacute;l es un ajuste del filtro?</h3>
<p>Es una acci&oacute;n que realiza despu&eacute;s del bloqueo de un mensaje leg&iacute;timo en la cuarentena o de la llegada de un spam en su buz&oacute;n. En el primer caso, el ajuste del filtro permite a MailCleaner ser m&aacute;s tolerante con un remitente en particular. En el segundo caso, el filtro de MailCleaner ser&aacute; m&aacute;s agresivo.</p>
<h3>&iquest;Qu&eacute; pasa durante una solicitud de ajuste del filtro?</h3>
<p>El <em>centro de an&aacute;lisis de MailCleaner</em> recibe una copia del mensaje. Despues de un examen, es posible que los ingenieros corrijan el filtro. 
Recibe los cambios efectuados por el centro de an&aacute;lisis en un plazo de un d&iacute;a laborable.</p>
<h4>Mensajes bloqueados en cuarentena</h4>
<h3>Un mensaje leg&iacute;timo queda bloqueado en la cuarentena. &iquest;Qu&eacute; tengo que hacer?</h3>
<p>Tiene que liberarlo para que pueda alcanzar su correo  electr&oacute;nico y efectuar una solicitud de ajuste del filtro desde la cuarentena.</p>
<h3>&iquest;Por qu&eacute; MailCleaner bloquea un mensaje que habr&iacute;a debido recibir?</h3>
<p>Porque este mensaje tiene or&iacute;genes dudosas (quiz&aacute;s sirve de relevo de spam por los piratas) y/o comporta un formato espec&iacute;fico que activa una regla de spam. Entonces no trata de un error de MailCleaner pero el filtro act&uacute;a de manera prudente frente a caracter&iacute;sticas espec&iacute;ficas de un mensaje, que pueden ser invisibles con una lectura simple del contenido.</p>
<h3>&iquest;C&oacute;mo liberar un mensaje?</h3>
<p>Haga clic en el icono con una flecha, situada en la l&iacute;nea que corresponde al mensaje, en un informe de cuarentena o en el seno del espacio de gesti&oacute;n.</p>
<h3>Liber&oacute; un mensaje pero siempre aparece en cuarentena. &iquest;Es normal?</h3>
<p>Un mensaje liberado queda en la cuarentena para que pueda lib&eacute;ralo de nuevo si es necesario. Sin embargo, aparece en cursiva para indicarle que ya fue liberado.</p>
<h3>&iquest;C&oacute;mo dejar de recibir informes de cuarentena?</h3>
<p>Puede modificar la frecuencia de recepci&oacute;n de los informes o suprimir su env&iacute;o en la parte Configuraci&oacute;n del espacio de gesti&oacute;n.</p>
<h4>Spams no detenidos</h4>
<h3>Un spam no fue filtrado. &iquest;Qu&eacute; tengo que hacer?</h3>
<p>Tiene que efectuar una solicitud de ajuste del filtro desde su correo electr&oacute;nico, para que las reglas de filtraci&oacute;n sean intensificadas.</p>
<h3>&iquest;Por qu&eacute; MailCleaner deja pasar spams?</h3>
<p>Algunos escapan a los controles de MailCleaner porque est&aacute;n muy pr&oacute;ximos de mensajes leg&iacute;timos. Es por esta raz&oacute;n que tiene que declarar este error al centro de an&aacute;lisis para intensificar algunas reglas. Sin olvidar que si tiene dudas, MailCleaner le env&iacute;a el mensaje porque estima que es menos grave recibir un spam que ser privado de un mensaje leg&iacute;timo.</p>
<h2>Virus et mensajes peligrosos</h2>
<h3>&iquest;C&oacute;mo MailCleaner trata los virus?</h3>
<p>MailCleaner suprime los virus. No recibe notificaci&oacute;n.</p>
<h3>&iquest;Cu&aacute;l es un contenido peligroso?</h3>
<p>Son informaciones que su administrador de correo electr&oacute;nico prefiere filtrar, por ejemplo Script ejecutables (.exe) en archivo adjunto o link hacia p&aacute;ginas web sospechosas. Si un mensaje contiene contenidos peligrosos, lo recibe sin estos contenidos. En este mensaje aparece une noticia que le explica como recibir el mensaje en conjunto.</p>
<h3>&iquest;C&oacute;mo reconocer un mensaje con contenido peligroso?</h3>
<p>Comporta una palabra clave (keyword) en el asunto &ndash; generalmente" {CONTENIDO PELIGROSO}" &ndash; e instrucciones de liberaci&oacute;n en archivo adjunto.</p>
<h3>&iquest;C&oacute;mo pedir a mi administrador que me env&iacute;e los contenidos peligrosos?</h3>
<p>Sigue las instrucciones en archivo adjunto. Tiene que indicar al administrador el identificador num&eacute;rico del mensaje bloqueado. Es posible que su administrador no le env&iacute;e los archivos adjuntos porque piensa que representen un verdadero peligro.</p>
';

$htxt['GLOSSARY'] = '
<h1>Definiciones de las palabras las m&aacute;s utilizadas.</h1>
<h3>Alojamiento Web</h3>
<p>Empresa que propone servicios de conexi&oacute;n a la Red y de correo electr&oacute;nico. En Ingl&eacute;s: ISP para Internet Service Provider.</p>
<h3>Centro de an&aacute;lisis</h3>
<p>Equipo de ingenieros especializados que trabajan en la sede del editor de MailCleaner, que garantiza en permanencia una alta calidad de filtraci&oacute;n, en funci&oacute;n del tr&aacute;fico mundial de los spams, de la actividad de los virus y de las solicitudes de ajuste efectuadas por les usuarios en el mundo entero.</p>
<h3>Complemento (plug-in)</h3>
<p>M&aacute;s conocido en ingl&eacute;s. Tiene que instalar el complemento es decir el plug-in en una aplicaci&oacute;n. El complemento MailCleaner para Microsolft Outlook simplifica la notificaci&oacute;n de los falsos-negativos.</p>
<h3>Contenido peligroso</h3> 
<p>Informaci&oacute;n sospechosa contenida en un mensaje, filtrada previamente por su administrador de correo electr&oacute;nico o su alojamiento Web.</p>
<h3>Cuarentena</h3>
<p>Zona de aislamiento de detiene fuera de sus correo electr&oacute;nico los spams.</p>
<h3>Dominio de Internet vigilado</h3>
<p>Conjunto de los dominios de Internet examinados por el mismo dispositivo MailCleaner (ejemplos: @martinez.com, @rodriguez.com).</p>
<h3>Espacio de gesti&oacute;n</h3>
<p>Zona Internet privada donde se sit&uacute;an los mensajes en cuarentena y donde puede configurar y personalizar los par&aacute;metros.</p>
<h3>Falso-negativo</h3>
<p>Mensaje que no fue analizado como un spam por MailCleaner. Tiene que notificar cada falso-negativo al centro de an&aacute;lisis para que el filtro pueda considerar esta excepci&oacute;n en el futuro.</p>
<h3>Falso-positivo</h3>
<p>Mensaje leg&iacute;timo que MailCleaner considera como un spam. Cada falso-positivo necesita una solicitud de ajuste del filtro.</p>
<h3>Fastnet SA</h3>
<p>Editor de MailCleaner. Es lo contrario de los spams. Su sede est&aacute; en St-Sulpice, en Suiza.</p>
<h3>Identificaci&oacute;n</h3>
<p>Proceso que sirve para verificar la identidad de una persona. En MailCleaner, con una buena identificaci&oacute;n, puede acceder a su cuarentena.</p>
<h3>Informe de cuarentena</h3>
<p>Informe peri&oacute;dico enviado de manera autom&aacute;tica que le da los mensajes detenidos en cuarentena y que dispone de herramientas de consultaci&oacute;n y de liberaci&oacute;n.</p>
<h3>Liberar un mensaje</h3>
<p>Autorizar un mensaje a quitar la cuarentena para llegar en su correo electr&oacute;nico.</p>
<h3>Lista blanca</h3>
<p>Lista que contiene los remitentes de confianza, que no sufren de la cuarentena. En ingl&eacute;s: White List.</p>
<h3>Lista de alarma</h3>
<p>Lista que contiene los remitentes de confianza. Recibe una notificaci&oacute;n cuando un mensaje est&aacute; en la cuarentena. En ingl&eacute;s: Warm list.</p>
<h3>Periodo de retenci&oacute;n</h3>
<p>Es el periodo de retenci&oacute;n de los mensajes en la cuarentena. Al final de este periodo, suprimimos los mensajes de manera autom&aacute;tica.</p>
<h3>RBL </h3>
<p>Realtime Blackhole List. Los RBLs tienen que mantener una lista de los servidores que tienen la reputaci&oacute;n de enviar spams. El principio de uso es muy simple: si el mensaje recibido viene de uno de estos servidores, es considerado como un spam. La dificultad del uso de los RBLs es que siempre hace falta verificar su fiabilidad.</p>
<h3>Regla de filtraci&oacute;n</h3>
<p>Examen matem&aacute;tico y estad&iacute;stico que permite saber si un mensaje es un spam o no.</p>
<h3>Resultado</h3>
<p>Indicador de cuarentena que le trae una evaluaci&oacute;n cifrada y ponderada de los &iacute;ndices de spam.</p>
<h3>SMTP</h3>
<p>Simple Mail Transfer Protocol. Protocolo utilizado para enviar un correo electr&oacute;nico.</p>
<h3>Spam</h3>
<p>Mensaje que el usuario no desea pero que no contiene ning&uacute;n contenido peligroso. A veces se llama "correo basura."</p>
<h3>Solicitud de ajuste del filtro</h3>
<p>Acci&oacute;n voluntaria consecutiva a un bloqueo de un mensaje leg&iacute;timo en la cuarentena o a la llegada de un spam en su buz&oacute;n.  En el primer caso, el ajuste del filtro permite a MailCleaner ser m&aacute;s tolerante con un remitente en particular. En el segundo caso, MailCleaner ser&aacute; m&aacute;s agresivo. Es el centro de an&aacute;lisis de MailCLeaner que trata las solicitudes.</p>
<h3>Suiza</h3>
<p>Pa&iacute;s de origen de MailCleaner. Elimina los spams con una calidad y una precisi&oacute;n extrema.</p>
<h3>Virus</h3>
<p>Elemento software intrusivo adjunto a un mansaje, capaz de alterar la integridad de su ordenador.</p>
';

$htxt['PLUGIN'] = '
<h1>Para controlar de manera eficaz los spams no filtrados desde su correo electr&oacute;nico.</h1>
<h6>Puede a&ntilde;adir un complemento (plug-in) al software de correo electr&oacute;nico Microsoft Outlook para Windows para facilitar la se&ntilde;alizaci&oacute;n de un spam no filtrado desde su correo electr&oacute;nico hasta el centro de an&aacute;lisis de MailCleaner. Instala en la barra de men&uacute; un bot&oacute;n que tiene el logotipo de MailCleaner y la palabra "indeseable".</h6>
<p class="note"><strong>Nota:</strong> es posible que su administrador de sistema proh&iacute;ba la instalaci&oacute;n de  complementos y de componentes Outlook en su ordenador. Llegado el caso, entra en contacto con &eacute;l.</p>
<h2>Descargar el complemwento MailCleaner para Outlook</h2>
<p>Para Microsoft Outlook 2003: <a href="__PLUGIN_OU2003_LINK__">Descargar</a>
 (Versi&oacute;n 1.0.3 &ndash; __PLUGIN_OU2003_SIZE__) </p>
<p>Para Microsoft Outlook 2007: <a href="__PLUGIN_OU2007_LINK__">Descargar</a>
 (Versi&oacute;n 1.0.3 &ndash; __PLUGIN_OU2007_SIZE__)</p>
<h2>Instalaci&oacute;n del complemento MailCleaner para Outlook</h2>
<p>Para instalar el complemento para Outlook de Windows:</p>
<ul>
<li>Descargue la &uacute;ltima versi&oacute;n desde la secci&oacute;n Ayuda del espacio de gesti&oacute;n</li>
<li>Salga de Outlook si esta aplicaci&oacute;n es activa</li>
<li>Haga un doble clic en el instalador</li>
<li>Siga las instrucciones</li>
<li>Un mensaje de confirmaci&oacute;n le indica el fin de la instalaci&oacute;n</li>
<li>Reinicialice su ordenador y reactiva Outlook</li>
</ul>
<p>Un nuevo bot&oacute;n aparece en la barra de herramientas.</p>
<h2>Gesti&oacute;n de los falsos-negativos con Outlook para Microsoft</h2>
<ul>
<li>Seleccione el spam en la lista de los mensajes</li>
<li>Haga clic en Indeseable</li>
<li>Enviamos una solicitud de ajuste del filtro al centro de an&aacute;lisis con una copia del mensaje</li>
<li>Suprima el spam si lo quiere</li>
</ul>
<p>No recibir&aacute; confirmaci&oacute;n, pero consideramos su notificaci&oacute;n en los procesos permanentes de correcci&oacute;n del filtro.</p>
<h2>ver tambi&eacute;n en el manual de usuario</h2>
<h3>Imprecisiones de filtraci&oacute;n</h3>
<p>Para determinar las medidas que tomar cuando un mensaje no fue filtrado correctamente.</p>
<p><a href="__MANUAL_ERRORS_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_ERRORS_NAME__" /></a> <a href="__MANUAL_ERRORS_LINK__">Descargar el cap&iacute;tulo</a> (__MANUAL_ERRORS_SIZE__)</p>
';

$htxt['ANALYSE'] = '
<h1>Solicitud de ajuste del filtro</h1>
<h6>Si no utiliza Microsoft Outlook con el complemento __LINKHELP_plugin__MailCleaner extension__LINK__, tiene que redactar manualmente un correo al centro de an&aacute;lisis de MailCleaner si un spam no fue filtrado.</h6>
<p>El spam es examinado para mejorar la filtraci&oacute;n.</p>
<h2>Correo electr&oacute;nico para se&ntilde;alar un spam</h2>
<p>El correo electr&oacute;nico para se&ntilde;alar un spam es:</p><p>__SPAM_EMAIL__</p>
<h2>&iquest;C&oacute;mo se&ntilde;alar un spam que escap&oacute; al control de MailCleaner? (falso-negativo)</h2>
<p>Para se&ntilde;alar un spam, tiene que enviarlo a la direcci&oacute;n dada arriba utilizando la funci&oacute;n Reenviar como archivo adjunto o su equivalente.</p>
<p>Sobre todo no tiene que enviar el spam con un copiar-pegar, lo que suprimir&iacute;a el largo encabezado de origen, indispensable para el an&aacute;lisis del mensaje.</p>
<h3>Se&ntilde;alar falsos negativos con Netscape, Mozilla y Thunderbird</h3>
<ul>
 <li>Seleccione el spam en la lista de los mensajes</li>
 <li>Escoja el men&uacute; Mensaje, y el submen&uacute; Forward as..., y el articulo Attachement</li>
 <li>Indique como destinatario la direcci&oacute;n del centro de an&aacute;lisis de MailCleaner escrita previamente.</li>
 <li>Una solicitud de ajuste del filtro es enviada con una copia del mensaje</li>
 <li>Suprima el spam si quiere</li>
 <li>No recibir&aacute; confirmaci&oacute;n, pero consideramos su notificaci&oacute;n en los procesos permanentes de correcci&oacute;n del filtro.</li>
</ul>
<h3>Se&ntilde;alar falsos negativos con Microsoft Entourage (ordenadores Apple)</h3>
<ul>
 <li>Seleccione el spam en la lista de los mensajes</li>
 <li>Escoja el men&uacute; Mensaje, y el articulo Reenviar</li>
 <li>Indique como destinatario la direcci&oacute;n del centro de an&aacute;lisis de MailCleaner escrita previamente</li>
 <li>Una solicitud de ajuste del filtro es enviada con una copia del mensaje</li>
 <li>Suprima el spam si quiere</li>
 <li>No recibir&aacute; confirmaci&oacute;n, pero consideramos su notificaci&oacute;n en los procesos permanentes de correcci&oacute;n del filtro.</li>
</ul>
<h3> Se&ntilde;alar falsos negativos con Mail (ordenadores Apple)</h3>
<ul>
 <li>Seleccione el spam en la lista de los mensajes</li>
 <li>Escoja el men&uacute; Mensaje, y el articulo Reenviar como archivo adjunto</li>
 <li>Indique como destinatario la direcci&oacute;n del centro de an&aacute;lisis de MailCleaner escrita previamente</li>
 <li>Una solicitud de ajuste del filtro es enviada con una copia del mensaje</li>
 <li>Suprima el spam si quiere</li>
 <li>No recibir&aacute; confirmaci&oacute;n, pero consideramos su notificaci&oacute;n en los procesos permanentes de correcci&oacute;n del filtro.</li>
</ul>
<h2>Ver tambi&eacute;n el manual de usuario</h2>
<h3>Imprecisiones de filtraci&oacute;n</h3>
<p>Para determinar las medidas que tomar cuando un mensaje no fue filtrado correctamente.</p>
<p><a href="__MANUAL_ERRORS_LINK__"><img src="__IMAGE_BASE__images/pdf.gif" alt="__MANUAL_ERRORS_NAME__" /></a> <a href="__MANUAL_ERRORS_LINK__">Descargar el cap&iacute;tulo</a> (__MANUAL_ERRORS_SIZE__)</p>
';

$htxt['SUPPORT'] = '
<h1>Soporte y ayuda</h1>
<h6>Nuestro servicio de soporte y nuestro servicio comercial est&aacute;n a su disposici&oacute;n durante el horario de atenci&oacute;n a los clientes, de lunes a viernes.</h6>
<h2>Si tiene un problema</h2>
<p>__SUPPORT_EMAIL__</p>
<p>Antes de contactar el servicio de asistencia, le rogamos asegurarle que su problema no aparece en el __LINKHELP_usermanual__manual de usuario__LINK__ 
o en la parte __LINKHELP_faq__preguntas frecuentes__LINK__.</p>
<h2>Si tiene una pregunta comercial</h2>
<p>__SALES_EMAIL__</p>
';
?>
