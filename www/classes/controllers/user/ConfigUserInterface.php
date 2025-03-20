<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the users interface settings
 */

/**
 * user interface configuration page controller
 *
 * @package mailcleaner
 */
class ConfigUserInterface
{

    private $form_;
    private $message_;

    public function __construct()
    {
        $this->form_ = new Form('prefs', 'post', $_SERVER['PHP_SELF'] . "?t=int");
    }

    public function processInput()
    {
        global $lang_;
        global $user_;

        $posted = $this->form_->getResult();
        if ($this->form_->shouldSave()) {
            if ($lang_->is_available($posted['lang'])) {
                $user_->setPref('language', $posted['lang']);
                if (!$user_->isStub()) {
                    if ($user_->save() == 'OKSAVED') {
                        $this->message_ = 'PARAMETERSSAVED';
                    }
                } else {
                    $this->message_ = 'PARAMETERSSAVED';
                }
                $add = null;
                foreach ($user_->getAddresses() as $add => $ismain) {
                    $addo = new Email();
                    if ($addo->load($add)) {
                        $addo->set_language($posted['lang']);
                        $addo->save();
                    }
                }

                $lang_->setLanguage($posted['lang']);
                $lang_->reload();
            }
        }
    }

    public function addReplace($replace, $template)
    {
        global $lang_;

        $replace['__BEGIN_PREFS_FORM__'] = $this->form_->open() . $this->form_->hidden('l', $lang_->getLanguage());
        $replace['__END_PREFS_FORM__'] = $this->form_->close();
        $replace['__SAVE_BUTTON__'] = $this->form_->submit('submit', $lang_->print_txt('SAVE'), '');

        $replace['__LANGUAGE_LIST__'] = $this->getLanguageListInTemplate($template);
        $replace['__MESSAGE__'] = $lang_->print_txt($this->message_);
        return $replace;
    }

    private function getLanguageListInTemplate($template)
    {
        global $lang_;

        $ret = "";

        $tmplang = new Language('user');
        foreach ($lang_->getLanguages('FULLNAMEASKEY') as $lname => $lang) {
            $t = $template->getTemplate('LANGUAGE');
            $t = str_replace('__SLANG__', $lang, $t);

            $tmplang->setlanguage($lang);
            $tmplang->reload();
            $t = str_replace('__S_CHOOSE_LANG__', $tmplang->print_txt('CHOOSETHISLANG'), $t);
            $ret .= $t;
        }
        return $ret;
    }
}
