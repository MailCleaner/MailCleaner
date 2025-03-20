<?php

/**
 * MailCleaner
 *
 * @license http://www.mailcleaner.net/open/licence_en.html MailCleaner Public License
 * @copyright 2015 Fastnet SA; 2023, John Mertz
 */

/**
 * User preferences controller
 */
class PreferencesController extends Zend_Controller_Action
{
    public function indexAction()
    {
        if (!empty($_SESSION['user'])) {
            $session = unserialize($_SESSION['user']);

            $userId = $session->getID();

            if (!empty($userId)) {
                $table = new Default_Model_DbTable_UserPreference();

                $row = $table->fetchRow($table->select()->where('id = ?', $userId));

                return $this->_helper->json(['preferences' => $row->toArray()]);
            }
        }
    }


    public function newslettersAction()
    {
        if (!empty($_SESSION['user'])) {
            $session = unserialize($_SESSION['user']);

            $userId = $session->getID();

            if (!empty($userId)) {
                if ('PATCH' == $this->getRequest()->getMethod()) {

                    $data = [];

                    parse_str($this->getRequest()->getRawBody(), $data);

                    $table = new Default_Model_DbTable_UserPreference();

                    $row = $table->fetchRow($table->select()->where('id = ?', $userId));

                    if ('switch' == $data['action']) {
                        if ($row->allow_newsletters) {
                            $row->allow_newsletters = 0;
                        } else {
                            $row->allow_newsletters = 1;
                        }
                    }

                    $row->save();

                    return $this->_helper->json(['preferences' => $row->toArray()]);
                }
            }
        }
    }
}
