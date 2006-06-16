<?php


class LiquidVariable {
	
	/**
	 * The filters to execute on the variable
	 *
	 * @var array
	 */
	var $filters;
	
	/**
	 * The name of the variable
	 *
	 * @var string
	 */
	var $name;
	
	/**
	 * The markup of the variable
	 *
	 * @var string
	 */
	var $markup;
	
	/**
	 * Constructor
	 *
	 * @param string $markup
	 * @return LiquidVariable
	 */
	function LiquidVariable($markup) {
		$this->markup = $markup;
		
		preg_match('/\s*('.QUOTED_FRAGMENT.')/', $markup, $matches);
		$this->name = $matches[1];
		
		if (preg_match('/'.FILTER_SEPERATOR.'\s*(.*)/', $markup, $matches)) {
			
			
			$filters = preg_split('/'.FILTER_SEPERATOR.'/', $matches[1]);
			
			foreach($filters as $filter) {
				preg_match('/\s*(\w+)/', $filter, $matches);
				$filtername = $matches[1];
				
				preg_match_all('/(?:'.FILTER_ARGUMENT_SEPERATOR.'|'.ARGUMENT_SPERATOR.')\s*('.QUOTED_FRAGMENT.')/', $filter, $matches);
				$matches = array_flatten($matches[1]);
				
				$this->filters[] = array($filtername, $matches);
				
			}
			
		} else {
			$this->filters = array();
			
		}
		
	}
	
	/**
	 * Renders the variable with the data in the context
	 *
	 * @param LiquidContext $context
	 */
	
	function render($context) {
		$output = $context->get($this->name);
		//debug('name', $this->name, 'output', $output);
		foreach ($this->filters as $filter) {
			list($filtername, $filter_arg_keys) = $filter;
			
			$filter_arg_values = array();
			
			foreach($filter_arg_keys as $arg_key) {
				$filter_arg_values[] = $context->get($arg_key);
				
			}
			
			$output = $context->invoke($filtername, $output, $filter_arg_values);
			
		}
		
		return $output;
		
	}
	
	
}



?>