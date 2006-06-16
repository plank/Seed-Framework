<?php

/**
 * Base class for filters
 *
 */

class LiquidFilter {
	
	/**
	 * The name of the filter, which will be derived from the classname if it isn't given
	 *
	 * @var string
	 */
	
	var $name;

	/**
	 * The execution context
	 *
	 * @var LiquidContext
	 */
	
	var $context;
	
	/**
	 * Constructor
	 *
	 * @param LiquidContext $context
	 * @return LiquidFilter
	 */
	
	function LiquidFilter() {
		
		if (!$this->name) {
			$this->name = str_replace('liquidfilter', '', strtolower(get_class($this)));

		}
		
	}
	
	function filter($value, $args) {
		return $value;
		
	}

	
}

?>