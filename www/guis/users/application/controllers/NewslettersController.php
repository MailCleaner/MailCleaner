<?php

/**
 * MailCleaner
 *
 * @license http://www.mailcleaner.net/open/licence_en.html MailCleaner Public License
 * @copyright 2015 Fastnet SA
 */

/**
 * Newsletters controller
 */
class NewslettersController extends Zend_Controller_Action
{
    public function indexAction()
    {
        die();
    }
    
    public function allowAction()
    {        
        $status = 0;
        
        $eximId = $this->getRequest()->getParam('id');
	
	if (strlen($eximId) == 0) { die(); }

        $spam = new Default_Model_DbTable_Spam();

        $row = $spam->fetchRow($spam->select()->where('exim_id = ?', $eximId));
                
        if (!empty($row)) {
            $status = 1;
            
            $recipient = $row->to_user.'@'.$row->to_domain;
            
	    $storage = $row->store_slave;

            $sender = $this->getFromHeader($eximId, $recipient);
            
            $status = 2;
            
            if ($sender) {
                
                $status = 3;
                
                $data = array(
                    'sender'    => $sender,
                    'recipient' => $recipient,
                    'type'      => 'wnews',
                    'expiracy'  => '0000-00-00',
                    'status'    => '1',
                    'comments'  => '[Newsletter]'
                );
                
                try {
                    $rule = new Default_Model_DbTable_NewsletterRule();
                    $rule->insert($data);   
                } catch (Zend_Db_Exception $e) {}
                
                $status = 4;
                          
                if (! $this->getRequest()->isXmlHttpRequest()) {
                  $this->release($eximId, $recipient, $storage);
                } else {
                    return $this->_helper->json(array('id' => $eximId, 'username' => $recipient, 'storage' => $storage, 'status' => $status));
                }                
            }  
        }
    }
    
    private function getFromHeader($id, $recipient)
    {
        require_once('/usr/mailcleaner/www/classes/user/Spam.php');  
        
        $spam = new Spam();
        $spam->loadDatas($id, $recipient);
        $spam->loadHeadersAndBody();
        $headers = $spam->getHeadersArray();
        
        $match = array();
        preg_match('/[<]?([-0-9a-zA-Z.+_\']+@[-0-9a-zA-Z.+_\']+\.[a-zA-Z-0-9]+)[>]?/', trim($headers['From']), $match);
        
        if (!empty($match[1])) {
           return $match[1];
        }
        
        return false;
    }
    
    private function release($eximId, $recipient, $storage, $news)
    {
        $url  = $this->getRequest()->getScheme() . '://' . $this->getRequest()->getHttpHost();
        $url .= '/fm.php?id='.$eximId.'&a='.$recipient.'&s='.$storage.'&n='.$news;
        
        $ch = curl_init();  
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_exec($ch);
        curl_close($ch);
    }
}
