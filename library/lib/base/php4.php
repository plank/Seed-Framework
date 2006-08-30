<?php

/**
 * Returns the name of the class that called the function
 *
 * @return string
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
	return array_unshift($stack, & $var);
	
}



?>