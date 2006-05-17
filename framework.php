<?php


/**
 * Bootstrap code for the framework
 * 
 * Including this file includes all the required subfiles, as well as defining the
 * general constants that are required by the framework
 * 
 * @package seed
 */

/**
 * Call this function to start processing
 */
function start() {
	// Require general config file
	require_once(CONFIG_PATH.'config.php');
	require_once(CONFIG_PATH.'routes.php');

	// Require envirnoment specific file, if it exists
	if (defined('ENVIRONMENT')) {
		if (file_exists(CONFIG_PATH.ENVIRONMENT.'.php')) {
			require_once(CONFIG_PATH.ENVIRONMENT.'.php');
		} else {
			trigger_error('No config file for environment '.ENVIRONMENT, E_USER_WARNING);
		}
	} else {
		trigger_error('No environment defined, please define ENVIRONMENT in the config file', E_USER_WARNING);
	}
	
	// Require all the files in the app's vendor path
	require_dir(VENDOR_PATH);
	
	// Require the global application controller, if it exists
	if (file_exists(CONTROLLER_PATH.'application.php')) {
		require_once(CONTROLLER_PATH.'application.php');
	}	
	
	$url = isset($_GET['url']) ? $_GET['url']: '';
	
	// Ignore extensions, if configured
	if (defined('IGNORE_EXTENSIONS') && IGNORE_EXTENSIONS) {
		$ignore = str_replace(',', '|', IGNORE_EXTENSIONS);

		if (preg_match('/\.('.$ignore.')$/i', $url)) {	
			Logger::log('dispatch', LOG_LEVEL_DEBUG, 'ignoring '. $url);
			header("HTTP/1.0 404 Not Found", true, 404);
			die('page not found');
		}
	}
	
	Logger::log('dispatch', LOG_LEVEL_DEBUG, 'dispatching '. $url);
	
	$db = DB::register('default', 'mysql', DB_HOST, DB_USER, DB_PASS, DB_NAME);
	
	Dispatcher::dispatch();
	
}


/**
 * These are default values that can be overridden by each application
 */
$default_values = array();

/**
 * All the files in each of these subfolders will be included
 */
$subfolders = array('classes', 'libs', 'view', 'model', 'controller');

/**
 * Path constants
 */
define('FRAMEWORK_PATH', dirname(__FILE__).'/');

define('APP_PATH', dirname(dirname($_SERVER['PATH_TRANSLATED'])).'/');	
define('CONTROLLER_PATH', APP_PATH.'app/controllers/');
define('TEMPLATE_PATH', APP_PATH.'app/views/');
define('MODEL_PATH', APP_PATH.'app/models/');
define('HELPER_PATH', APP_PATH.'app/helpers/');
define('CONFIG_PATH', APP_PATH.'config/');
define('PUBLIC_ROOT_PATH', APP_PATH.'public/');
define('VENDOR_PATH', APP_PATH.'vendor/');
define('FRAMEWORK_TEMPLATE_PATH', FRAMEWORK_PATH.'templates/');
define('LOG_PATH', APP_PATH.'logs/');

/**
 * Url constants
 */
define('APP_HOST', 'http://'.$_SERVER['HTTP_HOST']);

// allow custom settings for APP_URL, which would be set in index.php
if (defined('APP_URL')) {
	define('APP_ROOT', APP_HOST.dirname(APP_URL.'#').'/');
} elseif (dirname(dirname($_SERVER['PHP_SELF'])) == '/') {
	define('APP_URL', '/');	
} else {
	define('APP_URL', dirname(dirname($_SERVER['PHP_SELF'])).'/');
}

if (APP_URL == '/') {
	define('APP_ROOT', APP_HOST.'/');		
} else {
	define('APP_ROOT', APP_HOST.dirname(APP_URL.'#').'/');	
}

define('PUBLIC_ROOT', APP_ROOT.'public/');
define('REQUEST_URI', $_SERVER['REQUEST_URI']);

/**
 * Define the defaults
 */
foreach($default_values as $name => $value) {
	if (!defined($name)) {
		define($name, $value);
	}
}

/**
 * Include all the files in each subfolder
 */
foreach($subfolders as $subfolder) {
	$path = FRAMEWORK_PATH.$subfolder.'/';

	// include all classes
	require_dir($path);
}

/**
 * Includes all the files in a given dir
 *
 * @param string $path
 */

function require_dir($path) {
	$dir = dir($path);
	
	while(($file = $dir->read()) !== false ) {
		if (substr($file, 0, 1) == '.') {
			continue;
		}
		
		if(is_file($path.$file)) {
			require_once($path.$file);
		}
	}
	
}

if (defined('SESSION_LIFETIME')) {
	ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
}

// debug($_SERVER);

?>
