<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
/**
 * needs some defaults
 */
require_once("system/SystemConfig.php");

/**
 * this class handle the online documentation windows
 */
class Documentor {

  /**
   * documentor subjects
   * @var array
   */
  private $help_ = array();

  /**
   * constructor
   */
  public function __construct() {
    // include help texts
    $sysconf_ = SystemConfig::getInstance();
    $lang_ = Language::getInstance('admin');
    $help = array();
    require($sysconf_->SRCDIR_."/www/admin/htdocs/lang/".$lang_->getLanguage()."/help.php");
    $this->help_ = $help;
  }

  /**
   * return the link to the documentation page with button
   * @param  $subject  string  documentation subject
   * @return           string  html link to documentation page
   */
  public function help_button($subject) {
    if (! isset($this->help_[$subject])) {
      return "";
    }
    $url = "help.php?s=".$subject;
    return "<a href=\"#\" onClick=\"javascript:window.document.open('$url','','width=500,height=500,toolbar=no,resizable=yes,scrollbars=yes');\"><img src=\"templates/default/images/help.gif\" border=\"0\" align=\"absmiddle\"></a>" ;
  }

  /**
   * get the help text
   * @param  $subject  string  documentation subject
   * @return           string  documentation text
   */
  public function getHelpText($subject) {
    if (! isset($this->help_[$subject])) {
      return "";
    }
    return $this->help_[$subject];
  }
}
?>