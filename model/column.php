<?php
/**
 * part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package model
 */

/**
 * Integers
 */
define('INTEGER', 'integer');

/**
 * Floating point numbers
 */
define('FLOAT', 'float');

/**
 * Datetime
 */
define('DATETIME', 'datetime');

/**
 * Timestamp
 */
define('TIMESTAMP', 'timestamp');

/**
 * Time
 */
define('TIME', 'time');

/**
 * Date
 */
define('DATE', 'date');

/**
 * Text
 */
define('TEXT', 'text');

/**
 * Binary
 */
define('BINARY', 'binary');

/**
 * String
 */
define('STRING', 'string');

/**
 * Boolean
 */
define('BOOLEAN', 'boolean');


/**
 * Class for representing database columns
 *
 * @package model
 * @subpackage db
 */

class Column {
	var $name;
	var $default;
	var $type;
	var $null;

	function Column($name, $default, $sql_type = null, $null = true) {
		$this->name = $name;	
		$this->default = $default;
		$this->type = $this->simplified_type($sql_type);
		$this->null = $null;	
		
	}
	
	function simplified_type($sql_type) {
		$sql_type = strtolower($sql_type);
		
		$types = array(
			'int' => INTEGER,
			'float' => FLOAT,
			'double' => FLOAT,
			'decimal' => FLOAT,
			'numeric' => FLOAT,
			'datetime' => DATETIME,
			'timestamp' => TIMESTAMP,
			'time' => TIME,
			'date' => DATE,
			'clob' => TEXT,
			'text' => TEXT,
			'blob' => BINARY,
			'binary' => BINARY,
			'varchar' => STRING,
			'char' => STRING,
			'string' => STRING,
			'boolean' => BOOLEAN
		);
		
		
		if (key_exists($sql_type, $types)) {
			return $types[$sql_type];
		} else {
			trigger_error("No type for '$sql_type' in Column::simplified_type()", E_USER_WARNING);
			return false;	
		}
	}
}


?>