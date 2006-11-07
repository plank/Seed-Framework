<?php

/**
 * Error handler for development
 */
function error_handler($errno, $errstr, $errfile, $errline)
{
	// ignore suppressed errors
	if (error_reporting() == 0) {
		return;	
	}
	
	// ignore e_strict errors
	if ($errno == E_STRICT) {
		return;	
	}
	
	// clean all buffers
	ob_end_clean_all();
	
	message(error_string($errno), $errstr, "occured in $errfile in line $errline\n");
	
	die();
}


?>