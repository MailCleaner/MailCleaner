<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Mentor Reka
 * @copyright (C) 2017 Mentor Reka <reka.mentor@gmail.com>
 * Registration CE form
 */

class Default_Form_RegistrationCE extends ZendX_JQuery_Form
{
	
	protected $_registrationmgr;
	
	public function __construct($mgr) {
		$this->_registrationmgr = $mgr;
		parent::__construct();
	}
	
	
	public function init()
	{

           $t = Zend_Registry::get('translate');
	   $layout = Zend_Layout::getMvcInstance();
    	   $view=$layout->getView();

	   $this->setMethod('post');
           $this->setAttrib('id', 'registration_form_ce');

            $sysconf = MailCleaner_Config::getInstance();
	    require_once ('helpers/DM_Custom.php');
	    $db = DM_Custom :: getInstance('127.0.0.1', '3306', 'mailcleaner', $sysconf->getOption('MYMAILCLEANERPWD'), 'mc_community');

	    $query = "SELECT * from registration LIMIT 1";
	    $res = $db->getHash($query);


            $first_name = new  Zend_Form_Element_Text('first_name', array(
	            'label' => $t->_('First Name'). " :",
                    'required' => true));
            $first_name->setValue($res['first_name']);
	    $first_name->addValidator(new Zend_Validate_Regex(array('pattern' => '/^[a-z]{2,}[a-z\-\s]+$/i')));
	    $first_name->setErrorMessages(array('regexInvalid' => 'Only accept alphabet, spaces and "-" characters.',
                                                'regexNotMatch' => 'Only accept alphabet, spaces and "-" characters.',
                                                'regexErrorous' => 'Only accept alphabet, spaces and "-" characters.'));
            $this->addElement($first_name);

	    $last_name = new  Zend_Form_Element_Text('last_name', array(
                    'label' => $t->_('Last Name'). " :",
                    'required' => true));
            $last_name->setValue($res['last_name']);
            $last_name->addValidator(new Zend_Validate_Regex(array('pattern' => '/^[a-z]{2,}[a-z\-\s]+$/i')));
            $last_name->setErrorMessages(array('regexInvalid' => 'Only accept alphabet, spaces and "-" characters.',
                                                'regexNotMatch' => 'Only accept alphabet, spaces and "-" characters.',
                                                'regexErrorous' => 'Only accept alphabet, spaces and "-" characters.'));
            $this->addElement($last_name);


	    $email = new  Zend_Form_Element_Text('email', array(
                'label'    => $t->_('Email address')." :",
                'required' => false,
                'filters'    => array('StringToLower', 'StringTrim')));
            $email->setValue($res['email']);
            $email->addValidator(new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_LOCAL));
            $this->addElement($email);

	    $company = new  Zend_Form_Element_Text('company_name', array(
                    'label' => $t->_('Company'). " :",
                    'required' => false));
            $company->setValue($res['company']);
            $this->addElement($company);

	    $address = new  Zend_Form_Element_Text('address', array(
                    'label' => $t->_('Address'). " :",
                    'required' => false));
            $address->setValue($res['address']);
            $this->addElement($address);

            $postal_code = new  Zend_Form_Element_Text('postal_code', array(
                    'label' => $t->_('Postal code'). " :",
                    'required' => false));
            $postal_code->setValue($res['postal_code']);
            $postal_code->addValidator(new Zend_Validate_Alnum());
            $this->addElement($postal_code);

            $city = new  Zend_Form_Element_Text('city', array(
                    'label' => $t->_('City'). " :",
                    'required' => false));
            $city->setValue($res['city']);
            $this->addElement($city);

            $country = new  Zend_Form_Element_Select('country', array(
                    'label' => $t->_('Country'). " :",
		    'empty_option' => 'Please choose your country',
                    'required' => false,
		    'filters'    => array('StringTrim'),
	    ));

	   $countries = array( 
			"Afghanistan",
			"Albania",
			"Algeria",
			"American Samoa",
			"Andorra",
			"Angola",
			"Anguilla",
			"Antarctica",
			"Antigua and Barbuda",
			"Argentina",
			"Armenia",
			"Aruba",
			"Australia",
			"Austria",
			"Azerbaijan",
			"Bahamas",
			"Bahrain",
			"Bangladesh",
			"Barbados",
			"Belarus",
			"Belgium",
			"Belize",
			"Benin",
			"Bermuda",
			"Bhutan",
			"Bolivia",
			"Bosnia and Herzegovina",
			"Botswana",
			"Bouvet Island",
			"Brazil",
			"British Indian Ocean Territory",
			"Brunei Darussalam",
			"Bulgaria",
			"Burkina Faso",
			"Burundi",
			"Cambodia",
			"Cameroon",
			"Canada",
			"Cape Verde",
			"Cayman Islands",
			"Central African Republic",
			"Chad",
			"Chile",
			"China",
			"Christmas Island",
			"Cocos (Keeling) Islands",
			"Colombia",
			"Comoros",
			"Congo",
			"Congo, the Democratic Republic of the",
			"Cook Islands",
			"Costa Rica",
			"Cote D'Ivoire",
			"Croatia",
			"Cuba",
			"Cyprus",
			"Czech Republic",
			"Denmark",
			"Djibouti",
			"Dominica",
			"Dominican Republic",
			"Ecuador",
			"Egypt",
			"El Salvador",
			"Equatorial Guinea",
			"Eritrea",
			"Estonia",
			"Ethiopia",
			"Falkland Islands (Malvinas)",
			"Faroe Islands",
			"Fiji",
			"Finland",
			"France",
			"French Guiana",
			"French Polynesia",
			"French Southern Territories",
			"Gabon",
			"Gambia",
			"Georgia",
			"Germany",
			"Ghana",
			"Gibraltar",
			"Greece",
			"Greenland",
			"Grenada",
			"Guadeloupe",
			"Guam",
			"Guatemala",
			"Guinea",
			"Guinea-Bissau",
			"Guyana",
			"Haiti",
			"Heard Island and Mcdonald Islands",
			"Holy See (Vatican City State)",
			"Honduras",
			"Hong Kong",
			"Hungary",
			"Iceland",
			"India",
			"Indonesia",
			"Iran, Islamic Republic of",
			"Iraq",
			"Ireland",
			"Israel",
			"Italy",
			"Jamaica",
			"Japan",
			"Jordan",
			"Kazakhstan",
			"Kenya",
			"Kiribati",
			"Korea, Democratic People's Republic of",
			"Korea, Republic of",
			"Kosovo",
			"Kuwait",
			"Kyrgyzstan",
			"Lao People's Democratic Republic",
			"Latvia",
			"Lebanon",
			"Lesotho",
			"Liberia",
			"Libyan Arab Jamahiriya",
			"Liechtenstein",
			"Lithuania",
			"Luxembourg",
			"Macao",
			"Macedonia, the Former Yugoslav Republic of",
			"Madagascar",
			"Malawi",
			"Malaysia",
			"Maldives",
			"Mali",
			"Malta",
			"Marshall Islands",
			"Martinique",
			"Mauritania",
			"Mauritius",
			"Mayotte",
			"Mexico",
			"Micronesia, Federated States of",
			"Moldova, Republic of",
			"Monaco",
			"Mongolia",
			"Montserrat",
			"Morocco",
			"Mozambique",
			"Myanmar",
			"Namibia",
			"Nauru",
			"Nepal",
			"Netherlands",
			"Netherlands Antilles",
			"New Caledonia",
			"New Zealand",
			"Nicaragua",
			"Niger",
			"Nigeria",
			"Niue",
			"Norfolk Island",
			"Northern Mariana Islands",
			"Norway",
			"Oman",
			"Pakistan",
			"Palau",
			"Palestinian Territory, Occupied",
			"Panama",
			"Papua New Guinea",
			"Paraguay",
			"Peru",
			"Philippines",
			"Pitcairn",
			"Poland",
			"Portugal",
			"Puerto Rico",
			"Qatar",
			"Reunion",
			"Romania",
			"Russian Federation",
			"Rwanda",
			"Saint Helena",
			"Saint Kitts and Nevis",
			"Saint Lucia",
			"Saint Pierre and Miquelon",
			"Saint Vincent and the Grenadines",
			"Samoa",
			"San Marino",
			"Sao Tome and Principe",
			"Saudi Arabia",
			"Senegal",
			"Serbia and Montenegro",
			"Seychelles",
			"Sierra Leone",
			"Singapore",
			"Slovakia",
			"Slovenia",
			"Solomon Islands",
			"Somalia",
			"South Africa",
			"South Georgia and the South Sandwich Islands",
			"Spain",
			"Sri Lanka",
			"Sudan",
			"Suriname",
			"Svalbard and Jan Mayen",
			"Swaziland",
			"Sweden",
			"Switzerland",
			"Syrian Arab Republic",
			"Taiwan, Province of China",
			"Tajikistan",
			"Tanzania, United Republic of",
			"Thailand",
			"Timor-Leste",
			"Togo",
			"Tokelau",
			"Tonga",
			"Trinidad and Tobago",
			"Tunisia",
			"Turkey",
			"Turkmenistan",
			"Turks and Caicos Islands",
			"Tuvalu",
			"Uganda",
			"Ukraine",
			"United Arab Emirates",
			"United Kingdom",
			"United States",
			"United States Minor Outlying Islands",
			"Uruguay",
			"Uzbekistan",
			"Vanuatu",
			"Venezuela",
			"Viet Nam",
			"Virgin Islands, British",
			"Virgin Islands, U.s.",
			"Wallis and Futuna",
			"Western Sahara",
			"Yemen",
			"Zambia",
			"Zimbabwe"
	    );

	    for ($i = 0; $i < count($countries); $i++)
		$country->addMultiOption($countries[$i], $countries[$i]);
            $country->setValue($res['country']);
            $this->addElement($country);

            $accept_newsletters = new  Zend_Form_Element_Checkbox('accept_newsletters', array(
                    'label' => $t->_('I accept to receive the general newsletters'). " :",
                    'required' => true));
            $accept_newsletters->setValue($res['accept_newsletters'] || 1);
            $this->addElement($accept_newsletters);

            $accept_releases = new  Zend_Form_Element_Checkbox('accept_releases', array(
                    'label' => $t->_('I accept to receive information about releases'). " :",
                    'required' => true));
            $accept_releases->setValue($res['accept_releases'] || 1);
            $this->addElement($accept_releases);

            $accept_send_statistics = new  Zend_Form_Element_Checkbox('accept_send_statistics', array(
                    'label' => $t->_('I accept to send anonymous statistics'). " :",
                    'required' => true));
            $accept_send_statistics->setValue($res['accept_send_statistics'] || 1);
            $this->addElement($accept_send_statistics);

            $submitce = new Zend_Form_Element_Submit('register_ce', array(
		     'label'    => $t->_('Register')));
	    $this->addElement($submitce);
	}

}
