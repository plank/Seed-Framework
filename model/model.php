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
 * Base class for model objects, implements basic get/set
 *
 * @todo Associations need to be moved out of here
 *
 * @package model
 */
class DataSpace {
	
	/**
	 * An array containing field data
	 *
	 * @var array
	 */
	var $data = array();

	/**
	 * Constructor
	 *
	 * @param array $data
	 * @return DataSpace
	 */
	function DataSpace($data = null) {
		if (isset($data)) {
			$this->data = $data;
		} 
	}
	
	/**
	 * Generic getter method. First checks for the existance of a method called get_$field
	 * (i.e. if field = 'title', looks for get_title) and calls it if found, if not
	 * it returns the element of the data array that contains the key 'title'
	 *
	 * @param string $field
	 * @return mixed The scalar value or object associated to that field if there is one, null if there isn't
	 */
	function get($field) {
		if(!is_string($field)) {
			trigger_error("Parameter for model->get() must be a string", E_USER_WARNING);
			return null;
		}
		
		if (method_exists($this, 'get_'.$field)) {
			$args = func_get_args();
			array_shift($args);
			
			return call_user_func_array(array(& $this, 'get_'.$field), $args);
		} 
		
		if (isset($this->belongs_to_data[$field])) {
			
			$options = $this->belongs_to_data[$field];
			
//			debug($options);
			
			if (!key_exists($options['foreign_key'], $this->data)) {
				return null;	
			}
			
			$model = Model::factory($options['class_name']);
			
			return $model->find('first', array(
				'conditions' => $model->id_field." = ".$this->data[$options['foreign_key']]." AND ".$options['conditions'],
				'order' => $options['order']
			));
			
		}
		
		if (isset($this->has_one_data[$field])) {
			$options = $this->has_one_data[$field];
			
			$model = Model::factory($options['class_name']);
			
			return $model->find('first', array(
				'conditions' => $options['foreign_key']." = ".$this->get_id()." AND ".$options['conditions'],
				'order' => $options['order']
			));			
			
		}

		
		if (isset($this->has_many_data[$field])) {
			
			$options = $this->has_many_data[$field];
			
			$model = Model::factory($options['class_name']);
			
			return $model->find('all', array(
				'conditions' => $options['foreign_key']." = ".$this->get_id()." AND ".$options['conditions'],
				'order' => $options['order']
			));			
			
		}
		
		if (isset($this->has_and_belongs_to_many_data[$field])) {
			
			$options = $this->has_and_belongs_to_many_data[$field];
			
			$model = Model::factory($options['class_name']);
			
			return $model->find('all', array(
				'conditions' => $options['foreign_key']." = ".$this->get_id()." AND ".$options['conditions'],
				'joins' => $options['join_table'].' ON '.$options['join_table'].'.'.$options['association_foreign_key'].' = '.$model->table_name().'.'.$model->id_field,
				'order' => $options['order']
			));			
			
		}		
		
		
		if (isset($this->data[$field])) {
			return $this->data[$field]; 
		}
		
		return null;
	}
	
	/**
	 * Generic setter method. First checks for the existance of a method called set_$field
	 * (i.e. if field = 'title', looks for set_title) and calls it if found, if not it
	 * calls the assign method with the passed data.
	 *
	 * @param mixed $field
	 * @param mixed $value
	 * @return bool
	 */
	function set($field, $value = null) {
	
		if (!is_array($field)) {
			$field = array($field => $value);
		}
		
		return $this->assign($field);
		
	}
	
	/**
	 * Returns true if the field is set i.e. has a value other than null
	 *
	 * @param mixed $field
	 * @return bool
	 */
	function is_set($field) {
		return isset($this->data[$field]) || 
			isset($this->has_one_data[$field]) || 
			isset($this->belongs_to_data[$field]) ||
			isset($this->has_many_data[$field]) ||
			isset($this->has_and_belongs_to_many_data[$field]);
		
	}	
	
	/**
	 * Checks for the existance of a given field/key
	 *
	 * @param string $field
	 * @return bool
	 */
	function field_exists($field) {
		if (method_exists($this, 'set_'.$field)) {
			return true;
		} 

		if ($this->columns && key_exists($field, $this->columns)) {
			return true;
		}
		
		if(isset($this->has_one_data[$field]) || 
			isset($this->belongs_to_data[$field]) || 
			isset($this->has_many_data[$field]) ||
			isset($this->has_and_belongs_to_many_data[$field])) {
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Assign an array of fields and values to the object
	 *
	 * By default, only store values for fields stored in the _meta_data array
	 * 
	 * IMPORTANT: Any array passed as a value will get combined into a string, with dashes
	 * this format will work to enter a date into a datestamp field, but may not work at all
	 * in other databases.
	 * 
	 * @param array $data
	 * @return bool
	 */
	function assign($data) {
		unset($this->valid);
		
		foreach ($data as $field => $value) {
			if ($field == $this->id_field) {
				$this->id = $value;	
			} else {
				if ($this->field_exists($field)) {
					if (is_array($value)) {
						$value = implode('-', $value);
					}

					if (method_exists($this, 'set_'.$field)) {
						call_user_func(array(& $this, 'set_'.$field), $value);
					} else {
						$this->data[$field] = $value;
					}
					
				} 
			}
		}
		
		return true;
	}
	
}

/**
 * ActiveRecord ORM Model object
 *
 * @package model
 */
class Model extends DataSpace {
	/**
	 * Connection to the database
	 *
	 * @var DB
	 */
	var $db;
	
	/**
	 * The type of data the model represents
	 *
	 * @var string
	 */
	var $type;
	
	/**
	 * The name of the table
	 *
	 * @var string
	 */
	var $table;

	/**
	 * The field to use for unique ids
	 *
	 * @var string
	 */
	var $id_field = 'id';
	
	/**
	 * The field that contains the auto_increment or other sequence field. This is usually the
	 * same as the id_field, unless the primary key of the database is contained in several fields
	 *
	 * @var string
	 */
	var $sequence_field = 'id';
	
	/**
	 * Title
	 *
	 * @var string
	 */
	var $name_field = 'name';
	
	/**
	 * The unique identifier for the object
	 *
	 * @var mixed
	 */
	var $id;

	/**
	 * An array containing the data for the columns
	 *
	 * @var array
	 */
	var $columns = null;

	/**
	 * The last SQL query that was executed
	 *
	 * @var string
	 */
	var $sql;
	
	/**
	 * The field for marking an item as deleted
	 *
	 * @var string
	 */
	var $deleted_field; // = 'deleted';
	
	/**
	 * @var Array
	 */
	var $belongs_to_data = array();
	
	/**
	 * @var Array
	 */
	var $has_one_data = array();
	
	/**
	 * @var Array
	 */
	var $has_many_data = array();
	
	/**
	 * @var Array
	 */
	var $has_and_belongs_to_many_data = array();
	
	/**
	 * A validation object
	 *
	 * @var Validation
	 */
	var $validate;
	
	/**
	 * Constructor
	 *
	 * @param QueryDB $db
	 */
	function Model($db = null) {
		if ($db) {
			$this->db = $db;
		} else {
			$this->db = DB::get_db();
		}
		
		if (!$this->type) {
			$this->type = $this->_get_type();
		}
		
		if (!$this->table) {
			$this->table = $this->type;
		}

		$this->columns = $this->db->columns($this->table);
		
		foreach($this->columns as $column) {
			$this->data[$column->name] = $column->default;
		}		
		
		$this->validate = new Validation();
		$this->setup();
		
	}
	
	
	function setup() {
	
	}
	
	function belongs_to($field, $options = null) {
		
		if (is_null($options)) {
			$options = array();	
		}
		
		if (!isset($options['class_name'])) {
			$options['class_name'] = $field;	
		}
		
		if (!isset($options['conditions'])) {
			$options['conditions'] = 1;
		}
		
		if (!isset($options['order'])) {
			$class = model::factory($options['class_name']);
			
			$options['order'] = $class->id_field.' ASC';	
		}
		
		if (!isset($options['foreign_key'])) {
			$options['foreign_key'] = Inflector::underscore($options['class_name']).'_id';
		}
		
		$this->belongs_to_data[$field] = $options;	
		
	}
	
	function has_one($field, $options = null) {
		if (is_null($options)) {
			$options = array();
		}
		
		if (!isset($options['class_name'])) {
			$options['class_name'] = $field;	
		}
		
		if (!isset($options['conditions'])) {
			$options['conditions'] = 1;
		}
		
		if (!isset($options['order'])) {
			$options['order'] = 'id ASC';	
		}
		
		if (!isset($options['foreign_key'])) {
			$options['foreign_key'] = $this->_get_type().'_id';
		}
		
		if (!isset($options['dependent'])) {
			$options['dependent'] = false;
		}
		
		$this->has_one_data[$field] = $options;	
	}
	
	/**
	 * Options are:
	 *   class_name
	 *   conditions
	 *   order
	 *   group
	 *   foreign_key
	 */
	function has_many($field, $options = null) {
		if (is_null($options)) {
			$options = array();
		}
		
		if (!isset($options['class_name'])) {
			$options['class_name'] = $field;
		}
		
		if (!isset($options['conditions'])) {
			$options['conditions'] = 1;
		}
		
		if (!isset($options['order'])) {
			$options['order'] = 'id ASC';	
		}
		
		if (!isset($options['foreign_key'])) {
			$options['foreign_key'] = $this->_get_type().'_id';
		}
		
		if (!isset($options['dependent'])) {
			$options['dependent'] = false;
		}
		
		$this->has_many_data[$field] = $options;	 
		
	}
	
	/**
	 * Options are:
	 *  class_name
	 *  join_table
	 *  foreign_key
	 *  association_foreign_key
	 *  conditions
	 *  order
	 */
	function has_and_belongs_to_many($field, $options = null) {
		if (is_null($options)) {
			$options = array();
		}
		
		if (!isset($options['class_name'])) {
			$options['class_name'] = $field;
		}
		
		if (!isset($options['conditions'])) {
			$options['conditions'] = 1;
		}
		
		if (!isset($options['order'])) {
			$options['order'] = 'id ASC';	
		}
		
		if (!isset($options['foreign_key'])) {
			$options['foreign_key'] = $this->_get_type().'_id';
		}
		
		if (!isset($options['association_foreign_key'])) {
			$options['association_foreign_key'] = $field.'_id';
		}
		
		if (!isset($options['join_table'])) {
			if (strcasecmp($field, $this->_get_type()) <= 0) {
				$options['join_table'] = $field.'_'.$this->_get_type();
			} else {
				$options['join_table'] = $this->_get_type().'_'.$field;			
			}
		}
		
		$this->has_and_belongs_to_many_data[$field] = $options;		
	}
	
	/** Static methods **/
	
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
			trigger_error("File for model type '$type' not found in '$path'", E_USER_ERROR);
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
	function factory($type) {
		
		$class_name = Inflector::camelize($type).'Model';
		
		if (!class_exists($class_name)) {
			Model::import($type);
		}
		
		if (!class_exists($class_name)) {
			trigger_error("Class '$class_name' not found in '$path'", E_USER_ERROR);
			return false;
		}
		
		$model = new $class_name(DB::get_db());
		
		if (!is_a($model, 'Model')) {
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
	function find($args) {
		$db = db::get_db();
		
		$class_name = this::class_name();
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
			$result = this::call('find', 'all', $options);
			return $result->next();

		}
		
		if ($args[0] == 'all') {
			$sql = this::call('construct_finder_sql', $options);
			return this::call('find_by_sql', $sql);

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
			$options['conditions'] .= $db->escape_identifier(this::call('table_name')).".".this::get_var('id_field')." = '".$ids[0]."'";
			
			$result = this::call('find', 'all', $options);

			if ($expects_array) {
				return $result;	
			} else {
				return $result->next();
			}
			
		} else {
			// find multiple ids
			$options['conditions'] .= $db->escape_identifier(this::call('table_name')).".".this::get_var('id_field')." IN ('".implode("', '", $ids)."')";
			
			$result = this::call('find', 'all', $options);

			return $result;	
		
		}
		
	}
	
	/**
	 * Returns all the records that match the conditions given by pairs of arguments
	 * i.e. find_all_by('name', 'george') or find_all_by('username', 'admin', 'password', 'admin');
	 * An optional argument array can be added at the end, which works like passing options to the regular
	 * find method
	 */
	function find_all_by($field, $value, $options = null) {
		$args = func_get_args();
		
		$options = this::call('_arguments_to_options', $args);

		if ($options) {
			return this::call('find', 'all', $options);	
		}
		
		return false;
		
	}
	
	/**
	 * Returns the first record that matches the conditions given by pairs of arguments
	 * i.e. find_all_by('name', 'george') or find_all_by('username', 'admin', 'password', 'admin');
	 * An optional argument array can be added at the end, which works like passing options to the regular
	 * find method
	 */	
	function find_by($field, $value, $options = null) {
		$args = func_get_args();
		
		$options = this::call('_arguments_to_options', $args);
		
		if ($options) {
			return this::call('find', 'first', $options);
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
	
	function find_by_sql($sql) {
		$db = db::get_db();
		return new ModelIterator($db->query_iterator($sql), this::call('_get_type'));
	}
	
	function update_all($updates, $conditions = null) {
		$db = db::get_db();
		
		$sql = "UPDATE ".$db->escape_identifier(this::call('table_name'))." SET $updates";
		
		if ($conditions) {
			$sql .= " WHERE ".$conditions;	
		}
		
		
		$db->query($sql);
	}
	
	function delete_all($conditions) {
		$db = db::get_db();
		
		$sql = "DELETE FROM ".$db->escape_identifier(this::call('table_name'))." WHERE ".$conditions;
		
		$db->query($sql);
		
	}
	
	/**
	 * Returns the number of rows in that meet the given condition 
	 *
	 * @return int
	 */
	function count($conditions = '1 = 1') {
		$db = db::get_db();
		
		$sql = "SELECT COUNT(*) FROM ".$db->escape_identifier(this::call('table_name'))." WHERE ".$conditions;
		
		return this::call('count_by_sql', $sql);
	}
	
	function count_by_sql($sql) {
		$db = db::get_db();
		
		$result = $db->query_array($sql);
		
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
		$db = db::get_db();
		
		$query = new SelectQueryBuilder($db->escape_identifier(this::call('table_name')));
		
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

	/**
	 * Returns the name of the table used for persisting this class
	 *
	 * @static 
	 * @return string
	 */
	function table_name() {
		
		$table = this::get_var('table');
		
		if ($table) {
			return $table;	
		} else {
			return this::call('_get_type');
		}
		
	}
	
	
	/**
	 * Returns the type of the class. i.e. if the class is PageModel, returns page
	 *
	 * @return string
	 */
	function _get_type() {
		return Inflector::underscore(str_replace('model', '', class_name()));

	}	
	
	/**
	 * @return string
	 */
	function to_param()	{
		return $this->get_id();
		
	}
	
	/**
	 * Returns a string representation of the object
	 *
	 * @return string
	 */
	function to_string() {
		return $this->get($this->name_field);
		
	}
	
	/**
	 * Gets the id of the object
	 *
	 * @return int
	 */
	function get_id() {
		return $this->id;	
		
	}

	/**
	 * Sets the id of the object
	 *
	 * @param mixed $id
	 */
	function set_id($id) {
		$this->id = $id;
	}
	
	/**
	 * Returns the value of the autonumber field, which is usually the same as the id
	 *
	 * @return int
	 */
	function sequence_value() {
		return $this->data[$this->sequence_field];	
	}

	/**
	 * Returns the path where files for a given field should be uploaded. Depends on the
	 * presence of the constant UPLOAD_PATH.
	 *
	 * @param string $field
	 * @return string
	 */
	function upload_path($field) {
		if (!defined('UPLOAD_PATH')) {
			trigger_error("UPLOAD_PATH is not defined in the config file", E_USER_ERROR);
		}
		
		$path = UPLOAD_PATH.$this->_get_type().'/'.$field.'/';
		
		if (!file_exists($path)) {
			trigger_error("Upload path '$path' doesn't exist", E_USER_ERROR);
		}
		
		return $path;
	}
	
	/**
	 * Returns the filename to store for an upload
	 *
	 * @param string $file_name
	 * @param string $field
	 * @return string
	 */
	function upload_file_name($file_name, $field = null) {
		return $file_name;
	}	
	
	/**
	 * Updates the record in the database
	 *
	 * @return bool
	 */
	function update() {
		if (!$this->validate()) {
			return false;
		}
		
		if (!$this->validate_on_update()) {
			return false;
		}
		
		foreach ($this->columns as $column) {
			if (isset($this->data[$column->name])) {
				$fields[] = $column->name." = '".$this->db->escape($this->data[$column->name])."'";
			}
		}
		
		assert(isset($fields));
		
		$this->sql = "UPDATE ".$this->db->escape_identifier($this->table)." SET ".implode(", ", $fields).$this->where_this();

		return $this->db->query($this->sql);
	}
	
	/**
	 * Inserts the record into the database
	 *
	 * @return bool
	 */
	function insert() {
		if (!$this->validate()) {
			return false;
		}
		if (!$this->validate_on_update()) {
			return false;
		}
		
		// sequence field needs to be empty
		unset($this->data[$this->sequence_field]);
		
		
		foreach ($this->columns as $column) {
			if (isset($this->data[$column->name])) {
				$fields[] = $field_name;
				$values[] = "'".$this->db->escape($this->data[$column->name])."'";
			}
		}

		assert(isset($fields));
		
		$this->sql = "INSERT INTO ".$this->db->escape_identifier($this->table)." (".implode(", ", $fields).") VALUES (".implode(", ", $values).")";
	

		if ($this->db->query($this->sql)) {
			if ($this->sequence_field != $this->id_field) {
				$this->data[$this->sequence_field] = $this->db->insert_id($this->table, $this->sequence_field);
			} else {
				$this->set_id($this->db->insert_id($this->table, $this->id_field));
			}
			return true;
			
		} else {
			return false;
			
		}
	}
	
	/**
	 * Persists the current object to the database
	 *
	 * This method simply calls the update or the insert methods, depending on whichever
	 * is more appropriate
	 *
	 * @return bool
	 */
	
	function save() {
		if (!is_null($this->id) && $this->id != '') {
			return $this->update();
		} else {
			return $this->insert();
		}	
	}
	
	/**
	 * Deprecated, use destro instead
	 *
	 */
	function delete() {
		$this->destroy();
		
	}
	
	/**
	 * Deletes the item, either by removing it from the database, or
	 * setting its deleted flag to true
	 *
	 * @return bool
	 */
	function destroy() {

		$this->remove_dependents();
		
		if (isset($this->deleted_field)) {
			$query = "UPDATE ".$this->table." SET ".$this->deleted_field." = '1'".$this->where_this();	
			
		} else {
			$query = "DELETE FROM ".$this->table.$this->where_this();
			
		}
				
		return $this->db->query($query);
	}

	/**
	 * Removes an item's dependents, either by destroying each in turn, deleting them directly, or nulling their foreign keys
	 *
	 * @return bool
	 */
	function remove_dependents() {
		/** todo
		foreach($this->has_many_data as $key => $value) {
			debug($key, $value);
			
		}		

		foreach($this->has_one_data as $key => $value) {
			debug($key, $value);	
		}
		*/
		
		return true;
	}
	
	/**
	 * Sets deleted flag to false
	 *
	 */
	function undelete() {
		$query = "UPDATE ".$this->table." SET ".$this->deleted_field." = '0'".$this->where_this();	
		
		$this->db->query($query);		
		
	}	
	
	/**
	 * Returns a where part of a query
	 *
	 * @return string
	 */
	function where_this($id = null) {
		if (!$id) {
			$id = $this->id;
		}
		
		return " WHERE ".$this->id_field." = '".$this->db->escape($id)."'";
	}

	
	/**
	 * Generates debug dump of the data contained
	 *
	 * @return array
	 */
	function dump_data() {

		if ($this->id_field) {
			$return = array_merge(array($this->id_field=>$this->id), $this->data);
		} else {
			$return = $this->data;
		}
		
		return $return;
	}
	
	/**
	 * @return bool
	 */
	function validate() {
		return true;	
	}

	/**
	 * @return bool
	 */
	function validate_on_create() {
		return $this->validate->run($this->data, true);
	}

	/**
	 * @return bool
	 */
	function validate_on_update() {
		return $this->validate->run($this->data, false);
	}
	
}



/**
 * Ietaror for collections of models
 *
 * @package model
 */
class ModelIterator extends Iterator {
	
	/**
	 * @var Iterator
	 */
	var $iterator;
	
	/**
	 * @var string
	 */
	var $model_type;
	
	/**
	 * Constructor
	 *
	 * @param Iterator $iterator
	 * @param string $model_type
	 * @return ModelIterator
	 */
	function ModelIterator($iterator, $model_type) {
		$this->iterator = $iterator;
		$this->model_type = $model_type;
	}
	
	/**
	 * @return int
	 */
	function size() {
		return $this->iterator->size();	
	}
	
	/**
	 * @return bool
	 */
	function has_next() {
		return $this->iterator->has_next();
	}
	
	/**
	 * @return Model
	 */
	function next() {
		if (!$this->iterator->has_next()) {
			return false;	
		}
						
		$model = Model::factory($this->model_type);
		$model->assign($this->iterator->next());
		return $model;
	}
}
