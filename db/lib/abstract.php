<?php


/**
 * Abstract base class for connection adapters
 *
 * @package db
 * @subpackage adapters
 */
class AbstractAdapter {
	
	/**
	 * @var resource
	 */
	var $connection;
	
	/**
	 * @var array
	 */
	var $native_database_types;
	
	
	var $connection_options;
	
	/**
	 * Constructor
	 */
	function AbstractAdapter() {
		
		
	}
	
	function connect($options) {
		$this->connection_options = $option;
	}
	
	/**
	 * Returns the name of the adapter
	 *
	 * @return string
	 */
	function adapter_name() {
		$class_name = class_name($this);
		
		return substr($class_name, 0, strlen($class_name) - 7);
	}
		
	function is_active() {
		return true;	
	}
	
	// Quoting
	
	/**
	 * Returns a quoted value, depending on the variable type and the column type (if given)
	 *
	 * @param mixed $value
	 * @param Column $column
	 * @return string
	 */
	function quote($value, $column = null) {
		switch (true) {
		
		case is_null($value):
			return "NULL";
			
		case is_bool($value):
			if ($value === true) {
				return is_a($column, 'Column') && $column->type == INTEGER ? '1' : $this->quoted_true();
				
			} else {
				return is_a($column, 'Column') && $column->type == INTEGER ? '0' : $this->quoted_false();				
				
			}	
		
		case is_string($value):
			return $this->quote_string($value);
		
		case is_numeric($value):
			return $value;
			
		}		

	}
	
	/**
	 * @param string $string
	 * @return string
	 */
	function quote_string($string) {
		return "'".$string."'";
	}
	
	/**
	 * Returns the quoted column name
	 *
	 * @param string $name
	 * @return string
	 */
	function quote_column_name($name) {
		return '"'.$name.'"';	
	}
	
	/**
	 * Returns the quoted table name
	 *
	 * @param string $name
	 * @return string
	 */
	function quote_table_name($name) {
		return '"'.$name.'"';	
		
	}
	
	/**
	 * Returns a quoted true value
	 *
	 * @return string
	 */
	function quoted_true() {
		return "'t'";	
	}

	/**
	 * Returns a quoted false value
	 *
	 * @return string
	 */
	function quoted_false() {
		return "'f'";	
	}
	
	function quoted_date($value) {
		if (!is_numeric($value)) {
			$value = strtotime($value);
		}	
		
		return "'".date("Y-m-d h:i:s", $value)."'";
	}
	
	// Data Manipulation statements
	
	/**
	 * Returns an array of record arrays with the column names as keys and
	 * columns values as values.
	 *
	 * @param string $sql
	 * @param string $name
	 * @return array
	 */
	function select_all($sql, $name = null) {
		trigger_error("Select_all is an abstract method", E_USER_ERROR);		
	}
	
	
	/**
	 * Returns a record array with the columns names as keys and columns values as values.
	 *
	 * @param string $sql
	 * @param string $name
	 * @return array
	 */	
	function select_one($sql, $name = null) {
		$result = ($this->select_all($sql, $name));
		return reset($result);
	
	}
	
	/**
	 * Returns the first value from a record
	 *
	 * @param string $sql
	 * @param string $name
	 * @return mixed
	 */
	function select_value($sql, $name = null) {
		$result = $this->select_one($sql, $name);
		return reset($result);
	}
	
	/**
	 * Returns an array of the values of the first column in a select
	 *
	 * @param string $sql
	 * @param string $name
	 * @return array
	 */
	function select_values($sql, $name = null) {
		$query_result = $this->select_all($sql, $name);
		
		foreach($query_result as $row) {
			$result[] = reset($row);
			
		}
		
		return $result;
		
	}
	
	/**
	 * Executes the statement
	 *
	 * @param string $sql
	 * @param string $name
	 */
	function execute($sql, $name = null) {
		trigger_error("Execute is an abstract method", E_USER_ERROR);
	}
	
	/**
	 * Executes the insert statement, returning the last auto-generated id from the affected table
	 *
	 * @param string $sql
	 * @param string $name
	 * @param string $primary_key
	 * @param string $id_value
	 * @param string $sequence_name
	 * @return int
	 */
	function insert($sql, $name = null, $primary_key = null, $id_value = null, $sequence_name = null) {
		
	}
	
	/**
	 * Executes the update statement, returning the number of rows affected
	 *
	 * @param string $sql
	 * @param string $name
	 * @return int
	 */
	function update($sql, $name = null) {
		
		
	}
	
	/**
	 * Executes the delete statement, returning the number of rows affected
	 *
	 * @param string $sql
	 * @param string $name
	 * @return int
	 */
	function delete($sql, $name = null) {
		return 0;	
		
	}
	
	/**
	 * Begins a transaction, turning off auto-committing
	 *
	 */
	function begin_db_transaction() {
		
	}
	
	/**
	 * Commits the current transaction, turning on auto-committing
	 *
	 */
	function end_db_transaction() {
		
		
	}
	
	/**
	 * Rolls back the current transaction, turning on auto-committing
	 */
	function rollback_db_transaction() {
		
	}
	
	/**
	 * Adds limit and offset options to a SQL statement
	 *
	 * @param string $sql
	 * @param array $options
	 */
	function add_limit_offset($sql, $options = null) {
		if (isset($options['limit'])) {
			$sql .= " LIMIT ".$options['limit'];
			
			if (isset($options['offset'])) {
				$sql .= " OFFSET ".$options['offset'];	
			}
				
		}	
		
		return $sql;
		
	}

	/**
	 * Returns the default sequence name for a given table & columnn combination
	 *
	 * @param string $table
	 * @param string $column
	 * @return string
	 */
	function default_sequence_name($table, $column) {
		return null;	
	}
	
	/**
	 * Set the sequence to the maximum value of the given table's column
	 *
	 * @param string $table
	 * @param string $column
	 * @param string $sequence
	 */
	function reset_sequence($table, $column, $sequence = null) {
		// implement for PostgreSQL, Oracle...
		
	}
	

	
	/**
	 * Returns an array of columns objects for the given table
	 *
	 * @param string $table_name
	 * @param string $name
	 * @return array
	 */
	function columns($table_name, $name = null) {
		
	}
	
	function create_table($table_definition) {
		
	}
	
	function rename_table($name, $new_name) {
		
	}
	
	/**
	 * Drops the table with the given name
	 * 
	 * @param string $name
	 */
	function drop_table($name) {
		return $this->execute("DROP TABLE ".$this->quote_table_name($name));	
	}
	
	function add_column($table_name, $column_name, $type, $options = null) {
		if (!is_array($options)) {
			$options = array();	
		}
		
		if (!isset($options['limit'])) {
			$options['limit'] = null;
		}	
		
		$add_column_sql = "ALTER TABLE ".$this->quote_table_name($table_name)." ADD ".$this->quote_column_name($column_name);
		$add_column_sql .= " ".$this->type_to_sql($type, $options['limit']);
		$add_column_sql = $this->add_column_options($add_column_sql, $options);
		
		return $this->execute($add_column_sql);
		
	}
	
	/**
	 * Removes the column from the table
	 *
	 * @param string $table_name
	 * @param string $column_name
	 */
	function remove_column($table_name, $column_name) {
		return $this->execute("ALTER TABLE ".$this->quote_table_name($table_name)." DROP ".$this->quote_column_name($column_name));	
	}
	
	
	/**
	 * Changes a columns definition according to the new options
	 *
	 * @param string $table_name
	 * @param string $column_name
	 * @param string $type
	 * @param array $options
	 */
	function change_column($table_name, $column_name, $type, $options) {
		trigger_error("change_column is not implemented", E_USER_ERROR);
	}
	
	
	/**
	 * Changes a columns default value
	 *
	 * @param string $table_name
	 * @param string $column_name
	 * @param string $default
	 */
	function change_column_default($table_name, $column_name, $default) {
		trigger_error("change_column_default is not implemented", E_USER_ERROR);
		
	}
	
	/**
	 * Renames a column
	 *
	 * @param string $table_name
	 * @param string $column_name
	 * @param string $new_column_name
	 */	
	function rename_column($table_name, $column_name, $new_column_name) {
		trigger_error("rename_column is not implemented", E_USER_ERROR);
	}
	
	/**
	 * Adds an index to a table
	 *
	 * @param string $table_name
	 * @param mixed $column_name Can either be a string or an array of strings of table names
	 * @param array $options
	 */
	function add_index($table_name, $column_name, $options = null) {
		if (!is_array($column_name)) {
			$column_name = array($column_name);	
		}
		
		if (isset($options['name'])) {
			$index_name = $options['name'];
		} else {
			$index_name = $table_name.'_'.reset($column_name).'_index';
		}
		
		if (isset($options['unique'])) {
			$index_type = "UNIQUE ";
		} else {
			$index_type = "";
		}
		
		$column_name = array_map(array(& $this, 'quote_column_name'), $column_name);
		
		return $this->execute("CREATE ".$index_type."INDEX ".$index_name." ON ".$this->quote_table_name($table_name)." (".implode(', ', $column_name).")");
		
	}
	
	function remove_index($table_name, $options) {
		return $this->execute("DROP INDEX ".$this->index_name($table_name, $options)." ON ".$this->quote_table_name($table_name));	
		
	}
	
	function index_name($table_name, $options) {
		if (is_array($options)) {
			if (isset($options['column'])) {
				return $table_name.'_'.$options['column'].'_index';	
				
			} elseif (isset($options['name'])) {
				return $options['name'];
				
			} else {
				trigger_error('No index name given', E_USER_ERROR);
				
			}
			
		} else {
			return $table_name.'_'.$options.'_index';	
			
		}	
		
	}
	
	function structure_dump() {
		
	}
	
	/**
	 * Converts a native type to a string containing the corresponding
	 * sql type with limit
	 *
	 * @param string $type
	 * @param int $limit
	 * @return string
	 */
	function type_to_sql($type, $limit = null) {
		$native = $this->native_database_types[$type];
		
		if (is_null($limit)) {
			if (isset($native['limit'])) {
				$limit = $native['limit'];	
			} else {
				$limit = false;
			}
		}
		
		$column_type_sql = $native['name'];
		
		if($limit) {
			$column_type_sql .= "($limit)";
		}
		
		return $column_type_sql;
	}
	
	function add_column_options($sql, $options) {
		if (!isset($options['column'])) {
			$options['column'] = null;	
		}
		
		if (isset($options['default'])) {
			$sql .= " DEFAULT ".$this->quote($options['default'], $options['column']);
			
		}
		
		if (isset($options['null']) && $options['null'] == false) {
			$sql .= " NOT NULL";	
		}
		
		return $sql;
	}

	// Logging
	function log($sql, $name) {
		// do nothing for now, this will be implemented later
	}
	
}


?>