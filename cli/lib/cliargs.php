<?php

class CLIArgs {
	
	/**
	 * @var array
	 */
	var $options;
	
	/**
	 * @var array
	 */
	var $args;
	
	function CLIArgs() {
		list($this->args, $this->options) = $this->parse_args($this->get_args());
	}
	
	/**
	 * @return array
	 */
	function get_args() {
		$args = $_SERVER['argv'];
		
		if (!is_array($args)) {
			return array();
		}
		
		// pop the fiename off the beginning of the stack
		if ($args[0] == $_SERVER['PHP_SELF']) { 
			array_shift($args);	
		}
		
		return $args;
		
	}
	
	function parse_args($args, $short_options, $long_options = null) {
		if (!is_array($args)) {
			$args = split('[[:space:]]+', $args);			
		}
		
		foreach ($args as $index => $arg) {
			if (substr($arg, 0, 1) == '-') {
				$result = CLIArgs::parse_short_options($arg, $short_options);
			}
		}
		
		return $result;
		
	}
	
	function parse_short_options($arg, $options) {
		
	}
}


?>