<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Quarantined spam
 */

class Default_Model_QuarantinedSpam
{
	protected $_values = array(
      'to_domain' => '',
	  'to_user' => '',
	  'exim_id' => '',
	  'sender' => '',
	  'date_in' => '',
	  'time_in' => '',
	  'M_subject' => '',
	  'M_globalscore' => '',
	  'forced' => 0,
	  'store_slave' => 0,
          ### newsl
          'is_newsletter' => '0',
    );
    
	protected $_mapper;
	protected $_table = 'spam';

        protected $_destination;
	
	public function __construct() {
	    $table = MailCleaner_Config::getInstance()->getOption('SPAMTABLE');
		if ($table != "" && $table != 'spam') {
			$this->_table = $table;
			unset($this->_values['to_domain']);
			$this->_values['domain'] = 0;
		}
	}
	
	public function setParam($param, $value) {
		if (array_key_exists($param, $this->_values)) {
			$this->_values[$param] = $value;
		}
	}
	
	public function getParam($param) {
		$ret = null;
		if (array_key_exists($param, $this->_values)) {
			$ret = $this->_values[$param];
		}
	    if ($ret == 'false') {
			return 0;
		}
		return $ret;
	}
	
    public function getCleanParam($param) {
      $split_fields = array(
        'M_subject' => 80,
        'to_user' => 25,
        'to_domain' => 25,
        'sender' => 40
      );
      $data = $this->getParam($param);
      $data = iconv_mime_decode($data, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
      $ret = htmlentities($data, ENT_COMPAT, "UTF-8");
      $ret = preg_replace('/<([^>]*)>/', '&lt;$1&gt;', $ret);
      if (isset($split_fields[$param]) && (strlen($ret) > $split_fields[$param])) {
         $ret = substr($ret, 0, $split_fields[$param]);
         $ret .= '...';
      }

      return $ret;
    }
	
	public function getParamArray() {
		return $this->_values;
	}
	
	public function getAvailableParams() {
		$ret = array();
		foreach ($this->_values as $key => $value) {
			$ret[]=$key;
		}
		return $ret;
	}
		
	public function setId($id) {
	   $this->_id = $id;	
	}
	public function getId() {
		return $this->_id;
	}
		
    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Default_Model_QuarantinedSpamMapper());
        }
        return $this->_mapper;
    }

    public function getDestination() {
      return $this->_destination;
    }
    public function setDestination($local, $domain) {
       $this->_destination = $local."@".$domain;
    }
    public function getCleanAddress($address) {
      $locallen = 25;
      $domainlen = 25;
      $address = preg_replace('/<([^>]*)>/', '&lt;$1&gt;', $address);
      if (preg_match('/(\S+)\@(\S+)/', $address, $matches)) {
        $str = "";
        if (strlen($matches[1]) > $locallen) {
          $str .= substr($matches[1], 0, $locallen)."...";
        } else {
          $str .= $matches[1];
        }

        $str.="@";
        if (strlen($matches[2]) > $domainlen) {
          $str .= substr($matches[2], 0, $domainlen)."...";
        } else {
          $str .= $matches[2];
        }
        return $str;
      }
      return $address;
    }

    public function find($todomain, $touser, $exim_id)
    {
        $this->getMapper()->find($todomain, $touser, $exim_id, $this);
        return $this;
    }
        
    public function fetchAllCount($params = NULL) {
    	return $this->getMapper()->fetchAllCount($params);
    }
    public function fetchAll($params = NULL) {
    	return $this->getMapper()->fetchAll($params);
    }
    
    public function getNbPages() {
    	return $this->getMapper()->getNbPages();
    }
    public function getEffectivePage() {
    	return $this->getMapper()->getEffectivePage();
    }
      
    public function save()
    {	
        return $this->getMapper()->save($this);
    }
    
    public function delete()
    {
    	return $this->getMapper()->delete($this);
    }
    
}
