<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller for a blank page
 */

/**
 * require admin session and view
 */
require_once("admin_objects.php");
require_once('view/Template.php');

// create view
$template_ = new Template('blank.tmpl');

// prepare replacements
$replace = ['__LANG__' => $lang_->getLanguage()];

// output page
$template_->output($replace);
