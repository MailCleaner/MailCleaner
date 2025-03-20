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
require_once('config/DNSList.php');
/**
 * This class is only a settings wrapper for the PreFilter modules configuration
 */
class PreRBLs extends PreFilter
{

    /**
     * prefilter properties
     * @var array
     */
    private $specpref_ = [
        'spamhits' => 2,
        'highspamhits' => 2,
        'lists' => ""
    ];

    private $dnslists_ = [];
    private $form_;

    public function subload()
    {
        $db_slaveconf = DM_SlaveConfig::getInstance();
        $query = "SELECT name FROM dnslist WHERE type='blacklist' AND active=1";
        $list = $db_slaveconf->getList($query);
        foreach ($list as $el) {
            $dnslist = new DNSList();
            if ($dnslist->load($el)) {
                $this->dnslists_{
                    $el} = $dnslist;
            }
        }
    }

    public function addSpecPrefs()
    {
        $this->addPrefSet('PreRBLs', 'pr', $this->specpref_);
    }

    public function getSpecificTMPL()
    {
        return "prefilters/PreRBLs.tmpl";
    }

    public function getSpeciticReplace($template, $form)
    {
        global $lang_;
        $this->form_ = $form;
        $subtmpl = $template->getTemplate('DNSLIST');
        $tlist = "";
        foreach ($this->dnslists_ as $l) {
            $t = $subtmpl;
            if (preg_match('/__LANG_(\S+)__/', $t, $matches)) {
                $t = preg_replace('/__LANG_(\S+)__/', $lang_->print_txt($matches[1]), $t);
            }
            $t = preg_replace('/__NAME__/', $l->getPref('name'), $t);
            $t = preg_replace('/__URL__/', $l->getPref('url'), $t);
            $t = preg_replace('/__COMMENT__/', $l->getPref('comment'), $t);
            $t = preg_replace('/__FORM_ENABLELIST__/', $form->checkbox('enable_' . $l->getPref('name'), 1, $l->isEnabled($this->getPref('lists')), '', 1), $t);
            $tlist .= $t;
        }

        $countlist = [];
        for ($i = 0; $i <= count($this->dnslists_); $i++) {
            $countlist[$i] = $i;
        }
        $ret = [
            "__FORM_HITSTOBESPAM__" => $form->select('spamhits', $countlist, $this->getPref('spamhits'), ';'),
            "__DNSLIST_LIST__" => $tlist
        ];

        return $ret;
    }

    public function subsave($posted)
    {
        $res = "";
        if ($posted) {
            foreach ($posted as $key => $value) {
                $key = preg_replace('/UUU/', '-', $key);
                $key = preg_replace('/PPP/', '.', $key);
                if (!$value) {
                    continue;
                }
                if (preg_match('/_cb$/', $key)) {
                    continue;
                }
                if (preg_match('/enable_(\S+)/', $key, $matches)) {
                    $res .= " " . $matches[1];
                }
            }
        }
        $this->setPref('lists', $res);
    }
}
