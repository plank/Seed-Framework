<?php

/**
 * Allow an array unshift with a reference
 */
function array_unshift_byref(& $stack, & $var) {
	return array_unshift($stack, $var);
	
}

?>