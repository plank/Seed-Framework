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
		} else {
			$this->data = array();	
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

			if (!isset($options['order'])) {
				$options['order'] = null;	
			}			

			
			if (!key_exists($options['foreign_key'], $this->data)) {
				return null;	
			}
			
			if ($options['polymorphic']) {
				$class_name = $this->data[$field.'_type'];
			} else {
				$class_name = $options['class_name'];
			}
			
			$foreign_key = $options['foreign_key'];
			
			$finder = Finder::factory($class_name);

			return $finder->find('first', array(
				'conditions' => $finder->id_field()." = ".$this->data[$foreign_key]." AND ".$options['conditions'],
				'order' => $options['order']
			));
			
		}
		
		if (isset($this->has_one_data[$field])) {
			$options = $this->has_one_data[$field];
			
			if (!isset($options['order'])) {
				$options['order'] = null;	
			}
						
			$finder = Finder::factory($options['class_name']);
			
			return $finder->find('first', array(
				'conditions' => $options['foreign_key']." = ".$this->get_id()." AND ".$options['conditions'],
				'order' => $options['order']
			));			
			
		}

		
		if (isset($this->has_many_data[$field])) {
			
			$options = $this->has_many_data[$field];
			
			if (!isset($options['order'])) {
				$options['order'] = null;	
			}
						
			$finder = Finder::factory($options['class_name']);
			
			// choose the table the conditions will be applied to
			if ($options['through']) {
				$join_model = Model::factory($options['through']);
				
				$table_name = $join_model->table;
					
			} else {
				$table_name = $finder->table_name();	
				
			}
			
			$condition = $table_name.'.'.$options['foreign_key']." = ".$this->get_id();
			
			if ($options['as']) {
				$condition .= ' AND '.$table_name.'.'.$options['as']."_type = '".$this->type."'";
			}
			
			if ($options['through']) {
				$select = $finder->table_name().'.*';
				
				$join = $join_model->table.' ON '.$finder->table_name().'.'.$join_model->type.'_id = '.$join_model->table.'.id';

				return $finder->find('all', array(
					'select' => $select,
					'joins' => $join,
					'conditions' => $condition." AND ".$options['conditions'],
					'order' => $options['order']
				));
				
			} else {
			
				return $finder->find('all', array(
					'conditions' => $condition." AND ".$options['conditions'],
					'order' => $options['order']
				));		
			
			}	
			
		}
		
		if (isset($this->has_and_belongs_to_many_data[$field])) {
			
			$options = $this->has_and_belongs_to_many_data[$field];
			
			if (!isset($options['order'])) {
				$options['order'] = null;	
			}
						
			$finder = Finder::factory($options['class_name']);
			
			return $finder->find('all', array(
				'conditions' => $options['foreign_key']." = ".$this->get_id()." AND ".$options['conditions'],
				'joins' => $options['join_table'].' ON '.$options['join_table'].'.'.$options['association_foreign_key'].' = '.$finder->table_name().'.'.$finder->id_field(),
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
						$value = $this->columns[$field]->array_to_type(array_values($value));			
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
		
		$this->validate = & new Validation($this->type);
		$this->validate->model = & $this;
		$this->setup();
		
	}

	/**
	 * Returns the associated finder for this model
	 *
	 * @return Finder
	 */	
	function & finder() {
		$finder = & Finder::factory($this->_get_type());	
		
		return $finder;
	}
	
	/**
	 * Returns the current model version
	 *
	 * @return int
	 */
	function version() {
		return 2;	
	}
	
	function setup() {
	
	}
	
	/**
	 * Adds a 1-to-1 or n-to-1 relation to another class, depending on the corresponding
	 * relationship on the other class
	 * 
	 * @param string $field
	 * @param array $options
	 * Options are:
	 *   class_name
	 *   conditions
	 *   polymorphic
	 *   foreign_key
	 *	 order
	 * @return Model
	 */	
	function belongs_to($field, $options = null) {
		
		if (is_null($options)) {
			$options = array();	
		}
		
		if (!isset($options['class_name'])) {
			$options['class_name'] = $field;	
		}
		
		if (!isset($options['conditions'])) {
			$options['conditions'] = '1 = 1';
		}
		
		if (!isset($options['polymorphic'])) {
			$options['polymorphic'] = false;
		}

		if ($options['polymorphic']) {
			if (!isset($this->columns[$field.'_id']) || !isset($this->columns[$field.'_type'])) {
				trigger_error("Required fields for polymorphic association '$field' missing", E_USER_WARNING);
				return false;
			}
			
			$options['foreign_key'] = $field.'_id';
			
		} else {
			// order doesn't work for polymorphic classes, as they belong to several classes
			if (!isset($options['order'])) {
				$class = model::factory($options['class_name']);
				
				$options['order'] = $class->id_field.' ASC';	
			}
			
			if (!isset($options['foreign_key'])) {
				$options['foreign_key'] = Inflector::underscore($options['class_name']).'_id';
			}	
				
		}
		
		$this->belongs_to_data[$field] = $options;	
		
	}
	
	/**
	 * Adds a 1-1 relation to another class
	 *
	 * @param string $field
	 * @param array $options
	 * Options are:
	 *  class_name
	 *  conditions
	 *  order
	 *  foreign_key
	 *  dependant
	 * @return Model
	 */
	function has_one($field, $options = null) {
		if (is_null($options)) {
			$options = array();
		}
		
		if (!isset($options['class_name'])) {
			$options['class_name'] = $field;	
		}
		
		if (!isset($options['conditions'])) {
			$options['conditions'] = '1 = 1';
		}
		
		if (!isset($options['order'])) {
			$options['order'] = 'id ASC';	
		}
		
		if (!isset($options['foreign_key'])) {
			$options['foreign_key'] = $this->table_name().'_id';
		}
		
		if (!isset($options['dependent'])) {
			$options['dependent'] = false;
		}
		
		$this->has_one_data[$field] = $options;	
	}
	
	/**
	 * Adds a 1-n relation to another class
	 *
	 * @param string $field
	 * @param array $options
	 * Options are:
	 *   class_name
	 *   conditions
	 *   order
	 *   group
	 *   foreign_key
	 *	 dependent
	 *	 as
	 * @return ModelIterator
	 */
	function has_many($field, $options = null) {
		if (is_null($options)) {
			$options = array();
		}
		
		if (!isset($options['class_name'])) {
			$options['class_name'] = $field;
		}
		
		if (!isset($options['conditions'])) {
			$options['conditions'] = '1 = 1';
		}
		
		if (!isset($options['order'])) {
			$options['order'] = 'id ASC';	
		}
		
		if (!isset($options['dependent'])) {
			$options['dependent'] = false;
		}
		
		if (!isset($options['as'])) {
			$options['as'] = false;	
			
			if (!isset($options['foreign_key'])) {
				$options['foreign_key'] = $this->table_name().'_id';
			}
			
		} else {
			$options['foreign_key'] = $options['as'].'_id';
			
		}
	
		if (!isset($options['through'])) {
			$options['through'] = false;	
		}
		
		$this->has_many_data[$field] = $options;	 
		
	}
	
	/**
	 * Adds an n-to-n relationship to another class.
	 *
	 * @param string $field
	 * @param array $options
	 * Options are:
	 *  class_name
	 *  join_table
	 *  foreign_key
	 *  association_foreign_key
	 *  conditions
	 *  order
	 * @return ModelIterator
	 */
	function has_and_belongs_to_many($field, $options = null) {
		if (is_null($options)) {
			$options = array();
		}
		
		if (!isset($options['class_name'])) {
			$options['class_name'] = $field;
		}
		
		if (!isset($options['conditions'])) {
			$options['conditions'] = '1 = 1';
		}
		
		if (!isset($options['order'])) {
			$options['order'] = 'id ASC';	
		}
		
		if (!isset($options['foreign_key'])) {
			$options['foreign_key'] = $this->table_name().'_id';
		}
		
		if (!isset($options['association_foreign_key'])) {
			$options['association_foreign_key'] = $field.'_id';
		}
		
		if (!isset($options['join_table'])) {
			if (strcasecmp($field, $this->table_name()) <= 0) {
				$options['join_table'] = $field.'_'.$this->table_name();
			} else {
				$options['join_table'] = $this->table_name().'_'.$field;			
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
	function & factory($type) {
		
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
	 * Returns the name of the table used for persisting this class
	 *
	 * @return string
	 */
	function table_name() {
		if ($this->table) {
			return $this->table;	
		} else {
			return $this->_get_type();
		}
		
	}
	
	
	/**
	 * Returns the type of the class. i.e. if the class is PageModel, returns page
	 *
	 * @return string
	 */
	function _get_type() {
		return Inflector::underscore(str_replace('model', '', strtolower(get_class($this))));

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
				$fields[] = $this->db->escape_identifier($column->name)." = '".$this->db->escape($this->data[$column->name])."'";
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
				$fields[] = $this->db->escape_identifier($column->name);
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
		$this->before_save();
		
		if (!is_null($this->id) && $this->id != '') {
			return $this->update();
		} else {
			return $this->insert();
		}
		
		$this->after_save();
		
	}
	
	/**
	 * Returns true if the record is new
	 *
	 * @return bool
	 */
	function is_new_record() {
		return (is_null($this->id) || $this->id == '');
	}
	
	/**
	 * Called before saves
	 *
	 * @return bool
	 */
	function before_save() {
		return true;	
	}
	
	/**
	 * Called after saves
	 *
	 * @return bool
	 */
	function after_save() {
		return true;	
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
			$query = "UPDATE ".$this->db->escape_identifier($this->table)." SET ".$this->deleted_field." = '1'".$this->where_this();	
			
		} else {
			$query = "DELETE FROM ".$this->db->escape_identifier($this->table).$this->where_this();
			
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
			$return = array_merge($this->data, array($this->id_field=>$this->id));
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
		return $this->validate->run($this->dump_data(), true);
	}

	/**
	 * @return bool
	 */
	function validate_on_update() {
		return $this->validate->run($this->dump_data(), false);
	}
	
}



/**
 * Ietaror for collections of models
 *
 * @package model
 */
class ModelIterator extends SeedIterator {
	
	/**
	 * @var SeedIterator
	 */
	var $iterator;
	
	/**
	 * @var string
	 */
	var $model_type;
	
	/**
	 * Constructor
	 *
	 * @param SeedIterator $iterator
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
		$this->position ++;
		$model = Model::factory($this->model_type);
		$model->assign($this->iterator->next());
		return $model;
	}
	
	function reset() {
		return $this->iterator->reset();
	
	}
}
