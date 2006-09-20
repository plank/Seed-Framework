<?php

/**
 * Bootstrap code for the framework
 * 
 * Includes require libraries 
 * 
 * @package seed
 */

// We prefer E_ALL error reporting

error_reporting(E_ALL);

// CONSTANTS //

/**
 * Major php version
 */
define('SEED_PHP_VERSION', substr(phpversion(), 0, 1));

/**
 * Path to the framework
 */
define('FRAMEWORK_PATH', dirname(__FILE__).'/');

/**
 * Path to the called script
 */
define('SCRIPT_PATH', $_SERVER['SCRIPT_FILENAME']);	

/**
 * The current request URI
 */
define('REQUEST_URI', $_SERVER['REQUEST_URI']);

// INCLUDES //

/**
 * Base files
 */
seed_include('library/base');

/**
 * Custom error handler
 */
seed_include('library/error');

// FUNCTIONS //

/**
 * Includes a seed class or packages
 *
 * @param string $path  The class (e.g. "library/error") or package (e.g. "library") to include
 * @return bool
 */
function seed_include($path) {
	$path = strtolower($path);	
	$path_parts = explode('/', $path);
	
	if (!count($path_parts) || count($path_parts) > 2) {
		trigger_error("Inavlid path", E_USER_WARNING);
		return false;
		
	}
	
	$path = dirname(__FILE__).'/'.$path_parts[0].'/';
	
	if (isset($path_parts[1])) {
		$path .= '/lib/'.$path_parts[1].'.php';
	} else {
		$path .= $path_parts[0].'.php';	
	}
	
	if (!file_exists($path)) {
		trigger_error("Couldn't find required class in '$path'", E_USER_WARNING);
		return false;
	}
	
	require_once($path);
	
	return true;
	
}

function seed_vendor_include($path) {
	$path = strtolower($path);
	
	$path = dirname(__FILE__).'/vendor/'.$path.'.php';	
	
	if (!file_exists($path)) {
		trigger_error("Couldn't find required class in '$path'", E_USER_WARNING);
		return false;		
	}
	
	require_once($path);
	
	return true;
}

/**
 * Includes all the files in a given dir
 *
 * @param string $path
 * @return bool
 */
function seed_require_dir($path) {
	if (!file_exists($path) || !is_dir($path)) {
		trigger_error("'$path' is not a directory", E_USER_WARNING);
		return false;
	}
	
	$dir = dir($path);
	
	while(($file = $dir->read()) !== false ) {
		// ignore directories, hidden files, and files whose extension is not php
		if (substr($file, 0, 1) == '.' || substr($file, -4) != '.php') {
			continue;
		}
		
		// make sure the file exists
		if(is_file($path.$file)) {
			require_once($path.$file);
		}
	}
	
	return true;
}



?>