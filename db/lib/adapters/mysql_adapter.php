<?php

class MysqlAdapter extends AbstractAdapter {
	var $native_database_types = array(
		'primary_key' => "int(11) DEFAULT NULL auto_increment PRIMARY KEY",
		'string'      => array('name' => "varchar", 'limit' => 255 ),
		'text'        => array('name' => "text" ),
		'integer'     => array('name' => "int", 'limit' => 11 ),
		'float'       => array('name' => "float" ),
		'datetime'    => array('name' => "datetime" ),
		'timestamp'   => array('name' => "datetime" ),
		'time'        => array('name' => "time" ),
		'date'        => array('name' => "date" ),
		'binary'      => array('name' => "blob" ),
		'boolean'     => array('name' => "tinyint", 'limit' => 1)
	
	);	
	
	var $emulate_booleans = true;
	
	function connect($options) {
		$this->connection = mysql_connect($options['host'], $options['username'], $options['password']);	
		mysql_select_db($options['database'], $this->connection);
		
		if (isset($options['encoding'])) {
			$this->execute("SET NAMES '".$options['encoding']."'");
			
		}
		
		return true;
		
	}
	
	// Quoting
	
	function quote_column_name($name) {
		return "`$name`";
	}
	
	function quote_table_name($name) {
		return "`$name`";
	}
	
	function quote_string($string) {
		return mysql_real_escape_string($string, $this->connection);
	}
	
	function quoted_true() {
		return 1;
	}
	
	function quoted_false() {
		return 0;	
	}
	
	function is_active() {
		return is_array(mysql_stat($this->connection));
	}
	
	function reconnect() {
		mysql_close($this->connection);
		$this->connect($this->connection_options);
	}
	
	// Database statements
	
	function select_all($sql, $name = null) {
		return $this->_select($sql, $name);	
	}
	
	function execute($sql, $name = null) {
		$this->log($sql, $name);
		
		return mysql_query($sql);
	}
	
	function insert($sql, $name = null, $primary_key = null, $id_value = null, $sequence_name = null) {
		$this->execute($sql, $name);
		
		return is_null($id_value) ? $this->_insert_id() : $id_value;
	}
	
	function update($sql, $name = null) {
		$this->execute($sql, $name);
		
		return mysql_affected_rows($this->connection);
	}
	
	function delete($sql, $name = null) {
		return $this->update($sql, $name);
	}
	
	function begin_db_transaction() {
		return $this->execute('BEGIN');	
	}
	
	function commit_db_transaction() {
		return $this->execute('COMMIT');	
	}
	
	function rollback_db_transaction() {
		return $this->execute('ROLLBACK');
	}
	
	function add_limit_offset($sql, $options) {
		if (isset($options['limit'])) {
			if (isset($options['offset'])) {
				return $sql .= " LIMIT ".$options['limit'];	
			} else {
				return $sql .= " LIMIT ".$options['offset'].", ".$options['limit'];	
			}
		} 	
		
		return $sql;
	}
	
	function structure_dump() {
		$tables = $this->select_values('SHOW TABLES');
		
		foreach($table as $table) {
			$create_table = $this->select_one('SHOW CREATE TABLE '.$this->quote_table_name($table));	
			
			$result[] = $create_table['Create Table'].";\n\n";
			
		}
		
		return implode('', $result);
		
	}
	
	// Private
	function _select($sql, $name = null) {
		$result = $this->execute($sql, $name);	
	}
	
	/**
	 * return int
	 */
	function _insert_id() {
		return mysql_insert_id($this->connection);	
	}
}