<?php
/**
 * this.php, part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package library
 */

/**
 * Helper class for working with static method calls in PHP4
 *
 * @package library
 */
class this {
	
	/**
	 * Returns the name of the class that called 'this'
	 *
	 * @return string
	 */
	function class_name() {
		$trace = debug_backtrace();
		
		foreach($trace as $call) {
			if (isset($call['class']) && $call['class'] != __CLASS__) {
				return $call['class'];	
				
			}
		}
		
		return false;
		
	}
	
	/**
	 * Returns an array containing all the variables defined in the calling class
	 *
	 * @return array
	 */
	function get_vars() {
		return get_class_vars(this::class_name());
		
	}
	
	/**
	 * Returns the value of a particular variable in the calling class. Returns
	 * null if it doesn't exist
	 *
	 * @return mixed
	 */
	function get_var($var_name) {
		$vars = this::get_vars();
		
		if (isset($vars[$var_name])) {
			return $vars[$var_name];	
		} else {
			return null;
		}
	}

	/**
	 * Returns true if the variable exists in the calling class.
	 *
	 * @return bool
	 */
	function var_exists($var_name) {
		$vars = this::get_vars();
		
		return isset($vars[$var_name]);
		
	}
	
	/**
	 * Returns an array of all the methods defined in the calling class
	 *
	 * @return array
	 */	
	function get_methods() {
		return get_class_methods(this::class_name());	
		
	}
	
	/**
	 * Returns true if the method exists in the calling class.
	 *
	 * @param string $method_name
	 */
	function method_exists($method_name) {
		$methods = this::get_methods();
		
		return in_array($method_name, $methods);
	}
	
	/**
	 * Calls a static function in the calling class. All the parameters
	 * are passed to the function, as in call_user_func
	 *
	 * @param string $method_name
	 * @param mixed $args,...
	 * @return mixed
	 */
	function call($method_name) {
		$args = func_get_args();
		array_shift($args);
		
		return call_user_func_array(array(this::class_name(), $method_name), $args);
		
	}
	
	/**
	 * Calls a static function in the calling class. The args array
	 * is passed to the function, as in call_user_func_array
	 *
	 * @param string $method_name
	 * @param array $args
	 * @return mixed
	 */	
	function call_array($method_name, $args) {
		return call_user_func_array(array(this::class_name(), $method_name), $args);
		
	}
	
}


?>