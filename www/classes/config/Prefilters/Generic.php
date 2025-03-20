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

/**
 * This class is only a settings wrapper for the PreFilter modules configuration
 */
class Generic extends PreFilter
{

    public function subload()
    {
    }

    public function addSpecPrefs()
    {
    }

    public function getSpecificTMPL()
    {
        return "";
    }

    public function getSpeciticReplace($template, $form)
    {
        return [];
    }

    public function subsave($posted)
    {
    }
}
