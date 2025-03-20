<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 */

class Api_Model_SoapInterface
{
    /**
     * *************
     * Dummy
     * *************
     */
    /**
     * This function simply answer with the question
     *
     * @param string  $question
     * @return string
     */
    public function Test_getResponse($question)
    {
        return $question;
    }

    /**
     * *************
     * domainAdd
     * *************
     */
    /**
     * This function adds a domain with parameters
     *
     * @param string  $domain_name
     * @param array   $params
     * @return array
     */
    public function domainAdd($domain_name, $params = [])
    {
        $this->testAuth($params);
        $api = new Api_Model_DomainAPI();
        $api->add($domain_name, $params);
        return Zend_Registry::get('response')->getResponse();
    }
    /**
     * This function edit a domain with parameters
     *
     * @param string  $domain_name
     * @param array   $params
     * @return array
     */
    public function domainEdit($domain_name, $params = [])
    {
        $this->testAuth($params);
        $api = new Api_Model_DomainAPI();
        $api->edit($domain_name, $params);
        return Zend_Registry::get('response')->getResponse();
    }

    /**
     * This function test if a domain exists
     *
     * @param string  $domain_name
     * @return array
     */
    public function domainExists($domain_name, $params = [])
    {
        $this->testAuth($params);
        $api = new Api_Model_DomainAPI();
        $api->exists($domain_name);
        return Zend_Registry::get('response')->getResponse();
    }
    /**
     * This function removes a domain
     *
     * @param string  $domain_name
     * @return array
     */
    public function domainRemove($domain_name, $params = [])
    {
        $this->testAuth($params);
        $api = new Api_Model_DomainAPI();
        $api->remove($domain_name);
        return Zend_Registry::get('response')->getResponse();
    }

    /**
     * This function shows domain's data
     *
     * @param string  $domain_name
     * @param array   $params
     * @return array
     */
    public function domainShow($domain_name, $params = NULL)
    {
        $this->testAuth($params);
        $api = new Api_Model_DomainAPI();
        if (isset($params['params'])) {
            $api->show($domain_name,  preg_split('/[,:]/', $params['params']));
        } else {
            $api->show($domain_name, NULL);
        }
        return Zend_Registry::get('response')->getResponse();
    }

    /**
     * This function lists all domains
     *
     * @param array   $params
     * @return array
     */
    public function domainList($params = NULL)
    {
        $this->testAuth($params);
        $api = new Api_Model_DomainAPI();
        $api->domainList();
        return Zend_Registry::get('response')->getResponse();
    }

    private function testAuth($params)
    {
        if (isset($params['username']) && isset($params['password'])) {
            $user = new Default_Model_Administrator();
            if ($user->checkAPIAuthentication($params['username'], $params['password'])) {
                $user->find($params['username']);
                Zend_Registry::set('user', $user);
            } else {
                Zend_Registry::get('response')->setResponse(401, 'authentication failed');
            }
        }
    }

    /**
     * *************
     * user
     * *************
     */
    /**
     * This function test if a user exists
     *
     * @param array  $params
     * @return array
     */
    public function userExists($params = [])
    {
        $this->testAuth($params);
        $api = new Api_Model_UserAPI();
        $api->exists($params);
        return Zend_Registry::get('response')->getResponse();
    }

    /**
     * This function adds a user
     *
     * @param array  $params
     * @return array
     */
    public function userAdd($params = [])
    {
        $this->testAuth($params);
        $api = new Api_Model_UserAPI();
        $api->add($params);
        return Zend_Registry::get('response')->getResponse();
    }

    /**
     * This function edits a user's settings
     *
     * @param array  $params
     * @return array
     */
    public function userEdit($params = [])
    {
        $this->testAuth($params);
        $api = new Api_Model_UserAPI();
        $api->edit($params);
        return Zend_Registry::get('response')->getResponse();
    }

    /**
     * This function deletes a user
     *
     * @param array  $params
     * @return array
     */
    public function userDelete($params = [])
    {
        $this->testAuth($params);
        $api = new Api_Model_UserAPI();
        $api->delete($params);
        return Zend_Registry::get('response')->getResponse();
    }

    /**
     * This function lists users of a domain
     *
     * @param array  $params
     * @return array
     */
    public function userList($params = [])
    {
        $this->testAuth($params);
        $api = new Api_Model_UserAPI();
        $api->userList($params);
        return Zend_Registry::get('response')->getResponse();
    }

    /**
     * This function show user settings
     *
     * @param array  $params
     * @return array
     */
    public function userShow($params = [])
    {
        $this->testAuth($params);
        $api = new Api_Model_UserAPI();
        $api->show($params);
        return Zend_Registry::get('response')->getResponse();
    }

    /**
     * ***************
     * address (email)
     * ***************
     */
    /**
     * This function test if an address exists
     *
     * @param array  $params
     * @return array
     */
    public function addressExists($params = [])
    {
        $this->testAuth($params);
        $api = new Api_Model_AddressAPI();
        $api->exists($params);
        return Zend_Registry::get('response')->getResponse();
    }

    /**
     * This function adds an address
     *
     * @param array  $params
     * @return array
     */
    public function addressAdd($params = [])
    {
        $this->testAuth($params);
        $api = new Api_Model_AddressAPI();
        $api->add($params);
        return Zend_Registry::get('response')->getResponse();
    }

    /**
     * This function edits an address's settings
     *
     * @param array  $params
     * @return array
     */
    public function addressEdit($params = [])
    {
        $this->testAuth($params);
        $api = new Api_Model_AddressAPI();
        $api->edit($params);
        return Zend_Registry::get('response')->getResponse();
    }

    /**
     * This function deletes an address
     *
     * @param array  $params
     * @return array
     */
    public function addressDelete($params = [])
    {
        $this->testAuth($params);
        $api = new Api_Model_AddressAPI();
        $api->delete($params);
        return Zend_Registry::get('response')->getResponse();
    }

    /**
     * This function lists addresses of a domain
     *
     * @param array  $params
     * @return array
     */
    public function addressList($params = [])
    {
        $this->testAuth($params);
        $api = new Api_Model_AddressAPI();
        $api->addressList($params);
        return Zend_Registry::get('response')->getResponse();
    }
}
