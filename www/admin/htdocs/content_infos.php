<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller for the quarantines message content display
 */

/**
 * require admin session, view and content objects
 */
require_once('admin_objects.php');
require_once("view/Template.php");
require_once("user/Content.php");

/**
 * session globals
 */
global $lang_;
global $sysconf_;


$res = "BADARGS";
// create content
$content = new Content();
// get request settings and load content
if (isset($_GET['id']) && preg_match('/^[a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{6,11}-[a-z,A-Z,0-9]{2,4}$/', $_GET['id'])) {
    $res = $content->load($_GET['id']);
}

// create view
$template_ = new Template('content_infos.tmpl');
// get defaults
$images['VIRUS'] = $template_->getDefaultValue('VIRUS_IMG');
$images['NAME'] = $template_->getDefaultValue('NAME_IMG');
$images['OTHER'] = $template_->getDefaultValue('OTHER_IMG');
$images['SPAM'] = $template_->getDefaultValue('SPAM_IMG');

//prepare replacements
$replace = [
    '__MESSAGE__' => $message,
    '__MSG_ID__' => $content->getCleanData('id'),
    '__TO__' => $content->getCleanData('to_address'),
    '__FROM__' => $content->getCleanData('from_address'),
    '__DATE__' => $content->getCleanData('date') . " " . $content->getCleanData('time'),
    '__SUBJECT__' => $content->getCleanData('subject'),
    '__DANGEROUSCONTENT__' => $content->getContentFoundImages($images),
    '__REPORT__' => format_report($content->getCleanData('report')),
    '__SIZE__' => format_size($content->getCleanData('size')),
    '__SCORE__' => $content->getCleanData('sascore'),
    '__SAREPORT__' => $content->getCleanData('spamreport'),
    '__HEADERS__' => format_headers($content->getCleanData('headers'))
];

//output page
$template_->output($replace);

/**
 * format the report text
 * @param  $r  string  report text
 * @return     string  formatted report text
 */
function format_report($r)
{
    $r = preg_replace('/^(\S+):/', '<b>$1</b>:', $r);
    return preg_replace('/,(\S+):/', '<br/><b>$1</b>:', $r);
}

/**
 * format headers
 * @param  $h string headers text
 * @return    string formatted headers text
 */
function format_headers($h)
{
    $h = preg_replace('/([A-Z]\S+):/', '<br/><b>$1</b>:', $h);
    $h = preg_replace('/^<br\>/', '', $h, 1);
    return $h;
}

/**
 * format message size
 * @param  $s  numeric  size to be formatted
 * @return     string   formatted size
 */
function format_size($s)
{
    global $lang_;
    if ($s > 1024 * 1000) {
        return sprintf("%.2d " . $lang_->print_txt('MB'), $s / (1000.0)) . " (" . $s . " " . $lang_->print_txt('BYTES') . ")";
    } elseif ($s > 1024) {
        return sprintf("%.2d " . $lang_->print_txt('KB'), $s / (1024.0)) . " (" . $s . " " . $lang_->print_txt('BYTES') . ")";
    }
    return $s . " " . $lang_->print_txt('BYTES');
}
