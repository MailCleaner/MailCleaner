<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 */

class MCSoap_Quarantine
{

  /**
   * This function will fetch information on quarantined message
   * 
   * @param  array  params
   * @return array
   */
  static public function Quarantine_findSpam($params) {
    $id = 0;
    if (isset($params['id'])) {
      $id = $params['id'];
    }
    if (!$id || !preg_match('/^(\d{8})\/([a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{6,11}-[a-z,A-Z,0-9]{2,4})$/', $id, $matches)) {
      return array('status' => 0, 'error' => 'BADMSGID ('.$id.")");
    }
    $id = $matches[2];
    if (!isset($params['recipient']) || !preg_match('/^(\S+)\@(\S+)$/', $params['recipient'], $matches)) {
      return array('status' => 0, 'error' => 'BADRECIPIENT');
    }
    require_once('MailCleaner/Config.php');
    $mcconfig = MailCleaner_Config::getInstance();
    
    $file = $mcconfig->getOption('VARDIR').'/spam/'.$matches[2].'/'.$params['recipient'].'/'.$id;

    $ret['file'] = $file;
    $ret['status'] = 1;
    return $ret;
  }
	
}
