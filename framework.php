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
require_once('library/lib/file.php');

/**
 * Include logger for dispatch logging
 */
require_once('library/lib/logger.php');

/**
 * shortcut to app startup
 */
function start() {
	$seed = new Seed();
	$seed->start();	
	
}


/**
 * Application class
 *
 * This class handles the requests, loading the libraries and config files only if needed. Call the start() method to start execution. 
 */
class Seed {

	/**
	 * All the files in each of these components will be included
	 *
	 * @var array
	 */
	var $components = array('library', 'support', 'view', 'db', 'model', 'controller');
	
	/**
	 * A comma delimited list of file extensions the framework shouldn't handle 
	 *
	 * @var string
	 */
	var $ignore_extensions = 'js,css,jpg,jpeg,gif,png,flw';
	
	/**
	 * Turn on check for static files in the "public" directory. This is required
	 * when full mod_rewrite support is missing.
	 *
	 * @var bool
	 */
	var $handle_static_files = false;
	
	/**
	 * These are the various file names an index file can take
	 *
	 * @var array
	 */
	var $index_file_names = array('index', 'default', 'welcome');
	
	/**
	 * These are the various file extensions an index file can take
	 *
	 * @var array
	 */
	var $index_file_extensions = array('php', 'html', 'htm');
	
	/**
	 * Call this function to start processing
	 *
	 * @return bool Returns true
	 */
	function start() {
		$url = isset($_GET['url']) ? $_GET['url']: '';
		
		// if the $url points to a static file in the public folder, display that
		if ($this->display_static_files($url)) {
			return true;	
		}

		// ignore requests with extensions that we've decided to ignore
		if ($this->ignore_extensions($url)) {
			return true;		
		}

		// include all the framework libraries		
		$this->include_libraries();
		
		// include vendor libraries
		$this->include_vendor_libraries();
		
		// include the app's general config file
		$this->include_config();
	
		// include the app's environment config file
		$this->include_environment_config();

		// include application files
		$this->include_application_files();
		
		// register all objects
		$this->register_objects();
		
		// log the request
		Logger::log('dispatch', LOG_LEVEL_DEBUG, 'dispatching '. $url);
		
		// dispatch the request
		Dispatcher::dispatch();
		
		return true;
		
	}	
	
	/**
	 * Checks for the existance of a static file in the "public" directory at the given url
	 * and outputs that to the browser if a file exists there.
	 *
	 * @param string $url The url of the file to handle
	 * @return bool Returns true if a file was output, false if not
	 */
	function display_static_files($url) {
		if (!$this->handle_static_files || !$url) {
			return false;	
		}
		
		$file = new File(PUBLIC_PATH.$url);
		
		if (!$file->exists()) {
			return false;	
		}
		
		if ($file->is_directory()) {
			$file = $this->get_directory_index($file);
			
		}
		
		if ($file) {
			Logger::log('dispatch', LOG_LEVEL_DEBUG, 'static '. $url);
			
			return $this->output_file($file);
			
		} else {
			return false;
			
		}
		
	}
	
	/**
	 * Checks a given directory for the existance of an index file
	 *
	 * @param File $file
	 * @return File Returns a file object representing the index file if there was one found, return false if not.
	 */
	function get_directory_index($file) {
		// try all permutations of file names and extensions.
		foreach ($this->index_file_names as $name) {
			foreach ($this->index_file_extensions as $extension) {
				$index_file = new File($file->path.'/'.$name.'.'.$extension);
				
				if ($index_file->exists()) {
					return $index_file;	
				}
			}	
		}			

		return false;
		
	}
	
	/**
	 * Output the file to the browser with proper headers
	 *
	 * @param File $file
	 * @return bool Returns true.
	 */
	function output_file($file) {
		// if the file is a php file, include it
		if ($file->get_extension() == 'php') {
			include($file->get_path());
				
		} else {
			$mimetype = $file->get_mime_type();
			header("HTTP/1.0 200 OK", true, 200);
			header("Content-type: $mimetype");
	
			$file->output_contents();
			
		}

		return true;
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
	
	/**
	 * Register objects
	 */
	function register_objects() {
		// for backwards compatibility
		if (!defined('DB_TYPE')) {
			define('DB_TYPE', 'mysql');
		}
		
		// register db
		$db = DB::register('default', DB_TYPE, DB_HOST, DB_USER, DB_PASS, DB_NAME);		

	}
	
	/**
	 * Check the requested url to make sure it's not on the ignored extensions list
	 *
	 * @param string $url The requested url
	 * @return bool Returns true if $url is ignores
	 */
	function ignore_extensions($url) {
		// Ignore extensions, if configured
		if (!$this->ignore_extensions) {
			return false;
				
		}
			
		$ignore = str_replace(',', '|', $this->ignore_extensions);
	
		if (preg_match('/\.('.$ignore.')$/i', $url)) {	
			Logger::log('dispatch', LOG_LEVEL_DEBUG, 'ignoring '. $url);
			header("HTTP/1.0 404 Not Found", true, 404);
			print ('page not found');
			return true;
			
		} else {
			return false;
			
		}
		
	}

	/**
	 * Include all the libraries
	 */
	function include_libraries() {
		foreach($this->components as $component) {
			$path = FRAMEWORK_PATH.$component.'/lib/';
		
			// include all classes
			$this->require_dir($path);
		}		
		
	}
	
	/**
	 * Include all the vendor libraries
	 */
	function include_vendor_libraries() {
		// include all classes
		$this->require_dir(FRAMEWORK_PATH.'vendor/');

	}	
	
	/**
	 * Include application specific files
	 */
	function include_application_files() {

		// Require all the files in the app's vendor path
		$this->require_dir(VENDOR_PATH);
		
		// Require the global application controller, if it exists
		if (file_exists(CONTROLLER_PATH.'application.php')) {
			require_once(CONTROLLER_PATH.'application.php');
		}	
				
		
	}


	/**
	 * Includes all the files in a given dir
	 *
	 * @param string $path
	 */
	function require_dir($path) {
		if (!file_exists($path) || !is_dir($path)) {
			trigger_error("'$path' is not a directory");
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
}

/**
 * These are default values that can be overridden by each application
 */
$default_values = array();

// Define the defaults
 
foreach($default_values as $name => $value) {
	if (!defined($name)) {
		define($name, $value);
	}
}


// Framework path constants

/**
 * Path to the framework
 */
define('FRAMEWORK_PATH', dirname(__FILE__).'/');

/**
 * Path to the root of the framework templates. This is where the scaffold template are located.
 */
define('FRAMEWORK_TEMPLATE_PATH', FRAMEWORK_PATH.'templates/');


// Application path constants

/**
 * Path to the root of the application
 */
define('APP_PATH', dirname(dirname($_SERVER['PATH_TRANSLATED'])).'/');	

/**
 * Path to the root of controllers
 */
define('CONTROLLER_PATH', APP_PATH.'app/controllers/');

/**
 * Path to the root of the templates
 */
define('TEMPLATE_PATH', APP_PATH.'app/views/');

/**
 * Path to the root of the models
 */
define('MODEL_PATH', APP_PATH.'app/models/');

/**
 * Path to the root of the helpers
 */
define('HELPER_PATH', APP_PATH.'app/helpers/');

/**
 * Path to the root of the config files
 */
define('CONFIG_PATH', APP_PATH.'config/');

/**
 * Path to the root of the public files
 */
define('PUBLIC_PATH', APP_PATH.'public/');

/**
 * Path to the root of the vendor files
 */
define('VENDOR_PATH', APP_PATH.'vendor/');

/**
 * Path to the root of the log
 */
define('LOG_PATH', APP_PATH.'logs/');

// Application URL constants

/**
 * Hostname with protocol
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

/**
 * The current request URI
 */
define('REQUEST_URI', $_SERVER['REQUEST_URI']);

if (defined('SESSION_LIFETIME')) {
	ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
}


?>
