<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */

/**
 * this is a preference handler
 */
require_once('helpers/PrefHandler.php');
require_once('helpers/ListManager.php');

/**
 * we need virus scanners definitions
 */
require_once('config/Scanner.php');

/**
 * This class is only a settings wrapper for the antivirus configurations
 */
class AntiVirus extends PrefHandler
{

    /**
     * antivirus settings
     * @var array
     */
    private $pref_ = [
        'scanner_timeout' => 300,
        'silent' => 'yes',
        'file_timeout' => 20,
        'expand_tnef' => 'yes',
        'deliver_bad_tnef' => 'yes',
        'tnef_timeout' => 120,
        'max_message_size' => 0,
        'max_attach_size' => -1,
        'max_archive_depth' => 0,
        'send_notices' => 'no',
        'notices_to' => 'root',
        'usetnefcontent' => 'no'
    ];

    /**
     * scanners list
     * @var array
     */
    private $scanners_ = [];

    /**
     * constructor
     */
    public function __construct()
    {
        $this->addPrefSet('antivirus', 'a', $this->pref_);
    }

    /**
     * add a scanner to the list
     * @param  $scanner  Scanner  scanner to add
     * @return           boolean  true on success, false on failure
     */
    private function addScanner($scanner)
    {
        if ($scanner instanceof Scanner) {
            $this->scanners_[$scanner->getPref('name')] = $scanner;
            return true;
        }
        return false;
    }

    /**
     * get a scanner by its name
     * @param $scanner_name  string  scanner name to get
     * @return               Scanner scanner object, or null if not found
     */
    private function getScanner($scanner_name)
    {
        if (isset($this->scanners_[$scanner_name])) {
            return $this->scanners_[$scanner_name];
        }
        return null;
    }

    /**
     * load antivirus datas and scanners list
     * @return  boolean true on success, false on failure
     */
    public function load()
    {
        if (!$this->loadPrefs('', '', false)) {
            return false;
        }

        $db_slaveconf = DM_SlaveConfig::getInstance();
        $query = "SELECT name FROM scanner";
        $list = $db_slaveconf->getList($query);

        foreach ($list as $scanner_name) {
            $s = new Scanner;
            if ($s->load($scanner_name)) {
                $this->addScanner($s);
            }
        }
        return true;
    }

    /**
     * set scanner preferences
     * @param  $s  string  scanner name
     * @param  $p  string  preference name
     * @param  $v  mixed   preference value
     * @return     boolean true on success, false on failure
     */
    public function setScannerPref($s, $p, $v)
    {
        $scanner = $this->getScanner($s);
        if ($scanner) {
            return $scanner->setPref($p, $v);
        }
        return false;
    }

    /**
     * save preferences and scanners settings
     * @return    string  'OKSAVED' on success, error message on failure
     */
    public function save()
    {

        $sysconf_ = SystemConfig::getInstance();
        $sysconf_->setProcessToBeRestarted('ENGINE');
        if (!preg_match('/^(no|add|replace)$/', $this->getPref('usetnefcontent'))) {
            $this->setPref('usetnefcontent', 'no');
        }
        if (!$this->savePrefs('', '', '')) {
            return false;
        }

        foreach ($this->scanners_ as $scanner) {
            $retok = $scanner->save();
        }
        return $retok;
    }

    /**
     * return the html string for the scanners configuration bloc
     * @param  $t  string  html template of each scanner line
     * @param  $f  Form    html form containing the scanner list
     * @return     string  html list
     */
    public function drawScanners($t, $f)
    {
        global $lang_;

        $ret = "";
        foreach ($this->scanners_ as $name => $scanner) {
            $template = str_replace('__SCANNER_COMMON_NAME__', $scanner->getPref('comm_name'), $t);
            $template = str_replace('__INPUT_ACTIVESCANNER__', $f->checkbox($name . '_active', 1, $scanner->getPref('active'), '', $scanner->getPref('installed')), $template);
            $template = str_replace('__INPUT_PATH__', $f->input($name . '_path', 20, $scanner->getPref('path')), $template);
            $template = str_replace('__LANG_PATH__', $lang_->print_txt('PATH'), $template);
            $template = str_replace('__INSTALLED__', $this->getScanner($name)->getInstalledStatus(), $template);
            $ret .= $template;
        }
        return $ret;
    }
}
