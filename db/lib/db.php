<?php
/**
 * part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package model
 */

seed_include('library/iterator');
seed_include('library/logger');

/**
 * The date format used for database queries
 */
define('SQL_DATE_TIME_FORMAT', 'Y-m-d H:i:s');
define('SQL_DATE_FORMAT', 'Y-m-d H:i:s'); // deprecated, but we need to keep for now
define('SQL_TIME_FORMAT', 'H:i:s');

/**
 * Serves as a factory for creating db objects as well as a registry of created db objects
 */
class DB {
	
	var $last_query;
	
	/**
	 * Singleton function
	 *
	 * @param string $key The key of the db object to get
	 * @return DB
	 */
	function & get_db($key = 'default') {
		return DB::_db_storage($key);
	}	
	
	/**
	 * Creates a new db subclass of the desired type, using the passed connection settings
	 *
	 * @param string $type
	 * @param string $host
	 * @param string $user
	 * @param string $pass
	 * @param string $database
	 * @return DB
	 */
	function & factory($type, $host = DB_HOST, $user = DB_USER, $pass = DB_PASS, $database = DB_NAME) {
		$class_name = ucfirst($type).'DB';

		if (!class_exists($class_name)) {
			$file_name = dirname(__FILE__).'/adapters/'.$type.'.php';
			
			if (!file_exists($file_name)) {
				trigger_error("No adapter file for DB type $type", E_USER_ERROR);				
			}
			
			require_once($file_name);
			
		}
		
		if (!class_exists($class_name)) {
			trigger_error("No driver for DB type $type", E_USER_ERROR);
			$db = false;	
			
		} else {
			$db = new $class_name($host, $user, $pass, $database);
			
		}
		
		return $db;
	}
	
	/**
	 * Registers a new db with the give connection parameters
	 *
	 * @param string $key
	 * @param string $type
	 * @param string $host
	 * @param string $user
	 * @param string $pass
	 * @param string $database
	 * @return DB
	 */
	function register($key = 'default', $type = 'mysql', $host = DB_HOST, $user = DB_USER, $pass = DB_PASS, $database = DB_NAME) {
		$db = & DB::factory($type, $host, $user, $pass, $database);
		
		return DB::_db_storage($key, $db);
		
	}
	
	/**
	 * Static storage for the database objects
	 *
	 * @param string $key
	 * @param DB $db
	 * @return DB
	 */
	function & _db_storage($key = 'default', $db = null) {
		static $db_storage;
		
		if (!isset($db_storage) || $key === false) {
			$db_storage = array();	
		}
		
		if (isset($db)) {
			$db_storage[$key] = $db;
		}
		
		return $db_storage[$key];
		
	}
	
	/**
	 * Creates and executes an insert query
	 */
	function insert_query($table_name, $data = null, $unescaped_data = null) {
		$fields = array();
		$values = array();
		
		if (isset($data)) {
			foreach($data as $field => $value) {
				$fields[$field] = "`".$field."`";
				$values[$field] = "'".$this->escape($value)."'";
			}
		}
		
		if (isset($unescaped_data)) {
			foreach($unescaped_data as $field => $value) {
				$fields[$field] = "`".$field."`";
				$values[$field] = $value;
			}
		}		

		if (!count($values)) {
			trigger_error("No data in update_query", E_USER_WARNING);
			return false;
		}
				
		$sql = "INSERT INTO ".$this->escape_identifier($table_name)." (".implode(', ', $fields).") VALUES (".implode(', ', $values).")";
		
		return $this->query($sql);
		
	}
	
	/**
	 * Creates and executes an update query
	 */
	function update_query($table_name, $condition, $data = null, $unescaped_data = null) {
		$values = array();
		
		if (isset($data)) {
			foreach($data as $field => $value) {
				$values[$field] = "`".$field."` = '".$this->escape($value)."'";
			}
		}
		
		if (isset($unescaped_data)) {
			foreach($unescaped_data as $field => $value) {
				$values[$field] = "`".$field."` = ".$value;
			}
		}
		
		if (!count($values)) {
			trigger_error("No data in update_query", E_USER_WARNING);
			return false;
		}
		
		$sql = "UPDATE ".$this->escape_identifier($table_name)." SET ".implode(', ',$values)." WHERE ".$condition;
		
		return $this->query($sql);
		
	}	
	
	function escape($string) {
		return "'".$string."'";
	}
	
	function escape_identifier($string) {
		return '"'.$string.'"';
	}
	
}

