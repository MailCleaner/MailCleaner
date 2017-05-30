<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Antivirus configuration
 */

class Default_Model_AntivirusConfig
{
    protected $_id;
    protected $_values = array(
        'silent' => 'yes',
        'scanner_timeout' => 300,
        'file_timeout' => 20,
        'expand_tnef' => 	'yes',
        'deliver_bad_tnef' => 'no',
        'tnef_timeout' => 20,
        'usetnefcontent' => 'no',
        'max_message_size' => 0,
        'max_attach_size' => -1,
        'max_archive_depth' => 0,
        'max_attachments_per_message' => 200,
        'send_notices' => 'no',
        'notices_to' => '',
        'scanners' => ''
     );
	
	protected $_mapper;
	
	public function setId($id) {
	   $this->_id = $id;	
	}
	public function getId() {
		return $this->_id;
	}
	
	public function setParam($param, $value) {
		if (array_key_exists($param, $this->_values)) {
			$this->_values[$param] = $value;
		}
	}
	
	public function getParam($param) {
		if (array_key_exists($param, $this->_values)) {
			return $this->_values[$param];
		}
		return null;
	}
	
	public function getAvailableParams() {
		$ret = array();
		foreach ($this->_values as $key => $value) {
			$ret[]=$key;
		}
		return $ret;
	}
	
	public function getParamArray() {
		return $this->_values;
	}

    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Default_Model_AntivirusConfigMapper());
        }
        return $this->_mapper;
    }

    public function find($id)
    {
        $this->getMapper()->find($id, $this);
        return $this;
    }
   
    public function save()
    {
        return $this->getMapper()->save($this);
    }
    
    public function getActiveScanners() {
    	$scanners = array();
    	foreach (preg_split('/\s+/', $this->getParam('scanners')) as $sname) {
    		$scanner = new Default_Model_AntivirusScanner();
    		$scanner->findByName($sname);
    		if ($scanner->getID() > 0) {
    			$scanners[] = $scanner;
    		}
    	}
    	return $scanners;
    }
}