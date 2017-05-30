<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * File name
 */

class Default_Model_RRDGraphic
{
	private $RRD_command = '/usr/bin/rrdtool';
	private $_elements = array();
	
	private $_name = '';
	private $_type = 'count';
	private $_family = 'default';
	private $_id = 0;
	private $_host = 'global';
	
	private $_horizontal_title = '';
	private $_vertical_title = '';
	
	private $_end = 'now';
	private $_start = 'now-1d';

	private $_width = 500;
	private $_height = 100;
	
	private $_colors = array();
	private $_max_legend_length = 0;
	
	private $_base = 1024;
	private $_min_yvalue = 0;
	
	public function __construct($host = null) {
		$host = 'global';
	    $this->_host = $host;

	    ## load colors
	    $template = Zend_Registry::get('default_template');
        include_once(APPLICATION_PATH . '/../public/templates/'.$template.'/css/pieColors.php');
        $this->_colors = $data_colors;
	}
	
	public function setId($id) {
	    $this->_id = $id;	
	}
	public function getId() {
		return $this->_id;
	}
	public function setName($name) {
		$this->_name = $name;
	}
	public function getName() {
		return $this->_name;
	}
	public function setFamily($family) {
		$this->_family = $family;
	}
	public function getFamily() {
		return $this->_family;
	}
	public function setType($type) {
		if ($type == 'frequency') {
			$this->_type = 'frequency';
		}		
	}
	public function setBase($base) {
		if (is_numeric($base)) {
			$this->_base = $base;
		}
	}
	public function setYValue($value) {
		if (is_numeric($value)) {
			$this->_min_yvalue = $value;
		}
	}
	public function getType() {
		return $this->_type;
	}
	public function setHost($host) {
		
		$slave = new Default_Model_Slave();
        $slaves = $slave->fetchAll();
        
		if (is_numeric($host)) {
		    foreach ($slaves as $s) {
		    	if ($s->getId() == $host) {
		    		$this->_host = $s->getHostname();
		    	}
            }
		} else {
			foreach ($slaves as $s) {
				if ($s->getHostname() == $host) {
					$this->_host = $s->getHostname();
				}
			}
		}
	}
	public function getHost() {
		return $this->_host;
	}
	public function getColors() {
		return $this->_colors;
	}
	public function getMaxLegendLenght() {
		return $this->_max_legend_length;
	}
	public function setTitle($title = '') {
		if ($title != '') {
			$this->_horizontal_title = $title;
		}
		$t = Zend_Registry::get('translate');
		$this->_horizontal_title = $t->_($this->getName()."_rrdtitle");
		if ($this->_type == 'count') {
			$this->_horizontal_title = $t->_('count'.$this->getName()."_rrdtitle");
		}
		if ($this->_host != 'global') {
			$this->_horizontal_title .= " (".$this->_host.")";
		}
	}
	public function setLegend($legend = '') {
		if ($legend != '') {
			$this->_vertical_title = $legend;
		}
		$t = Zend_Registry::get('translate');
		
		$this->_vertical_title = $t->_($this->getName()."_rrdlegend");
		if ($this->_type == 'count') {
			$this->_vertical_title = $t->_('count'.$this->getName()."_rrdlegend");
		}
	}
	public function getLegend() {
		return $this->_vertical_title;
	}
	
    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Default_Model_RRDGraphicMapper());
        }
        return $this->_mapper;
    }

    public function find($elementid)
    {
    	if (!$elementid) {
    		$elementid = 1;
    	}
        $this->getMapper()->find($elementid, $this);
        return $this;
    }
    
	public function fetchAll($params) {
		return $this->getMapper()->fetchAll($params);
	}
	
	public function getFamilies($params = array()) {
		$families = array();
		$list = $this->fetchAll($params);
		foreach ($list as $g) {
			$families[$g->getFamily()][] = $g;
		}
		return $families;
	}
    
	public function addElement($element) {
		array_push($this->_elements, $element);
		$element->setGraphic($this);
		$t = Zend_Registry::get('translate');
		$legend_length = strlen($t->_($element->getLegend()));
		if ( $legend_length > $this->_max_legend_length ) {
			$this->_max_legend_length = $legend_length;
			#echo "set max to: ".$legend_length." from ".$element->getLegend()."<br />";
		}
	}
	
	public function addElements($elements) {
		foreach ($elements as $e) {
			$this->addElement($e);
		}
	}
	
	public function setStartTime($start) {
		if (! is_a($start, 'Zend_Date')) {
			$start = new Zend_Date($start);
		}
		
		$this->_start = $start->toString('U');
	}
	
	public function setEndTime($end) {
		if (! is_a($end, 'Zend_Date')) {
			$end = new Zend_Date($end);
		}
		
		$this->_end = $end->toString('U');		
	}
	
	public function setPeriod($period) {
		if (preg_match('/^-\d+[hsdwmy]/', $period)) {
			$this->_end = 'now';
			$this->_start = $this->_end.$period;
		} elseif (preg_match('/^\+?\d+[hsdwmy]/', $period)) {
			$this->_end = $this->_start.$period;
		} else {
			switch ($period) {
				case 'hour':
				case 'lasthour':
					$this->setPeriod('-1h');
					break;
				case 'week':
				case 'lastweek':
					$this->setPeriod('-1w');
					break;
				case 'month':
				case 'lastmonth':
					$this->setPeriod('-1m');
					break;
				case 'year':
				case 'lastyear':
					$this->setPeriod('-1y');
					break;
			}
		}
	}
	
	public function stroke() {
		
		$cmd = $this->RRD_command.' graph -';
		
		## setup titles
		if ($this->_horizontal_title != '') {
			$cmd .= ' -t \''.$this->_horizontal_title.'\'';
		}
                if ($this->_vertical_title != '') {
			$cmd .= ' -v \''.$this->_vertical_title.'\'';
		}
		## setup start and end dates
		$cmd .= ' --end '.$this->_end.' --start '.$this->_start;
		
		## setup size
		$cmd .= ' -w '.$this->_width;
		$cmd .= ' -h '.$this->_height;
		
		## slope mode
		$cmd .= ' --slope-mode';
		
		## Y-axis limits
		$cmd .= ' --rigid --alt-autoscale-max';
		$cmd .= ' --lower-limit=0';
		if (is_numeric($this->_min_yvalue) && $this->_min_yvalue > 0) { 
     		$cmd .= ' --upper-limit='.$this->_min_yvalue;
		}
		
		## Base
		if ($this->_base == 1000 || $this->_base == 1024) {
    		$cmd .= ' --base='.$this->_base;
		}
		
		## Fonts
		$cmd .= ' --font \'TITLE:10\' --font \'AXIS:7\' --font \'LEGEND:8\' --font \'UNIT:7\'';

                ## Border
                $cmd .= ' --color SHADEA#AAAAAA00 --color SHADEB#AAAAAA00';
		foreach ($this->_elements as $e) {
			$cmd .= ' '.$e->getDEFParamString($e);
			$cmd .= ' '.$e->getPlotParamString($e);
			$cmd .= ' '.$e->getPrintParamString($e);
		}
	    #die($cmd);
		$res = `$cmd`;
		
		header('Content-type: image/png');
		echo $res;
	}
	
}
