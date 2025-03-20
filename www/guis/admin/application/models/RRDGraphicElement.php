<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Pending alias request
 */

class Default_Model_RRDGraphicElement
{
    protected $_id;

    protected $_values = [
        'name' => '',
        'type' => '',
        'function' => '',
        'oid' => '',
        'draw_name'  => '',
        'draw_order' => 0,
        'draw_style' => '',
        'min' => 'U',
        'max' => 'U',
        'draw_factor' => '*1',
        'draw_format' => '8.0lf',
        'draw_unit' => ''
    ];

    protected $_graphic;
    protected $_mapper;

    public function setParam($param, $value)
    {
        if (array_key_exists($param, $this->_values)) {
            $this->_values[$param] = $value;
        }
    }

    public function getParam($param)
    {
        $ret = null;
        if (array_key_exists($param, $this->_values)) {
            $ret = $this->_values[$param];
        }
        if ($ret == 'false') {
            return 0;
        }
        return $ret;
    }

    public function getParamArray()
    {
        return $this->_values;
    }

    public function setId($id)
    {
        $this->_id = $id;
    }
    public function getId()
    {
        return $this->_id;
    }
    public function setGraphic($graphic)
    {
        $this->_graphic = $graphic;
    }

    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Default_Model_RRDGraphicElementMapper());
        }
        return $this->_mapper;
    }

    public function find($elementid)
    {
        $this->getMapper()->find($elementid, $this);
        return $this;
    }

    public function fetchAll($params = NULL)
    {
        return $this->getMapper()->fetchAll($params);
    }

    public function save()
    {
        return $this->getMapper()->save($this);
    }

    public function delete()
    {
        return $this->getMapper()->delete($this);
    }

    private function getMinValue()
    {
        return 0;
    }

    public function getDEFParamString($e)
    {

        $name = $this->getParam('name');
        $nocdefname = 'nocdef' . $name;
        $str = '';

        if (preg_match('/([*])(\d+)/', $this->getParam('draw_factor'), $matches)) {
            $str = "DEF:$nocdefname=\"" . $this->getRRDArchiveFile() . "\":$name:" . $this->getParam('function');
            $str .= " CDEF:$name=$nocdefname," . $matches[2] . "," . $matches[1];
        } else {
            $str = "DEF:$name=\"" . $this->getRRDArchiveFile() . "\":$name:" . $this->getParam('function');
        }
        return $str;
    }

    public function getPlotParamString($e)
    {
        # 'LINE:refused#FF0000:Refused '

        ## find out colors
        $c = $this->_graphic->getColors();
        $colorname = $this->getParam('name');
        if (preg_match('/^count(\S+)/', $colorname, $matches)) {
            $colorname = $matches[1];
        }
        $color = sprintf('#%02x%02x%02x', $c[$colorname]['R'], $c[$colorname]['G'], $c[$colorname]['B']);

        ## get text with alignment (padding)
        $padding = 20;
        if ($this->_graphic->getType() == 'count') {
            $padding += 5;
        }
        $text = str_pad(ucfirst($this->getLegend()), max($padding, $this->_graphic->getMaxLegendLenght()));
        $str = strtoupper($this->getParam('draw_style')) . ":" . $this->getParam('name') . $color . ":" . $text;
        return "'" . $str . "'";
    }

    public function getDrawUnit()
    {
        return $this->getParam('draw_unit');
    }

    public function getPrintParamString($e)
    {

        $fullstr = '';
        $t = Zend_Registry::get('translate');

        $name = $this->getParam('name');

        if ($this->_graphic->getType() == 'frequency') {
            $current = "GPRINT:" . $name . ":LAST:\"" . $t->_('last') . "\:%" . $this->getParam('draw_format') . " " . $this->getDrawUnit() . "\"";
            $average = "GPRINT:" . $name . ":AVERAGE:\"" . $t->_('average') . "\:%" . $this->getParam('draw_format') . " " . $this->getDrawUnit() . "\"";
            $maximum = "GPRINT:" . $name . ":MAX:\"" . $t->_('maximum') . "\:%" . $this->getParam('draw_format') . " " . $this->getDrawUnit() . "\"";
            $fullstr = $current . " " . $average . " " . $maximum;
        } else {
            $current = "GPRINT:" . $name . ":LAST:\"" . $t->_('last') . "\:%" . $this->getParam('draw_format') . " " . $this->getDrawUnit() . "\"";
            $maximum = "GPRINT:" . $name . ":MAX:\"" . $t->_('maximum') . "\:%" . $this->getParam('draw_format') . " " . $this->getDrawUnit() . "\"";
            $fullstr = $current . " " . $maximum;
        }

        return $fullstr;
        #$newname = 'n'.$name;
        #$cdefstr = 'CDEF:'.$newname.'='.$name.',1024,/';
        #$name = $newname;
        #
        #if ($this->getParam('type') == 'COUNTER' || $this->getParam('type') == 'DERIVE') {
        #
        #    ## last value
        #    $lastname = $name."last";
        #    $vdefstr .= 'VDEF:'.$lastname.'='.$name.',LAST';
        #    ## average values
        #    $avgname = $name."avg";
        #    $vdefstravg .= 'VDEF:'.$avgname.'='.$name.',AVERAGE';
        #    ## max value
        #    $maxname = $name."max";
        #    $vdefstrmax .= 'VDEF:'.$maxname.'='.$name.',MAXIMUM';
        #
        #    $str = 'GPRINT:'.$lastname.':'.$t->_(last).'\: %3.2lf';
        #    $avgstr = 'GPRINT:'.$avgname.':'.$t->_(average).'\: %3.2lf';
        #    $maxstr = 'GPRINT:'.$maxname.':'.$t->_(maximum).'\: %3.2lf';
        #    return "'".$cdefstr."' '".$vdefstr."' "."'".$str."' "."'".$vdefstravg."' "."'".$avgstr."' "."'".$vdefstrmax."' "."'".$maxstr."' ";
        #} else {
        #       $str = 'GPRINT:'.$name.':LAST:%10.0lf';
        #       return "'".$cdefstr."' '".$str."'";
        #}
        #return '';
    }

    private function getRRDArchiveFile()
    {
        $config = MailCleaner_Config::getInstance();
        $file = $config->getOption('VARDIR') . '/spool/newrrds/' . $this->_graphic->getName() . '_' . $this->_graphic->getType() . '/' . $this->_graphic->getHost() . '.rrd';
        return $file;
    }

    public function getLegend()
    {
        $t = Zend_Registry::get('translate');
        return $t->_($this->getParam('draw_name'));
    }
}
