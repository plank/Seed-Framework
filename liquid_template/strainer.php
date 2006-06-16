<?php

/**
 * Base class for filters
 *
 */

class Strainer {
	
	/**
	 * Context object
	 *
	 * @var LiquidContext
	 */
	
	var $context;
	
	/**
	 * Constructor
	 *
	 * @param LiquidContext $context
	 * @return Strainer
	 */
	
	function Strainer($context) {
		$this->context = $context;
	}
	
	
}

?>