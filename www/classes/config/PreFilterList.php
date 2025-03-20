<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */

require_once("config/PreFilter.php");

/**
 * This class is a Prefilter container
 */
class PreFilterList
{

    /**
     * list of prefilters
     * @var array
     */
    private $prefilters_ = [];

    /**
     * flat positions list
     * @var array
     */
    private $positions_ = [];

    /**
     * constructor
     */
    public function __construct()
    {
    }

    /**
     * load prefilters from database
     * @return     boolean  true on success, false on failure
     */
    public function load()
    {

        $db_slaveconf = DM_SlaveConfig::getInstance();
        $query = "SELECT name FROM prefilter WHERE set_id=1 ORDER BY position";
        $list = $db_slaveconf->getList($query);

        $pos = 1;
        $nb = 0;
        foreach ($list as $prefilter_name) {
            $nb++;
            $pf = PreFilter::factory($prefilter_name);
            if ($pf->load($prefilter_name)) {
                $this->addPrefilter($pf, $pos);
                $this->positions_[$pos] = $pos;
                $pos++;
            }
        }

        $this->nb_available_prefilters_ = $nb;
        return true;
    }

    /**
     * add a prefilter to the list
     * @param  $prefilter  PreFilter  prefilter to add
     * @param  $position   integer    prefilter position
     * @return boolean true on success, false on failure
     */
    public function addPrefilter($prefilter, $position)
    {
        if ($prefilter instanceof PreFilter) {
            $this->prefilters_[$position] = $prefilter;
            return true;
        }
        return false;
    }


    /**
     * save datas to database
     * @return    string  'OKSAVED' on success, error message on failure
     */
    public function save()
    {
        foreach ($this->prefilters_ as $pf) {
            if (!$pf->save(null)) {
                return false;
            }
        }
        return true;
    }

    /**
     * return the element at given position
     * @param  position   int       position of element
     * @return            PreFilter element
     */
    public function getElementAtPosition($position)
    {
        if (isset($this->prefilters_[$position])) {
            return $this->prefilters_[$position];
        }
        return null;
    }


    /**
     * populate template with prefilter list
     * @param  $template   Template template of list element
     * @param  $form       Form     form fo editing fields
     * @return             string   populated template
     */
    public function getList($template, $form)
    {
        global $lang_;
        $ret = "";
        foreach ($this->prefilters_ as $pf) {
            $t = $template->getTemplate('MODULESSLIST');

            $enable = false;
            if ($pf->getPref('active')) {
                $enable = true;
            }
            $t = preg_replace('/__LANG_([A-Z0-9]+)__/', $lang_->print_txt("$1"), $t);
            $t = preg_replace('/__ROWID__/', 'module_' . $pf->getPref('position'), $t);
            if ($pf->getPref('active')) {
                $t = str_replace('__ROWSTYLE__', 'infoBoxContent', $t);
            } else {
                $t = str_replace('__ROWSTYLE__', 'infoBoxContentDisabled', $t);
            }
            $t = str_replace('__ACTIVE__', $form->checkBox("active_" . $pf->getPref('position'), 1, $pf->getPref('active'), "javascript:disableRow(" . $pf->getPref('position') . ")", true), $t);
            $t = str_replace('__POSITION__', $pf->getPref('position'), $t);
            if ($enable) {
                $t = str_replace('__NAME__', $pf->getPref('name'), $t);
            } else {
                $t = str_replace('__NAME__', "<font color=\"#999999\">" . $pf->getPref('name') . "</font>", $t);
            }
            $uparrow = $template->getDefaultValue('EMPTYARROW_IMG');
            $downarrow = $template->getDefaultValue('EMPTYARROW_IMG');
            if ($pf->getPref('position') > 1) {
                $uparrow = $template->getDefaultValue('UPARROW_IMG');
            }
            if ($pf->getPref('position') < $this->getNumberOfElements()) {
                $downarrow = $template->getDefaultValue('DOWNARROW_IMG');
            }
            $t = str_replace('__UP_ARROW__', $uparrow, $t);
            $t = str_replace('__DOWN_ARROW__',  $downarrow, $t);
            $t = str_replace('__UPLINK__', $_SERVER['PHP_SELF'] . "?up&m=" . $pf->getPref('position'), $t);
            $t = str_replace('__DOWNLINK__', $_SERVER['PHP_SELF'] . "?down&m=" . $pf->getPref('position'), $t);

            $t = str_replace('__NEG_DECISIVE__', $form->checkbox("neg_decisive_" . $pf->getPref('position'), 1, $pf->getPref('neg_decisive'), '', $enable), $t);
            $t = str_replace('__POS_DECISIVE__', $form->checkbox("pos_decisive_" . $pf->getPref('position'), 1, $pf->getPref('pos_decisive'), '', $enable), $t);

            $ret .= $t;
        }
        return $ret;
    }

    /**
     * return the number of elements
     * @return     int   number of elements
     */
    public function getNumberOfElements()
    {
        return count($this->prefilters_);
    }

    /**
     * reorder one element (in fact, switch one element with the one in the given position)
     * @param  original int      original position
     * @param  position int      new position
     * @return           boolean true on success, false on failure
     */
    public function orderElement($original, $position)
    {
        $temp = $this->prefilters_[$original];
        $this->prefilters_[$original] = $this->prefilters_[$position];
        $this->prefilters_[$original]->setPref('position', $original);
        $this->prefilters_[$position] = $temp;
        $this->prefilters_[$position]->setPref('position', $position);
        return true;
    }
}
