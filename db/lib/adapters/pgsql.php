<?php

/**
 * Mysql db abstraction class
 *
 * @package model
 * @subpackage db
 */
class PgsqlDB extends DB {

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
	function PgsqlDB($host, $user, $pass, $database) {
		
		if ($host) {
			$connection[] = "host=$host";	
		}
		
		if ($user) {
			$connection[] = "user=$user";
		}
		
		if ($pass) {
			$connection[] = "password=$pass";
		}
		
		if ($database) {
			$connection[] = "dbname=$database";	
		}
						
		$this->link = pg_connect(implode(" ", $connection)) or trigger_error("Couldn't connect to database $database", E_USER_ERROR);
		
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
		//debug($sql);
		$this->result = pg_query($this->link, $sql);	
		
		if ($this->result == false) {
			trigger_error(pg_last_error($this->link)."\n".$sql, E_USER_ERROR);
		}
		
		return $this->result;
	}
	
	function query_iterator($sql) {
		return new PgsqlIterator($this->query($sql));
		
	}
	
	/**
	 * Executes a query and returns a single value
	 *
	 * @param string $sql
	 * @return string
	 */
	function query_value($sql) {
		
		$this->query($sql);
		
		$num_results = pg_num_rows($this->result);
		
		if ($num_results == 0) {
			return false;
			
		} elseif ($num_results == 1) {
			list($return) = pg_fetch_row($this->result);
			
		} else {
			$return = array();
			
			while($row = pg_fetch_row($this->result)) {
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
		
		while ($row = pg_fetch_assoc($this->result)) {
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
	function insert_id($table_name = '', $id_name = '') {
		return $this->query_value("select currval('{$table_name}_{$id_name}_seq')");
		
		// need to : select currval('tablename_idname_seq');
		return pg_insert_id($this->link);	
	
	}
	

	
	/**
	 * Escapes a given string for entry into the database
	 *
	 * @param string $string
	 * @return string
	 */
	function escape($string) {
		return pg_escape_string($string);
	}
	
	/**
	 * Escapes an indentifier
	 *
	 * @param string $string
	 * @return string
	 */
	function escape_identifier($string) {
		return '"'.$string.'"';
	}
	
	/**
	 * Closes the db connection and frees the stored result
	 */
	function close() {
		pg_free_result($this->result);	
		pg_close($this->link);
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
	    			$this->default_value($column['Default']), 
	    			$this->translate_field_type($column['Type']), 
	    			$column['Null'] == 't'
	    		);
	    		
	    	}
    	
    		$tables[$table] = $result;
    	
    	}
    	
    	return $tables[$table];
    }
    
    /**
     * Returns the default value for a postgres default, which is actually an expression
     *
     * @param string $value
     * @return string
     */
    function default_value($value) {
    	// boolean types
    	if (strpos($value, 'true') !== false) { 
    		return true;
    	}
    	
    	if (strpos($value, 'false') !== false) {
    		return false;
    	}
    	
    	// char/string types
    	if (preg_match("/^'(.*)'::(bpchar|text|character varying)$/", $value, $matches)) {
    		return $matches[1];	
    	}
    	
    	// numeric types
    	if (preg_match("/^[0-9]+(\.[0-9]*)?/", $value)) {
    		return $value;	
    	}
    	
    	// date / time magic value
        if (preg_match("/^now\(\)|^\('now'::text\)::(date|timestamp)/i", $value, $matches)) {
        	return date(SQL_DATE_TIME_FORMAT);	
        }
    	
        // fixed date / times
        if (preg_match("/^'(.+)'::(date|timestamp)/", $value, $matches)) {
        	return $matches[1];	
        }
    	
        // anything else, we don't know how to handle
        return null;
    }
    
    /**
     * Translate postgres specific fiels types into regular sql ones
     *
     * @param string $field_type
     * @return string
     */
    function translate_field_type($sql_type) {
				
		$types = array(
			'/^timestamp/i' => DATETIME,
			'/^real/i' => FLOAT,
			'/^money/i' => FLOAT,
			'/^interval/i' => STRING,
			'/^(?:point|lseg|box|"?path"?|polygon|circle)/i' => STRING,
			'/^bytea/i' => BINARY,
		);
		
		$type = preg_replace(array_keys($types), array_values($types), $sql_type);
		
		return $type;
	    	
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
/* old
$sql = <<<sql
SELECT
  a.attnum,
  a.attname AS field,
  t.typname AS type,
  a.attlen AS length,
  a.atttypmod AS lengthvar,
  a.attnotnull AS notnull
FROM
  pg_class c,
  pg_attribute a,
  pg_type t
WHERE
  c.relname = '$table'
  AND a.attnum > 0
  AND a.attrelid = c.oid
  AND a.atttypid = t.oid
ORDER BY 
  a.attnum
sql;
*/	

$sql = <<<sql
SELECT 
	a.attname as "Field", 
	format_type(a.atttypid, a.atttypmod) as "Type", 
	a.attnotnull as "Null", 
	pg_get_expr(d.adbin, d.adrelid) as "Default"
FROM 
	pg_attribute a 
LEFT JOIN 
	pg_attrdef d ON a.attrelid = d.adrelid AND a.attnum = d.adnum
WHERE 
	a.attrelid = '$table'::regclass 
	AND a.attnum > 0 
	AND NOT a.attisdropped
ORDER BY 
	a.attnum
sql;


		return $this->query_array($sql);
		
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
		return;
		$sql = "LOCK TABLES ".$this->escape_identifier($table_name);
		
		if (isset($alias)) {
			$sql .= " AS $alias";	
		}
		
		$sql .= " WRITE";
		
		return $this->query($sql);
		
	}

	function unlock_tables() {
		return;
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
class PgsqlIterator extends SeedIterator  {
	
	function _validate_data($data) {
		if (!is_resource($data)) {
			trigger_error("MysqlIterator expects a resource as data parameter in constructor", E_USER_ERROR);
						
			return false;
		} else {
			return true;
		}
	}
	
	function size() {
		return pg_num_rows($this->data);
	}
	
	function has_next() {
		return pg_num_rows($this->data) > $this->position;
	}
	
	function next() {
		$this->position ++;
		return pg_fetch_assoc($this->data);
		
	}
	
}

/**
 * Returns a row from a mysql result as a multidimensional array of table then field
 *
 * @param resource $result
 * @return array
 */
function pg_fetch_multi($result) {
	$row = pg_fetch_row($result);

	if(!$row) {
		return false;	
	}
	
	foreach ($row as $offset => $value) {
		$return[pg_field_table($result, $offset)][pg_field_name($result, $offset)] = $value;
		
	}
	
	return $return;
	
}

?>