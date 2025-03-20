<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Setup base view variables
 */

class MailCleaner_View_Helper_InputRow extends Zend_View_Helper_Abstract
{

    public $view;

    public function setView(Zend_View_Interface $view)
    {
        $this->view = $view;
    }

    /*
     * possible params:
     *
     * field_only: boolean
     * label_only: boolean
     * norow: boolean
     * nobox : boolean
     * row_id : string
     * row_class: string
     * label_classes: string
     * label_addclass: string
     * field_classes: string
     * field_addclass: string
     * post_field_text: string
     * pre_field_text: string
     * error_higlight: boolean
     * error_display: boolean
     * error_addclass: string
     */

    public function InputRow($element, $params = [])
    {
        $t = Zend_Registry::get('translate');

        if (!isset($params['error_higlight'])) {
            $params['error_higlight'] = true;
        }
        if (!isset($params['error_display'])) {
            $params['error_display'] = true;
        }

        $string = '';

        // tr
        if ((!isset($params['field_only']) || !$params['field_only']) &&
            (!isset($params['label_only']) || !$params['label_only']) &&
            (!isset($params['nobox']) || !$params['nobox']) &&
            (!isset($params['norow']) || !$params['norow'])
        ) {


            $string .= '<tr';
            if (isset($params['row_id'])) {
                $string .= ' id="' . $params['row_id'] . '"';
            }
            if (isset($params['row_class'])) {
                $string .= ' class="' . $params['row_class'] . '"';
            }
            $string .= ">\n";
        }

        // label
        if (!isset($params['field_only']) || !$params['field_only']) {
            if (!isset($params['nobox']) || !$params['nobox']) {
                $string .= '<td';
                if (isset($params['label_classes'])) {
                    $string .= ' class="' . $params['label_classes'] . '"';
                } else {
                    $string .= ' class="flabel';
                    if (isset($params['label_addclass'])) {
                        $string .= ' ' . $params['label_addclass'];
                    }
                    $string .= '"';
                }

                $string .= '>';
            }
            $string .= $element->getLabel();
            if (!isset($params['nobox']) || !$params['nobox']) {
                $string .= "</td>\n";
            }
        }

        // field
        if (!isset($params['label_only']) || !$params['label_only']) {
            if (!isset($params['nobox']) || !$params['nobox']) {
                $string .= '<td';
                if (isset($params['field_classes'])) {
                    $string .= ' class="' . $params['field_classes'];
                } else {
                    $string .= ' class="fvalue';
                    if (isset($params['field_addclass'])) {
                        $string .= ' ' . $params['field_addclass'];
                    }
                }
                // error highlight
                if ($params['error_higlight'] && $element->getMessages()) {
                    $string .= ' ferror';
                }
                $string .= '">';
            } else {
                // error highlight
                if ($params['error_higlight'] && $element->getMessages()) {
                    $string .= '<span class="ferror">';
                }
            }
            if (isset($params['pre_field_text'])) {
                $string .= $params['pre_field_text'];
            }
            $string .= $element->renderViewHelper();

            if (!isset($params['nobox']) || !$params['nobox']) {
            } else {
                if ($params['error_higlight'] && $element->getMessages()) {
                    $string .= '</span>';
                }
            }
            // error text display
            if ($params['error_display'] && $element->getMessages()) {
                $string .= '<img class="ferrorimg" src="' . $this->view->images_path . '/warning_mini.png"';
                $msg = array_pop($element->getMessages());
                $msg = preg_replace('/\'/', '"', $msg);
                $string .= ' onmouseover="javascript:showFieldError(event, \'' . htmlentities($msg) . '\'); return true;" onmouseout="javascript:hideFieldError(); return true;"';
                $string .= '/>';
            }
            if (isset($params['post_field_text'])) {
                $string .= " " . $params['post_field_text'];
            }

            if (!isset($params['nobox']) || !$params['nobox']) {
                $string .= "\n</td>\n";
            }
        }

        // /tr
        if ((!isset($params['field_only']) || !$params['field_only']) &&
            (!isset($params['label_only']) || !$params['label_only']) &&
            (!isset($params['nobox']) || !$params['nobox']) &&
            (!isset($params['norow']) || !$params['norow'])
        ) {
            $string .= "</tr>\n";
        }

        return $string;
    }
}
