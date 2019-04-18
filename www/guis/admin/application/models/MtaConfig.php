<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * SMTP server settings
 */

class Default_Model_MtaConfig
{
    protected $_id;
    protected $_values = array(
       'verify_sender' => 1,
       'smtp_enforce_sync' => 'true',
       'allow_mx_to_ip' => 'false',
       'rbls' => '',
       'rbls_timeout' => 5,
       'rbls_ignore_hosts' => '',
       'spf_dmarc_ignore_hosts' => '',
       'callout_timeout' => 10,
       'smtp_conn_access' => '*',
       'relay_from_hosts' => '',
       'allow_relay_for_unknown_domains' => 0,
       'host_reject' => '',
       'sender_reject' => '',
       'user_reject' => '',
       'recipient_reject' => '',
       'smtp_receive_timeout' => 0,
       'smtp_accept_max' => 0,
       'smtp_reserve' => 0,
       'smtp_load_reserve' => 0,
       'smtp_accept_max_per_host' => 0,
       'smtp_accept_max_per_trusted_host' => 0,
       'smtp_accept_queue_per_connection' => 0,
       'smtp_accept_max_per_connection' => 100,
       'global_msg_max_size' => 0,
       'errors_reply_to' => '',
       'retry_rule' => '',
       'ratelimit_rule' => '',
       'ratelimit_delay' => 0,
       'ratelimit_enable' => 0,
       'trusted_ratelimit_rule' => '',
       'trusted_ratelimit_delay' => 0,
       'trusted_ratelimit_enable' => 0,
       'no_ratelimit_hosts' => '',
       'use_incoming_tls' => 1,
       'tls_use_ssmtp_port' => 0,
       'tls_certificate_data' => '',
       'tls_certificate_key' => '',
       'hosts_require_tls' => '',
       'hosts_require_incoming_tls' => '',
       'domains_require_tls_from' => '',
       'domains_require_tls_to' => '',
       'outgoing_virus_scan' => 0,
       'mask_relayed_ip' => 0,
       'masquerade_outgoing_helo' => 0,
       'forbid_clear_auth' => 0,
       'dkim_default_domain' => '',
       'dkim_default_selector' => '',
       'dkim_default_pkey' => '',
       'relay_refused_to_domains' => '',
       'reject_bad_spf' => 0,
       'reject_bad_rdns' => 0,
       'dmarc_follow_reject_policy' => 0,
       'dmarc_enable_reports' => 0
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
            $this->setMapper(new Default_Model_MtaConfigMapper());
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
    
    public function useRBL($rbl) {
    	return preg_match('/\b'.$rbl.'\b/', $this->getParam('rbls'));
    }
    
    public function checkSSLCertificate() {
    	## openssl x509 -noout -modulus -in certificate.crt | openssl md5
    	## openssl rsa -noout -modulus -in privateKey.key | openssl md5
     
    	## openssl verify certificate.crt
    }
}
