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

	// send email to admin?
	if (defined('ADMIN_EMAIL') && _send_error_email($errno.$errstr)) {
		//print 'sending email';
		$error_message = ucfirst(error_string($errno))."\n".$errstr."\n$errfile in line $errline\n\n";
		$error_message .= "-- backtrace --\n\n".backtrace(2)."\n";
		$error_message .= "-- get --\n\n".print_r($_GET, true)."\n\n";
		$error_message .= "-- post --\n\n".print_r($_POST, true)."\n\n";
		$error_message .= "-- files --\n\n".print_r($_FILES, true)."\n\n";
		$error_message .= "-- cookies --\n\n".print_r($_COOKIE, true)."\n\n";
		$error_message .= "-- server --\n\n".print_r($_SERVER, true)."\n\n";
		//$error_message .= "-- context --\n\n".print_r($errcontext, true);
		//print($error_message);
		mail(ADMIN_EMAIL, 'PHP '.ucfirst(error_string($errno)).': '.$errstr, $error_message);

	} else {
		//print 'dont send email';
	}
	
	die();
}

/**
 * Used to determine whether or not to send an error email
 * To solve problem of N emails all exactly the same when some script kiddie hits one of our sites
 * Basic idea is:
 * Make hash of error info,
 * Write a file with that name, and put timestamp for when the error suppression should be good till.
 * 
 * If older, then send the mail, and write new timestamp.
 *
 * Needs to be php4 + 5 compat. Idea is it can be dropped into any old sites based on seed.
 * 
 * @doc Make sure you have the LOG PATH setup (usually /cms/logs)
 * 
 * @param $err Used to make a hash filename. Combo between error no + error string 
 * @return bool
 **/
function _send_error_email($err) {
		
	$filename = md5($err);
	$filepath = LOG_PATH.$filename;
	
	if(file_exists($filepath)){

		$error_expires = file_get_contents($filepath);

		if(mktime() > $error_expires) {
			_create_error_tracker($filepath);
		} else {
			return false;
		}
	} else {
		_create_error_tracker($filepath);
	}
	
	//Default to true - in case of mistake, would rather err on side of send me error message	
	return true;

}

/**
 * Creates the little error tracker file
 *
 * @return void
 **/
function _create_error_tracker($filepath) {

	//In minutes
	if(!defined('ERROR_EXPIRY')) {
		define('ERROR_EXPIRY', 5);
	}

	//@change to file_put_contents?
	$handle = fopen($filepath, 'w');
	//Minutes till expires
	$error_expires = mktime() + (ERROR_EXPIRY * 60);
	fwrite($handle, $error_expires);
	fclose($handle);


}


?>