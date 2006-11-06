<?php

/**
 * Error handler for dev
 */
function error_handler($errno, $errstr, $errfile, $errline, $errcontext)
{
	// ignore error if suppresed
	if (error_reporting() == 0) {
		return;	
	}
	
	// ignore e_strict errors
	if ($errno == E_STRICT) {
		return;	
	}

	// clean all buffers to avoid showing half rendered page
	ob_end_clean_all();	
	
	$error_file = PUBLIC_PATH.'500.php';
	
	header('HTTP/1.1 500 Internal Server Error');
	
	if (file_exists($error_file)) {
		require_once($error_file);
		
	} else {
		print '<h1>500: Internal Server Error</h1>\n';
		print '<p>The server encountered an unexpected condition which prevented it from fulfilling the request.</p>\n';
		print "<p>An email has been generated and sent to our technical staff</p>";	
	}
	
	// send email to admin
	if (defined('ADMIN_EMAIL')) {
		$error_message = ucfirst(error_string($errno))."\n".$errstr."\n$errfile in line $errline\n\n";
		$error_message .= "-- backtrace --\n\n".backtrace(2)."\n";
		$error_message .= "-- get --\n\n".print_r($_GET, true)."\n\n";
		$error_message .= "-- post --\n\n".print_r($_POST, true)."\n\n";
		$error_message .= "-- cookies --\n\n".print_r($_COOKIE, true)."\n\n";
		$error_message .= "-- server --\n\n".print_r($_SERVER, true)."\n\n";
		//$error_message .= "-- context --\n\n".print_r($errcontext, true);
		//print($error_message);
		mail(ADMIN_EMAIL, 'PHP Error', $error_message);
	}
	
	die();
}

?>