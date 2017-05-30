<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Reporting statistics
 */

class Default_Model_ReportingStats
{
	protected $_values = array();
	protected $_days = array();
	protected $_what = '';
	protected $_fromdate;
	protected $_todate;
	
	protected $_linemapper = array('msgs', 'spams', 'highspams', 'viruses', 'names', 'others', 'cleans', 'bytes', 'users', 'domains');

	protected $_mapper;
	
	protected $_statscachefile = '/tmp/stat.cache';

	public function setWhat($what) {
		$this->_what = $what;
	}
	public function getWhat() {
		return $this->_what;
	}
	
	public function setValue($param, $value) {
		if (array_key_exists($param, $this->_values)) {
			$this->_values[$param] = $value;
		}
	}

	public function getValue($param) {
		$ret = null;
		if (array_key_exists($param, $this->_values)) {
			$ret = $this->_values[$param];
		}
		if ($ret == 'false') {
			return 0;
		}
		return $ret;
	}
	
	public function getPercentValue($value) {
		$msgs = $this->getValue('msgs');
		$value = $this->getValue($value);
		
		if ($msgs == 0 || $value == 0) {
			return 0;
		}
		$p = 100 / $msgs;
		$pv = $p * $value;
		return round($pv, 2);
	}

    public function getBarWidth($what, $fullwidth) {
        if($this->getPercentValue($what) == 0) {
  	        return 0;
        }
        $ratio = $fullwidth / 100;
        if ($what == 'cleans') {
            return $fullwidth - (ceil($this->getPercentValue('spams')*$ratio) + ceil($this->getPercentValue('viruses')*$ratio));	
        }
        if ($what == 'spams' && (ceil($this->getPercentValue('spams')*$ratio) + ceil($this->getPercentValue('viruses')*$ratio)) > $fullwidth) {
            return floor($this->stats_[$what]*$ratio);
        }
        return ceil( $this->getPercentValue($what)*$ratio );
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
	
	public function setFromDate($str) {
		$this->_fromdate = $str;
		#new Zend_Date($str, 'yyyyMMdd');
	}
    public function setToDate($str) {
		$this->_todate = $str;
		#new Zend_Date($str, 'yyyyMMdd');
	}
	
	public function getDate($when) {
		$date = new Zend_Date($this->_todate, 'yyyyMMdd');
		if ($when == 'from') {
     		$date = new Zend_Date($this->_fromdate, 'yyyyMMdd');
		}
		return $date->get(Zend_Date::DATE_LONG);
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
			$this->setMapper(new Default_Model_ReportingStatsMapper());
		}
		return $this->_mapper;
	}

	public function find($id)
	{
		$this->getMapper()->find($id, $this);
		return $this;
	}

	public function startFetchAll($params) {
		return $this->getMapper()->startFetchAll($params);
	}
    public function abortFetchAll($params) {
		return $this->getMapper()->abortFetchAll($params);
	}
	public function getStatusFetchAll($params) {
		return $this->getMapper()->getStatusFetchAll($params);
	}
	public function fetchAll($params = NULL) {
		return $this->getMapper()->fetchAll($params);
	}
	
	public function loadFromLine($line, $day = null) {
		
	}
	
	public function addFromLine($line, $day = null) {
		$matches = preg_split('/\|/', $line);
		$i = 0;
		foreach ($matches as $m) {
		    if (isset($this->_linemapper[$i])) {
		        if ($day) {
		        	if (!isset($this->_days[$day][$this->_linemapper[$i]])) {
		        		$this->_days[$day][$this->_linemapper[$i]] = 0;
		        	}
		        	if ($this->_linemapper[$i] == 'users' || $this->_linemapper[$i] == 'domains') {
		        		$this->_days[$day][$this->_linemapper[$i]] = max($this->_days[$day][$this->_linemapper[$i]], $matches[$i]);
		        	} else {
    		     		$this->_days[$day][$this->_linemapper[$i]] += $matches[$i];
		        	}
		     	} else {
		     		if (!isset($this->_values[$this->_linemapper[$i]])) {
		     			$this->_values[$this->_linemapper[$i]] = 0;
		     		}
		     		if ($this->_linemapper[$i] == 'users' || $this->_linemapper[$i] == 'domains') {
    		     		$this->_values[$this->_linemapper[$i]] = max($this->_values[$this->_linemapper[$i]], $matches[$i]);
		     		} else {
		     			$this->_values[$this->_linemapper[$i]] += $matches[$i];
		     		}
		     	}
		     }
		     $i++;
		}
	}
	
	public function isGlobal() {
		if (preg_match('/_global\@(\S+)/', $this->getWhat(), $matches)) {
			return $matches[1];
		}
		if ($this->getWhat() == '_global') {
			$t = Zend_registry::get('translate');
			return $t->_('all domains');
		}
		return null;
	}
	
	public function isDomain() {
		if (!preg_match('/\@/', $this->getWhat())) {
			return true;
		}
		return false;
	}

    static public function compareMsgs($a, $b) {
    	$va = $a->getValue('msgs');
    	$vb = $b->getValue('msgs');
    	if ($va == $vb) {
    		return 0;
    	}
        return ($va > $vb) ? -1 : +1;
    }
    static public function compareSpams($a, $b) {
    	$va = $a->getValue('spams');
    	$vb = $b->getValue('spams');
    	if ($va == $vb) {
    		return 0;
    	}
        return ($va > $vb) ? -1 : +1;
    }
    
    static public function compareSpamsPercent($a, $b) {
    	$va = $a->getPercentValue('spams');
    	$vb = $b->getPercentValue('spams');
    	if ($va == $vb) {
    		return 0;
    	}
        return ($va > $vb) ? -1 : +1;
    }
    static public function compareWhat($a, $b) {
    	$va = strtolower($a->getWhat());
    	$vb = strtolower($b->getWhat());
    	if ($va == $vb) {
    		return 0;
    	}
        return ($va > $vb) ? +1 : -1;
    }
    static public function compareViruses($a, $b) {
    	$va = $a->getValue('viruses');
    	$vb = $b->getValue('viruses');
    	if ($va == $vb) {
    		return 0;
    	}
        return ($va > $vb) ? -1 : +1;
    }
   static public function compareUsers($a, $b) {
    	$va = $a->getValue('users');
    	$vb = $b->getValue('users');
    	if ($va == '' || $vb == '') {
    		return Default_Model_ReportingStats::compareWhat($a, $b);
    	}
    	if ($va == $vb) {
    		return 0;
    	}
        return ($va > $vb) ? -1 : +1;
    }
    
    public function createPieChart($id = null, $data = null, $params = array()) {
        include("pChart/class/pData.class.php"); 
        include("pChart/class/pDraw.class.php"); 
        include("pChart/class/pPie.class.php"); 
        include("pChart/class/pImage.class.php"); 

        $t = Zend_Registry::get('translate');
        $DataSet = new pData;
        if ($data) {
            foreach ($data as $key => $value) {
              if (isset($params['label_orientation']) && $params['label_orientation'] == 'vertical') {
                  $vwhats[] = $value." ".$t->_($key);
              } else {
              	  $vwhats[] = $t->_($key);
              }
              $vdata[] = $value;
            }
        } else {
        	$vdata = array($data['cleans'], $data['spams'], $data['viruses']+$data['contents']);
        	$vwhats = array($t->_('clean'), $t->_('spam'), $t->_('dangerous'));
        }
        
        $size = array(190, 190);
        $position = array(125, 80);

        $radius = 60;
        $border = array('E'=>true,'R'=>250,'G'=>250,'B'=>250);
        $value = array('T'=>PIE_VALUE_PERCENTAGE,'P'=>PIE_VALUE_INSIDE,'R'=>50,'G'=>50,'B'=>50);
        $label_pos = array(60,165);
        if (count($vdata) > 3) {
        	$label_pos = array(20,165);
        }
        if (count($vdata) > 4) {
        	$label_pos = array(10,165);
        }
        $label_bg = array('R'=>240,'G'=>240,'B'=>240,'A'=>255);
        $label_size = array('S' => -50, 'M' => 5, 'O' => LEGEND_HORIZONTAL);
      
        if (isset($params['size']) && is_array($params['size'])) {
                $size = $params['size'];
        }
        $picture = new pImage($size[0],$size[1],$DataSet);
        // 2D settings
        if (!isset($params['style']) || $params['style'] != '3D') {
        	$picture->setShadow(TRUE,array("X"=>2,"Y"=>2,"R"=>150,"G"=>150,"B"=>150,"Alpha"=>100));
        

            if (isset($params['label_orientation']) && $params['label_orientation'] == 'vertical') {
            	$label_size = array('S' => -50, 'M' => 5, 'O' => LEGEND_VERTICAL);
            	if (count($vdata) <= 5) {
                 	$label_pos = array(10,130);
    	        } else {
    		        $label_pos = array(10, 115);
    	        }
                $position = array(155, 80);
            } else {
            	$radius = 80;
            	$position = array(95, 95);
            }

        } else {
        // 3D settings
            $position = array(125, 85);
            $label_pos = array(60,150);

            if (isset($params['label_orientation']) && $params['label_orientation'] == 'vertical') {
        		$label_size = array('S' => -50, 'M' => 5, 'O' => LEGEND_VERTICAL);
            	$label_pos = array(60,150);
                if (count($vdata) <= 5) {
                    $label_pos = array(10,130);
                } else {
                    $label_pos = array(10, 115);
                }
                $position = array(135, 80);
            }
        }
        if (isset($params['position']) && is_array($params['position'])) {
            $position = $params['position'];
        }
        if (isset($params['radius']) && is_numeric($params['radius'])) {
            $radius = $params['radius'];
        }
        // now final sanity checks
        if (($radius*2) > min($size[0], $size[1])) {
            $radius = floor(min($size[0], $size[1])/2)-3;
            $position = array(floor($size[0]/2), floor($size[1]/2));
        }
        if ($radius > min($position[0], $position[1])) {
            $position = array($radius,$radius);
        }

        
        $config = new MailCleaner_Config();
        $DataSet->AddPoints($vdata,"values");  
        $DataSet->AddPoints($vwhats,"labels"); 
        $DataSet->setAbscissa("labels");

        $template = Zend_Registry::get('default_template');
        include_once(APPLICATION_PATH . '/../public/templates/'.$template.'/css/pieColors.php');
        $chart = new pPie($picture,$DataSet);
        $slice = 0;
        foreach ($data as $what => $zzz) {
        	if (isset($data_colors[$what])) {
                $chart->setSliceColor($slice, $data_colors[$what]);
        	}
        	$slice++;
        }

        $picture->setFontProperties(array("FontName"=>$config->getOption('SRCDIR')."/www/guis/admin/application/library/pChart/fonts/pf_arma_five.ttf","FontSize"=>6,"R"=>80,"G"=>80,"B"=>80)); 
        
        $nonnull = false;
        foreach ($vdata as $d) {
        	if ($d > 0) {
        		$nonnull = true;
        		break; 
        	}
        }
        if ($nonnull) {
        	if (!isset($params['style']) || $params['style'] != '3D') {
                $chart->draw2DPie(
                           $position[0],$position[1],
                           array(
                                 "Radius" => $radius,
                                 "Border"=>$border['E'],"BorderR"=>$border['R'],"BorderG"=>$border['G'],"BorderB"=>$border['B'],
                                 "DrawLabels"=>FALSE,"WriteValues"=>$value['T'],"ValuePosition"=>$value['P'],
                                 "ValueR"=>$value['R'],"ValueG"=>$value['G'],"ValueB"=>$value['B']));
        	} else {
        		$chart->draw3DPie(
        		           $position[0],$position[1],
        		           array(
        		                  "SliceHeight"=>10, 
        		                  "DrawLabels"=>FALSE,"WriteValues"=>$value['T'],"ValuePosition"=>$value['P'],
        		                  "ValueR"=>$value['R'],"ValueG"=>$value['G'],"ValueB"=>$value['B']));
        	}
        } else {
             $picture->drawFilledCircle($position[0],$position[1],$radius,array("R"=>230, "G"=>230, "B"=>230));
        }
        $picture->setShadow(FALSE);
        $picture->setFontProperties(array("FontName"=>$config->getOption('SRCDIR')."/www/guis/admin/application/library/pChart/fonts/pf_arma_five.ttf","FontSize"=>6,"R"=>80,"G"=>80,"B"=>80)); 
        if (!isset($params['no_label'])) {
            $chart->drawPieLegend(
                       $label_pos[0],$label_pos[1],
                       array(
                               "Style"=>LEGEND_ROUND,"Mode"=>$label_size['O'],
                               "Surrounding"=>$label_size['S'],"Margin"=>$label_size['M'],
                               'R'=>$label_bg['R'], 'G'=>$label_bg['G'], 'B'=>$label_bg['B'], 'Alpha'=>$label_bg['A'] 
                       ));
        }
        
        if (isset($params['render']) && $params['render']) {
        	$picture->Stroke();
        } else {
            if (!$id) {
               $id = "Zob4458";
            }
            $file = $config->getOption('SRCDIR').'/www/guis/admin/public/tmp/'.$id.".png";
            $picture->render($file);
            return $id;
        }
    }
    
    public function getTodayStatElements($type) {
		$els = array(          'cleans' => 'globalCleanCount',
    		                   'spams'=>  'globalSpamCount',
    		                   'dangerous' => 'globalNameCount+globalOtherCount',
    		                   'viruses'=>  'globalVirusCount');
		if (!isset($type)) {
			return $els;
		}
		switch ($type) {
			case 'refused':
				$els = array(  'rbl' => 'globalRefusedRBLCount+globalRefusedBackscatterCount',
    		                   'blacklists'=>  'globalRefusedHostCount+globalRefusedBlacklistedSenderCount', 
    		                   'relay' => 'globalRefusedRelayCount',
    		                   'policies' => 'globalRefusedSpoofingCount+globalRefusedBATVCount+globalRefusedBadSPFCount+globalRefusedUnauthenticatedCount+globalRefusedUnencryptedCount+globalRefusedBadRDNSCount',
    	                       'callout' => 'globalRefusedCalloutCount',
    	                       'syntax' => 'globalRefusedLocalpartCount+globalRefusedBadSenderCount+globalRefusedBadSenderCount');
				break;
			case 'global':
				$els = array(  'cleans' => 'globalCleanCount',
    		                   'spams'=>  'globalRefusedCount+globalSpamCount',
    		                   'dangerous' => 'globalNameCount+globalOtherCount',
    		                   'viruses'=>  'globalVirusCount', 
    	                       'outgoing' => 'globalRelayedCount');
				break;
			case 'delayed':
				$els = array(  'greylisted' => 'globalDelayedGreylistCount',
    		                   'rate limited'=>  'globalDelayedRatelimitCount');
				break;
			case 'relayed':
				$els = array(  'by hosts' => 'globalRelayedHostCount',
    		                   'authentified'=>  'globalRelayedAuthenticatedCount', 
    		                   'refused' => 'globalRelayedRefusedCount',
    	                       'viruses' => 'globalRelayedVirusCount');
				break;
			case 'sessions':
				$els = array(  'accepted' => 'globalAcceptedCount',
    		                   'refused'=>  'globalRefusedCount', 
    		                   'delayed' => 'globalDelayedCount',
    	                       'relayed' => 'globalRelayedCount');
				break;
		}
		return $els;
	}
	
	public function getTodayValues($what, $slaveid, $type = 'unknown') {
	    $slave = new Default_Model_Slave();
	    $slaves = array();
	    if (is_numeric($slaveid) && $slaveid > 0) {
	    	$slave->find($slaveid);
	    	$slaves = array($slave);
	    } else {
            $slaves = $slave->fetchAll();
	    }
        $total = array();
        
        foreach ($slaves as $s) {
            $total = $this->cumulStats($total, $s->getTodaySNMPStats($what));
        }
        
        if (!$type || $type == '') {
        	$type = 'unknown';
        }
        if (count($slaves) > 1) {
           $cachefile = $this->_statscachefile.".".$type;
        } else {
        	$ts = array_pop($slaves);
        	$cachefile = $this->_statscachefile.".".$ts->getId().".".$type;
        }
		file_put_contents($cachefile, serialize($total));
        return $total;
	}
	
	private function cumulStats($total, $stats) {
		foreach ($stats as $key => $value) {
			$total[$key] += $value;
		}
		return $total;
	}
	
	public function getTodayPie($what, $slaveid, $usecache, $type = 'global', $graph_params = array()) {
		
    	$total = null;
    	$cachefile = $this->_statscachefile.".".$type;
    	if (is_numeric($slaveid) && $slaveid > 0) {
    		$cachefile = $this->_statscachefile.".".$slaveid.".".$type;
    	}
		if ($usecache && file_exists($cachefile)) {
			$lmod = filemtime($cachefile);
			if ((time() - $lmod) < 60) {
    			$total = unserialize(file_get_contents($cachefile));
			} else {
    			unlink($cachefile);
			}
	    }
	    if (!$total) {
	    	$total = $this->getTodayValues($what, $slaveid, $type);
		}
    	$stats = new Default_Model_ReportingStats();
        $params = array('render'=>true, 'no_label'=>true);
    	$stats->createPieChart(0,$total,array_merge($params, $graph_params));
	}
}
