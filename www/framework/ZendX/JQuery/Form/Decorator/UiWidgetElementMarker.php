<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category    ZendX
 * @package     ZendX_JQuery
 * @subpackage  View
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license     http://framework.zend.com/license/new-bsd     New BSD License
 * @version     $Id$
 */

/**
 * Marking UiWidgetElement rendering decorator.
 *
 * Marker Interface to make sure that programmer using ZendX_JQuery is not
 * switching ZendX_JQuery_Form_Decorator_UiWidgetElement with Zend_Form_Decorator_ViewHelper
 * without noticing that this is not possible.
 */
interface ZendX_JQuery_Form_Decorator_UiWidgetElementMarker {
}
