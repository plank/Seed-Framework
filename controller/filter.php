<?php
/**
 * filter.php, part of the seed framework
 *
 * Contains the abstract class for implementing filters
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package controller
 * @subpackage filter 
 */


/**
 * Base class for implenting simple filters
 *
 * @abstract 
 * @package controller
 * @subpackage filter  
 */
class SimpleFilter {
	
	/**
	 * Method to excute during the chain.
	 *
	 * @return bool
	 */
	function filter(&$controller) {
		
		
	}	
}

/**
 * Base class for implementing around filters
 *
 * @abstract 
 * @package controller
 * @subpackage filter 
 */
class AroundFilter {
	
	/**
	 * Method to execute during the before chain
	 *
	 * @return bool
	 */
	function before(&$controller) {
		trigger_error('AroundFilter::before is not implemented', E_USER_WARNING);
		return false;		
	}
	
	/**
	 * Method to execute during the after chain
	 *
	 * @return bool
	 */
	function after(&$controller) {
		trigger_error('AroundFilter::after is not implemented', E_USER_WARNING);				
		return false;
	}
}

?>