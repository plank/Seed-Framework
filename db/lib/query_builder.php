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
 * Class for building queries
 *
 * @package model
 * @subpackage db
 */
class SelectQueryBuilder {
	/**
	 * @var string
	 */
	var $table;
	
	/**
	 * @var array
	 */
	var $fields;	
	
	/**
	 * @var array
	 */
	var $conditions;
	
	/**
	 * @var array
	 */
	var $group_by;
	
	/**
	 * @var string
	 */
	var $order;
	
	/**
	 * @var int
	 */
	var $limit;
	
	/**
	 * @var int
	 */
	var $offset;
	
	/**
	 * @var array
	 */
	var $joins;
	
	/**
	 * Constructor
	 *
	 * @param string $table
	 * @param array $fields
	 */
	function SelectQueryBuilder($table, $fields = null) {
		$this->table = $table;
		
		$this->reset();
		
		if (isset($fields)) {
			$this->fields =  $fields;
		} 
		
	}
	
	/**
	 * Resets all the vars of the object, except for the table name
	 */
	function reset() {
		$this->reset_fields();
		$this->reset_conditions();
		$this->reset_joins();
		$this->reset_group_by();
		$this->limit = 0;
		$this->offset = 0;
		$this->order = '';
	}
	
	/**
	 * Adds one or more fields to the query
	 *
	 * @param string $field,...
	 */
	function add_fields($field) {
		$args = array_flatten(func_get_args());
		$args = array_diff($args, array(''));	// removes empty values from the parameters		
		
		$this->fields = array_merge($this->fields, $args);
	}

	/**
	 * Returns the array of fields being retrieved. If no fields are set
	 * explicitely, returns all the fields of the table
	 * 
	 * @return array
	 */
	function get_fields() {
		if (count($this->fields)) {
			return $this->fields;	
		} else {
			return array($this->table.'.*');
		}	
	}

	/**
	 * Resets the fields parameters
	 */
	function reset_fields() {
		$this->fields = array();	
	}
	
	/**
	 * Adds one or more conditions to the query
	 *
	 * @param string $condition,...
	 */
	function add_conditions($condition) {
		$args = array_flatten(func_get_args());
		$args = array_diff($args, array('', '1'));	// removes empty values and 1s from the parameters
				
		$this->conditions = array_merge($this->conditions, $args);
	}

	function add_in_condition($field, $values) {
		$this->add_conditions("$field IN ('".implode("','", $values)."')");	
		
	}
	
	/**
	 * Adds a LIKE condition for one or more fields
	 *
	 * @param mixed $field Can be either the name of a single field, or an array of field names
	 * @param string $value The value to search for
	 */
	function add_like_condition($fields, $value) {
		if (!is_array($fields)) {
			$fields = array($fields);	
		}
		
		$conditions = array();
		
		foreach($fields as $field) {
			$conditions[] = $field." LIKE '$value'";
		}
			
		$this->add_conditions(implode(' OR ', $conditions));

	}
	
	/**
	 * Returns the conditions
	 *
	 * @return array
	 */
	function get_conditions() {
		return $this->conditions;	
	}
	
	/**
	 * Resets the conditions
	 */
	function reset_conditions() {
		$this->conditions = array();
	}
	
	/**
	 * Adds a table to be joined to the query
	 *
	 * @param string $join_table The table to join to
	 * @param string $foreign_field The field on the joined table to associate
	 * @param string $local_fields The field on this table to associate
	 */
	function add_join($join_table, $foreign_field = '', $local_field = 'id') {
		
		if (!$foreign_field) {
			$foreign_field = $this->table.'_id';	
		}
		
		$this->joins[] = "$join_table ON $join_table.$foreign_field = $this->table.$local_field";
	}

	/**
	 * Adds an already constructed join statement to the query
	 *
	 * @param string $join,...
	 */
	function add_join_string($join) {
		$args = array_flatten(func_get_args());
		$args = array_diff($args, array(''));	// removes empty values from the parameters		
		
		$this->joins = array_merge($this->joins, $args);
		
	}
	
	/**
	 * Returns the joins
	 *
	 * @return array
	 */
	function get_joins() {
		return $this->joins;	
	}
	
	/**
	 * Reset the joins
	 */
	function reset_joins() {
		$this->joins = array();	
	}
	
	/**
	 * Adds one or more group_by to the query
	 *
	 * @param string $field,...
	 */
	function add_group_by($field) {
		$args = array_flatten(func_get_args());
		$args = array_diff($args, array(''));	// removes empty values from the parameters		
		
		$this->group_by = array_merge($this->group_by, $args);
	}

	/**
	 * Returns the array of group_by being retrieved. 
	 * 
	 * @return array
	 */
	function get_group_by() {
		return $this->group_by;	
	}

	/**
	 * Resets the group_by parameters
	 */
	function reset_group_by() {
		$this->group_by = array();	
	}	
	
	/**
	 * Generates the sql query
	 *
	 * @param bool $count
	 * @return string
	 */
	function generate($count = false) {
	
		$sql = "SELECT ";
		
		if ($count) {
			if (count($this->group_by)) {
				$sql .= "COUNT(DISTINCT ".implode(', ', $this->get_group_by()).") as count";				
			} else {
				$sql .= "COUNT(*) as count";
			}
		} else {

			$sql .= implode(', ', $this->get_fields());
		}
		
		$sql .= " FROM ".$this->table;
		
		if (count($this->joins)) {
			$sql .= " LEFT JOIN ".implode(' LEFT JOIN ', $this->joins);
		}
		
		if (count($this->conditions)) {
			$sql .= " WHERE (".implode(') AND (', $this->conditions).")";
		}

		if ($count) {
			return $sql;
		}
		
		if (count($this->group_by)) {
			$sql .= " GROUP BY ".implode(', ', $this->get_group_by());	
			
		}

		if ($this->order) {
			$sql .= " ORDER BY ".$this->order;
		}
		
		if ($this->limit) {
			$sql .= " LIMIT ";
			
			if ($this->offset) {
				$sql .= $this->offset.", ";
			}
			
			$sql .= $this->limit;
		}
			
		return $sql;
	}
	
	/**
	 * Generate a count query, which returns the number of rows the query would return without
	 * a limit
	 *
	 * @return string
	 */
	function generate_count() {
		return $this->generate(true);	
	}
	
}

?>