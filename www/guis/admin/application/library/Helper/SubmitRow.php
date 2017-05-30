<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Setup base view variables
 */

class MailCleaner_View_Helper_SubmitRow extends Zend_View_Helper_Abstract
{
	
    public $view;
 
    public function setView(Zend_View_Interface $view)
    {
        $this->view = $view;
    }
	
	/*
	 * possible params:
	 * 
	 * cols: int
	 * row_id : string
	 * row_class: string
	 * field_only: boolean
     * nobox : boolean
	 * field_classes: string
	 * field_addclass: string
	 */
	
	public function SubmitRow($element, $params = array())
	{
		$t = Zend_Registry::get('translate');
		
		$string = '';
		
		// tr
		if ( (!isset($params['field_only']) || !$params['field_only']) ) {    
    	    $string .= '<tr';
    	    if (isset($params['row_id'])) {
    	    	$string .= ' id="'.$params['row_id'].'"';
    	    }
		    if (isset($params['row_class'])) {
                $string .= ' class="'.$params['row_class']."'";
            }
	        $string .= ">\n";
		}
		
		
		// field
		if (!isset($params['nobox']) || !$params['nobox']) {
        	$string .= '<td';
	        if (isset($params['field_classes'])) {
                $string .= ' class="'.$params['field_classes'];
            } else {
                $string .= ' class="fvalue fsubmit';
                if (isset($params['field_addclass'])) {
                    $string .= ' '.$params['field_addclass'];
                }          
            }
            $string .= '"';
		    $string .= ' colspan="';
		    if (isset($params['cols']) && is_numeric($params['cols'])) {
			    $string .= $params['cols'];
		    } else {
			    $string .= '2';
		    }
            $string .= '">';
		}
        $string .= $element->renderViewHelper();

        if (!isset($params['nobox']) || !$params['nobox']) {
     	    $string .= "\n</td>\n";
        }
		
		// /tr
		if ( (!isset($params['field_only']) || !$params['field_only'])) {
	        $string .= "</tr>\n";
        } 
        
		return $string;
	}
}