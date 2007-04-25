<?php

/**
 * Returns the name of the class that called the function
 *
 * @return string
 * @deprecated Since this is a hack that doesn't work as intended in PHP5, it should not be used and will be removed
 */
function class_name() {
	$trace = debug_backtrace();

	if (isset($trace[1]['class'])) {
		return $trace[1]['class'];
	} else {
		return false;
	}
}

/**
 * Add a clone function to PHP versions earlier than 4
 *
 */
function clone($object) {
	return $object;
}

/**
 * Allow an array unshift with a reference
 */
function array_unshift_byref(& $stack, & $var) {
	$return = array_unshift($stack,'');
	$stack[0] =& $var;
	return $return;	
	
}

/**
 * Creates an array by using one array for keys and another for its values
 *
 * @param array $keys
 * @param array $values
 * @return array
 */
function array_combine($keys, $values) {
	if (count($keys) != count($values) || !count($keys)) {
		return false;
	}
	
	$keys = array_values($keys);
	$values = array_values($values);
		
	for($x = 0; $x < count($keys); $x++) {
		$result[$keys[$x]] = $values[$x];	
		
	}	
	
	return $result;
	
}

?>