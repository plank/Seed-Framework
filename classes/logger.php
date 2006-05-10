<?php

/**
 * Logger.php part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @package logger
 */

/**
 * Constants
 */

define('LOG_LEVEL_DEBUG', 0);
define('LOG_LEVEL_NOTICE', 1);
define('LOG_LEVEL_WARNING', 2);
define('LOG_LEVEL_ERROR', 3);



/**
 * Logging class
 *
 */
class Logger {
	/**
	 * @var string
	 */
	var $key;	
	
	/**
	 * Returns a string representation of a given log level
	 *
	 * @param int $log_level
	 * @return string
	 */
	function log_level_string($log_level) {
		$log_levels = array('Debug', 'Notice', 'Warning', 'Error');
		
		if (isset($log_levels[$log_level])) {
			return $log_levels[$log_level];
		} else {
			return false;	
		}
		
	}
	
	/**
	 * Creates a logger subclass of a given type
	 *
	 * @param string $type
	 * @param array $params An array of config parameters for the logger
	 * @return Logger
	 */
	function factory($type, $key, $params) {
		$class_name = ucfirst($type).'Logger';	
		
		if (!class_exists($class_name)) {
			trigger_error("No class for $type", E_USER_ERROR);
			return false;	
			
		}
	
		$logger = new $class_name($key, $params);
		
		return $logger;
		
	}
	
	/**
	 * Registers a logger with a given key with the static class
	 *
	 * @param string $key The key to identify the logger (i.e. sql for a query log)
	 * @param string $type The type of log, which corresponds to a logger subclass
	 * @param array $params An array of config parameters for the logger
	 */
	function register($key, $type, $params = null) {
		$logger = Logger::factory($type, $key, $params);
		$logger->key = $key;
		
		Logger::_storage($key, $logger);
			
	}
	
	/**
	 * Logs a message to a given logger
	 *
	 * @param string $key The key indentifying the logger. If no logger by that name exists, the command is ignored
	 * @param int $level The severity level of the message
	 * @param string $message The message to log
	 * @return bool True if the message was succesfully logged
	 */
	function log($key, $level, $message) {
		$logger = Logger::_storage($key);
		
		if (!$logger) {
			return false;	
		}
		
		return $logger->log($level, $message);
		
	}
	
	/**
	 * Static storage for log objects. Returns the logger with a given key, passing a logger will overwrite that key.
	 * Setting $key to false resets the storage.
	 *
	 * @param string $key
	 * @param Logger $logger
	 * @return Logger
	 */
	function _storage($key, $logger = null) {
		static $storage;
		
		if (!isset($storage) || $key === false) {
			$storage = array();	
		}
		
		if (isset($logger)) {
			$storage[$key] = $logger;	
		}
		
		if (isset($storage[$key])) {
			return $storage[$key];	
		}
		
		return false;
	}
}

class FileLogger extends Logger {
	var $filename;
	var $date_format = 'r';
	
	/**
	 * Constructor
	 *
	 * @param array $data
	 * @return FileLogger
	 */
	function FileLogger($key, $data) {
		$this->key = $key;
		
		if (isset($data['filename'])) {
			$this->filename = $data['filename'];
			
		} else {
			$this->filename = LOG_PATH.$key.'.log';
			
		}
		
		// $this->log(LOG_LEVEL_DEBUG, 'Logger '.$this->key.' started');
	}

	/**
	 * Logs a message to a file
	 *
	 * @param int $log_level
	 * @param string $message
	 * @return bool
	 */
	function log($log_level, $message) {
		$fp = fopen($this->filename, 'a');
		chmod($this->filename, 0777);
		fwrite($fp, date($this->date_format)."\t".$this->key."\t".$this->log_level_string($log_level)."\t".$message."\n");
		fclose($fp);
		
		return true;
			
	}
	
	
}


?>