<?php

/**
 * part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package model
 * @subpackage associations
 */

// class that will need to be fixed to support this:
// controller/scaffolding
// model/model & test
// view/form
//
// Running into reference issues passing model into constructor, need to look into this more closesly

/**
 * Base class for modelling associations
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package model
 * @subpackage associations
 */ 
 
class ModelAssociation {
	/**
	 * The Model this association belongs to
	 *
	 * @var Model
	 */
	var $model;
	
	/**
	 * A string describing the type of associtaion
	 *
	 * @var string
	 */
	var $type;
	
	/**
	 * The name of the field this association is tied to
	 *
	 * @var string
	 */
	var $field;
	
	/**
	 * @var string
	 */
	var $class_name;
	
	/**
	 * @var string
	 */	
	var $foreign_key;	
	
	/**
	 * @var string
	 */	
	var $conditions = '1 = 1';
	
	/**
	 * @var string
	 */	
	var $order = 'id ASC';

	/**
	 * @var string
	 */
	var $find_type = 'all';
	
	/**
	 * Sets all the properties with the given array of options
	 *
	 * @param array $options
	 */
	function set_properties($options) {
		foreach ($options as $key => $value) {
			$this->$key = $value;
			
		}	
	}
	
	/**
	 * Returns an array of parameters for the finder
	 *
	 * @return array
	 */
	function get_params() {
		return false;	
	}	
	
	/**
	 * Returns models as defined by the associtaion
	 *
	 * @param array $params  Additional params to pass, should generally be limited to sort, limit and offset
	 * @param bool $count	 If this is true, this will simply retrieve a count
	 * @return mixed
	 */
	function get(&$model, $params = null, $count = false) {
		
		$this->model = & $model;
		
		$association_params = $this->get_params();
		
		if (!$association_params) {
			return false;	
		}
		
		// merge in user params if present
		if (!is_null($params)) {
			$association_params = array_merge($association_params, $params);	
		}
		
		if ($count) {
			$result = $this->finder->count($association_params['conditions']);
		} else {
			$result = $this->finder->find($this->find_type, $association_params);	
			
			//debug($this->finder->db->last_query);
			
		}		
		
		return $result;
		
	}	
	
	/**
	 * Sets a new model to the association
	 *
	 * 
	 * @return bool
	 */
	function set(& $model, $field, $value) {
		return false;	
		
	}
}

/**
 * Base class for modelling belongs to associations
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package model
 * @subpackage associations
 */ 

class BelongsToAssociation extends ModelAssociation {
	var $type = 'belongs_to';
	
	var $polymorphic = false;	
	
	var $find_type = 'first';
	
	/**
	 * Constuctor
	 *
	 * @param string $field
	 * @param array $options;
	 * @return BelongsToAssociation
	 */
	function BelongsToAssociation(& $model, $field, $options = null) {
		$this->model = & $model;
		
		if (is_null($options)) {
			$options = array();	
		}
		
		if (!isset($options['class_name'])) {
			$options['class_name'] = $field;	
		}

		if (isset($options['polymorphic']) && $options['polymorphic']) {
			if (!isset($this->model->columns[$field.'_id']) || !isset($this->model->columns[$field.'_type'])) {
				trigger_error("Required fields for polymorphic association '$field' missing", E_USER_WARNING);
				return false;
			}
			
			$options['foreign_key'] = $field.'_id';
			
		} else {
			if (!isset($options['foreign_key'])) {
				$options['foreign_key'] = Inflector::underscore($options['class_name']).'_id';
			}	
				
		}		
		
		$this->field = $field;
		$this->set_properties($options);
		
	}
	
	/**
	 * Handle retrieving belongs_to associated fields
	 *
	 * @return array
	 */
	function get_params() {
		if (!key_exists($this->foreign_key, $this->model->data)) {
			return null;	
		}
		
		if ($this->polymorphic) {
			$class_name = $this->model->data[$this->field.'_type'];
		} else {
			$class_name = $this->class_name;
			
			// order doesn't work for polymorphic classes, as they belong to several classes
			if (!isset($this->order)) {
				$class = Model::factory($class_name);
				
				$this->order = $class->id_field.' ASC';	
			}			
			
		}
		
		$foreign_key = $this->foreign_key;

		$this->finder = Finder::factory($class_name);

		$association_params = array(
			'conditions' => $this->finder->id_field()." = ".$this->model->data[$foreign_key]." AND ".$this->conditions,
			'order' => $this->order
		);
		
		return $association_params;
		
	}
	
}

/**
 * Class for modelling has many associations
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package model
 * @subpackage associations
 */ 

class HasManyAssociation extends ModelAssociation {
	var $type = 'has_many';
	
	var $group;
	var $dependant = false;
	var $limit;
	var $offset;
	var $select;
	var $as;
	var $through = false;
	var $source;
	
	/**
	 * Constuctor
	 *
	 * @param string $field
	 * @param array $options;
	 * @return HasManyAssociation
	 */	
	function HasManyAssociation(& $model, $field, $options) {
		$this->model = & $model;		
		
		if (!isset($options['class_name'])) {
			$options['class_name'] = $field;
		}
		
		if (!isset($options['as'])) {
			$options['as'] = false;	
			
			if (!isset($options['foreign_key'])) {
				$options['foreign_key'] = $this->model->table_name().'_id';
			}
			
		} else {
			$options['foreign_key'] = $options['as'].'_id';
			
		}
		
		$this->field = $field;
		$this->set_properties($options);		
		
	}
	
	
	/**
	 * Handle retrieving has_many associated fields
	 *
	 * @return array
	 */
	function get_params() {
		if (!$this->model->get_id()) {
			return false;
		}	
		
		$this->finder = Finder::factory($this->class_name);
		
		// choose the table the conditions will be applied to
		if ($this->through) {
			$join_model = Model::factory($this->through);
			$table_name = $join_model->table;
				
		} else {
			$table_name = $this->finder->table_name();	
			
		}
		
		// allow foreign keys to specify a table
		if (strpos($this->foreign_key, '.') === false) {
			$condition = $table_name.'.'.$this->foreign_key." = ".$this->model->get_id();	
		} else {
			$condition = $this->foreign_key." = ".$this->model->get_id();	
		}
		
		if ($this->as) {
			$condition .= ' AND '.$table_name.'.'.$this->as."_type = '".$this->model->_get_type()."'";
		}
		
		if ($this->through) {
			// @TODO check if we really needed this...
			//$select = $this->finder->table_name().'.*';
			
			if (!isset($join_model->associations[$this->field])) {
				trigger_error("No association data for request field ".$this->field);	
				return false;
			}
			
			if ($join_model->associations[$this->field]->type == 'has_many') {
				$join = $join_model->table.' ON '.$this->finder->table_name().'.'.$join_model->type.'_id = '.$join_model->table.'.id';
			} else {
				$join = $join_model->table.' ON '.$this->finder->table_name().'.id = '.$join_model->table.'.'.$this->finder->table_name().'_id';
			}

			$association_params = array(
				//'select' => $select,
				'joins' => $join,
				'conditions' => $condition." AND ".$this->conditions,
				'order' => $this->order
			);
			
		} else {
		
			$association_params = array(
				'conditions' => $condition." AND ".$this->conditions,
				'order' => $this->order
			);		
			
		}
		//debug($association_params);
		return $association_params;

	}
	
}

/**
 * Class for modelling has one associations
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package model
 * @subpackage associations
 */ 

class HasOneAssociation extends ModelAssociation {
	var $type = 'has_one';
	
	var $dependant = false;
	var $as;
	
	var $find_type = 'first';
	
	/**
	 * Constuctor
	 *
	 * @param string $field
	 * @param array $options;
	 * @return HasOneAssociation
	 */	
	function HasOneAssociation(& $model, $field, $options) {
		$this->model = & $model;
		
		if (is_null($options)) {
			$options = array();
		}
		
		if (!isset($options['class_name'])) {
			$options['class_name'] = $field;	
		}

		if (!isset($options['foreign_key'])) {
			$options['foreign_key'] = $this->model->table_name().'_id';
		}
		
		$this->field = $field;
		$this->set_properties($options);			
		
	}

	/**
	 * Handle retrieving has_one associated fields
	 *
	 * @return array
	 */
	function get_params() {
		if (!$this->model->get_id()) {
			return false;	
		}
		
		$this->finder = Finder::factory($this->class_name);
		
		$association_params = array(
			'conditions' => $this->foreign_key." = ".$this->model->get_id()." AND ".$this->conditions,
			'order' => $this->order
		);
		
		return $association_params;
		

	}	
	
}

/**
 * Class for modelling has and belongs to associations
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package model
 * @subpackage associations
 */ 

class HasAndBelongsToManyAssociation extends ModelAssociation {
	var $type = 'has_and_belongs_to_many';
	
	var $join_table;
	var $association_foreign_key;
	var $group;
	var $limit;
	var $offset;
	var $select;
	
	/**
	 * Constuctor
	 *
	 * @param string $field
	 * @param array $options;
	 * @return HasAndBelongsToManyAssociation
	 */
	function HasAndBelongsToManyAssociation(& $model, $field, $options) {
		$this->model = & $model;
		
		if (is_null($options)) {
			$options = array();
		}
		
		if (!isset($options['class_name'])) {
			$options['class_name'] = $field;
		}
		
		if (!isset($options['foreign_key'])) {
			$options['foreign_key'] = $this->model->table_name().'_id';
		}
		
		if (!isset($options['association_foreign_key'])) {
			$options['association_foreign_key'] = $field.'_id';
		}
		
		if (!isset($options['join_table'])) {
			if (strcasecmp($field, $this->model->table_name()) <= 0) {
				$options['join_table'] = $field.'_'.$this->model->table_name();
			} else {
				$options['join_table'] = $this->model->table_name().'_'.$field;			
			}
		}		
		
		$this->field = $field;
		$this->set_properties($options);			
		
	}
	
	/**
	 * Handle retrieving has_and_belongs_to associated fields
	 *
	 * @return array
	 */
	function get_params() {
		if (!$this->model->get_id()) {
			return false;	
		}
		
		$this->finder = Finder::factory($this->class_name);
		
		$association_params = array(
			'conditions' => $this->foreign_key." = ".$this->model->get_id()." AND ".$this->conditions,
			'joins' => $this->join_table.' ON '.$this->join_table.'.'.$this->association_foreign_key.' = '.$this->finder->table_name().'.'.$this->finder->id_field(),
			'order' => $this->order
		);	

		return $association_params;
		
	}		
	
}



?>