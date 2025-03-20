<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 * @abstract This is the admin logout page controller
 */

/**
 * requires admin session
 */
require_once("admin_objects.php");
require_once("view/Language.php");
require_once("view/Template.php");

/**
 * session globals
 */
global $lang_;

// create view
$template_ = new Template('logout.tmpl');
// prepare replacements
$replace = [
    "__ADMIN_BASE_URL__" => $_SERVER['SERVER_NAME'] . "/admin/",
    "__USER_BASE_URL__" => $_SERVER['SERVER_NAME']
];

// output page
$template_->output($replace);

// actually execute logout
unregisterAll();
