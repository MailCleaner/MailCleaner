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
 * user address list configuration page controller
 *
 * @package mailcleaner
 */
class ConfigUserAddressList
{

    private $message_;
    private $add_;
    private $addform_;
    private $remform_;

    public function __construct()
    {
        $this->addform_ = new Form('add', 'post', $_SERVER['PHP_SELF'] . "?t=addlist");
        $this->remform_ = new Form('rem', 'post', $_SERVER['PHP_SELF'] . "?t=addlist");
    }

    public function processInput()
    {
        global $user_;

        if ($user_->isStub()) {
            return;
        }
        $addposted = $this->addform_->getResult();
        if ($this->addform_->shouldSave()) {
            // adding address ($addposted['address'])
            require_once('user/AliasRequest.php');
            $request = new AliasRequest(null);
            $this->message_ = $request->requestForm($addposted['address']);
            $this->add_ = $addposted['address'];
        }

        $remposted = $this->remform_->getResult();
        if ($this->remform_->shouldSave()) {
            foreach ($remposted as $key => $val) {
                $matches = [];
                if ($val == 1 && preg_match("/^add_(\S+)/", $key, $matches)) {
                    if (preg_match('/_cb$/', $key)) {
                        continue;
                    }
                    $add = str_replace('_AAA_', '@', $matches[1]);
                    $add = str_replace('_PPP_', '.', $add);
                    $add = str_replace('UUU', '-', $add);
                    // removing address ($add)
                    $message = 'CANNOTREMOVEMAINADD';
                    if ($user_->removeAddress($add)) {
                        $message = '';
                    }
                    // removing any pending request
                    require_once('user/AliasRequest.php');
                    $request = new AliasRequest(null);
                    if ($request->remAliasWithoutID($add)) {
                        $message = '';
                    }
                    $this->add_ = $add;
                }
            }
        }
    }

    public function addReplace($replace, $template)
    {
        global $lang_;

        $replace['__ADDRESS_LIST__'] = $this->getAddressListInTemplate($template, $this->remform_);

        $replace['__BEGIN_ADD_FORM__'] = $this->addform_->open();
        $replace['__END_ADD_FORM__'] = $this->addform_->close();
        $replace['__INPUT_ADDADDRESS__'] = $this->addform_->input('address', 40, '');
        $replace['__INPUT_ADDSUBMIT__'] = $this->addform_->submit('addaddress', $lang_->print_txt('ADDTHEADDRESS'), '');

        $replace['__BEGIN_REM_FORM__'] = $this->remform_->open();
        $replace['__END_REM_FORM__'] = $this->remform_->close();
        $replace['__INPUT_REMSUBMIT__'] = $this->remform_->submit('remaddress', $lang_->print_txt('REMTHEADDRESS'), '');

        $replace['__MESSAGE__'] = $lang_->print_txt_param($this->message_, $this->add_);
        return $replace;
    }

    private function getAddressListInTemplate($template, $f)
    {
        global $user_;
        global $lang_;

        $ret = "";
        foreach ($user_->getAddressesWithPending() as $add => $pending) {
            $t = $template->getTemplate('ADDRESS');

            $t = str_replace('__ADDRESS__', $add, $t);
            $pending_str = "";
            $pending_class = "notpending";
            if ($pending) {
                $pending_str = "(" . $lang_->print_txt('WAITINGCONFIRMATION') . ")";
                $pending_class = "pending";
                $t = preg_replace('/__IF_PENDING__(.*)__FI_PENDING__/', '${1}', $t);
            } else {
                $t = preg_replace('/__IF_PENDING__(.*)__FI_PENDING__/', '', $t);
            }
            $t = str_replace('__PENDINGCLASS__', $pending_class, $t);
            $t = str_replace('__PENDING__', $pending_str, $t);

            $cleanadd = str_replace('@', '_AAA_', $add);
            $cleanadd = str_replace('.', '_PPP_', $cleanadd);
            $t = str_replace('__INPUT_CHECKBOXADD__', $f->checkbox('add_' . $cleanadd, 1, 0, '', 1), $t);
            $ret .= $t;
        }
        return $ret;
    }
}
