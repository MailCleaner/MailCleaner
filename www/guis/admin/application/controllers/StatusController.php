<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * controller for status page
 */

class StatusController extends Zend_Controller_Action
{
	public function quickstatusAction()
    {   	
    	
    	$this->_helper->layout->disableLayout();
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	
    	# default values
    	$view->hardware_status = 'cannotgetdata';
    	$view->spools_status = 'cannotgetdata';
    	$view->load_status = 'cannotgetdata';
    	
    	
    	$slave = new Default_Model_Slave();
        $slaves = $slave->fetchAll();
        
        $users = 0;
        foreach ($slaves as $s) {
           foreach (array('hardware', 'spools', 'load') as $what) {
           	  $status = $s->getStatus($what);
           	  if (is_array($status)) {
           	  	$var = $what."_status";
           	  	$view->$var = $status['message'];
           	  }
           }
        }
    }
	
    public function informationalAction()
    {
    	$this->_helper->layout->disableLayout();
    	$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	
    	$informationals = array();
    	
    	$m = new Default_Model_InformationalMessage();
    	
    	$slave = new Default_Model_Slave();
    	$slaves = $slave->fetchAll();
    	foreach ($slaves as $s) {
    		$msgs = $s->getInformationalMessages();
    		foreach ($msgs as $m) {
                        if (! $m instanceof Default_Model_InformationalMessage) {
    			    $mo =  unserialize(base64_decode($m));
                        } else {
                            $mo = $m;
                        } 
                        if (! $mo instanceof Default_Model_InformationalMessage) {
                           continue;
                        }
    			$already_in = false;
    			foreach ($informationals as $inf) {
    				if ($inf->getDescription() == $mo->getDescription()) {
    					$inf->addSlave($s->getId());
    					$already_in = true;
    				}
    			}
    			if (!$already_in) {
                                $mo->addSlave($s->getId());
        			array_push($informationals, $mo);
    			}
    		}
    	}

    	$view->informationals = $informationals;
    }
}
