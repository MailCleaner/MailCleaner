<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */

/**
 * this class handles spam criteria found in the spam message
 */
class ReasonSet
{

    /**
     * spam criteria keys are sa's criteria name, values are array of (criteria name, text, score)
     * @var array
     */
    private $reasons_ = [];

    /**
     * total score of the message
     * @var  numeric
     */
    private $total_score_ = 0;

    /**
     * fetch the reason set from the slave host
     * @param   $msg_id      string  message id
     * @param   $destination string  destination address of the message
     * @param   $host        string  slave host where the message is stored
     * @return               boolean true on success, false on failure
     */
    public function getReasons($msg_id, $destination, $host)
    {
        require_once('view/Language.php');
        require_once('system/SoapTypes.php');

        $lang = Language::getInstance('user');
        if (!preg_match('/^([a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{6,11}-[a-z,A-Z,0-9]{2,4})$/', $msg_id) || !preg_match('/^\S+\@\S+$/', $destination)) {
            return false;
        }
        require_once("system/Soaper.php");
        $soaper = new Soaper();
        if (!$soaper->load($host)) {
            return false;
        }

        $soap_res = $soaper->queryParam('getReasons', [$msg_id, $destination, $lang->getLanguage()]);
        if (!is_object($soap_res) || !is_array($soap_res->reasons)) {
            return false;
        }
        $res = $soap_res->reasons;
        if (isset($soap_res->reasons->item)) {
            $res = $soap_res->reasons->item;
        }

        $score_a = [];
        foreach ($res as $res_l) {
            if (preg_match('/^(\S+)\:\:(\S+)\:\:(.*)$/', $res_l, $score_a)) {
                if ($score_a[1] == "TOTAL_SCORE") {
                    $this->total_score_ = $score_a[2];
                } else {
                    $this->reasons_[$score_a[1]] = [$score_a[2], $score_a[3]];
                }
            }
        }
        return true;
    }

    /**
     * return the number of reasons found
     * @return  numeric  number of reasons
     */
    public function getNbReasons()
    {
        return count($this->reasons_);
    }

    /**
     * return the total score of the message
     * @return  numeric  total score
     */
    public function getTotalScore()
    {
        return $this->total_score_;
    }
    /**
     * return the html string of the reasons array
     * @param  $t  string  html template for the array lines
     * @return     string  html string of reasons lines
     */
    public function getHtmlList($t)
    {
        $ret = '';
        $i = 0;
        foreach ($this->reasons_ as $reason => $values) {
            if ($i++ % 2) {
                $template = preg_replace("/__COLOR1__(\S{7})__COLOR2__(\S{7})/", "$1", $t);
            } else {
                $template = preg_replace("/__COLOR1__(\S{7})__COLOR2__(\S{7})/", "$2", $t);
            }
            $template = str_replace('__REASON__', htmlentities($values[1]), $template);
            $template = str_replace('__REASON_SCORE__', htmlentities($values[0]), $template);
            $ret .= $template;
        }
        return $ret;
    }
}
