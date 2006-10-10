<?php
/**
 * error.php, part of the seed framework
 *
 * Helper functions for error handling and debugging
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package library
 */

/**
 * Define the E_STRICT error level if it doesn't exist
 */
if (!defined('E_STRICT')) {
	define('E_STRICT', 2048);	
}

/**
 * A custom error handler
 */
function error_handler($errno, $errstr, $errfile, $errline)
{
	// ignore e_strict errors
	if ($errno == E_STRICT) {
		return;	
	}
	
	message(error_string($errno), $errstr, "occured in $errfile in line $errline\n");
	die();
}

set_error_handler('error_handler');
ini_set('display_errors', 1);
ini_set('html_errors', 0);

/**
 * Prints a debug message to screen
 */
function debug() {
	$args = func_get_args();
	
	array_unshift($args, 'debug');
	
	call_user_func_array('message', $args);
	
}

/**
 * Prints a debug message if the first argument evaluates to true
 */
function debug_if() {
	$args = func_get_args();
	
	if (!$args[0]) {
		return;
	}
	
	$args[0] = 'debug';
	
	call_user_func_array('message', $args);	
	
}

/**
 * Prints a formated message to the screen
 *
 */
function message($type = 'debug', $arg = '') {
	
	static $message_number = 0;
	
	$message_number ++;
	
	$args = func_get_args();
	array_shift($args);
	
	$colors = array(
		'debug' => array('green', 'white'),
		'notice' => array('yellow', 'black'),
		'warning' => array('orange', 'white'),
		'error' => array('red', 'white')
	);
	
	list($bar_color, $text_color) = ($colors[$type]);
	
	$return = "<div class='{$type}_box' style='border:1px solid #ccc'>\n";
	$return .= "<div style='font-family: monaco, courrier; padding: 4px; border-bottom: 1px solid #ccc; background-color:{$bar_color}; color:{$text_color}'>".ucfirst($type)."</div>\n";
	$return .= "<pre style='font-family: monaco, courrier; padding: 4px; background-color: white; color: black; overflow: auto'>\n";
	
	foreach ($args as $arg) {
		$return .= htmlentities(print_r($arg, true), ENT_QUOTES)."\n";	
		
	}
	
	// Display togglable backtrace
	$return .= "<a href='#' onclick='javascript: document.getElementById(\"message_number_$message_number\").style.display=\"block\"; return false;'>backtrace:</a>\n";
	$return .= "<div id='message_number_$message_number' style='display:none'>\n";
	$return .= backtrace(3);	
	$return .= "</div>";
	
	
	$return .= "</pre>";
	$return .= "</div>";
	
	print $return;	
	
}

function backtrace($level = 1) {
	$backtrace = debug_backtrace();
	
	for($x = 0; $x < $level; $x++) {
		array_shift($backtrace);
	}
	
	$return = '';
	
	foreach($backtrace as $call) {
		
		$arguments = array();
		
		if (isset($call['args'])) {
			foreach ($call['args'] as $argument) {
				$arguments[] = clean_argument($argument);	
			}
		}

		$return .= assign($call['class']).assign($call['type']).$call['function']."(".implode(', ', $arguments).")\n";
		
		if (isset($call['file'])) {
			$return .= 'called from '.$call['file'].' on line '.$call['line']."\n";	
		}
	
		$return .= "\n";
		
	}
	
	return $return;
	
}

function clean_argument($argument) {
	if ($argument === '') {
		return '';	
	}
	
	if (is_null($argument)) {
		return 'null';	
	}
	
	if (is_array($argument)) {
		return 'array';	
	} 
	
	if (is_object($argument)) {
		return get_class($argument).' object';
	}
	
	if (is_resource($argument)) {
		return 'resource';	
	}
	
	return '"'.$argument.'"';	
	
	die("unrecognized argument type '$argument'");
}


function file_upload_error_string($error_value, $file_name) {
	
	$error_strings = array(	
		UPLOAD_ERR_OK => "File uploaded with success.",
		UPLOAD_ERR_INI_SIZE => "The uploaded file '$file_name' exceeds the upload_max_filesize directive in php.ini.",
		UPLOAD_ERR_FORM_SIZE => "The uploaded file '$file_name' exceeds the MAX_FILE_SIZE directive specified in the HTML form.",
		UPLOAD_ERR_PARTIAL => "The uploaded file '$file_name' was only partially uploaded.",
		UPLOAD_ERR_NO_FILE => "No file was uploaded.",
		UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder.",
		UPLOAD_ERR_CANT_WRITE => "Failed to write the file '$file_name' to disk."
	);
	
	return $error_strings[$error_value];
	
}

function error_string($error_value) {
	$error_strings = array(
		E_ERROR => "error",
		E_WARNING => "warning",
		E_PARSE => "error",
		E_NOTICE => "notice",
		E_CORE_ERROR => "error",
		E_CORE_WARNING => "warning",
		E_COMPILE_ERROR => "error",
		E_COMPILE_WARNING => "warning",
		E_USER_ERROR => "error",
		E_USER_WARNING => "warning",
		E_USER_NOTICE => "notice",
		E_STRICT => "notice"
	);
	
	return $error_strings[$error_value];
}

?>