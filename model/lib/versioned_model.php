<?php

/**
 * First attempt at a versioned model
 */

class VersionedModel extends Model {
	
	/**
	 * @var Model
	 */
	var $version;
	
	/**
	 * @var string
	 */
	var $version_table = null;

	/**
	 * @var string
	 */
	var $version_field = 'revision';
	
	/**
	 * @var string
	 */
	var $last_version_field = 'last_revision';
	
	function VersionedModel($db = null) {
		parent::Model($db);
		
		$this->version = Model::factory($this->type.'_version');
		
		$this->has_many('versions', array('class_name' => $this->type.'_version', 'order'=>'revision'));
		
	}	
	
	/**
	 * Adds a join to the versions table to the finder sql construction
	 *
	 * @param array $options
	 * @return string
	 */
	function construct_finder_sql($options) {
		// get field and table names
		$table_name = this::call('table_name');
		$version_table_name = this::call('version_table_name');
		$id_field = this::get_var('id_field');
		$foreign_key = this::call('foreign_key');
		$version_field = this::get_var('version_field');
		
		if (isset($options['version_field'])) {
			$last_version_field = $options['version_field'];
			
		} else {
			$last_version_field = this::get_var('last_version_field');
			
		}
		
		// add the join to the version table
		$options['joins'] = "$version_table_name ON $table_name.$id_field = $version_table_name.$foreign_key";
		
		// this may be optional... needs to be tested thoroughly
		$options['group'] = "$table_name.$id_field";
		
		
		if (!isset($options['select'])) {
			$options['select'] = "$table_name.*, $version_table_name.*";	
		}
		
		// a special condition string used to identify which revision to pull
		if (!isset($options['version_conditions'])) {
			$options['version_conditions'] = "$table_name.$last_version_field = $version_table_name.$version_field";
		}
		
		// original function, which needs to be called from here for 'this' to work... refactoring in sight! //
		
		$query = new SelectQueryBuilder(this::call('table_name'));
		
		if (isset($options['select'])) {
			$query->add_fields($options['select']);
			
		}
		
		if (isset($options['joins'])) {
			$query->add_join_string($options['joins']);
		}
		
		if (isset($options['conditions'])) {
			$query->add_conditions($options['conditions']);	
		}

		if (isset($options['version_conditions'])) {
			$query->add_conditions($options['version_conditions']);
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

//		debug($query->generate());
		
		return $query->generate();
		
	}
	

	/**
	 * Returns true if the field is set i.e. has a value other than null
	 *
	 * @param mixed $field
	 * @return bool
	 */
	function is_set($field) {
		return $this->version->is_set($field) || parent::is_set($field);
		
	}
	
	/**
	 * Returns the name of the table used for keeping versions of this class
	 *
	 * @static 
	 * @return string
	 */
	function version_table_name() {
		
		$table = this::get_var('version_table');
		
		if ($table) {
			return $table;	
		} else {
			return this::call('_get_type').'_versions';
		}
		
	}
	
	/**
	 * Returns the name of foreign key on the table used for keeping versions of this class
	 *
	 * @static 
	 * @return string
	 */	
	function foreign_key() {
		$foreign_key = this::get_var('foreign_key');
		
		if ($foreign_key) {
			return $foreign_key;	
		} else {
			return this::call('_get_type').'_id';
		}			
		
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
					
				} elseif ($this->version->field_exists($field)) {
					if (is_array($value)) {
						$value = implode('-', $value);
					}

					if (method_exists($this->version, 'set_'.$field)) {
						call_user_func(array(& $this->version, 'set_'.$field), $value);
					} else {
						$this->version->data[$field] = $value;
					}					
				}
			}
		}
		
		return true;
	}	
	
	/**
	 * Generic getter method. First checks for the existance of a method called get_$field
	 * (i.e. if field = 'title', looks for get_title) and calls it if found, if not
	 * it returns the element of the data array that contains the key 'title'
	 *
	 * @param string $field
	 * @return mixed
	 */
	function get($field) {
		
		if(!is_string($field)) {
			trigger_error("Parameter for model->get() must be a string", E_USER_WARNING);
			return false;
			
		}
		
		if (method_exists($this, 'get_'.$field)) {
			$args = func_get_args();
			array_shift($args);
			
			return call_user_func_array(array(& $this, 'get_'.$field), $args);
			
		} 
		
		if ($this->field_exists($field)) {
			return parent::get($field); 
			
		} else {
			return $this->version->get($field);	

		}
		
		return false;
	}
	
	/**
	 * Updates the model by inserting a new versions
	 */
	function update() {
		$this->version->insert();
		$this->set($this->last_version_field, $this->version->sequence_value());
		return parent::update();
	}
	
	function insert() {
		$this->set($this->last_version_field, 1);
		parent::insert();
		
		$this->version->set($this->foreign_key(), $this->get_id());
		$this->version->insert();
		
		return true;
	}
	
	function delete() {
		if (!$this->deleted_field) {
			// delete all the versions		
			$sql = "DELETE FROM ".$this->version_table_name()." WHERE ".$this->foreign_key()." = ".$this->get_id();
			$this->db->query($sql);
		}
		
		return parent::delete();
				
	}
	
	function mark($type, $revision = null) {
		$type .= '_revision';
		
		if (!$this->field_exists($type)) {
			return false;
		}
		
		if (is_null($revision)) {
			$revision = $this->get($this->last_version_field);			
		} elseif (!is_numeric($revision)) {
			$revision = $this->get($revision.'_revision');	
		}
		
		$this->set($type, $revision);
		return parent::update();
		
	}
	
}

class VersionModel extends Model {
	var $sequence_field = 'revision';
	var $parent_field = '';
	
	
	function parent_field() {
		if ($this->parent_field) {
			return $this->parent_field;
			
		} else {
			return str_replace('version', '', $this->_get_type().'_id');

		}	
		
	}
	
	function where_this() {
		return " WHERE ".$this->sequence_field." = ".$this->data[$this->sequence_field]." AND ".$this->parent_field()." = ".$this->data[$this->parent_field()];
	
	}
	
	
}

?>