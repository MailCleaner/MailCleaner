<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * @abstract This is the index page controller
 */
 
/**
 * requires admin session, adn view
 */
require_once ("admin_objects.php");
require_once ("view/Template.php");

// create view
$template_ = new Template('index.tmpl');

// prepare replacements
$replace = array (
        "__TOP_PAGE__" => 'top.php', 
        "__NAVIGATION_PAGE__" => 'navigation.php', 
        "__WELCOME_PAGE__" => 'welcome.php'
);

// output page
$template_->output($replace);
?>