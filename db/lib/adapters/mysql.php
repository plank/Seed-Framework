<?php

/**
 * Mysql db abstraction class
 *
 * @package model
 * @subpackage db
 */
class MysqlDB extends DB {

	/**
	 * Connection to the database
	 * @var resource
	 */
	var $link;
	
	/**
	 * Result of the last query
	 * @var resource
	 */
	var $result;
	
	/**
	 * Constructor
	 */
	function MysqlDB($host = DB_HOST, $user = DB_USER, $pass = DB_PASS, $database = DB_NAME) {
		$this->link = mysql_connect($host, $user, $pass) or trigger_error("Couldn't connect to database", E_USER_ERROR);

		$this->select_db($database);
		
	}

	
	function select_db($database) {
		return mysql_select_db($database, $this->link) or trigger_error("Couldn't select database", E_USER_ERROR);			
		
	}
	
	/**
	 * Executes a given query, then returns the result resource
	 *
	 * @param string $sql
	 * @return resource
	 */
	function query($sql) {
		if (!is_string($sql)) {
			trigger_error("Invalid parameter passed to db::query, expecting string", E_USER_WARNING);
			return false;
		}
		
		if ($sql == '') {
			trigger_error("Invalid parameter passed to db::query, string was empty", E_USER_WARNING);
			return false;
		}

		Logger::log('SQL', LOG_LEVEL_DEBUG, $sql);
		
		$this->result = mysql_query($sql, $this->link);	
		
		if ($this->result == false) {
			trigger_error(mysql_error($this->link)."\n".$sql, E_USER_ERROR);
		}
		
		return $this->result;
	}
	
	function query_iterator($sql) {
		return new MysqlIterator($this->query($sql));
		
	}
	
	/**
	 * Executes a query and returns a single value
	 *
	 * @param string $sql
	 * @return string
	 */
	function query_value($sql) {
		
		$this->query($sql);
		
		$num_results = mysql_num_rows($this->result);
		
		if ($num_results == 0) {
			return false;
			
		} elseif ($num_results == 1) {
			list($return) = mysql_fetch_row($this->result);
			
		} else {
			$return = array();
			
			while($row = mysql_fetch_row($this->result)) {
				$return[] = $row[0];
			}
		}
		
		return $return;
	}
	
	/**
	 * Executes a query and returns the result as an array.
	 *
	 * @param string $sql
	 * @return array
	 */
	function query_array($sql, $primary_key = null) {
	
		$this->query($sql);	
		
		$return = array();		
		
		while ($row = mysql_fetch_assoc($this->result)) {
			if (isset($primary_key) && key_exists($primary_key, $row)) {
				$return[$row[$primary_key]] = $row;	
			} else {
				$return[] = $row;
			}
		}
		
		return $return;
	}
	
	
	function query_single($sql) {
		$result = $this->query_array($sql);
		
		if (count($result) == 0) {
			return false;
		} else {
			return $result[0];
		}
		
	}
	
	/**
	 * Returns the last insert id
	 *
	 * @return int
	 */
	function insert_id() {
		return mysql_insert_id($this->link);	
	
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
				
		$sql = "INSERT INTO `$table_name` (".implode(', ', $fields).") VALUES (".implode(', ', $values).")";
		
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
		
		$sql = "UPDATE `$table_name` SET ".implode(', ',$values)." WHERE ".$condition;
		
		return $this->query($sql);
		
	}
	
	/**
	 * Escapes a given string for entry into the database
	 *
	 * @param string $string
	 * @return string
	 */
	function escape($string) {
		return mysql_real_escape_string($string, $this->link);
	}
	
	/**
	 * Escapes an indentifier
	 *
	 * @param string $string
	 * @return string
	 */
	function escape_identifier($string) {
		return '`'.$string.'`';
	}
	
	/**
	 * Closes the db connection and frees the stored result
	 */
	function close() {
		mysql_free_result($this->result);	
		mysql_close($this->link);
	}
    
	/**
	 * Creates an order by part of a query
	 */
    function order_by($params, $default_field, $default_order = 'ASC') {
    	if (isset($params['sortby'])) {
    		$default_field = $params['sortby'];
    	}
    	
    	if (isset($params['sortdir'])) {
    		$default_order = $params['sortdir'];
    	}
    	
    	return "ORDER BY $default_field $default_order";
    	
    }
		
    
    /**
     * Retrieve the collection of columns for the table
     *
     * @param string $table
     * @return array
     */
    function columns($table) {
    	static $tables;
    	
    	if (!isset($tables[$table])) {
	    	$columns = $this->column_definitions($table);
	    	
	    	foreach($columns as $column) {
	    		$result[$column['Field']] = new Column(
	    			$column['Field'], 
	    			$column['Default'], 
	    			$column['Type'], 
	    			$column['Null'] == 'YES'
	    		);
	    		
	    	}
    	
    		$tables[$table] = $result;
    	
    	}
    	
    	return $tables[$table];
    }    
    
	/**
	 * Alias for column_definitions. for backwards compatibility
	 *
	 * To be removed!
	 */    
    function describe($table) {
    	return $this->column_definitions($table);
    	
    }    
    
	/**
	 * Performs a describe query on the given table
	 */
	function column_definitions($table) {
		return $this->query_array("DESCRIBE `$table`");
		
	}
	
	function show_indices($table) {
		return $this->query_array("SHOW INDEX FROM `$table`");
		
	}
	
	/**
	 * Returns an array of metadata for a model
	 */ 
	function get_meta_data($table, $names_only = false) {
		static $tables = array();
		
		if (!isset($tables[$table])) {
			$meta_data = array();
			
			foreach ($this->describe($table) as $column) {
				if ($names_only) {
					$meta_data[$column['Field']] = $column['Field'];
				} else {
					$meta_data[$column['Field']] = $column['Type'];
				}
			}
			
			$tables[$table] = $meta_data;
		}
		
		return $tables[$table];
		
	}    
	
	/**
	 * Returns an array containing the names of all the tables in the database
	 *
	 * @return array
	 */
	function get_tables() {
		$tables = $this->query_array('SHOW TABLES');
		$table_names = array();
		
		foreach ($tables as $table) {
			$table_name = current($table);
			
			$table_names[$table_name] = $table_name;
			
		}

		return $table_names;
		
	}
	
	/**
	 * Returns all the fields in a given index
	 */
	function get_index($table, $index_name) {
		$results = $this->show_indices($table);
		$return = array();
		
		foreach($results as $result) {
			if ($result['Key_name'] == $index_name) {
				$return[] = $result['Column_name'];	
			}	
		}
		
		return $return;
		
	}
	
	
	function lock_table($table_name, $alias = null) {
		$sql = "LOCK TABLES $table_name";
		
		if (isset($alias)) {
			$sql .= " AS $alias";	
		}
		
		$sql .= " WRITE";
		
		return $this->query($sql);
		
	}

	function unlock_tables() {
		return $this->query('UNLOCK TABLES');	
		
	}
	
	function drop_table($table_name, $if_exists = false) {
		$sql = "DROP TABLE ";
		
		if ($if_exists) {
			$sql .= "IF EXISTS ";
	
		}
		
		$sql .= $table_name;
		
		return $this->query($sql);
	}
	
	function truncate_table($table_name) {
		$sql = 	"TRUNCATE TABLE ".$this->escape_identifier($table_name);
		
		return $this->query($sql);
		
	}
	
	/*
	function create_table($table_name, $columns) {
		
		
	}
	*/
}

/**
 * db result iteration class
 *
 * @package model
 * @subpackage db
 */
class MysqlIterator extends SeedIterator  {
	
	function _validate_data($data) {
		if (!is_resource($data)) {
			trigger_error("MysqlIterator expects a resource as data parameter in constructor", E_USER_ERROR);
						
			return false;
		} else {
			return true;
		}
	}
	
	function size() {
		return mysql_num_rows($this->data);
	}
	
	function has_next() {
		return mysql_num_rows($this->data) > $this->position;
	}
	
	function next() {
		$this->position ++;
		return mysql_fetch_assoc($this->data);
		
	}
	
	function reset() {
		if ($this->position) {
			$this->position = 0;
			return mysql_data_seek($this->data, 0);
		}
	}
}

/**
 * Returns a row from a mysql result as a multidimensional array of table then field
 *
 * @param resource $result
 * @return array
 */
function mysql_fetch_multi($result) {
	$row = mysql_fetch_row($result);

	if(!$row) {
		return false;	
	}
	
	foreach ($row as $offset => $value) {
		$return[mysql_field_table($result, $offset)][mysql_field_name($result, $offset)] = $value;
		
	}
	
	return $return;
	
}

?>
