<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker: */

/**
 * Storage driver for use against PEAR website
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Authentication
 * @package    Auth
 * @author     Yavor Shahpasov <yavo@netsmart.com.cy>
 * @author     Adam Ashley <aashley@php.net>
 * @copyright  2001-2006 The PHP Group
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    CVS: $Id: PEAR.php 289652 2009-10-15 04:42:18Z aashley $
 * @link       http://pear.php.net/package/Auth
 * @since      File available since Release 1.3.0
 */

/**
 * Include PEAR HTTP_Client.
 */
require_once 'Pear/HTTP/Client.php';
/**
 * Include Auth_Container base class
 */
require_once 'Auth/Container.php';

/**
 * Storage driver for authenticating against PEAR website
 *
 * This driver provides a method for authenticating against the pear.php.net
 * authentication system.
 *
 * Supports two options:
 * - "url": The base URL with schema to authenticate against
 * - "karma": An array of karma levels which the user needs one of.
 *            When empty, no karma level is required.
 *
 * @category   Authentication
 * @package    Auth
 * @author     Yavor Shahpasov <yavo@netsmart.com.cy>
 * @author     Adam Ashley <aashley@php.net>
 * @author     Adam Harvey <aharvey@php.net>
 * @copyright  2001-2007 The PHP Group
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@  File: $Revision: 289652 $
 * @link       http://pear.php.net/package/Auth
 * @since      Class available since Release 1.3.0
 */
class Auth_Container_Pear extends Auth_Container
{
    // {{{ properties

    /**
     * URL to connect to, with schema
     *
     * @var string
     */
    var $url = 'https://pear.php.net/rest-login.php/';

    /**
     * Array of karma levels the user can have.
     * A user needs only one of the levels to succeed login.
     * No levels mean that only username and password need to match
     *
     * @var array
     */
    var $karma = [];

    // }}}
    // {{{ Auth_Container_Pear() [constructor]

    /**
     * Constructor
     *
     * Accepts options "url" and "karma", see class docs.
     *
     * @param array $data Array of options
     *
     * @return void
     */
    function Auth_Container_Pear($data = null)
    {
        if (!is_array($data)) {
            PEAR::raiseError('The options for Auth_Container_Pear must be an array');
        }
        if (isset($data['karma'])) {
            if (is_array($data['karma'])) {
                $this->karma = $data['karma'];
            } else {
                $this->karma = [$data['karma']];
            }
        }

        if (isset($data['url'])) {
            $this->url = $data['url'];
        }
    }

    // }}}
    // {{{ fetchData()

    /**
     * Get user information from pear.php.net
     *
     * This function uses the given username and password to authenticate
     * against the pear.php.net website
     *
     * @param string    Username
     * @param string    Password
     * @return mixed    Error object or boolean
     */
    function fetchData($username, $password)
    {
        $this->log('Auth_Container_PEAR::fetchData() called.', AUTH_LOG_DEBUG);

        $client = new HTTP_Client;

        $this->log('Auth_Container_PEAR::fetchData() getting salt.', AUTH_LOG_DEBUG);
        $code = $client->get($this->url . '/getsalt');
        if ($code instanceof PEAR_Error) {
            return $code;
        }
        if ($code != 200) {
            return PEAR::raiseError('Bad response to salt request.', $code);
        }
        $resp = $client->currentResponse();
        $salt = $resp['body'];

        $this->log('Auth_Container_PEAR::fetchData() calling validate.', AUTH_LOG_DEBUG);
        $postOptions = [
            'username' => $username,
            'password' => md5($salt . md5($password))
        ];
        if (is_array($this->karma) && count($this->karma) > 0) {
            $postOptions['karma'] = implode(',', $this->karma);
        }

        $code = $client->post($this->url . '/validate', $postOptions);
        if ($code instanceof PEAR_Error) {
            return $code;
        }
        if ($code != 200) {
            return PEAR::raiseError('Bad response to validate request.', $code);
        }
        $resp = $client->currentResponse();

        list($code, $message) = explode(' ', $resp['body'], 2);
        if ($code != 8) {
            return PEAR::raiseError($message, $code);
        }
        return true;
    }

    // }}}

}
