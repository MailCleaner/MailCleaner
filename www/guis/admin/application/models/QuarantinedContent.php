<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Quarantined dangerous content
 */

class Default_Model_QuarantinedContent
{
    protected $_values = [
        'to_address' => '',
        'from_address' => '',
        'id' => '',
        'date' => '',
        'time' => '',
        'subject' => '',
        'content' => '',
        'store_id' => 0,
        'virusinfected' => 0,
        'otherinfected' => 0,
        'nameinfected' => 0,
        'report' => '',
        'size' => 0
    ];

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

    public function getCleanParam($param)
    {
        $t = Zend_Registry::get('translate');
        $split_fields = [
            'subject' => 80,
        ];
        $data = $this->getParam($param);

        if (preg_match('/(\=\?[^?]{3,15}\?.\?[^?]+\?\=)/', $data, $matches)) {
            $ddata = @iconv_mime_decode($matches[1], ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            $data = preg_replace('/\=\?[^?]{3,15}\?.\?[^?]+\?\=/', $ddata, $data);
        }

        $ret = htmlentities($data, ENT_COMPAT, "UTF-8");
        if ($param == 'report') {
            $ret = preg_replace('/,/', '<br />', $ret);
        }

        if ($param == 'size' && is_numeric($ret)) {
            if ($ret > 1000000000) {
                $ret = ($ret / 1000000000) . " " . $t->_('Gb');
            } else if ($ret > 1000000) {
                $ret = ($ret / 1000000) . " " . $t->_('Mb');
            } else if ($ret > 1000) {
                $ret = ($ret / 1000) . " " . $t->_('Kb');
            } else {
                $ret .= " " . $t->_('bytes');
            }
        }
        if (isset($split_fields[$param]) && (strlen($ret) > $split_fields[$param])) {
            $ret = substr($ret, 0, $split_fields[$param]);
            $ret .= '...';
        }

        return $ret;
    }

    public function getParamArray()
    {
        return $this->_values;
    }

    public function getAvailableParams()
    {
        $ret = [];
        foreach ($this->_values as $key => $value) {
            $ret[] = $key;
        }
        return $ret;
    }

    public function setId($id)
    {
        $this->_id = $id;
    }
    public function getId()
    {
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
            $this->setMapper(new Default_Model_QuarantinedContentMapper());
        }
        return $this->_mapper;
    }

    public function getCleanAddress($address)
    {
        $locallen = 25;
        $domainlen = 25;
        $res = $address;
        $ca = [];
        foreach (preg_split('/,/', $address) as $a) {
            if (preg_match('/(\S+)\@(\S+)/', $a, $matches)) {
                $str = "";
                if (strlen($matches[1]) > $locallen) {
                    $str .= substr($matches[1], 0, $locallen) . "...";
                } else {
                    $str .= $matches[1];
                }

                $str .= "@";
                if (strlen($matches[2]) > $domainlen) {
                    $str .= substr($matches[2], 0, $domainlen) . "...";
                } else {
                    $str .= $matches[2];
                }
                $ca[] = $str;
            }
        }
        $str = implode(',', $ca);
        return substr($str, 0, 50);
    }

    public function find($id)
    {
        $this->getMapper()->find($id, $this);
        return $this;
    }

    public function fetchAllCount($params = NULL)
    {
        return $this->getMapper()->fetchAllCount($params);
    }
    public function fetchAll($params = NULL)
    {
        return $this->getMapper()->fetchAll($params);
    }

    public function getNbPages()
    {
        return $this->getMapper()->getNbPages();
    }
    public function getEffectivePage()
    {
        return $this->getMapper()->getEffectivePage();
    }

    public function getDestination()
    {
        return $this->getParam('to_address');
    }
    public function getStoreId()
    {
        return $this->getParam('store_id');
    }
    public function getSender()
    {
        return $this->getParam('from_address');
    }

    public function getFullId()
    {
        $id = '';
        if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $this->getParam('date'), $matches)) {
            $id = $matches[1] . $matches[2] . $matches[3] . "-";
        }
        $id .= $this->getParam('id');
        return $id;
    }

    static public function parseHeaders($str)
    {
        $res = [];

        $lines = preg_split('/\n/', $str);

        $last_header = "";
        $matches = [];

        $lh = "";
        foreach ($lines as $line) {
            if (preg_match('/(\=\?[^?]{3,15}\?.\?[^?]+\?\=)/', $line, $matches)) {
                $dline = @iconv_mime_decode($matches[1], ICONV_MIME_DECODE_STRICT, 'UTF-8');
                $line = preg_replace('/\=\?[^?]{3,15}\?.\?[^?]+\?\=/', $dline, $line);
            }

            if (strlen($line) > 78) {
                $line = substr($line, 0, 78) . "...";
            }
            $line = htmlentities($line, ENT_COMPAT, 'UTF-8');
            if (preg_match('/^([A-Z]\S+):(.*)/', $line, $matches)) {
                if ($last_header != "" && $lh != "") {
                    array_push($res, [$last_header, $lh]);
                }
                $last_header = $matches[1];
                $lh = $matches[2];
            } else {
                $line = preg_replace('/\n/', '', $line);
                $lh .= "<br />&nbsp;&nbsp;&nbsp;" . $line;
            }
        }
        if ($last_header != "" && $lh != "") {
            if (strlen($lh) > 72) {
                $lh = substr($lh, 0, 72) . "...";
            }
            #array_push($res, [$last_header, $lh."-"]);
        }
        return $res;
    }
}
