<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller for the navigation page
 */

/**
 * require valid session
 */
require_once("objects.php");
require_once("view/Template.php");
global $lang_;
global $sysconf_;

// set the navigation pictures
$p_img = "nav_param_n.gif";
$q_img = "nav_quar_n.gif";
$s_img = "nav_supp_n.gif";

// check if one is selected
if (isset($_GET['m'])) {
    switch ($_GET['m']) {
        case 'p':
            $p_img = "nav_param_s.gif";
            break;
        case 'q':
            $q_img = "nav_quar_s.gif";
            break;
        case 's':
            $s_img = "nav_supp_s.gif";
            break;
    }
}

// create view
$template_ = new Template('navigation.tmpl');

// Registered?
require_once('helpers/DataManager.php');
$file_conf = DataManager::getFileConfig($sysconf_::$CONFIGFILE_);
$is_enterprise = 0;
if (isset($file_conf['REGISTERED']) && $file_conf['REGISTERED'] == '1') {
    $is_enterprise = 1;
}

$replace = [
    '__LANG__' => $lang_->getLanguage(),
    '__C_LINK__' => '</a>',
    '__O_LINK_PARAMETERS__' => '<a href="parameters.php" target="main_frame" onClick="doButton(\'p\');">',
    '__LINK_PARAMETERS__' => 'parameters.php',
    '__O_LINK_QUARANTINE__' => '<a href="quarantine.php" target="main_frame" onClick="doButton(\'q\');">',
    '__LINK_QUARANTINE__' => 'quarantine.php',
    '__O_LINK_SUPPORT__' => '<a href="support.php" target="main_frame" onClick="doButton(\'s\');">',
    '__LINK_SUPPORT__' => 'support.php',
    '__O_LINK_LOGOUT__' => '<a href="logout.php" target="_parent">',
    '__LINK_LOGOUT__' => 'logout.php',
    '__P_IMG__' => $p_img,
    '__Q_IMG__' => $q_img,
    '__S_IMG__' => $s_img,
    '__INCLUDE_JS__' => "<script type=\"text/javascript\" language=\"javascript\">\n
                                   function doButton(button) {
                                     window.location.href='navigation.php?m='+button;
                                   }
                                 </script>",
    '__PRINT_USERNAME__' => $user_->getName(),
    '__COPYRIGHTLINK__' => $is_enterprise ? "www.mailcleaner.net" : "www.mailcleaner.org",
    '__COPYRIGHTTEXT__' => $is_enterprise ? $lang_->print_txt('COPYRIGHTEE') : $lang_->print_txt('COPYRIGHTCE'),
];

// display page
$template_->output($replace);
