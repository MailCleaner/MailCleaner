<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 * @abstract This is the admin login page controller
 */

/**
 * requires admin session, and Login stuff
 */
require_once("variables.php");
require_once("system/SystemConfig.php");
require_once("view/Language.php");
require_once("view/AdminLoginDialog.php");
require_once("view/Template.php");

// global objects
$sysconf_ = SystemConfig::getInstance();
$lang_ = Language::getInstance('admin');

// create login dialog
$login_ = new AdminLoginDialog();
// start authentification (redirected here if authenticated)
$login_->start();

// create view
$template_ = new Template('login.tmpl');

// prepare replacements
$replace = [
    "__PRINT_STATUS__" => $login_->printStatus(),
    "__BEGIN_LOGIN_FORM__" => "<form method=\"post\" action=\"" . $_SERVER['PHP_SELF'] . "\">\n",
    "__END_LOGIN_FORM__" => "</form>\n",
    "__LOGIN_FIELD__" => "<input type=\"text\" name=\"username\" size=\"20\">",
    "__PASSWORD_FIELD__" => "<input type=\"password\" name=\"password\" size=\"20\">",
    "__SUBMIT_BUTTON__" => "<input type=\"submit\" name=\"" . $lang_->print_txt('SUBMIT') . "\"></td>"
];

// output page
$template_->output($replace);
