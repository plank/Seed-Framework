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
 * The file class is needed for handling requests
 */
require_once('classes/file.php');


/**
 * shortcut to app startup
 */
function start() {
	$seed = new Seed();
	$seed->start();	
	
}


/**
 * Application class
 */
class Seed {

	/**
	 * All the files in each of these subfolders will be included
	 */
	var $subfolders = array('classes', 'libs', 'view', 'model', 'controller');
	
	var $handle_static_files = false;
	
	function display_static_files($url) {
		if (!$this->handle_static_files || !$url) {
			return true;	
		}
		
		$file = new File(PUBLIC_ROOT_PATH.$url);
		
		if (!$file->exists()) {
			return true;	
		}
		
		$mimetype = $file->get_mime_type();
		header("HTTP/1.0 200 OK", true, 200);
		header("Content-type: $mimetype");

		$file->output_contents();
			
		die();
		
	}
	
	/**
	 * Include the general config files
	 */
	function include_config() {
		// Require general config file
		require_once(CONFIG_PATH.'config.php');

	}
	
	/**
	 * Include the environment config files
	 */
	function include_environment_config() {
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
	}
	
	function register_objects() {
		if (!defined('DB_TYPE')) {
			define('DB_TYPE', 'mysql');
		}
		
		// register db
		$db = DB::register('default', DB_TYPE, DB_HOST, DB_USER, DB_PASS, DB_NAME);		
		
		// register routes
		require_once(CONFIG_PATH.'routes.php');
	}
	
	/**
	 * Check the requested url to make sure it's not on the ignored extensions list
	 *
	 * @param string $url The requested url
	 */
	function ignore_extensions($url) {
		// Ignore extensions, if configured
		if (defined('IGNORE_EXTENSIONS') && IGNORE_EXTENSIONS) {
			$ignore = str_replace(',', '|', IGNORE_EXTENSIONS);
	
			if (preg_match('/\.('.$ignore.')$/i', $url)) {	
				Logger::log('dispatch', LOG_LEVEL_DEBUG, 'ignoring '. $url);
				header("HTTP/1.0 404 Not Found", true, 404);
				die('page not found');
			}
		}		
		
	}
	
	function include_libraries() {
		
		/**
		 * Include all the files in each subfolder
		 */
		foreach($this->subfolders as $subfolder) {
			$path = FRAMEWORK_PATH.$subfolder.'/';
		
			// include all classes
			$this->require_dir($path);
		}		
		
	}
	
	function include_application_files() {

		// Require all the files in the app's vendor path
		$this->require_dir(VENDOR_PATH);
		
		// Require the global application controller, if it exists
		if (file_exists(CONTROLLER_PATH.'application.php')) {
			require_once(CONTROLLER_PATH.'application.php');
		}	
				
		
	}
	
	/**
	 * Call this function to start processing
	 */
	function start() {
		$url = isset($_GET['url']) ? $_GET['url']: '';
		
		// if the $url points to a static file in the public folder, display that
		$this->display_static_files($url);
		
		// include the app's general config file
		$this->include_config();
	
		// include the app's environment config file
		$this->include_environment_config();
		
		// ignore requests with extensions defined in IGNORE_EXTENSIONS
		$this->ignore_extensions($url);
		
		// include all the framework libraries		
		$this->include_libraries();

		// include application files
		$this->include_application_files();
		
		// register all objects
		$this->register_objects();
		
		// log the request
		Logger::log('dispatch', LOG_LEVEL_DEBUG, 'dispatching '. $url);
		
		// dispatch the request
		Dispatcher::dispatch();
		
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
	
}

/**
 * These are default values that can be overridden by each application
 */
$default_values = array();

/**
 * Define the defaults
 */
foreach($default_values as $name => $value) {
	if (!defined($name)) {
		define($name, $value);
	}
}


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
if (!defined('APP_URL')) {
	if (dirname(dirname($_SERVER['PHP_SELF'])) == '/') {
		define('APP_URL', '/');	
	} else {
		define('APP_URL', dirname(dirname($_SERVER['PHP_SELF'])).'/');
	}
}

if (APP_URL == '/') {
	define('APP_ROOT', APP_HOST.'/');		

} else {
	define('APP_ROOT', APP_HOST.dirname(APP_URL.'#').'/');
	
}

define('PUBLIC_ROOT', APP_ROOT.'public/');
define('REQUEST_URI', $_SERVER['REQUEST_URI']);







if (defined('SESSION_LIFETIME')) {
	ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
}

// debug($_SERVER);




?>
