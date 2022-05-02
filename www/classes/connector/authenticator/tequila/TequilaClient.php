<?php
/*========================================================================

	PHP client for Tequila, v. 2.0.4 (Tue Nov 14 10:47:18 CET 2006)
	(C) 2004, Lionel Clavien [lionel dot clavien AT epfl dot ch]
	This code is released under the GNU GPL v2 terms (see LICENCE file).
	
	Changelog:
		0.1.0, 2004-06-27: Creation
		0.1.1, 2004-08-29: Changed RSA authentication method to use the new
			           server certificate in lieu of the server public key
				   [openssl bug ?]
		0.1.2, 2004-09-04: Configuration options put in tequila_config.inc.php
                ......

		2.0.3 : I forgot.
		2.0.4 : Fix problem with cookie. Now it is a session cookie.

	TODO:
		- implement more documented features (allows, ?)
		
========================================================================*/

// Disable PHP error reporting
error_reporting (0);
error_reporting (E_ALL); // DEBUG

// Start output buffering, for the authentication redirection to work...
ob_start ();

// Load configuration file
require_once ('tequila_config.inc.php');

// PHP 4.3.0
if (!function_exists ('file_get_contents')) {
  function file_get_contents ($sFileName) {
    $rHandle = fopen($sFileName, "rb");
    if (!$rHandle) return (FALSE);
    $sContents = fread ($rHandle, filesize ($sFileName));
    return ($sContents);
  }
}

// PHP 4.2.0
if(!function_exists('ob_clean')) {
  function ob_clean() {
    ob_end_clean();
    ob_start();
  }
}

// Constants declarations
define('SESSION_INVALIDKEY',                8);
define('SESSION_READ',                      9);
define('SESSION_TIMEOUT',                   7);

define('AUTHENTICATE_RSA',                  1); // No longer used, ignored.
define('AUTHENTICATE_URL',                  2); // No longer used, ignored.

define('ERROR_AUTH_METHOD_UNKNOWN',       129);
define('ERROR_CREATE_FILE',               134);
define('ERROR_CREATE_SESSION_DIR',        137);
define('ERROR_CREATE_SESSION_FILE',       145);
define('ERROR_NO_DATA',                   133);
define('ERROR_NO_KEY',                    131);
define('ERROR_NO_MESSAGE',                139);
define('ERROR_NO_SERVER_DEFINED',         143);
define('ERROR_NO_SERVER_KEY',             140);
define('ERROR_NO_SESSION_DIR',            130);
define('ERROR_NO_SIGNATURE',              138);
define('ERROR_NO_VALID_PUBLIC_KEY',       141);
define('ERROR_NOT_READABLE',              135);
define('ERROR_SESSION_DIR_NOT_WRITEABLE', 148);
define('ERROR_SESSION_FILE_EXISTS',       144);
define('ERROR_SESSION_FILE_FORMAT',       146);
define('ERROR_SESSION_TIMEOUT',           136);
define("ERROR_UNKNOWN_ERROR",             127);
define('ERROR_UNSUPPORTED_METHOD',        132);
define('ERROR_CURL_NOT_LOADED',           149);

define('LNG_DEUTSCH', 2);
define('LNG_ENGLISH', 1);
define('LNG_FRENCH',  0);

define('COOKIE_LIFE', 86400);
define('COOKIE_NAME', 'TequilaPHP');

class TequilaClient {
  var $aLanguages = array (
			   LNG_ENGLISH => 'english',
			    LNG_FRENCH => 'francais',
			   );
  var $aErrors = array(
	ERROR_UNKNOWN_ERROR => array(
       		LNG_ENGLISH => 'An unknown error has occurred.',
       		LNG_FRENCH => 'Une erreur inconnue est survenue.',
       	),
       	ERROR_SESSION_DIR_NOT_WRITEABLE => array(
       		LNG_ENGLISH => 'Error: the given sessions directory is not writable.',
       		LNG_FRENCH => 'Erreur: le répertoire à sessions indiqué ne peut pas être écrit.',
       	),
       	ERROR_SESSION_FILE_FORMAT => array(
       		LNG_ENGLISH => 'Error: invalid session file format.',
       		LNG_FRENCH => 'Erreur: format de fichier de session non valide.',
       	),
       	ERROR_CREATE_SESSION_FILE => array(
       		LNG_ENGLISH => 'Error: session file creation failed.',
       		 LNG_FRENCH => 'Erreur: échec lors de la création du fichier de session.',
       	),
       	ERROR_NO_DATA => array(
       		LNG_ENGLISH => 'Error: no session data.',
       		 LNG_FRENCH => 'Erreur: aucune donnée de session.',
       	),
       	ERROR_NO_SESSION_DIR => array(
       		LNG_ENGLISH => 'Error: nonexistent or unspecified sessions directory.',
       		 LNG_FRENCH => 'Erreur: dossier à sessions inexistant ou non spécifié.',
       	),
       	ERROR_NO_SERVER_DEFINED => array(
       		LNG_ENGLISH => 'Error: no authentication server available.',
       		 LNG_FRENCH => 'Erreur: aucun serveur d\'authentification disponible.',
       	),
       	ERROR_UNSUPPORTED_METHOD => array(
       		LNG_ENGLISH => 'Error: unsupported request method.',
       		 LNG_FRENCH => 'Erreur: méthode de transmission inconnue.',
       	),
       	ERROR_NOT_READABLE => array(
       		LNG_ENGLISH => 'Error: unable to read session file.',
       		 LNG_FRENCH => 'Erreur: fichier de session non lisible.',
       	),
       	ERROR_CREATE_FILE => array(
       		LNG_ENGLISH => 'Error: unable to create session file.',
       		 LNG_FRENCH => 'Erreur: impossible de créer le fichier de sessions.',
       	),
       	ERROR_SESSION_TIMEOUT => array(
       		LNG_ENGLISH => 'Error: session timed out.',
       		 LNG_FRENCH => 'Erreur: la session a expiré.',
       	),
       	ERROR_CREATE_SESSION_DIR => array(
       		LNG_ENGLISH => 'Error: unable to create sessions directory.',
       		 LNG_FRENCH => 'Erreur: impossible de créer le dossier à sessions défini.',
       	),
       	ERROR_NO_MESSAGE => array(
       		LNG_ENGLISH => 'Error: no message to authenticate.',
       		 LNG_FRENCH => 'Erreur: pas de message à vérifier.',
       	),
       	ERROR_NO_SERVER_KEY => array(
       		LNG_ENGLISH => 'Error: no public key defined.',
		 LNG_FRENCH => 'Erreur: la clé publique du serveur d\'authentification n\'est pas définie ou disponible.',
		),
       	ERROR_NO_VALID_PUBLIC_KEY => array(
       		LNG_ENGLISH => 'Error: invalid public key.',
       		 LNG_FRENCH => 'Erreur: la clé publique fournie n\'est pas valide.',
       	),
       	ERROR_NO_SIGNATURE => array(
       		LNG_ENGLISH => 'Error: no signature for message authentication.',
       		 LNG_FRENCH => 'Erreur: pas de signature pour la vérification du mesage.',
       	),
       	ERROR_NO_KEY => array (
       		LNG_ENGLISH => 'Error: no session key.',
       		 LNG_FRENCH => 'Erreur: pas de clé de session.',
       	),
       	ERROR_SESSION_FILE_EXISTS => array (
       		LNG_ENGLISH => 'Error: session already created.',
       		 LNG_FRENCH => 'Erreur: session déjà créée.',
	),
       	ERROR_CURL_NOT_LOADED => array (
       		LNG_ENGLISH => 'Error: CURL Extension is not loaded.',
       		 LNG_FRENCH => 'Erreur: L\'extension CURL n\'est pas présente.',
       	),
  );
  var      $aWantedRights = array ();
  var       $aWantedRoles = array ();
  var  $aWantedAttributes = array ();
  var  $aWishedAttributes = array ();
  var      $aWantedGroups = array ();
  var       $aCustomAttrs = array ();
  var      $sCustomFilter = '';
  var      $sAllowsFilter = '';
  var          $iLanguage = LNG_FRENCH;
  var    $sApplicationURL = '';
  var   $sApplicationName = '';
  var          $sResource = '';
  var               $sKey = '';
  var                $key = '';
  var                $org = '';
  var               $user = '';
  var               $host = '';
  var           $sMessage = '';
  var        $aAttributes = array();
  var           $iTimeout;
  var            $sServer = '';
  var         $sServerUrl = '';
  var $sSessionsDirectory = '';
  var            $sCAFile = '';
  var          $sCertFile = '';
  var           $sKeyFile = '';
  var   $bIsAuthenticated = FALSE;
  var      $bReportErrors = TRUE;
  var $stderr;

  var $requestInfos = array();

  //====================== Constructor
  function TequilaClient ($sServer = '', $sSessionsDirectory = '', $iTimeout = NULL) {
    $this->stderr = fopen ('php://stderr', 'w');
    if (!extension_loaded ('curl')) {
      return $this->Error (ERROR_CURL_NOT_LOADED);
    }
    if (empty ($sServer))
      $sServer = GetConfigOption ('sServer');
    if (empty ($sServer))
      $sServerUrl = GetConfigOption ('sServerUrl');
    if (empty ($sSessionsDirectory)) {
     $sSessionsDirectory = GetConfigOption ('sSessionsDirectory');
     if (empty ($sSessionsDirectory)) 
       $sSessionsDirectory = session_save_path();
     if (empty ($sSessionsDirectory)) 
       $sSessionsDirectory = dirname($_SERVER['SCRIPT_FILENAME']) .
	 DIRECTORY_SEPARATOR . 'sessions';
    }
    $aEtcConfig = $this->LoadEtcConfig ();

    if (empty ($sServer))            $sServer            = $aEtcConfig ['sServer'];
    if (empty ($sServerUrl))         $sServerUrl         = $aEtcConfig ['sServerUrl'];
    if (empty ($sSessionsDirectory)) $sSessionsDirectory = $aEtcConfig ['sSessionsDirectory'];

    if (empty ($sServerUrl) && !empty ($sServer))
      $sServerUrl = $sServer . '/cgi-bin/tequila';
    if (empty ($iTimeout))
      $iTimeout = GetConfigOption ('iTimeout', 86400);

    $this->sServer    = $sServer;
    $this->sServerUrl = $sServerUrl;
    $this->SetSessionsDirectory ($sSessionsDirectory);
    $this->iTimeout   = $iTimeout;
    
    $this->iCookieLife= COOKIE_LIFE;
    $this->sCookieName= COOKIE_NAME;
  }

  //====================== Error management
  function Error ($iError) {
    if (!$this->bReportErrors) return ($iError);
    $iCurrentLanguage = $this->GetLanguage ();
    if (empty ($iCurrentLanguage))
      $iCurrentLanguage = LNG_FRENCH;
    if (array_key_exists ($iError, $this->aErrors))
      echo "\n<br /><font color='red' size='5'>" .
	$this->aErrors[$iError][$iCurrentLanguage] .
	"</font><br />\n";
    else
      echo "\n<br /><font color='red' size='5'>" .
	$this->aErrors [ERROR_UNKNOWN_ERROR][$iCurrentLanguage] .
	"</font><br />\n";
    return ($iError);
  }

  function SetReportErrors ($bReportErrors) {
    $this->bReportErrors = $bReportErrors;
  }

  function GetReportErrors () {
    return ($this->bReportErrors);
  }

  function LoadEtcConfig () {
    $sFile = '/etc/tequila.conf';
    if (!file_exists ($sFile)) return false;
    if (!is_readable ($sFile)) return false;
    
    $aConfig = array ();
    $sConfig = trim (file_get_contents ($sFile));
    $aLine = explode ("\n", $sConfig);
    foreach ($aLine as $sLine) {
      if (preg_match  ('/^TequilaServer:\s*(.*)$/i', $sLine, $match))
	$aConfig ['sServer'] = $match [1];

      if (preg_match  ('/^TequilaServerUrl:\s*(.*)$/i', $sLine, $match))
	$aConfig ['sServerUrl'] = $match [1];

      if (preg_match  ('/^SessionsDir:\s*(.*)$/i', $sLine, $match))
	$aConfig ['sSessionsDirectory'] = $match [1];
      
    }
    return $aConfig;
  }

  function SetAuthenticationMethod($iAuthenticationMethod) { // Nothing, obsolete.
  }
  
  function GetAuthenticationMethod () { // Nothing, obsolete.
    return (0);
  }
  
  //====================== Custom parameters
  function SetCustomParamaters ($customParamaters) {
    foreach ($customParamaters as $key => $val) {
      $this->requestInfos [$key] = $val;
    }
  }
  function GetCustomParamaters () {
    return $this->requestInfos;
  }
	
  //====================== Required rights ("wantright" parameter)
  function SetWantedRights ($aWantedRights) {
    $this->aWantedRights = $aWantedRights;
  }
	
  function AddWantedRights ($aWantedRights) {
    $this->aWantedRights = array_merge ($this->aWantedRights, $aWantedRights);
  }

  function RemoveWantedRights ($aWantedRights) {
    foreach ($this->aWantedRights as $sWantedRight)
      if (in_array($sWantedRight, $aWantedRights))
	unset($this->aWantedRights[array_search($sWantedRight, $this->aWantedRights)]);
  }

  function GetWantedRights () {
    return ($this->aWantedRights);
  }

  //====================== Required roles ("wantrole" parameter)
  function SetWantedRoles ($aWantedRoles) {
    $this->aWantedRoles = $aWantedRoles;
  }
	
  function AddWantedRoles ($aWantedRoles) {
    $this->aWantedRoles = array_merge ($this->aWantedRoles, $aWantedRoles);
  }

  function RemoveWantedRoles ($aWantedRoles) {
    foreach ($this->aWantedRoles as $sWantedRole)
      if (in_array ($sWantedRole, $aWantedRoles))
	unset ($this->aWantedRoles [array_search ($sWantedRole, $this->aWantedRoles)]);
  }

  function GetWantedRoles () {
    return ($this->aWantedRoles);
  }

  //====================== Required attributes ("request" parameter)
  function SetWantedAttributes ($aWantedAttributes) {
    $this->aWantedAttributes = $aWantedAttributes;
  }
  
  function AddWantedAttributes ($aWantedAttributes) {
    $this->aWantedAttributes = array_merge ($this->aWantedAttributes,
					    $aWantedAttributes);
  }
  
  function RemoveWantedAttributes ($aWantedAttributes) {
    foreach ($this->aWantedAttributes as $sWantedAttribute)
      if (in_array($sWantedAttribute, $aWantedAttributes))
	unset ($this->aWantedAttributes [array_search($sWantedAttribute,
						      $this->aWantedAttributes)]);
  }

  function GetWantedAttributes () {
    return ($this->aWantedAttributes);
  }
  
  //====================== Desired attributes ("wish" parameter)
  function SetWishedAttributes ($aWishedAttributes) {
    $this->aWishedAttributes = $aWishedAttributes;
  }

  function AddWishedAttributes ($aWishedAttributes) {
    $this->aWishedAttributes = array_merge ($this->aWishedAttributes,
					    $aWishedAttributes);
  }
  
  function RemoveWishedAttributes ($aWishedAttributes) {
    foreach ($this->aWishedAttributes as $aWishedAttribute)
      if (in_array($aWishedAttribute, $aWishedAttributes))
	unset ($this->aWishedAttributes[array_search($aWishedAttribute,
						     $this->aWishedAttributes)]);
  }
  
  function GetWishedAttributes () {
    return ($this->aWishedAttributes);
  }
  
  //====================== Required groups ("belongs" parameter)
  function SetWantedGroups ($aWantedGroups) {
    $this->aWantedGroups = $aWantedGroups;
  }
  
  function AddWantedGroups ($aWantedGroups) {
    $this->aWantedGroups = array_merge($this->aWantedGroups, $aWantedGroups);
  }
  
  function RemoveWantedGroups ($aWantedGroups) {
    foreach ($this->aWantedGroups as $aWantedGroup)
      if (in_array($aWantedGroup, $aWantedGroups))
	unset($this->aWantedGroups[array_search($aWantedGroup,
						$this->aWantedGroups)]);
  }
  
  function GetWantedGroups () {
    return ($this->aWantedGroups);
  }
  
  //====================== Own filter ("require" parameter)
  function SetCustomFilter ($sCustomFilter) {
    $this->sCustomFilter = $sCustomFilter;
  }
  
  function GetCustomFilter () {
    return ($this->sCustomFilter);
  }
  
  //====================== Allows filter ("allows" parameter)
  function SetAllowsFilter ($sAllowsFilter) {
    $this->sAllowsFilter = $sAllowsFilter;
  }
  
  function GetAllowsFilter () {
    return ($this->sAllowsFilter);
  }
  
  //====================== Interface language ("language" parameter)
  function SetLanguage ($sLanguage) {
    $this->iLanguage = $sLanguage;
  }
  
  function GetLanguage () {
    return ($this->iLanguage);
  }
  
  //====================== Application URL ("urlaccess" parameter)
  function SetApplicationURL ($sApplicationURL) {
    $this->sApplicationURL = $sApplicationURL;
  }
  
  function GetApplicationURL () {
    return ($this->sApplicationURL);
  }
  
  //====================== Application name ("service" parameter)
  function SetApplicationName ($sApplicationName) {
    $this->sApplicationName = $sApplicationName;
  }
  
  function GetApplicationName () {
    return ($this->sApplicationName);
  }
  
  //====================== Resource name
  function SetResource ($sResource) {
    $this->sResource = $sResource;
  }
  
  function GetResource () {
    return ($this->sResource);
  }
  
  //====================== Session key
  function SetKey ($sKey) {
    $this->sKey = $sKey;
  }
  
  function GetKey () {
    return ($this->sKey);
  }
  
  //====================== Session message
  function SetMessage ($sMessage) {
    $this->sMessage = $sMessage;
  }
  
  function GetMessage () {
    return ($this->sMessage);
  }
  
  //====================== Tequila server
  function SetServer ($sServer) {
    $this->sServer = $sServer;
  }
  
  function GetServer () {
    return ($this->sServer);
  }
  
  //====================== server URL
  function SetServerURL ($sURL) {
    $this->sServerUrl = $sURL;
  }
  
  function GetServerURL () {
    return ($this->sServerUrl);
  }
  
  //====================== Session manager parameters
  function SetTimeout ($iTimeout) {
    $this->iTimeout = $iTimeout;
  }
  
  function GetTimeout () {
    return ($this->iTimeout);
  }
  
  //====================== Cookie Life parameters
  function SetCookieLife ($iTimeout) { // Obsolete
    $this->iCookieLife = $iTimeout;
  }

  //====================== Cookie Life parameters
  function SetCookieName ($sCookieName) {
    $this->sCookieName = $sCookieName;
  }

  function SetSessionsDirectory ($sSessionsDirectory) {
    $sDefaultDirectory = dirname($_SERVER['SCRIPT_FILENAME']) .
      DIRECTORY_SEPARATOR . 'sessions';
    if (!is_writeable ($sSessionsDirectory)) {
      if (!@mkdir ($sSessionsDirectory, 0700)) {
	if (is_dir ($sDefaultDirectory)) {
	  $this->sSessionsDirectory = $sDefaultDirectory;
	} else {
	  if (!@mkdir ($sDefaultDirectory, 0700))
	    print "<li>sDefaultDirectory=$sDefaultDirectory<br>";
	    return ($this->Error (ERROR_CREATE_SESSION_DIR) );
	}
      }
    }
    $this->sSessionsDirectory = $sSessionsDirectory;
    return (TRUE);
  }
  
  function GetSessionsDirectory () {
    return ($this->sSessionsDirectory);
  }
  
  function CreateSession ($attributes) {
    $sSessionsDirectory = $this->sSessionsDirectory;
    if (empty ($sSessionsDirectory)) {
      return ($this->Error (ERROR_NO_SESSION_DIR));
    }    
    
    if (!is_writeable ($sSessionsDirectory))
      return ($this->Error (ERROR_SESSION_DIR_NOT_WRITEABLE));
    
    $sKey = $this->sKey;
    if (empty ($sKey)) return ($this->Error (ERROR_NO_KEY));
    
    $sFile = $sSessionsDirectory . DIRECTORY_SEPARATOR . $sKey;
    if (file_exists ($sFile)) return ($this->Error (ERROR_SESSION_FILE_EXISTS));
    
    $rHandle = @fopen ($sFile, 'wb');
    if (!$rHandle) return ($this->Error(ERROR_CREATE_FILE));
    $sAttributes = "";
    foreach ($attributes as $key => $val) {
      $sAttributes = $key . '=' . $val . "\n";
      $this->aAttributes[$key] = $val;
      @fwrite ($rHandle, $sAttributes);
    }
    @fclose ($rHandle);
    if (!filesize($sFile)) return ($this->Error(ERROR_CREATE_SESSION_FILE));
    return (TRUE);
  }
	
  function LoadSession () {
    $sSessionDir = $this->sSessionsDirectory;
    if (empty ($sSessionDir)) return ($this->Error (ERROR_NO_SESSION_DIR));
    
    $sKey = $this->sKey;
    if (empty ($sKey)) return ($this->Error (ERROR_NO_KEY));
    
    $sFile = $sSessionDir . DIRECTORY_SEPARATOR . $sKey;
    if (!file_exists ($sFile))
      return FALSE;
    if (!is_readable ($sFile))
      return ($this->Error (ERROR_NOT_READABLE));
    if ($this->UpdateSession () === SESSION_TIMEOUT)
      return ($this->Error (ERROR_SESSION_TIMEOUT));
   
    $sAttributes = trim (file_get_contents ($sFile));
    $aTemp = explode ("\n", $sAttributes);
    foreach ($aTemp as $sTemp) {
      if (strpos ($sTemp, '=') === FALSE)
	return ($this->Error (ERROR_SESSION_FILE));
      list ($sAttribute, $sValue) = explode ('=', $sTemp);
      if ($sAttribute == 'host') continue;
      $this->aAttributes [$sAttribute] = $sValue;
    }
    $this->bIsAuthenticated = TRUE;
    return (TRUE);
  }

  //====================== Session checking
  function UpdateSession () {
    $iTimeout = $this->iTimeout;
    if ($iTimeout <= 0) {
      $iTimeout = 600;
      $this->SetTimeout ($iTimeout);
    } 
    $sFile = $this->sSessionsDirectory . DIRECTORY_SEPARATOR . $this->sKey;
    // The existence has been tested in LoadSession ()
    if ((time () - filectime ($sFile)) > $iTimeout) {
      unlink ($sFile);
      return (SESSION_TIMEOUT);
    }
    return (TRUE);
  }
  
  //====================== Session cleaning
  function DestroySession () {
    // TODO
  }
	
  //====================== Sessions cleaning
  function PurgeAll ($iTimeout = 3600) {
    // TODO
  }
  
  //====================== User attributes
  //	@out	Array containing attributes names as indexes and attributes values as values
  function GetAttributes() {
    return ($this->aAttributes);
  }
  
  // @in	Array containing wanted attributes as keys
  // @out	The same array with TRUE or FALSE as value for the corresponding attribute
  function HasAttributes (&$aAttributes) {
    foreach ($aAttributes as $sAttribute => $sHasIt)
      if (array_key_exists($sAttribute, $this->aAttributes))
	$aAttributes [$sAttribute] = TRUE;
      else
	$aAttributes [$sAttribute] = FALSE;
  }
  
  function Authenticate () {
    if ($this->bIsAuthenticated) return (TRUE);

    if (isset ($_REQUEST ['key']) && !empty ($_REQUEST ['key'])) {
      $this->SetKey ($_REQUEST ['key']);
    } else
    if (isset ($_COOKIE  [$this->sCookieName]) && !empty ($_COOKIE [$this->sCookieName])) {
      $this->SetKey ($_COOKIE [$this->sCookieName]);
    } else {
      $this->createRequest ();
 	// ic - set cookie -
 	  setcookie($this->sCookieName, $this->sKey);
 	//-------------------------------------
      $url = $this->getAuthenticationUrl ();
      header ('Location: ' . $url);
      exit;
    }

    if ($this->LoadSession ()) {
      return (TRUE);
    }
    $attributes = $this->fetchAttributes ($this->sKey);
    $this->CreateSession ($attributes);
  }
  
  /**
   * Sends an authentication request to Tequila.
   */
  function createRequest () {
    $urlaccess = $this->sApplicationURL;
    if (empty ($urlaccess))
      $urlaccess = ((isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) ? 'https://' : 'http://') .
	$_SERVER['SERVER_NAME'] . ":" . $_SERVER['SERVER_PORT'] . $_SERVER['PHP_SELF'];

    $this->requestInfos ['urlaccess'] = $urlaccess;
    if (!empty ($this->sApplicationName))
      $this->requestInfos ['service'] = $this->sApplicationName;
    if (!empty ($this->aWantedRights))
      $this->requestInfos ['wantright'] = implode($this->aWantedRights, '+');
    if (!empty ($this->aWantedRoles))
      $this->requestInfos ['wantrole'] =  implode($this->aWantedRoles, '+');
    if (!empty ($this->aWantedAttributes)) 
      $this->requestInfos ['request'] = implode ($this->aWantedAttributes, '+');
    if (!empty ($this->aWishedAttributes))
      $this->requestInfos ['wish'] = implode ($this->aWishedAttributes, '+');
    if (!empty ($this->aWantedGroups))
      $this->requestInfos ['belongs'] = implode($this->aWantedGroups, '+');
    if (!empty ($this->sCustomFilter))
      $this->requestInfos ['require'] = $this->sCustomFilter;
    if (!empty ($this->sAllowsFilter))
      $this->requestInfos ['allows'] = $this->sAllowsFilter;
    if (!empty ($this->iLanguage))
      $this->requestInfos ['language'] = $this->aLanguages [$this->iLanguage];

    ob_end_clean();

    $response = $this->askTequila ('createrequest', $this->requestInfos);
    $this->sKey = substr (trim ($response), 4); // 4 = strlen ('key=')
  }

  /**
   * Returns current URL.
   * @return string
   */
  function getCurrentUrl () {
    return 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
  }

  /**
   * Checks that user has correctly authenticated and retrieves its data.
   * @return mixed
   */
  function fetchAttributes ($key) {
    $fields = array ('key' => $key);
    $response = $this->askTequila ('fetchattributes', $fields);
    if (!$response)  return false;

    $result = array ();
    $attributes = explode ("\n", $response);
    foreach ($attributes as $attribute) {
      $attribute = trim ($attribute);
      if (!$attribute)  continue;
      if ($key == 'key') { $this->key  = $attribute; }
      if ($key == 'org') { $this->org  = $attribute; }
      if ($key == 'user') { $this->user = $attribute; }
      if ($key == 'host') { $this->host = $attribute; }
      list ($key, $val) = explode ('=', $attribute);
      $result [$key] = $val;
    }
    return $result;
  }

  /**
   * Gets Tequila Server config info.
   * @return string
   */
  function getConfig () {
    return $this->askTequila ('config');
  }

  /**
   * Returns the Tequila authentication form URL.
   * @return string
   */
  function getAuthenticationUrl () {
    return sprintf('%s/requestauth?requestkey=%s',
		   $this->sServerUrl,
		   $this->sKey);
  }
  
  function getLogoutUrl ($redirectUrl = '') {
    $url = sprintf('%s/logout', $this->sServerUrl);
    if (!empty($redirectUrl)) {
      $url .= "?urlaccess=$redirectUrl";
    }
    return $url;
  }

  function KillSessionFile() {
    $sSessionsDirectory = $this->sSessionsDirectory;
    if (empty ($sSessionsDirectory)) {
      return ($this->Error (ERROR_NO_SESSION_DIR));
    }    
    
    // Make sure the session directory is writable
    if (!is_writeable ($sSessionsDirectory))
      return ($this->Error (ERROR_SESSION_DIR_NOT_WRITEABLE));
    
    $sKey = $this->sKey;
    if (empty ($sKey)) return ($this->Error (ERROR_NO_KEY));
    
    // Get the complete session filename
    $sFile = $sSessionsDirectory . DIRECTORY_SEPARATOR . $sKey;

    // Return if the file already exists
    if (!file_exists ($sFile)) {
      return;
    }

    // Delete the session file
    unlink($sFile);
  }

  function KillSessionCookie() {
    // Delete cookie by setting expiration time in the past
 	  setcookie($this->sCookieName, "", time()-3600);
  }

  function KillSession() {
    $this->KillSessionFile();
    $this->KillSessionCookie();
  }

  function Logout ($redirectUrl = '') {
    // Kill session cookie and session file
    $this->KillSession();

    // Unset the authenticated flag
    $this->bIsAuthenticated = false;
    
    // Redirect the user to the tequila server logout url
    header("Location: " . $this->getLogoutUrl($redirectUrl));
  }
  
  function askTequila ($type, $fields = array()) {
    $ch = curl_init ();
    
    curl_setopt ($ch, CURLOPT_HEADER,         false);
    curl_setopt ($ch, CURLOPT_POST,           true);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, false);

    if ($this->sCAFile)   curl_setopt ($ch, CURLOPT_CAINFO,  $this->sCAFile);
    if ($this->sCertFile) curl_setopt ($ch, CURLOPT_SSLCERT, $this->sCertFile);
    if ($this->sKeyFile)  curl_setopt ($ch, CURLOPT_SSLKEY,  $this->sKeyFile);

    $url = $this->sServerUrl;
    switch ($type) {
      case 'createrequest':
	$url .= '/createrequest';
	break;
	
      case 'fetchattributes':
	$url .= '/fetchattributes';
	break;
	
      case 'config':
	$url .= '/getconfig';
	break;
	
      case 'logout':
	$url .= '/logout';
	break;
	
      default:
	return;
    }
    curl_setopt ($ch, CURLOPT_URL, $url);
    if (is_array ($fields) && count ($fields)) {
      $pFields = array ();
      foreach ($fields as $key => $val) {
	$pFields[] = sprintf('%s=%s', $key, $val);
      }
      $query = implode("\n", $pFields) . "\n";
      curl_setopt ($ch, CURLOPT_POSTFIELDS, $query);
    }    
    $response = curl_exec ($ch);
    // If connection failed (HTTP code 200 <=> OK)
    if (curl_getinfo ($ch, CURLINFO_HTTP_CODE) != '200') {
      $response = false;
    }
    curl_close ($ch);
    return $response;
  }
}

?>
