<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controller for the top page
 */
 
/**
 * requires admin session and view
 */ 
require_once('admin_objects.php');
require_once("view/Template.php");
 
/**
 * session globals
 */
global $lang_;
global $admin_;

// create view
$template_ = new Template('top.tmpl');

// prepare replacements
$replace = array(
        "__LANG__" => $lang_->getLanguage(),
        "__USERNAME__" => $admin_->getPref('username'),
        "__LINK_LOGOUT__" => "/admin/logout.php"
);

// output page
$template_->output($replace);
?>