<?php

/**
 * part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package model
 */


seed_include('library/iterator');
seed_include('library/inflector');
seed_include('model/associations');
seed_include('db');


/**
 * Factory for model objects
 *
 */
class ModelFactory {

	/**
	 * An array of key-value pairs, where the key is a type, and the value is the file for that type
	 *
	 * @var array
	 */
	var $mappings;
	
	/**
	 * Constructor
	 *
	 * @return ModelFactory
	 */
	function ModelFactory() {
		$this->mappings = array();	
	}
	
	function register($type, $path) {
		$this->mappings[$type] = $path;	
	}	
	
	/**
	 * Singleton method
	 *
	 * @static 
	 * @return ModelFactory
	 */
	function & get_instance() {
		static $instances;
		
		if (!isset($instances[0])) {
			$instances[0] = new ModelFactory();	
		}
		
		return $instances[0];
		
	}	
	
	/**
	 * Loads the file for the given type of controller.
	 *
	 * @param string $type
	 * @param bool $ignore_errors
	 * @return bool
	 */
	function import($type, $ignore_errors = false) {
		$type = strtolower($type);
		
		if (isset($this->mappings[$type])) {
			$path = $this->mappings[$type];
		} else {
			$path = MODEL_PATH.$type.".php";
		}		
		
		if (!file_exists($path) && !$ignore_errors) {
			trigger_error("File for model type '$type' not found in '$path'", E_USER_ERROR);
			return false;
		}
		
		require_once($path);

		return true;	
	
	}

	function & finder($type, $db = null) {
		
		if (is_null($db)) {
			$db = DB::get_db();	
		}
		
		$class_name = Inflector::camelize($type).'Finder';
		
		if (!class_exists($class_name)) {
			ModelFactory::import($type);
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
	 * Factory for models
	 *
	 * @param string $type
	 * @return Model
	 */
	function & model($type) {
		
		$class_name = Inflector::camelize($type).'Model';
		
		if (!class_exists($class_name)) {
			ModelFactory::import($type);
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
	
	
}


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

	function find_associated() {
		return null;	
	}
	
	function set_associated() {
		return false;	
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
		
		$result = $this->find_associated($field);

		if (!is_null($result)) {
			return $result;
		}
		
		if (isset($this->data[$field])) {
			return $this->data[$field]; 
		}	
		
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
	 * Returns an array of attributes that should be read only
	 */
	function protected_attributes() {
		return array();
			
	}
	
	function cast($field, $value) {
		return $value;	
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
				continue;
			}
			
			if (!in_array($field, $this->protected_attributes()) && $this->field_exists($field) ) {
				$value = $this->cast($field, $value);
				
				if (method_exists($this, 'set_'.$field)) {
					call_user_func(array(& $this, 'set_'.$field), $value);
				} else if (!$this->set_associated($field, $value)) {
					$this->data[$field] = $value;
				}
			}
		}
		
		return true;
	}

	function to_array() {
		return $this->data;	
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
	 * The field to use as the name/title of the model
	 *
	 * @var string
	 */
	var $name_field = 'name';
	
	/**
	 * The field used for single table inheritance
	 *
	 * @var string
	 */
	var $inheritance_field = '';
	
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
	 * If this field is present, it will be set to the current timestamp when a record is created
	 *
	 * @var string
	 */
	var $created_at_field = 'created_at';
	
	/**
	 * If this field is present, it will be set to the current timestamp when a record is updated
	 *
	 * @var string
	 */
	var $updated_at_field = 'updated_at';
	
	/**
	 * An array containing all association data
	 *
	 * @var array
	 */
	var $associations = array();
	
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
		
		// grab columns and asign default values
		$this->columns = $this->db->columns($this->table);
		
		foreach($this->columns as $column) {
			$this->data[$column->name] = $column->default;
		}		
		
		// set the inheritance field value
		if ($this->inheritance_field && isset($this->columns[$this->inheritance_field])) {
			$this->data[$this->inheritance_field] = $this->type;
		}
		
		// setup validator
		$this->validate = & new Validation($this->type);
		$this->validate->model = & $this;
		$this->setup();
		
	}

	function columns() {
		return $this->columns;	
	}
	
	/* not ready to implement yet
	function protected_attributes() {
		return array($this->sequence_field, $this->inheritance_field());
			
	}	
	*/
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
	 * Casts a value to the right type for a given field
	 *
	 * @param string $field  The name of the field
	 * @param mixed $value	 The value to cast
	 * @return mixed		 The cast value
	 */
	function cast($field, $value) {
		// if the column doesn't exist, don't cast the value
		if (!isset($this->columns[$field])) {
			return $value;	
		}
		
		// precast arrays
		if (is_array($value)) {
			$value = $this->columns[$field]->array_to_type(array_values($value));			
		}
		
		// cast value
		$value = $this->columns[$field]->type_cast($value);
	
		return $value;	
		
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
		
		$this->associations[$field] = &new BelongsToAssociation($this, $field, $options);
		
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
		$this->associations[$field] = &new HasOneAssociation($this, $field, $options);		
		
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
		$this->associations[$field] = &new HasManyAssociation($this, $field, $options);
		
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
		$this->associations[$field] = &new HasAndBelongsToManyAssociation($this, $field, $options);
		
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
	
	/**
	 * Handle retrieving belongs_to associated fields
	 *
	 * @param string $field  The association to retrieve
	 * @param array $params  Additional params to pass, should generally be limited to sort, limit and offset
	 * @param bool $count	 If this is true, this will simply retrieve a count
	 * @return mixed
	 */
	function _handle_belongs_to($field, $params = null, $count = false) {
		if (!isset($this->belongs_to_data[$field])) {
			return null;
		}
			
		if (is_null($params)) {
			$params = array();	
		}		
		
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
			
			// order doesn't work for polymorphic classes, as they belong to several classes
			if (!isset($options['order'])) {
				$class = Model::factory($class_name);
				
				$options['order'] = $class->id_field.' ASC';	
			}			
			
		}
		
		$foreign_key = $options['foreign_key'];
		
		$finder = Finder::factory($class_name);

		$association_params = array(
			'conditions' => $finder->id_field()." = ".$this->data[$foreign_key]." AND ".$options['conditions'],
			'order' => $options['order']
		);
		
		$association_params = array_merge($association_params, $params);
		
		if ($count) {
			return $finder->count($association_params['conditions']);
		} else {
			return $finder->find('first', $association_params);
		}
		
	}
	
	/**
	 * Handle retrieving has_one associated fields
	 *
	 * @param string $field  The association to retrieve
	 * @param array $params  Additional params to pass, should generally be limited to sort, limit and offset
	 * @param bool $count	 If this is true, this will simply retrieve a count
	 * @return mixed
	 */
	function _handle_has_one($field, $params = null, $count = false) {
		if (!isset($this->has_one_data[$field])) {
			return null;
		}
		
		if (is_null($params)) {
			$params = array();	
		}		
		
		$options = $this->has_one_data[$field];
		
		if (!isset($options['order'])) {
			$options['order'] = null;	
		}
					
		$finder = Finder::factory($options['class_name']);
		
		
		$association_params =array(
			'conditions' => $options['foreign_key']." = ".$this->get_id()." AND ".$options['conditions'],
			'order' => $options['order']
		);
		
		$association_params = array_merge($association_params, $params);
		
		if ($count) {
			return $finder->count($association_params['conditions']);
		} else {
			return $finder->find('first', $association_params);
		}			
		

	}
	
	/**
	 * Handle retrieving has_many associated fields
	 *
	 * @param string $field  The association to retrieve
	 * @param array $params  Additional params to pass, should generally be limited to sort, limit and offset
	 * @param bool $count	 If this is true, this will simply retrieve a count
	 * @return mixed
	 */
	function _handle_has_many($field, $params = null, $count = false) {
		if (!isset($this->has_many_data[$field])) {
			return null;
		}
		
		if (is_null($params)) {
			$params = array();	
		}
		
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
			
			if (isset($join_model->has_many_data[$field])) {
				$join = $join_model->table.' ON '.$finder->table_name().'.'.$join_model->type.'_id = '.$join_model->table.'.id';
			} else if (isset($join_model->belongs_to_data[$field])) {
				$join = $join_model->table.' ON '.$finder->table_name().'.id = '.$join_model->table.'.'.$finder->table_name().'_id';
			}

			$association_params = array(
				'select' => $select,
				'joins' => $join,
				'conditions' => $condition." AND ".$options['conditions'],
				'order' => $options['order']
			);
			
		} else {
		
			$association_params = array(
				'conditions' => $condition." AND ".$options['conditions'],
				'order' => $options['order']
			);		
			
		}

		$association_params = array_merge($association_params, $params);
		
		if ($count) {
			$result = $finder->count($association_params['conditions']);
		} else {
			$result = $finder->find('all', $association_params);	
		}		
		
		return $result;
		
	}
	
	/**
	 * Handle retrieving has_and_belongs_to associated fields
	 *
	 * @param string $field  The association to retrieve
	 * @param array $params  Additional params to pass, should generally be limited to sort, limit and offset
	 * @param bool $count	 If this is true, this will simply retrieve a count
	 * @return mixed
	 */
	function _handle_has_and_belongs_to_many($field, $params = null, $count = false) {
		if (!isset($this->has_and_belongs_to_many_data[$field])) {
			return null;	
		}
		
		if (is_null($params)) {
			$params = array();	
		}		
		
		$options = $this->has_and_belongs_to_many_data[$field];
		
		if (!isset($options['order'])) {
			$options['order'] = null;	
		}
					
		$finder = Finder::factory($options['class_name']);
		
		$association_params = array(
			'conditions' => $options['foreign_key']." = ".$this->get_id()." AND ".$options['conditions'],
			'joins' => $options['join_table'].' ON '.$options['join_table'].'.'.$options['association_foreign_key'].' = '.$finder->table_name().'.'.$finder->id_field(),
			'order' => $options['order']
		);	

		$association_params = array_merge($association_params, $params);
		
		if ($count) {
			return $finder->count($association_params['conditions']);
		} else {
			return $finder->find('all', $association_params);	
		}		
		
	}	
	
	function set_associated($field, $value) {
		if (isset($this->associations[$field])) {
			return $this->associations[$field]->set($this, $field, $value);
		}		
		
	}
	
	/**
	 * Allows retrieving associated models with additional params
	 *
	 * @param string $field
	 * @param array $parms
	 * @return mixed  Returns a single model or a collection, depending on the type of association. Will return false if the
	 * association exists but no records are being returned, and will return null if the association doesn't exist
	 */
	function find_associated($field, $params = null) {
		if (isset($this->associations[$field])) {
			return $this->associations[$field]->get($this, $params);	
		}
/*		
		if (isset($this->belongs_to_data[$field])) {
			return $this->_handle_belongs_to($field, $params);
		}
		
		if (isset($this->has_one_data[$field])) {
			return $this->_handle_has_one($field, $params);
		}

		if (isset($this->has_many_data[$field])) {
			return $this->_handle_has_many($field, $params);
		}
		
		if (isset($this->has_and_belongs_to_many_data[$field])) {
			return $this->_handle_has_and_belongs_to_many($field, $params);
		}
*/
		return null;
	}
	
	/**
	 * Allows counting of associated models with additional params
	 *
	 * @param string $field
	 * @param array $parms
	 * @return mixed  Returns a single model or a collection, depending on the type of association. Will return false if the
	 * association exists but no records are being returned, and will return null if the association doesn't exist
	 */
	function count_associated($field, $params = null) {
		if (isset($this->associations[$field])) {
			return $this->associations[$field]->get($this, $params, true);	
		}
/*		
		if (isset($this->belongs_to_data[$field])) {
			return $this->_handle_belongs_to($field, $params, true);
		}
		
		if (isset($this->has_one_data[$field])) {
			return $this->_handle_has_one($field, $params, true);
		}

		if (isset($this->has_many_data[$field])) {
			return $this->_handle_has_many($field, $params, true);
		}
		
		if (isset($this->has_and_belongs_to_many_data[$field])) {
			return $this->_handle_has_and_belongs_to_many($field, $params, true);
		}
*/
		return null;
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
		
		return $factory->model($type);
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
	 * Returns the name of the inheritance field
	 *
	 * @return string
	 */
	function inheritance_field() {
		return $this->inheritance_field;	
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
		
		if (!$this->before_update()) {
			return false;	
		}
		
		// set the updated time if the field is present
		if ($this->updated_at_field && isset($this->columns[$this->updated_at_field])) {
			$this->data[$this->updated_at_field] = now();
		}			
		
		foreach ($this->columns as $column) {
			if (isset($this->data[$column->name])) {
				$fields[] = $this->db->escape_identifier($column->name)." = '".$this->db->escape($this->data[$column->name])."'";
			}
		}
		
		assert(isset($fields));
		
		$this->sql = "UPDATE ".$this->db->escape_identifier($this->table)." SET ".implode(", ", $fields).$this->where_this();

		$result = $this->db->query($this->sql);
		
		$this->after_update();
		
		return $result;
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
		
		if (!$this->before_create()) {
			return false;	
		}
		
		// sequence field needs to be empty
		unset($this->data[$this->sequence_field]);
		
		// set the updated time if the field is present
		if ($this->created_at_field && isset($this->columns[$this->created_at_field])) {
			$this->data[$this->created_at_field] = now();
		}		
		
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
			
			$this->after_create();
			
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
		
		if (!$this->before_save()) {
			return false;	
		}
		
		if ($this->is_new_record()) {
			$result = $this->insert();
		} else {
			$result = $this->update();
		}

		$this->after_save();
		
		return $result;
		
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
	 * Deletes the item, either by removing it from the database, or
	 * setting its deleted flag to true
	 *
	 * @return bool
	 */
	function destroy() {
		if (!$this->before_destroy()) {
			return false;	
		}
		
		$this->remove_dependents();
		
		if (isset($this->deleted_field)) {
			$query = "UPDATE ".$this->db->escape_identifier($this->table)." SET ".$this->deleted_field." = '1'".$this->where_this();	
			
		} else {
			$query = "DELETE FROM ".$this->db->escape_identifier($this->table).$this->where_this();
			
		}
				
		$result = $this->db->query($query);
		
		$this->after_destroy();
		
		return $result;
		
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
	 * Converts the model to an array
	 *
	 * @return array
	 */
	function to_array() {
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
	
	// Callbacks
	
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
	 * Called before create
	 *
	 * @return bool
	 */
	function before_create() {
		return true;	
	}
	
	/**
	 * Called after create
	 *
	 * @return bool
	 */
	function after_create() {
		return true;
	}
	
	/**
	 * Called before update
	 *
	 * @return bool
	 */
	function before_update() {
		return true;
	}
	
	/**
	 * Called after update
	 *
	 * @return bool
	 */
	function after_update() {
		return true;
	}
	
	/**
	 * Called before destroy
	 *
	 * @return bool
	 */
	function before_destroy() {
		return true;
	}

	/**
	 * Called after destroy
	 *
	 * @return bool
	 */
	function after_destroy() {
		return true;
	}			
	
	// Deprecated methods; these have been replaced, but are kept for backwards compatibility
	
	/**
	 * Deprecated, use to_array() instead
	 *
	 * @return array
	 */
	function dump_data() {
		return $this->to_array();

	}	
	
	/**
	 * Deprecated, use destroy instead
	 *
	 * @return bool
	 */
	function delete() {
		return $this->destroy();
		
	}	
	
	// PHP5 magic methods
	function __toString() {
		return $this->to_string();	
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

	/**
	 * Resets the iterator to the beginning
	 */
	function reset() {
		return $this->iterator->reset();
	
	}
	
	/**
	 * Returns an array of values of a given field
	 *
	 * @param string $field  The field to return as values. Defaults to the name field.
	 * @return array
	 */
	function to_name_array($value_field = null, $key_field = null) {
		$result = array();
		
		while($option = $this->next()) {
			if (is_null($value_field)) {
				$value_field = $option->name_field;	
			}
			
			if (is_null($key_field)) {
				$key_field = 'id';
			}
			
			$result[$option->get($key_field)] = $option->get($value_field);	
		}
		
		$this->reset();
		
		return $result;		
		
	}
	
}

?>