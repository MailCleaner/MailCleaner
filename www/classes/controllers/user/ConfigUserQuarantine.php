<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the users interface settings
 */
 
/** 
 * user interface configuration page controller
 * 
 * @package mailcleaner
 */
class ConfigUserQuarantine {
 
 private $form_;
 private $message_ = "";
 
 public function __construct() {
 	$this->form_ = new Form('quar', 'post', $_SERVER['PHP_SELF']."?t=quar");
 }   
    
 public function processInput() {
    global $lang_;
    global $user_;
    	
    $posted = $this->form_->getResult();
    if ($this->form_->shouldSave()) {
      $this->message_ = 'NOTSAVED';
      foreach (array('gui_displayed_spams', 'gui_displayed_days', 'gui_mask_forced', 'gui_graph_type', 'gui_group_quarantines') as $po) {
          if (isset($posted[$po])) {
              $user_->setPref($po, $posted[$po]);
          }
      }
      if ($user_->hasAddress($posted['gui_default_address'])) {
        $user_->setPref('gui_default_address', $posted['gui_default_address']);
      }
      if (!$user_->isStub()) {
        if ($user_->save()) {
          $this->message_ = 'PARAMETERSSAVED';
        }
      } else {
      	$user_->clearTmpPref('gui_displayed_days');
      	$_SESSION['user'] = serialize($user_);
      	$email = new Email();
      	$email->load($user_->getMainAddress());
      	foreach (array('gui_displayed_spams', 'gui_displayed_days', 'gui_mask_forced', 'gui_graph_type', 'gui_group_quarantines') as $p) {
      		$email->setPref($p, $user_->getPref($p));
      	}
      	if ($email->save()) {
      		$this->message_ = 'PARAMETERSSAVED';
      	}
      }
    }
 }
 
 public function addReplace($replace, $template) {
   global $lang_;
   global $user_;
   
   if (!$this->form_) {
     return '';
   }
   $nbspams_select = array('5' => '5', '10' => '10', '20' => '20', '50' => '50', '100' => '100'); 
   $replace['__BEGIN_QUAR_FORM__'] = $this->form_->open();
   $replace['__END_QUAR_FORM__'] = $this->form_->close();
   //$user_ = new User();
   //$user_->load($user_->getPref('username'));
   $replace['__INPUT_SELECTQUARSPAMDISPLAYED__'] = $this->form_->select('gui_displayed_spams', $nbspams_select, $user_->getPref('gui_displayed_spams'), ';');
   $replace['__INPUT_INPUTQUARNBDAYS__'] = $this->form_->input('gui_displayed_days', 4, $user_->getPref('gui_displayed_days'));
   $replace['__INPUT_CHECKBOXMASKFORCED__'] = $this->form_->checkbox('gui_mask_forced', '1', $user_->getPref('gui_mask_forced'), '', 1);
   $replace['__INPUT_SELECTADDRESSES__'] = $this->form_->select('gui_default_address', $user_->getAddressesForSelect(), $user_->getPref('gui_default_address'), ';', $user_->getPref('gui_group_quarantines'));
   $replace['__INPUT_GROUPQUARANTINES__'] = $this->form_->checkbox('gui_group_quarantines', '1', $user_->getPref('gui_group_quarantines'), 'javascript=groupAddressesConfig();', 1);
   $replace['__SAVE_BUTTON__'] = $this->form_->submit('submit', $lang_->print_txt('SAVE'), '');
   $replace['__MESSAGE__'] = $lang_->print_txt($this->message_);
   
   return $replace;
 }   
    
}
?>
