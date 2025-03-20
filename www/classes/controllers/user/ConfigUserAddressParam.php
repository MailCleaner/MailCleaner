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
 * user address parameters configuration page controller
 *
 * @package mailcleaner
 */
class ConfigUserAddressParam
{

    private $form_;
    private $message_;
    private $add_;
    private $data_ = ['delivery_type', 'spam_tag', 'quarantine_bounces', 'summary_type', 'summary_to', 'allow_newsletters'];

    public function __construct()
    {
        global $user_;

        $this->form_ = new Form('param', 'post', $_SERVER['PHP_SELF'] . "?t=addparam");

        $mainadd = $user_->getMainAddress();
        $this->add_ = new Email();
        $this->add_->load($mainadd);
    }

    public function processInput()
    {
        global $lang_;
        global $user_;

        $posted = $this->form_->getResult();

        if (isset($posted['address']) && $user_->hasAddress($posted['address'])) {
            $this->add_->load($posted['address']);
        }

        if ($this->form_->shouldSave()) {

            $this->message_ = 'NOTSAVED';
            $check = $this->checkParams($posted);
            if ($check != 'OK') {
                $this->message_ = $check;
                return false;
            }
            $this->setParams($this->add_, $posted);
            $this->add_->save();
            $this->message_ = 'PARAMETERSSAVED';
            $this->add_->load($this->add_->getPref('address'));

            if ($user_->getPref('gui_group_quarantines')) {
                $this->setSummaryParam($user_, $posted);
                $user_->save();
                foreach ($user_->getAddresses() as $address => $ismain) {
                    $add = new Email();
                    $add->load($address);
                    $this->setSummaryParam($add, $posted);
                    $add->save();
                }
            }

            if ($posted['applytoall'] > 0) {
                foreach ($user_->getAddresses() as $address => $ismain) {
                    $add = new Email();
                    $add->load($address);
                    $this->setParams($add, $posted);
                    if (!$add->save()) {
                        $this->message_ = 'NOTSAVED';
                    }
                }
            }
        }
    }

    private function checkParams($posted)
    {
        if (!$posted['summary_to'] == '' && !preg_match('/^\S+\@\S+$/', $posted['summary_to'])) {
            return 'INVALIDSUMMARYTO';
        }
        return 'OK';
    }

    private function setParams($add, $posted)
    {

        foreach ($this->data_ as $data) {
            if (isset($posted[$data])) {
                $add->setPref($data, $posted[$data]);
                $this->setSummaryParam($add, $posted);
            }
        }
        return true;
    }

    private function setSummaryParam($object, $posted)
    {
        $object->setPref('daily_summary', 0);
        $object->setPref('weekly_summary', 0);
        $object->setPref('monthly_summary', 0);
        if (isset($posted['summaryfreq'])) {
            $object->setPref($posted['summaryfreq'], 1);
        }
        $object->setPref('summary_type', $posted['summary_type']);
        $summary_to = '';
        if ($object instanceof Email && $posted['summary_to_select'] != $object->getPref('address') && $posted['summary_to_select'] != 'other') {
            $summary_to = $posted['summary_to_select'];
        }
        if ($object instanceof User && $posted['summary_to_select'] != $object->getMainAddress() && $posted['summary_to_select'] != 'other') {
            $summary_to = $posted['summary_to_select'];
        }
        if ($posted['summary_to_select'] == 'other' && isset($posted['summary_to'])) {
            $summary_to = $posted['summary_to'];
        }
        $object->setPref('summary_to', $summary_to);
        return true;
    }

    public function addReplace($replace, $template)
    {
        global $lang_;
        global $user_;

        $freqs[$lang_->print_txt('DAILY')] = 'daily_summary';
        $freqs[$lang_->print_txt('WEEKLY')] = 'weekly_summary';
        $freqs[$lang_->print_txt('MONTHLY')] = 'monthly_summary';
        $freqs[$lang_->print_txt('NOSUMMARY')] = 'none';
        $sfreq = 'none';
        $main_address = new Email();
        $main_address->load($user_->getMainAddress());
        foreach ($freqs as $key => $val) {
            if ($user_->getPref('gui_group_quarantines')) {
                if ($main_address->getPref($val) > 0) {
                    $sfreq = $val;
                }
            } else {
                if ($this->add_->getPref($val) > 0) {
                    $sfreq = $val;
                }
            }
        }


        $formats[$lang_->print_txt('PLAINTEXT')] = 'text';
        $formats[$lang_->print_txt('HTML')] = 'html';
        $formats[$lang_->print_txt('DIGEST')] = 'digest';

        $replace['__BEGIN_PARAM_FORM__'] = $this->form_->open();
        $replace['__END_PARAM_FORM__'] = $this->form_->close();

        $select_for_summary_to = $user_->getAddressesForSelect();
        $select_for_summary_to[' ' . $lang_->print_txt('OTHER')] = 'other';
        $selected_summary_to = $this->add_->getPref('address');
        if ($this->add_->getPref('summary_to') != '') {
            if (array_key_exists($this->add_->getPref('summary_to'), $select_for_summary_to)) {
                $selected_summary_to = $this->add_->getPref('summary_to');
            } else {
                $selected_summary_to = 'other';
            }
        }

        $replace['__ADDRESSSELECT__'] = $this->form_->select('address', $user_->getAddressesForSelect(), $this->add_->getPref('address'), '');
        $replace['__INPUT_RADIOQUARANTINE__'] = $this->form_->radiojs('delivery_type', '2', $this->add_->getPref('delivery_type'), 'javascript=enableSpamTag(2);');
        $replace['__INPUT_RADIOTAG__'] = $this->form_->radiojs('delivery_type', '1', $this->add_->getPref('delivery_type'), 'javascript=enableSpamTag(1);');
        $replace['__INPUT_RADIODROP__'] = $this->form_->radiojs('delivery_type', '3', $this->add_->getPref('delivery_type'), 'javascript=enableSpamTag(3);');

        $replace['__INPUT_RADIONEWSLETTER_QUARANTINE__'] = $this->form_->radio('allow_newsletters', '0', $this->add_->getPref('allow_newsletters'));
        $replace['__INPUT_RADIONEWSLETTER_ALLOW__'] = $this->form_->radio('allow_newsletters',  '1', $this->add_->getPref('allow_newsletters'));

        $replace['__DELIVERYTYPE__'] = $this->add_->getPref('delivery_type');
        $replace['__INPUT_TAG__'] = $this->form_->input('spam_tag', 10, $this->add_->getPref('spam_tag'));
        $replace['__INPUT_SELECTSUMMARYFREQ__'] = $this->form_->select('summaryfreq', $freqs, $sfreq, ';');
        if ($user_->getPref('gui_group_quarantines')) {
            $replace['__INPUT_SELECTSUMMARYFORMAT__'] = $this->form_->select('summary_type', $formats, $user_->getPref('summary_type'), ';');
        } else {
            $replace['__INPUT_SELECTSUMMARYFORMAT__'] = $this->form_->select('summary_type', $formats, $this->add_->getPref('summary_type'), ';');
        }
        $replace['__INPUT_SUMMARYTO__'] = $this->form_->input('summary_to', 40, $this->add_->getPref('summary_to'));
        $replace['__INPUT_SELECTSUMMARYTO__'] = $this->form_->select('summary_to_select', $select_for_summary_to, $selected_summary_to, 'javascript=changeOtherSummaryToField();');
        $replace['__INPUT_CHECKBOXAPPLYTOALL__'] = $this->form_->checkbox('applytoall', '1', '', '', 1);
        $replace['__INPUT_SUBMIT__'] = $this->form_->submit('submit', $lang_->print_txt('SAVE'), '');
        $replace['__INPUT_CHECKBOXBQUARBOUNCES__'] = $this->form_->checkbox('quarantine_bounces', '1', $this->add_->getPref('quarantine_bounces'), '', 1);
        $replace['__MESSAGE__'] = $lang_->print_txt_param($this->message_, $this->add_->getPref('address'));
        return $replace;
    }
}
