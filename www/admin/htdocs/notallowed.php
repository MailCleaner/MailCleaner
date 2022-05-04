<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller for the page for not allowed accesses
 */
 
/**
 * require admin session and view
 */
require_once("admin_objects.php");
require_once("view/Template.php");

/**
 * session globals
 */
global $lang_;

// create view
$template_ = new Template('notallowed.tmpl');

// prepare replacements
$replace = array(
        "__LANG__" => $lang_->getLanguage()
);

// output page
$template_->output($replace);
?>
