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
 * Finder object for retrieving models
 *
 */
class Finder {

	/**
	 * @var DB
	 */
	var $db;
	
	/**
	 * @var Model
	 */
	var $model;
	
	/**
	 * @var string
	 */
	var $last_query;
	
	/**
	 * Constructor
	 */
	function Finder(& $db) {
		$this->db = & $db;
		
		if (!is_a($this->db, 'DB')) {
			trigger_error("Paramter passed to Finder constructor must be a DB", E_USER_ERROR);
		}
		
		$this->model = & Model::factory($this->get_type());
		
		if (!is_a($this->model, 'Model')) {
			trigger_error("Couldn't create model in finder for type '".$this->get_type()."'", E_USER_ERROR);
		}
		
	}
	
	/**
	 * Returns the type of the class. i.e. if the class is PageModel, returns page
	 *
	 * @return string
	 */
	function get_type() {
		return Inflector::underscore(str_replace('finder', '', strtolower(get_class($this))));

	}		
	
	/**
	 * Return sthe name of the table
	 *
	 * @return string
	 */
	function table_name() {
		
		return $this->model->table_name();
	}
	
	/**
	 * Returns the name of the field that contains the id
	 *
	 * @return int
	 */
	function id_field() {
		return $this->model->id_field;	
	}
	
	/**
	 * Loads the file for the given type of controller.
	 *
	 * @static 
	 * @param string $type
	 * @param bool $ignore_errors
	 * @return bool
	 */
	function import($type, $ignore_errors = false) {
		$factory = ModelFactory::get_instance();
		
		return $factory->import($type, $ignore_errors);
	
	}

	/**
	 * Factory for models
	 *
	 * @static 
	 * @param string $type
	 * @return Model
	 */
	function & factory($type) {
		$factory = ModelFactory::get_instance();
		
		return $factory->finder($type);
	}	
	
	/**
	 * Finds objects of the class type with a given criterea
	 *
	 * @static 
	 * @param mixed $args,...  last argument can be an array of options, which can include; select, joins, conditions, group, order, limit, offset
	 * @return mixed
	 */
	function & find($args) {
		$args = func_get_args();
		$arg_count = count($args);

		// if there's more than one argument and the last one is an array, assume
		// that it's the options.
		if($arg_count > 1 && is_array($args[$arg_count - 1])) {
			$options = array_pop($args);
				
		} else {
			$options = array();
			
		}
		
		// if the first argument is null or evaluates to false, return false
		if (is_null($args[0]) || !$args[0]) {
			$result = false;
			return $result;
		}
		
		if ($args[0] == 'first') {
			$options['limit'] = 1;
			$result = $this->find('all', $options);
			$result = $result->next();
			return $result;

		}
		
		if ($args[0] == 'all') {
			$sql = $this->construct_finder_sql($options);
			$result = $this->find_by_sql($sql);
			return $result;

		}
			
		if (is_array($args[0])) {
			if (count($args[0]) == 0) {
				return false;
			} else {
				$expects_array = true;
			}
		} else {
			$expects_array = false;	
		}
		
		$ids = array_unique(array_flatten($args));
		
		if (count($ids) == 0) {
			return false;
		}
		

		if (isset($options['conditions'])) {
			$options['conditions'] .= " AND ";
		} else {
			$options['conditions'] = '';
			
		}		
		
		if (count($ids) == 1) {
			// find a single id				
			$options['conditions'] .= $this->db->escape_identifier($this->table_name()).".".$this->id_field()." = '".$this->db->escape($ids[0])."'";
			
			$result = $this->find('all', $options);

			if (!$expects_array) {
				$result = $result->next();
			}
			
			return $result;	
			
		} else {
			// find multiple ids
			$options['conditions'] .= $this->db->escape_identifier($this->table_name()).".".$this->id_field()." IN ('".implode("', '", $ids)."')";
			
			$result = $this->find('all', $options);

			return $result;	
		
		}
		
	}
	
	/**
	 * Returns all the records that match the conditions given by pairs of arguments
	 * i.e. find_all_by('name', 'george') or find_all_by('username', 'admin', 'password', 'admin');
	 * An optional argument array can be added at the end, which works like passing options to the regular
	 * find method
	 */
	function & find_all_by($field, $value, $options = null) {
		$args = func_get_args();
		
		$options = $this->_arguments_to_options($args);

		if ($options) {
			$result = & $this->find('all', $options);	
		} else {
			$result = false;	
		}
		
		return $result;
	}
	
	/**
	 * Returns the first record that matches the conditions given by pairs of arguments
	 * i.e. find_all_by('name', 'george') or find_all_by('username', 'admin', 'password', 'admin');
	 * An optional argument array can be added at the end, which works like passing options to the regular
	 * find method
	 *
	 * @return Model
	 */	
	function & find_by($field, $value, $options = null) {
		$args = func_get_args();
		
		$options = $this->_arguments_to_options($args);
		
		if ($options) {
			return $this->find('first', $options);
		}
		
		return false;
		
	}

	/**
	 * Creates and saves a new model using the given values
	 *
	 * @param array $values
	 * @return Model
	 */
	function create($values = array()) {
		$model = $this->build($values);
		$model->save();
		
		return $model;
		
	}
	
	/**
	 * Creates a new model using the given values, but does not save it
	 *
	 * @param array $values
	 * @return Model
	 */
	function build($values = array()) {
		$model = Model::factory($this->get_type());
		$model->assign($values);	
		
		return $model;	
		
	}
	
	function _arguments_to_options($args) {
		// if there's an odd number of arguments, treat the last one as an array of options
		if (count($args) % 2 == 1) {
			$options = array_pop($args);
			
			if (!is_array($options)) {
				trigger_error('Final argument to find_by* must be an array of options if number of options is odd', E_USER_WARNING);	
				return false;
			}
			
		} else {
			$options = array();	
			
		}
		
		$conditions = array();
		
		if (isset($options['conditions'])) {
			$conditions[] = $options['conditions'];	
		}		
		
		for ($x = 0; $x < count($args); $x += 2) {
			$conditions[] = $args[$x]." = '".$this->db->escape($args[$x + 1])."'";
			
		}		

		$options['conditions'] = implode(' AND ', $conditions);
		
		return $options;
		
	}
	
	function & find_by_sql($sql) {
		$this->last_query = $sql;
		
		$result = new ModelIterator($this->db->query_iterator($sql), $this->get_type());
		
		return $result;
	}
	
	function update_all($updates, $conditions = null) {
		
		$sql = "UPDATE ".$this->db->escape_identifier($this->table_name())." SET $updates";
		
		$sql .= " WHERE ".$this->add_conditions($conditions);	
		
		$this->db->query($sql);
	}
	
	function delete_all($conditions) {
		
		$sql = "DELETE FROM ".$this->db->escape_identifier($this->table_name())." WHERE ".$this->add_conditions($conditions);
		
		$this->db->query($sql);
		
	}
	
	/**
	 * Returns the number of rows in that meet the given condition 
	 *
	 * @return int
	 */
	function count($conditions = null) {
		
		$sql = "SELECT COUNT(*) FROM ".$this->db->escape_identifier($this->table_name())." WHERE ".$this->add_conditions($conditions);
		
		return $this->count_by_sql($sql);
	}
	
	function count_by_sql($sql) {
		
		$result = $this->db->query_array($sql);
		
		if (count($result)) {
			return reset($result[0]);	
		} else {
			return false;	
		}
		
	}
	
	
	/**
	 * Returns a SQL query for the given options array
	 *
	 * @param array $options
	 */
	function construct_finder_sql($options) {
		
		$query = new SelectQueryBuilder($this->db->escape_identifier($this->table_name()));
		
		if (isset($options['select'])) {
			$query->add_fields($options['select']);	
		}
		
		if (isset($options['joins'])) {
			$query->add_join_string($options['joins']);
		}
		
		if (isset($options['conditions'])) {
			$query->add_conditions($this->add_conditions($options['conditions']));	
		} else {
			$query->add_conditions($this->add_conditions());	
		}
		
		if (isset($options['group'])) {
			$query->add_group_by($options['group']);	
		}
		
		if (isset($options['order'])) {
			$query->order = $options['order'];	
		}
		
		if (isset($options['limit'])) {
			$query->limit = $options['limit'];	
		}
		
		if (isset($options['offset'])) {
			$query->offset = $options['offset'];	
		}

		if (isset($options['having'])) {
			$query->having = array($options['having']);	
		}
		
		return $query->generate($this->db);
	}	
	
	/**
	 * Creates a like condition for a given value using all string fields
	 *
	 * @param string $value
	 * @return string
	 */
	function like_condition($value) {
		$columns = $this->model->columns();
		$search = array();
		
		foreach($columns as $column) {
			if ($column->type == 'string') {
				$search[] = $this->db->escape_identifier($column->name)." LIKE '".$this->db->escape($value)."'";
			}
		}
		
		if (count($search)) {
			return '('.implode(' OR ', $search).')';
		} else {
			return false;	
		}
		
	}
	
	/**
	 * Returns a string of conditions to add for a query
	 *
	 * @param string $conditions
	 * @return string
	 */
	function add_conditions($conditions = null) {
		
		if (is_null($conditions)) {
			$conditions = "1 = 1";	
		}
		
		if ($this->model->inheritance_field()) {
			$conditions = " AND ".$this->db->escape_identifier($this->model->inheritance_field())." = ".$this->db->escape($this->model->type);
		}
		
		return $conditions;
		
	}
	
}


/**
 * A registry of finder objects
 *
 */
class FinderPool {

	/**
	 * @var array
	 */
	var $_finders;
	
	/**
	 * @var DB
	 */
	var $db;
	
	/**
	 * Constructor
	 */
	function FinderPool(& $db) {
		$this->db = & $db;	
		
		$this->_finders = array();
	}
	
	
	function get($name) {
		if (!isset($this->_finders[$name])) {
			$this->_finders[$name] = & Finder::factory($name);
		}	
		
		return $this->_finders[$name];
	}
	
	function __get($name) {
		return $this->get($name);		
	
	}
	
}

?>