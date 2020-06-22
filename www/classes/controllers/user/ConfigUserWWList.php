<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2020, John Mertz
 *
 * This is the users interface settings
 */

/**
 * user address list configuration page controller
 *
 * @package mailcleaner
 */
class ConfigUserWWList {

    private $form_;
    private $message_;
    private $add_;
    private $wwlist_;
    private $type_ = 'warn';

    public function __construct() {
        global $user_;

        if ($_GET['t'] && $_GET['t'] == 'white') {
            $this->type_ = 'white';
        }
        if ($_GET['t'] && $_GET['t'] == 'black') {
            $this->type_ = 'black';
        }
        if ($_GET['t'] && $_GET['t'] == 'wnews') {
            $this->type_ = 'wnews';
        }
        if ($_GET['t'] && $_GET['t'] == 'warn') {
            $this->type_ = 'warn';
        }

        $this->selform_ = new Form('selectadd', 'post', $_SERVER['PHP_SELF']."?t=".$this->type_);
        $this->addform_ = new Form('add', 'post', $_SERVER['PHP_SELF']."?t=".$this->type_);
        $this->remform_ = new Form('rem', 'post', $_SERVER['PHP_SELF']."?t=".$this->type_);
        $this->add_ = $user_->getPref('gui_default_address');
    }

    public function processInput() {
        global $lang_;
        global $user_;


        $selectedposted = $this->selform_->getResult();
        if (isset($selectedposted['sa']) && $user_->hasAddress($selectedposted['sa'])) {
            $this->add_ = $selectedposted['sa'];
        }
        $addposted = $this->addform_->getResult();
        if (isset($addposted['sa']) && $user_->hasAddress($addposted['sa'])) {
            $this->add_ = $addposted['sa'];
        }
        $remposted = $this->remform_->getResult();
        if (isset($remposted['sa']) && $user_->hasAddress($remposted['sa'])) {
            $this->add_ = $remposted['sa'];
        }
        if ($user_->isStub()) {
            $this->add_ = $user_->getMainAddress();
        }

        require_once('user/WWList.php');
        $this->wwlist_ = new WWList();
        $this->wwlist_->load($this->add_, $this->type_);

        if ($this->addform_->shouldSave()) {
            // adding entry
            if ($addposted['entry'] && $addposted['entry'] != "") {
                require_once('user/WWEntry.php');
                $new = new WWEntry();
                // FILTER_VALIDATE_DOMAIN requires PHP >= 7; must do manually
                if (!filter_var($addposted['entry'], FILTER_VALIDATE_EMAIL) && !preg_match('/^[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.[a-zA-Z]{2,}$/', $addposted['entry'])) {
                    $this->message_ = 'Add ' . $addposted['entry'] . ' failed (invalid sender)'; 
                } else {
                    $sender = $addposted['entry'];

                    if (isset($addposted['togroup'])) {
                        $this->message_ = 'Adding ' . $sender . ' to:';
                        foreach ($user_->getAddresses() as $address => $ismain) {
                            $new->load(0);
                            $new->setPref('sender', $sender);
                            $new->setPref('comments', $addposted['comment']);
                            $new->setPref('type', $this->type_);
                            $new->setPref('status', '1');
                            $new->setPref('recipient', $address);
                            if ($new->save()) {
                                $this->message_ .= ' ' . $address . '(success),';
                            } else {
                                $this->message_ .= ' ' . $address . '(failed),';
                            }
                            $this->message_ = preg_replace('/,$/', '', $this->message_);
                        }
                    } else {
                        $new->load(0);
                        $new->setPref('sender', $sender);
                        $new->setPref('comments', $addposted['comment']);
                        $new->setPref('type', $this->type_);
                        $new->setPref('status', '1');
                        $new->setPref('recipient', $this->add_);
                        if ($new->save()) {
                            $this->message_ = 'Adding ' . $sender . ' to: ' . $this->add_ . '(success)';
                        }
                        $this->message_ = 'Adding ' . $sender . ' to: ' . $this->add_ . '(failed)';
                    }
                }
            }
            $this->wwlist_->reload();
        }

        if ($this->remform_->shouldSave()) {
            foreach ($remposted as $key => $val) {
                $matches = array();
                if ($val == 1 && preg_match("/^ent_(\S+)/", $key, $matches)) {
                    if (preg_match('/_cb$/', $key)) { continue; }
			        $add = $this->wwlist_->decodeVarName($matches[1]);
                    if ($remposted['wantdisable'] && $remposted['wantdisable'] > 0) {
                        $ent = $this->wwlist_->getEntryByPref('sender', $add);
                        if ($ent->getPref('status') < 1) {
                            $ent->enable();
                        } else {
                            $ent->disable();
                        }
                    } else {
                        $ent = $this->wwlist_->getEntryByPref('sender', $add);
                        $ent->delete();
                        $this->wwlist_->reload();
                    }
                }
            }
        }
    }

    public function addReplace($replace, $template) {
        global $lang_;
        global $user_;

        $replace['__ENTRY_LIST__'] = $this->getEntryListInTemplate($template, $this->remform_);

        $replace['__BEGIN_SELECT_FORM__'] = $this->selform_->open();
        $replace['__END_SELECT_FORM__'] = $this->selform_->close();
        $replace['__INPUT_SELECTADDRESS__'] = $this->selform_->select('sa', $user_->getAddressesForSelect(), $this->add_, '');
        $replace['__BEGIN_ADD_FORM__'] = $this->addform_->open().$this->addform_->hidden('sa', $this->add_);
        $replace['__END_ADD_FORM__'] = $this->addform_->close();

        # get antispam global prefs
        require_once('config/AntiSpam.php');
        $antispam_ = new AntiSpam();
        $antispam_->load();

        if ($this->type_ == "white" && $antispam_->getPref('enable_whitelists') && $user_->getDomain()->getPref('enable_whitelists'))  {
            $replace['__INPUT_ADDADDRESS__'] = $this->addform_->input('entry', 38, '');
            $replace['__INPUT_ADDCOMMENT__'] = $this->addform_->input('comment', 35, '');
            $replace['__INPUT_ADDSUBMIT__']  = $this->addform_->submit('addentry', $lang_->print_txt('ADDTHEENTRY'), '');
            $replace['__INPUT_ADDTOGROUP__'] = $this->addform_->submit('togroup', $lang_->print_txt('ADDTOGROUP'), '');
        } else if ($this->type_ == "black" && $antispam_->getPref('enable_blacklists') && $user_->getDomain()->getPref('enable_blacklists'))  {
            $replace['__INPUT_ADDADDRESS__'] = $this->addform_->input('entry', 38, '');
            $replace['__INPUT_ADDCOMMENT__'] = $this->addform_->input('comment', 35, '');
            $replace['__INPUT_ADDSUBMIT__']  = $this->addform_->submit('addentry', $lang_->print_txt('ADDTHEENTRY'), '');
            $replace['__INPUT_ADDTOGROUP__'] = $this->addform_->submit('togroup', $lang_->print_txt('ADDTOGROUP'), '');
        } else if ($this->type_ == "wnews")  {
            $replace['__INPUT_ADDADDRESS__'] = $this->addform_->input('entry', 38, '');
            $replace['__INPUT_ADDCOMMENT__'] = $this->addform_->input('comment', 35, '');
            $replace['__INPUT_ADDSUBMIT__']  = $this->addform_->submit('addentry', $lang_->print_txt('ADDTHEENTRY'), '');
            $replace['__INPUT_ADDTOGROUP__'] = $this->addform_->submit('togroup', $lang_->print_txt('ADDTOGROUP'), '');
        } else if ($this->type_ == "warn" && $antispam_->getPref('enable_warnlists') && $user_->getDomain()->getPref('enable_warnlists'))  {
            $replace['__INPUT_ADDADDRESS__'] = $this->addform_->input('entry', 38, '');
            $replace['__INPUT_ADDCOMMENT__'] = $this->addform_->input('comment', 35, '');
            $replace['__INPUT_ADDSUBMIT__']  = $this->addform_->submit('addentry', $lang_->print_txt('ADDTHEENTRY'), '');
            $replace['__INPUT_ADDTOGROUP__'] = $this->addform_->submit('togroup', $lang_->print_txt('ADDTOGROUP'), '');
        } else {
            $replace['__INPUT_ADDADDRESS__'] = $this->addform_->inputDisabled('entry', 38, '');
            $replace['__INPUT_ADDCOMMENT__'] = $this->addform_->inputDisabled('comment', 35, '');
            $replace['__INPUT_ADDSUBMIT__']  = $this->addform_->submitDisabled('none', $lang_->print_txt('ADDTHEENTRY'), '') . '<p style="color:red;">'.$lang_->print_txt('SPAM_WHITELIST_DISABLED').'</p>';
            $replace['__INPUT_ADDTOGROUP__'] = $this->addform_->submit('togroup', $lang_->print_txt('ADDTOGROUP'), '');
        }

        $replace['__BEGIN_REM_FORM__'] = $this->remform_->open().$this->remform_->hidden('sa', $this->add_).$this->remform_->hidden('wantdisable', 0);
        $replace['__END_REM_FORM__'] = $this->remform_->close();
        $replace['__INPUT_REMSUBMIT__'] = $this->remform_->submit('rementry', $lang_->print_txt('REMTHEENTRY'), '');
        $replace['__INPUT_DISABLESUBMIT__'] = $this->remform_->button('disableentry', $lang_->print_txt('DISABLETHEENTRY'), 'window.document.forms[\''.$this->remform_->getName().'\'].'.$this->remform_->getName().'_wantdisable.value=\'1\';');

        $replace['__MESSAGE__'] = $lang_->print_txt_param($this->message_, $this->add_);
        return $replace;
    }

    private function getEntryListInTemplate($template, $f) {
        global $user_;
        global $lang_;

        $ret = "";
        foreach ($this->wwlist_->getElements() as $entry) {
            if (! $entry instanceof WWEntry ) {
                continue;
            }
            $t = $template->getTemplate('ENTRY');
            $entrytext = htmlentities($entry->getPref('sender'));
            $cleanentry = $this->wwlist_->encodeVarName($entrytext);
            $t = str_replace('__ENTRY__', $entrytext, $t);
            $t = str_replace('__INPUT_CHECKBOXENTRY__', $f->checkbox('ent_'.$cleanentry, 1, 0, '', 1).$f->hidden('id', $entry->getPref('id')), $t);

            $t = str_replace('__COMMENT__', htmlentities($entry->getPref('comments')), $t);
            if ($entry->getPref('comments') != "") {
                $t = preg_replace('/__IF_COMMENT__(.*)__FI_COMMENT__/', '${1}', $t);
            } else {
                $t = preg_replace('/__IF_COMMENT__(.*)__FI_COMMENT__/', '', $t);
            }

            $active_class = 'entryactive';
            $comment_icon = $template->getDefaultValue('COMMENTICONACTIVE');
            if ($entry->getPref('status') < 1) {
                $active_class = 'entryinactive';
            $comment_icon = $template->getDefaultValue('COMMENTICONINACTIVE');
            }
            $t = str_replace('__COMMENTICON__', $comment_icon, $t);
            $t = str_replace('__ACTIVECLASS__', $active_class, $t);
            $ret .= $t;
        }

        return $ret;
    }

}
?>
