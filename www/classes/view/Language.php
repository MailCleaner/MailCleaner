<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */

/**
 * Language only needs some global configuration settings
 */
require_once("system/SystemConfig.php");
/**
 * Language handler class
 * This class takes care of the languages and tranlsation for the web interfaces
 * It basically takes the language file corresponding to the desired language
 * and replace tags with translated equivalence when needed
 *
 * @package mailcleaner
 */

class Language
{
    /**
     * Language actually used (default is English)
     * @var  string
     */
    private    $lang_ = "en";

    /*
     * Available language
     * @var array array of language. Shortcut name as key, and full name as value
     */
    private $available_languages_ = [];

    /*
     * Available languages array in reversed key <==> value
     * This one is useful for html select inputs
     * @var array  array of language. Full name as key, and shortcut as value
     */
    private $inversed_languages_ = [];

    /**
     * Array of translated message
     * Stored with tags as keys and translated text as value
     * @var array
     */
    private    $txts_;

    /**
     * class instance of the SystemConfig object
     * @var SystemConfig
     */
    private    $sysconf_;

    /**
     * work mode of the language class. It can be 'user' or 'admin' depending of the interface displayed
     * This mode will define where the languages file will be searched (in user htdocs or in admin htdocs)
     * @var  string
     */
    private    $type_ = "user";

    /**
     * Instance of this singleton
     * @var $Language
     */
    static private $instance_;


    /**
     * Constructor
     * This will load the correct language file corresponding to the mode (user/admin) and the language
     * @param  $type  string  work mode (can be user or admin)
     */
    function __construct($type)
    {
        require_once('user/User.php');
        require_once('config/Administrator.php');
        global $user_;
        global $admin_;
        $this->sysconf_ = SystemConfig::getInstance();

        // Initialize "dynamicly" $this->available_languages_ and inversed_languages_
        $langDir = $this->sysconf_->SRCDIR_ . "/www/" . $this->type_ . "/htdocs/lang/";
        $dirs = array_filter(glob($langDir . '*'), 'is_dir'); // get all lang in directories

        // read langages csv
        $language_codes = [];
        $row = 1;
        if (($handle = fopen($this->sysconf_->SRCDIR_ . "/www/classes/view/languages.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
                $language_codes[$data[0]] = $data[1];
                $row++;
            }
            fclose($handle);
        }

        // in case of EE version
        // read available languages for EE
        require_once('helpers/DataManager.php');
        $baseconf = DataManager::getFileConfig(SystemConfig::$CONFIGFILE_);
        $ISENTERPRISE = 0;
        if (isset($baseconf['REGISTERED']) && $baseconf['REGISTERED'] == '1') {
            $ISENTERPRISE = 1;
        }
        $ee_languages = [];
        if ($ISENTERPRISE) {
            if (($handle = fopen($this->sysconf_->SRCDIR_ . "/www/classes/view/EELanguages.txt", "r")) !== FALSE) {
                while (($data = fgets($handle)) !== FALSE) {
                    $ee_languages[] = trim($data);
                }
                fclose($handle);
            }
        }

        // Exception for last langages who doesn't respect standards naming conventions
        // for langs.
        $currLangs = ["en" => "en_US", "de" => "de_DE", "fr" => "fr_FR", "it" => "it_IT", "nl" => "nl_NL", "es" => "es_ES"];
        foreach ($dirs as $l) {
            foreach ($language_codes as $l_code => $l_title) {
                $ll = basename($l);
                // Ignore duplicates
                if (array_key_exists($ll, $this->available_languages_) || in_array($l_title, $this->available_languages_)) {
                    continue;
                }
                if (array_key_exists($ll, $currLangs)) {
                    if (in_array($language_codes[$currLangs[$ll]], $this->available_languages_)) {
                        continue;
                    }
                }

                if ($ISENTERPRISE) {
                    if (array_key_exists($ll, $currLangs) && in_array($ll, $ee_languages)) {
                        $this->available_languages_[$ll] = $language_codes[$currLangs[$ll]];
                        $this->inversed_languages_[$language_codes[$currLangs[$ll]]] = $ll;
                        break;
                    } else {
                        if (preg_match("/^${ll}/", $l_code) == 1 && in_array($ll, $ee_languages)) {
                            $this->available_languages_[$ll] = $l_title;
                            $this->inversed_languages_[$l_title] = $ll;
                            break;
                        }
                    }
                } else {
                    if (array_key_exists($ll, $currLangs)) {
                        $this->available_languages_[$ll] = $language_codes[$currLangs[$ll]];
                        $this->inversed_languages_[$language_codes[$currLangs[$ll]]] = $ll;
                        break;
                    } else {
                        if (preg_match("/^${ll}/", $l_code) == 1) {
                            $this->available_languages_[$ll] = $l_title;
                            $this->inversed_languages_[$l_title] = $ll;
                            break;
                        }
                    }
                }
            }
        }

        asort($this->available_languages_);
        asort($this->inversed_languages_);
        // first get global system configuration language
        $lang = $this->sysconf_->getPref('default_language');
        // secondly, if a user object is already instantiated (logged), get its language preferences
        if (isset($user_)) {
            $lang = $user_->getPref('language');
            if ($user_->isStub()) {
                $mainaddress = $user_->getMainAddress();
                $addo = new Email();
                if ($addo->load($mainaddress)) {
                    $lang = $addo->getPref('language');
                }
            }
        }

        if (isset($_SESSION['admin'])) {
            $lang = 'en';
        }
        // third, if a lang variable is passed through the url, it overrides preferences
        if (isset($_REQUEST['lang']) && $this->is_available($_REQUEST['lang'])) {
            $lang = $_REQUEST['lang'];
        }
        if (isset($_GET['l']) && $this->is_available($_GET['l'])) {
            $lang = $_GET['l'];
        }

        // finally we check that the language exists and is available
        if (!$this->is_available($lang)) {
            $this->lang_ = "en";
        } else {
            $this->lang_ = $lang;
        }

        // admin actually only exists in English
        if ($type == 'admin') {
            $this->lang_ = "en";
        }

        $this->type_ = $type;
        $this->reload();
    }

    /**
     * Get the actual language used
     * @return string language shortcut
     */
    public function getLanguage()
    {
        return $this->lang_;
    }

    /**
     * Set the language to be used
     * @param $lang  string new language shortcut (must be available)
     * @return       bool   true on success, false on failure
     */
    public function setLanguage($lang)
    {
        if (isset($this->available_languages_[$lang])) {
            $this->lang_ = $lang;
            return true;
        }
        return false;
    }

    /**
     * Get this class object instance
     * The parameter is the type to be used (user or admin), and is only used the first time it is called
     * @param  $type  string
     * @return        Language
     */
    public static function getInstance($type)
    {
        if (empty(self::$instance_)) {
            self::$instance_ = new Language($type);
        }
        return self::$instance_;
    }

    /**
     * Reload (or load) the language file
     * This will read the language files again and repopulate the translation arrays
     * @return   bool  true on success, false on failure
     */
    function reload()
    {
        if ($this->type_ == 'admin') {
            $this->lang_ = 'en';
        }

        $txt = [];
        $this->txts_ = [];

        // Load the default arrays and overwrite it by selected language.
        // This permits to have default words for missing translations.
        include($this->sysconf_->SRCDIR_ . "/www/" . $this->type_ . "/htdocs/lang/en/texts.php");

        foreach ($txt as $t => $str) {
            $this->txts_[$t] = $str;
        }
        include($this->sysconf_->SRCDIR_ . "/www/" . $this->type_ . "/htdocs/lang/" . $this->lang_ . "/texts.php");

        foreach ($txt as $t => $str) {
            $this->txts_[$t] = $str;
        }
        return true;
    }

    /**
     * Get the languages array
     * Return all the available language as an array of shortcut => full name or inversed
     * @param  $mode  string return array with full names as key if equals to FULLNAMEASKEY, shortcuts as key otherwise
     * @return        array  array of languages
     */
    public function getLanguages($mode = '')
    {
        if ($mode == 'FULLNAMEASKEY') {
            return $this->inversed_languages_;
        }
        return $this->available_languages_;
    }

    /**
     * Set a text translation
     * The tag corresponding may not already exists in the global language file, so this can be used to add texts in templates
     * @param $text  string  tag of the text
     * @param $value string  translated value
     * @return       bool    true on success, false on failure
     */
    public function setTextValue($text, $value)
    {
        $this->txts_[$text] = $value;
    }

    /**
     * get the translation of a text
     * @param $text_tag string text tag to be retrieved
     * @return          string text translated or "" if not found
     */
    public function print_txt($text_tag)
    {
        if (isset($this->txts_[$text_tag])) {
            return $this->txts_[$text_tag];
        }
        return "";
    }

    /**
     * get the translation of a text with a variable inside
     * @param $text_tag string  test tag to be retrieved
     * @param $param    mixed   value to be inserted/replaces inside the text
     * @return          string  text translated or "" if not found
     */
    public function print_txt_param($text_tag, $param)
    {
        if (isset($this->txts_[$text_tag])) {
            $str = $this->txts_[$text_tag];
            return str_replace('__PARAM__', $param, $str);
        }
        return "";
    }

    public function print_txt_mparam($text_tag, $params)
    {
        $i = 1;
        $str = $this->txts_[$text_tag];
        foreach ($params as $param) {
            $str = str_replace("__PARAM" . $i . "__", $param, $str);
            $i++;
        }
        return $str;
    }

    /**
     * @todo to be removed !
     */
    public function html_select()
    {
        $ret = "<select name=\"lang\">\n";
        foreach ($this->available_languages_ as $short => $long) {
            $ret .= "  <option value=\"$short\"";
            if ($short == $this->lang_) {
                $ret .= " selected=\"selected\"";
            }
            $ret .= ">$long</option>\n";
        }
        $ret .= "</select>\n";
        return $ret;
    }

    /**
     * Check if a language exists
     * @param  $lang  string  language shortcut
     * @return        bool    true if language exists, false otherwise
     */
    public function is_available($lang)
    {
        if (isset($this->available_languages_[$lang])) {
            return true;
        }
        return false;
    }
}
