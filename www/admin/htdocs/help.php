<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * @abstract This is the documentation window controller
 */
 
/**
 * requires admin session, and documentation stuff
 */
require_once('variables.php');
require_once('admin_objects.php');
require_once('view/Documentor.php');
require_once('view/Template.php');

// create Documentor object
$doc = new Documentor();
// create view
$template = new Template('help.tmpl');
 
// prepare replacements 
$replace = array(
        '__DOC_TEXT__' => $template->processText($doc->getHelpText($_GET['s']), array())
);

// output page
$template->output($replace);
?>