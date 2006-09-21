<?php

class Finder {

	/**
	 * @var DB
	 */
	var $db;
	
	/**
	 * @var Model
	 */
	var $model;
	
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
	
	function table_name() {
		
		return $this->model->table_name();
	}
	
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
		$type = strtolower($type);
		
		$path = MODEL_PATH.$type.'.php';
		
		if (!file_exists($path) && !$ignore_errors) {
			trigger_error("File for finder type '$type' not found in '$path'", E_USER_ERROR);
			return false;
		}
		
		require_once($path);

		return true;	
	
	}

	/**
	 * @static 
	 * @param string $type
	 * @return Model
	 */
	function & factory($type, $db = null) {
		
		if (is_null($db)) {
			$db = DB::get_db();	
		}
		
		$class_name = Inflector::camelize($type).'Finder';
		
		if (!class_exists($class_name)) {
			Finder::import($type);
		}
		
		if (!class_exists($class_name)) {
			trigger_error("Class '$class_name' not found", E_USER_ERROR);
			return false;
		}
		
		$model = new $class_name($db);
		
		if (!is_a($model, 'Finder')) {
			trigger_error("Class '$class_name' doesn't extend Model", E_USER_ERROR);
			return false;
		}
		
		return $model;

	}	
	
	/**
	 * Finds objects of the class type with a given criterea
	 *
	 * @static 
	 * @param mixed $args,...
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
		
		if ($args[0] == 'first') {
			$options['limit'] = 1;
			$result = $this->find('all', $options);
			return $result->next();

		}
		
		if ($args[0] == 'all') {
			$sql = $this->construct_finder_sql($options);
			return $this->find_by_sql($sql);

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
			$options['conditions'] .= $this->db->escape_identifier($this->table_name()).".".$this->id_field()." = '".$ids[0]."'";
			
			$result = $this->find('all', $options);

			if ($expects_array) {
				return $result;	
			} else {
				return $result->next();
			}
			
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
			return $this->find('all', $options);	
		}
		
		return false;
		
	}
	
	/**
	 * Returns the first record that matches the conditions given by pairs of arguments
	 * i.e. find_all_by('name', 'george') or find_all_by('username', 'admin', 'password', 'admin');
	 * An optional argument array can be added at the end, which works like passing options to the regular
	 * find method
	 */	
	function & find_by($field, $value, $options = null) {
		$args = func_get_args();
		
		$options = $this->_arguments_to_options($args);
		
		if ($options) {
			return $this->find('first', $options);
		}
		
		return false;
		
	}

	function _arguments_to_options($args) {
		// if there's an odd number of arguments, treat the last one as an array of options
		if (count($args) % 2 == 1) {
			$options = array_pop($args);
			
			if (!is_array($options)) {
				trigger_error('Final argument to find_by must be an array of options if number of options is odd', E_USER_WARNING);	
				return false;
			}
			
		} else {
			$options = array();	
			
		}
		
		$conditions = array();
		
		for ($x = 0; $x < count($args); $x += 2) {
			$conditions[] = $args[$x]." = '".$args[$x + 1]."'";
			
		}		

		$options['conditions'] = implode(' AND ', $conditions);
		
		return $options;
		
	}
	
	function & find_by_sql($sql) {
		$result = new ModelIterator($this->db->query_iterator($sql), $this->get_type());
		
		return $result;
	}
	
	function update_all($updates, $conditions = null) {
		
		$sql = "UPDATE ".$this->db->escape_identifier($this->table_name())." SET $updates";
		
		if ($conditions) {
			$sql .= " WHERE ".$conditions;	
		}
		
		
		$this->db->query($sql);
	}
	
	function delete_all($conditions) {
		
		$sql = "DELETE FROM ".$this->db->escape_identifier($this->table_name())." WHERE ".$conditions;
		
		$this->db->query($sql);
		
	}
	
	/**
	 * Returns the number of rows in that meet the given condition 
	 *
	 * @return int
	 */
	function count($conditions = '1 = 1') {
		
		$sql = "SELECT COUNT(*) FROM ".$this->db->escape_identifier($this->table_name())." WHERE ".$conditions;
		
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
	 * @static 
	 * @param array $options
	 */
	function construct_finder_sql($options) {
		
		$query = new SelectQueryBuilder($this->db->escape_identifier($this->table_name()));
		
		if (isset($options['select'])) {
			$query->fields = $options['select'];	
		}
		
		if (isset($options['joins'])) {
			$query->add_join_string($options['joins']);
		}
		
		if (isset($options['conditions'])) {
			$query->add_conditions($options['conditions']);	
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

		return $query->generate();
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