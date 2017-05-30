<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller page for the status page
 */
 
/**
 * require admin session and Slave stuff
 */
require_once("variables.php");
require_once("admin_objects.php"); 
require_once("view/Template.php");
require_once("system/Slave.php");
require_once("view/Documentor.php");

/**
 * session globals
 */
global $sysconf_;
global $admin_;

// check authorizations
$admin_->checkPermissions(array('can_view_stats'));

// create the view objects
$template_ = new Template('monitor_global_status.tmpl');
$documentor = new Documentor();

// prepare template replacements
$nb_msgs_choice = array('2' => 2, '5' => 5, '10' => 10, '20' => 20, '50' => 50, '100' => 100);
$replace = array(
	    '__DOC_MONGLOBALHOSTID__' => $documentor->help_button('MONGLOBALHOSTID'),
        '__DOC_MONGLOBALHOST__' => $documentor->help_button('MONGLOBALHOST'),
        '__DOC_MONGLOBALPROCESSES__' => $documentor->help_button('MONGLOBALPROCESSES'),
        '__DOC_MONGLOBALSPOOLS__' => $documentor->help_button('MONGLOBALSPOOLS'),
        '__DOC_MONGLOBALLOAD__' => $documentor->help_button('MONGLOBALLOAD'),
        '__DOC_MONGLOBALDISKUSAGE__' => $documentor->help_button('MONGLOBALDISKUSAGE'),
        '__DOC_MONGLOBALMEMORYUSAGE__' => $documentor->help_button('MONGLOBALMEMORYUSAGE'),
        '__DOC_MONGLOBALLASTPATCH__' => $documentor->help_button('MONGLOBALLASTPATCH'),
        '__DOC_MONGLOBALTODAYSCOUNTS__' => $documentor->help_button('MONGLOBALTODAYSCOUNTS'),
        '__LANG__' => $lang_->getLanguage(),
	    '__HOSTLIST__' => drawHostList($template_),
	    "__RELOAD_NAV_JS__" => "window.parent.frames['navig_frame'].location.reload(true)",
       );

// output page
$template_->output($replace);

/**
 * draw a hosts status
 * @param   $t Template  template used to display host list
 * @return     string    html string of the hosts list status
 */
function drawHostList($t) {
  // get template values
  $spoolview_height = $t->getDefaultValue('SPOOLVIEWHEIGHT');
  $spoolview_width = $t->getDefaultValue('SPOOLVIEWWIDTH');
  $patchview_width = $t->getDefaultValue('PATCHESWIDTH');
  $patchview_height = $t->getDefaultValue('PATCHESHEIGHT');
  $restarter_width = $t->getDefaultValue('RESTARTERWIDTH');
  $restarter_height = $t->getDefaultValue('RESTARTERHEIGHT');
  $needrestart = $t->getDefaultValue('NEEDRESTART');

  $images['ASC'] = $t->getDefaultValue('ASC_IMG'); 
  $images['DESC'] = $t->getDefaultValue('DESC_IMG');
  $images['VIRUS'] = $t->getDefaultValue('VIRUS_IMG');
  $images['NAME'] = $t->getDefaultValue('NAME_IMG');
  $images['OTHER'] = $t->getDefaultValue('OTHER_IMG');
  $images['SPAM'] = $t->getDefaultValue('SPAM_IMG');

  $colors['OK'] = $t->getDefaultValue('COLOR_OK');
  $colors['CRITICAL'] = $t->getDefaultValue('COLOR_CRITICAL');
  $colors['INACTIVE'] = $t->getDefaultValue('COLOR_INACTIVE'); 
  $colors['SPAMS'] = $t->getDefaultValue('COLOR_SPAMS');
  $colors['VIRUSES'] = $t->getDefaultValue('COLOR_VIRUSES');
  $colors['CONTENT'] = $t->getDefaultValue('COLOR_CONTENT');
  $colors['MESSAGES'] = $t->getDefaultValue('COLOR_MESSAGES');

  // global objects
  $sysconf_ = SystemConfig::getInstance();
  $slaves_ = $sysconf_->getSlaves();

  $ret = "";
  $i = 0;
  foreach( $slaves_ as $slave ) {
    $retsoap = $slave->isAvailable();
    
    if ($retsoap == "OK") {
      if ($i++ % 2) {
        $tmp = preg_replace("/__COLOR1__(\S{7})__COLOR2__(\S{7})/", "$1", $t->getTemplate('HOSTLIST'));
      } else {
        $tmp = preg_replace("/__COLOR1__(\S{7})__COLOR2__(\S{7})/", "$2", $t->getTemplate('HOSTLIST'));
      } 
      $tmp = str_replace('__HOSTID__', $slave->getPref('id'), $tmp);
      $tmp = str_replace('__HOST__', $slave->getPref('hostname'), $tmp);
      $tmp = str_replace('__PROCESSES__', $slave->showProcesses($t->getTemplate('PROCESSESOPEN'), $colors, $needrestart, $restarter_height, $restarter_width), $tmp);
      $tmp = str_replace('__SPOOLS__', $slave->showSpools($t->getTemplate('SPOOLS'), $spoolview_width, $spoolview_height), $tmp);
      $tmp = str_replace('__LOAD__', $slave->showLoad($t->getTemplate('LOAD')), $tmp);
      $tmp = str_replace('__DISKUSAGE__', $slave->showDiskUsage($t->getTemplate('DISK')), $tmp);
      $tmp = str_replace('__MEMORYUSAGE__', $slave->showMemUsage($t->getTemplate('MEMORY'), $colors), $tmp);
      $tmp = str_replace('__LASTPATCH__', $slave->getLastPatch(), $tmp);
      $tmp = str_replace('__TODAYSCOUNTS__', $slave->showTodaysCounts($t->getTemplate('COUNTS'), $colors), $tmp);
      $tmp = preg_replace('/__LINK_LASTPATCH__/', "javascript:open_popup('view_patches.php?h=".$slave->getPref('id')."',$patchview_width,$patchview_width);", $tmp);

      $ret .= $tmp;
    } else {
      $ret .= "<tr bgcolor=\"white\"><td>".$slave->getPref('id')."</td><td align=\"center\" colspan=\"7\"><font color=\"red\">host not responding ($retsoap)</font></td></tr>";
    }
  }
  return $ret;
}
?>