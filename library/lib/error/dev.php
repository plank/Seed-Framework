<?php



/**
 * Error handler for dev
 */
function error_handler($errno, $errstr, $errfile, $errline)
{
	if (error_reporting() == 0) {
		return;	
	}
	
	// ignore e_strict errors
	if ($errno == E_STRICT) {
		return;	
	}
	
	ob_end_clean_all();
	
	message(error_string($errno), $errstr, "occured in $errfile in line $errline\n");
	
	die();
}


?>