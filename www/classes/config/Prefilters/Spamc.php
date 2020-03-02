<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */

/**
 * this is a preference handler
 */
 require_once('helpers/PrefHandler.php');

/**
 * This class is only a settings wrapper for the PreFilter modules configuration
 */
class Spamc extends PreFilter {

    /**
     * prefilter properties
     * @var array
     */
	private $specpref_ = array(
                        'use_bayes' => 1,
                        'bayes_autolearn' => 0,
                        'use_rbls' => 1,
                        'rbls_timeout' => 20,
                        'use_dcc' => 1,
                        'dcc_timeout' => 20,
                        'use_razor' => 1,
                        'razor_timeout' => 20,
                        'use_pyzor' => 1,
                        'pyzor_timeout' => 20,
                        'sa_rbls' => '',
                        'use_ocr' => 1,
                        'use_pdfinfo' => 1,
                        'use_imageinfo' => 1,
                        'use_botnet' => 1,
                        'use_domainkeys' => 1,
                        'domainkeys_timeout' => 5,
                        'use_spf' => 1,
                        'spf_timeout' => 5,
                        'use_dkim' => 1,
                        'dkim_timeout' => 5
	                 );

public function subload() {}

public function addSpecPrefs() {
   $this->addPrefSet('antispam', 'a', $this->specpref_);
}

public function getSpecificTMPL() {
  return "prefilters/Spamc.tmpl";
}

public function getSpeciticReplace($template, $form) {
  $ret = array(
         "__FORM_INPUTUSEBAYES__" => $form->checkbox('use_bayes', 1, $this->getPref('use_bayes'), '', 1),
         "__FORM_INPUTUSEAUTOLEARN__" => $form->checkbox('bayes_autolearn', 1, $this->getPref('bayes_autolearn'), '', 1),
         "__FORM_INPUTUSEOCR__" => $form->checkbox('use_ocr', 1, $this->getPref('use_ocr'), '', 1),
         "__FORM_INPUTUSEIMAGEINFO__" => $form->checkbox('use_imageinfo', 1, $this->getPref('use_imageinfo'), '', 1),
         "__FORM_INPUTUSEPDFINFO__" => $form->checkbox('use_pdfinfo', 1, $this->getPref('use_pdfinfo'), '', 1),
         "__FORM_INPUTUSERBLS__" => $form->checkbox('use_rbls', 1, $this->getPref('use_rbls'), '', 1),
         "__FORM_INPUTUSEDCC__" => $form->checkbox('use_dcc', 1, $this->getPref('use_dcc'), '', 1),
         "__FORM_INPUTUSERAZOR__" => $form->checkbox('use_razor', 1, $this->getPref('use_razor'), '', 1),
         "__FORM_INPUTUSEPYZOR__" => $form->checkbox('use_pyzor', 1, $this->getPref('use_pyzor'), '', 1),
         "__FORM_INPUTUSESPF__" => $form->checkbox('use_spf', 1, $this->getPref('use_spf'), '', 1),
         "__FORM_INPUTUSEDOMAINKEYS__" => $form->checkbox('use_domainkeys', 1, $this->getPref('use_domainkeys'), '', 1),
         "__FORM_INPUTUSEDKIM__" => $form->checkbox('use_dkim', 1, $this->getPref('use_dkim'), '', 1),
         "__FORM_INPUTUSEBOTNET__" => $form->checkbox('use_botnet', 1, $this->getPref('use_botnet'), '', 1),

         "__FORM_INPUTRBLSTIMEOUT__" => $form->input('rbls_timeout', 4, $this->getPref('rbls_timeout')),
         "__FORM_INPUTDCCTIMEOUT__" => $form->input('dcc_timeout', 4, $this->getPref('dcc_timeout')),
         "__FORM_INPUTRAZORTIMEOUT__" => $form->input('razor_timeout', 4, $this->getPref('razor_timeout')),
         "__FORM_INPUTPYZORTIMEOUT__" => $form->input('pyzor_timeout', 4, $this->getPref('pyzor_timeout')),
         "__FORM_INPUTSPFTIMEOUT__" => $form->input('spf_timeout', 4, $this->getPref('spf_timeout')),
         "__FORM_INPUTDOMAINKEYSTIMEOUT__" => $form->input('domainkeys_timeout', 4, $this->getPref('domainkeys_timeout')),
         "__FORM_INPUTDKIMTIMEOUT__" => $form->input('dkim_timeout', 4, $this->getPref('dkim_timeout')),

         "__FORM_INPUTSARBLS__" => $form->input('sa_rbls', 40, $this->getPref('sa_rbls')),


        );

  return $ret;
}

public function subsave($posted) {}
}
?>
