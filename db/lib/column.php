<?php
/**
 * part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package db
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
 * @package db
 */

class Column {
	/**
	 * The name of the columns
	 *
	 * @var string
	 */
	var $name;

	/**
	 * The default value of the column
	 *
	 * @var string
	 */
	var $default;
	
	/**
	 * The type of the column
	 *
	 * @var int
	 */
	var $type;

	/**
	 * The limit to the number of characters for the column
	 *
	 * @var int
	 */
	var $limit;

	/**
	 *
	 * @var bool
	 */
	var $null;

	/**
	 * Constructor
	 *
	 * @param string $name
	 * @param string $default
	 * @param string $sql_type
	 * @param bool $null
	 * @return Column
	 */
	function Column($name, $default = null, $sql_type = null, $null = true) {
		$this->name = $name;	
		$this->default = $default;
		$this->type = $this->simplified_type($sql_type);
		$this->limit = $this->extract_limit($sql_type);
		$this->null = $null;	
		
	}
	
	/**
	 * Cast the value to an appropriate type
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	function type_cast($value) {
		if (is_null($value)) return null;
		
		switch ($this->type) {
			case DATE:	
				return date(SQL_DATE_TIME_FORMAT, strtotime($value));
			
			case INTEGER:
				return intval($value);
				
			case FLOAT:
				return floatval($value);
			
			case BOOLEAN:
				return boolval($value);
					
			default:
				return $value;
		}
	}
	
	/**
	 * Convert an array to the appropriate type
	 *
	 * This is mostly for the various date types, less useful for strings, and mostly useless for other types
	 *
	 * @param array $value
	 * @return mixed
	 */
	function array_to_type($value) {
		switch ($this->type) {
			case DATETIME:
				$value = array_values($value);
				return date(SQL_DATE_TIME_FORMAT, mktime($value[3], $value[4], $value[5], $value[1], $value[2], $value[0]));			
				
			case DATE:
				if (count($value) > 3) {
					$value = array_slice($value, 0, 3);	
				}
			
				return implode('-', $value);
			
			case TIME:
				if (count($value) > 3) {
					$value = array_slice($value, 0, 3);	
				}
			
				return implode(':', $value);
			
			case TIMESTAMP:
				return mktime($value[3], $value[4], $value[5], $value[1], $value[2], $value[0]);
			
			case STRING:
			case TEXT:
				return implode(', ', $value);
				
			default:
				return implode('', $value);
			
		}	
		
	}
	
	/**
	 * Returns a search condition appropriate for the type
	 *
	 * @param mixed $value
	 * @return string
	 */
	function search_condition($value) {

		if (is_array($value)) {
			$value = $this->array_to_type($value);	
		}
		
		switch ($this->type) {

			case TIMESTAMP:					
			case INTEGER:
				$result = $this->name." = ".intval($value);
				break;
				
			case FLOAT:
				$result = $this->name." = ".floatval($value);
				break;
				
			case DATETIME:
			case TIME:
			case DATE:
				$result = $this->name." = '".$value."'";
				break;
				
			case BINARY:
			case TEXT:
			case STRING:
			default:
				$result = $this->name." LIKE '".$value."'";
				break;
			
		}		
		
		return $result;
		
	}
	
	/**
	 * Returns a simplified type for a given SQL type
	 *
	 * @param string $sql_type
	 * @return string
	 */
	function simplified_type($sql_type) {
		$sql_type = $this->extract_type($sql_type);
		//debug($sql_type);
		$types = array(
			'/(.*)int(.*)/i' => INTEGER,
			'/^float/i' => FLOAT,
			'/^double/i' => FLOAT,
			'/^decimal/i' => FLOAT,
			'/^numeric/i' => FLOAT,
			'/^datetime/i' => DATETIME,
			'/^timestamp/i' => TIMESTAMP,
			'/^time/i' => TIME,
			'/^date/i' => DATE,
			'/^clob/i' => TEXT,
			'/(.*)text/i' => TEXT,
			'/^blob/i' => BINARY,
			'/^binary/i' => BINARY,
			'/^varchar(.*)/i' => STRING,
			'/^char(.*)/i' => STRING,
			'/^string/i' => STRING,
			'/^boolean/i' => BOOLEAN
		);
		
		$type = preg_replace(array_keys($types), array_values($types), $sql_type);
		
		return $type;
		
	}
	
	function extract_type($sql_type) {
		$result = preg_match("/\w*/", $sql_type, $matches);
		
		if ($result) {
			return $matches[0];
			
		} else {
			return false;	
			
		}		
		
	}
	
	/**
	 * Extract the limit from an sql type string
	 *
	 * @param string $sql_type
	 * @return int
	 */
	function extract_limit($sql_type) {
		$result = preg_match("/\((.*)\)/", $sql_type, $matches);
		
		if ($result) {
			return $matches[1];
			
		} else {
			return false;	
			
		}
		
	}
	
}


?>