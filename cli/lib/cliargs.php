<?php

class CLIArgs {
	
	/**
	 * @var array
	 */
	var $options;
	
	/**
	 * @var array
	 */
	var $params;

	/**
	 * @var array
	 */
	var $short_options;
	
	/**
	 * @var array
	 */
	var $long_options;
	
	/**
	 * @var bool
	 */
	var $valid;
	
	/**
	 * Constructor
	 *
	 * @param array $args
	 * @param string $short_options
	 * @param array $long_options
	 * @return CLIArgs
	 */
	function CLIArgs($args = null, $short_options = null, $long_options = null) {
		if (is_null($args)) {
			$this->params = $this->get_args();	
		} elseif (is_string($args)) {
			$this->params = preg_split('/[[:space:]]+/', $args);
		} elseif (is_array($args)) {
			$this->params = $args;	
		}

		if (is_string($short_options)) {
			$this->short_options = $this->parse_short_options($short_options);	
		} elseif (is_array($short_options)) {
			$this->short_options = $short_options;	
		}
		
		$this->options = array();
		
		$this->valid = $this->extract_args();
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
	
	/**
	 * Extracts options from params according to the given rules
	 *
	 * @return bool
	 */
	function extract_args() {

		while(!is_null($arg = array_shift($this->params))) {

			// explicit end of options
			if (is_null($arg) || $arg == '--') {
				return true;	
			}
			
			// end of options
			if (substr($arg, 0, 1) != '-' && substr($arg, 0, 2) != '--') {
				array_unshift($this->params, $arg);	
				return true;
			}
			
			// long option
			if (substr($arg, 0, 2) == '--') {
				$result = $this->extract_long_option(substr($arg, 2));
				
			} else {
				$result = $this->extract_short_option(substr($arg, 1));	

			}
			
			// was there an error?
			if (!$result) {
				return false;	
			}
			
		}
		
		return true;
		
	}
	
	/**
	 * @param string $string
	 * @return array
	 */
	function parse_short_options($string) {
		$result = array();
		
		preg_match_all('/([A-Za-z0-9])(:{0,2})/', $string, $matches);
		
		for($i = 0; $i < count($matches[1]); $i ++) {
			$result[$matches[1][$i]] = strlen($matches[2][$i]);	
		}
		
		return $result;
		
	}
	
	/**
	 * @param array $string
	 * @return array
	 */	
	function parse_long_options($array) {
		$result = array();
		
		foreach($array as $element) {
			preg_match('/([A-Za-z0-9]*)(={0,2})/', $element, $matches);
			$result[$matches[1]] = strlen($matches[2]);
		}
		
		return $result;
	}
	
	/**
	 * @param string $arg
	 * @return bool
	 */
	function extract_short_option($arg) {
		for($i = 0; $i < strlen($arg); $i ++) {
			$option = $arg{$i};
			
			// found key isn't in the options
			if (!key_exists($option, $this->short_options)) {
				return false;
			}
			
			$specifier = $this->short_options[$option];
			
			if ($specifier) {
				if ($i < strlen($arg) - 1) {
					// if there are characters left in the argument grab those, if not grab the next param
					$this->options[$option] = substr($arg, $i + 1);	
				
				} elseif (count($this->params) && $specifier == 1) {
					// if there's a next option grab that, but only if the value is required 
					// need to confirm that this is this correct behavior
					$this->options[$option] = array_shift($this->params);	
					
				} elseif ($specifier == 1) {
					// if the specificer is not optional, we've got an error
					return false;	
					
				}
				
				return true;
				
			} else {
				$this->options[$option] = true;	
			}
		}
		
		return true;
	}

	/**
	 * @param string $arg
	 * @return bool
	 */
	function extract_long_option($arg) {
		
	}
	
}


?>