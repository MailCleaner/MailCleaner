<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Help with pagination calculations
 */

class Tools_SearchPagination
{

    static public function paginateElements($elements, $nbelperpage, $currentpage, $view)
    {
       $view->elementscount = count($elements);
       $view->lastpage = ceil($view->elementscount / $nbelperpage);
       
    	
       $page = 1;
       if ($currentpage && is_numeric($currentpage) && $currentpage > 0) {
    		if ($currentpage > $view->lastpage) {
    			$page = $view->lastpage;
    		} else {
    		    $page = $currentpage;
    		}
    	}
    	$offset = $nbelperpage * ($page - 1);
    	$view->nbelperpage = $nbelperpage;
  	    $view->elements = array_slice($elements, $offset, $nbelperpage);
  	    $view->page = $page;
    }
}