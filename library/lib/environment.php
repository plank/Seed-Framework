<?php


class Environment {

	/**
	 * Array of server vars, should be set to $_SERVER
	 *
	 * @var array
	 */
	var $server_vars;
	
	/**
	 * The registered environments
	 *
	 * @var array
	 */
	var $environments;
	
	/**
	 * The default key to use in server vars
	 *
	 * @var string
	 */
	var $default_key = 'SCRIPT_FILENAME';
	
	/**
	 * The directory containing the environment files
	 *
	 * @var string
	 */
	var $environment_dir;

	/**
	 * Constructor
	 *
	 * @param array $server_vars	   Normally $_SERVER
	 * @param string $environment_dir
	 */
	function Environment(& $server_vars, $environment_dir = '') {
		$this->server_vars = & $server_vars;	
		$this->environment_dir = $environment_dir;
		
		$this->environments = array();
	}
	
	/**
	 * Register a given environment with a regex and key
	 *
	 * @param string $name   The name of the environment
	 * @param string $regex  The regex used on the key to detect the environment
	 * @param string $key	 The key of the server vars to use
	 * @return bool
	 */
	function register($name, $regex, $key = null) {
		if (!isset($key)) {
			$key = $this->default_key;	
		}
		
		$this->environments[$name] = array($regex, $key);	
		
		return true;
		
	}
	
	/**
	 * @return string
	 */
	function detect() {
		foreach($this->environments as $name => $data) {
			list($regex, $key) = $data;
			
			if (preg_match($regex, $this->server_vars[$key])) {
				return $name;
					
			}
			
		}	
		
		return false;
		
	}
	
	/**
	 * Imports an environment by loading the correct file
	 *
	 * @return bool
	 */
	function import() {
		$environment = $this->detect();
		
		if (!$environment) {
			trigger_error("No environment found", E_USER_WARNING);
			return false;	
			
		}
		
		$filename = $this->environment_dir.$environment.'.php';
		
		if (file_exists($filename)) {
			require_once($filename);
			return true;
		} else {
			trigger_error("File for environment '$environment' not found at '$filename'", E_USER_WARNING);
			return false;
				
		}

	}
	
}


?>