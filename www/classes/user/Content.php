<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */

/**
 * This is the class is mainly a data wrapper for the quarantined content objects
 */
class Content
{

    /**
     * datas of the content
     * @var array
     */
    private $prefs_ = [
        'timestamp' => '',
        'date' => '',
        'time' => '',
        'id' => '',
        'from_address' => '',
        'to_address' => '',
        'subject' => '',
        'isspam' => 0,
        'virusinfected' => 0,
        'nameinfected' => 0,
        'otherinfected' => 0,
        'report' => '',
        'sascore' => 0,
        'size' => 0,
        'spamreport' => '',
        'headers' => '',
        'slave' => 0,
        'content_forced' => 0
    ];


    /**
     * load the content datas from the given array
     * @param  $datas  array   datas to be set
     * @return         bool    true on success, false on failure
     */
    public function setDatas($datas)
    {
        foreach ($datas as $key => $value) {
            $this->setPref($key, $value);
        }
        return true;
    }

    /**
     * set a content data
     * @param  pref   string  data name
     * @param  $value mixed   data value
     * @return        bool    true on success, false on failure
     */
    public function setPref($pref, $value)
    {
        if (isset($this->prefs_[$pref])) {
            $this->prefs_[$pref] = $value;
        }
    }

    /**
     * get a content data
     * @param  pref   string  data name
     * @return        mixed   data value or "" if not found
     */
    public function getPref($pref)
    {
        if (isset($this->prefs_[$pref])) {
            return $this->prefs_[$pref];
        }
        return "";
    }

    /**
     * return the quarantine path to the message file
     * @return  string  message path
     */
    public function getPathToFile()
    {
        $matches = [];
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})\s.*$/', $this->getPref('timestamp'), $matches)) {
            return 'CANNOTFINDFILEPATH';
        }
        if (!preg_match('/^[a-zA-Z0-9]{6}-[a-zA-Z0-9]{6,11}-[a-zA-Z0-9]{2,4}$/', $this->getPref('id'))) {
            return 'BADCONTENTID';
        }
        return $matches[1] . $matches[2] . $matches[3] . "/" . $this->getPref('id');
    }

    /**
     * get data cleaned from html code
     * @param  $field  string  data field name
     * @return         mixed   cleaned data value
     */
    public function getCleanData($field)
    {
        return htmlentities($this->getPref($field));
    }

    /**
     * this will load content datas from the database(s)
     * @param  $id   string  message id
     * @return       string  OK on success, error message on failure
     */
    public function load($id)
    {
        $sysconf_ = SystemConfig::getInstance();
        $ret = "OK";

        // first some sanity checks
        if (!preg_match('/^[a-zA-Z0-9]{6}-[a-zA-Z0-9]{6,11}-[a-zA-Z0-9]{2,4}$/', $id)) {
            return 'BADSEARCHID';
        }

        // we have to loop on all slaves to found where the content has been detected and stored
        foreach ($sysconf_->getSlaves() as $s) {

            require_once('helpers/DM_Custom.php');
            $slave = $sysconf_->getSlavePortPasswordID($s->getPref('hostname'));
            $slave_id = $slave[2];
            if ($slave[0] == 0) {
                $slaves = $sysconf_->getSlaves();
                $slave = $slaves[0];
                $slave_id = 1;
            }
            $db = DM_Custom::getInstance($s->getPref('hostname'), $slave[0], 'mailcleaner', $slave[1], 'mc_stats');

            $clean_id = $db->sanitize($id);

            // build the query
            $query = "SELECT timestamp, to_domain, id, from_address, to_address, subject, isspam, virusinfected, nameinfected, otherinfected, report, date, time, size, sascore, spamreport, headers, content_forced FROM maillog WHERE";
            $query .= " quarantined=1 AND ";
            $query .= " id='" . $clean_id . "'";

            $res = $db->getHash($query);
            if (!is_array($res)) {
                return $res;
            }
            if (!empty($res)) {
                // and populate the datas
                $this->setDatas($res);
                $this->setPref('slave', $slave_id);
                break;
            }
        }
        if ($this->getPref('slave') < 1) {
            return "CONTENTIDNOTFOUND";
        }
        return $ret;
    }

    /**
     * will force the quarantined message to be delivered with its full contents
     * @return   string  'OK' on success, error message on failure
     */
    public function force()
    {
        require_once("system/Soaper.php");
        $sysconf_ = SystemConfig::getInstance();

        $path = $this->getPathToFile();
        if (!preg_match('/\d{8}\/([a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{6,11}-[a-z,A-Z,0-9]{2,4})$/', $path)) {
            return 'CANNOTFINDFILEPATH';
        }

        // we have to call the soap service on the correct slave in order to do that
        $soaper = new Soaper();
        if (!$soaper->load($sysconf_->getSlaveName($this->getPref('slave')))) {
            return false;
        }
        $sid = $soaper->authenticateAdmin();
        if (preg_match('/^[A-Z]+$/', $sid)) {
            return $sid;
        }
        $res = $soaper->queryParam('forceContent', [$sid, $path]);

        return $res;
    }

    /**
     * get the html string containing the correct images to be displayed
     * @param  $images  array   Array of images to be used
     * @return          string  html string
     */
    public function getContentFoundImages($images)
    {
        $ret = "";
        if ($this->prefs_['virusinfected']) {
            $ret .= $images['VIRUS'];
        }
        if ($this->prefs_['nameinfected']) {
            $ret .= $images['NAME'];
        }
        if ($this->prefs_['otherinfected']) {
            $ret .= $images['OTHER'];
        }
        if ($this->prefs_['isspam']) {
            $ret .= $images['SPAM'];
        }
        return $ret;
    }
}
