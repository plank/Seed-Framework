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
 * Class for generating full text index search queries
 *
 * @package model
 * @subpackage db
 */
class FulltextSearch {
	
	/**
	 * @var db
	 */
	var $db;
	
	/**
	 * @var array
	 */
	var $tables;
	
	/**
	 * Additional search conditions
	 */
	var $conditions;
	
	/**
	 * The name to use for the score result
	 */
	var $score_field_name = 'score';
	
	/**
	 * Constructor
	 *
	 * @param db $db
	 */
	function FulltextSearch($db, $conditions = '1') {
		$this->db = $db;
		
		$this->tables = array();
		
		$this->conditions = $conditions;
	}
	
	/**
	 * Adds a table to search
	 *
	 * @param string $table_name The name of the table
	 * @param mixed $index Either a string containing the name of the index, or an array of the field names comprising the index
	 * @param array $fields_to_return An array containing the fields to return. All tables must return the
	 * same fields
	 */
	function add_table($table_name, $index, $fields_to_return) {
		if (is_array($index)) {
			$fields_to_search = $index;	
		} else {
			$fields_to_search = $this->db->get_index($table_name, $index);
		}
		
		$this->tables[$table_name] = array('search'=>$fields_to_search, 'return'=>$fields_to_return);
		
	}
	
	/**
	 * Search the tables for the search terms given
	 *
	 * @param string $search_terms
	 * @return array
	 */
	function search($search_terms) {
		
		$queries = array();
		
		foreach ($this->tables as $table_name => $data) {
			
			$queries[] = "SELECT ".implode(', ', $data['return']).", ".$this->make_match($data['search'], $search_terms)." as $this->score_field_name, '$table_name' as table_name FROM $table_name WHERE $this->conditions AND ".$this->make_match($data['search'], $search_terms, true);
			
		}
		
		$search_query = "(".implode(") UNION (", $queries).") ORDER BY $this->score_field_name DESC";
		
		return $this->db->query_array($search_query);
	}
	
	/**
	 * Creates a MATCH AGAINST string for the given parameters
	 *
	 * @param array $columns The columns to search in
	 * @param string $search_terms The terms to search for
	 * @param bool $boolean_mode Make a boolean mode search
	 * @return string
	 */
	function make_match($columns, $search_terms, $boolean_mode = false) {
	
		if ($boolean_mode) {
			$boolean_switch = ' IN BOOLEAN MODE';
		} else {
			$boolean_switch = '';
		}
		
		$match = sprintf("MATCH (%s) AGAINST ('%s'%s)", implode(', ', $columns), mysql_escape_string($search_terms), $boolean_switch);
		
		return $match;
	}
	
}

?>