<?php

require_once('finder.php');

class MultilangFinder extends Finder {
	/**
	 * Adds a join to the versions table to the finder sql construction
	 *
	 * @param array $options
	 * @return string
	 */
	function construct_finder_sql($options) {
		// get field and table names
		$table_name = $this->model->table_name();
		$version_table_name = $this->model->version_table_name();
		$id_field = $this->model->id_field;
		$foreign_key = $this->model->foreign_key();
		$version_field = $this->model->version_field;
		$language_field = $this->model->language_field;
		$default_language = $this->model->default_language;

		if (isset($options['latest_field'])) {
			$latest_field = $options['latest_field'];

		} else {
			$latest_field = $this->model->latest_field;

		}

		// add the join to the version table
		$version_join = "$version_table_name ON $table_name.$id_field = $version_table_name.$foreign_key";


		if (isset($options['joins']) && $options['joins']) {
			$options['joins'] = $version_join.' LEFT JOIN '.$options['joins'];
		} else {
			$options['joins'] = $version_join;
		}


		if (!isset($options['select'])) {
			$options['select'] = "$table_name.*, $version_table_name.*";
		}

		// a special condition string used to identify which revision to pull
		// by default, we want the latest version
		if (!isset($options['version_conditions'])) {
			$options['version_conditions'] = "$version_table_name.$latest_field = 1";
		}

		if (!isset($options['language'])) {
			$options['language'] = $default_language;
		}

		// original function, which needs to be called from here for 'this' to work... refactoring in sight! //
		$query = new SelectQueryBuilder($this->table_name());

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

		$query->add_conditions("$version_table_name.$language_field = '".$options['language']."'");

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
	 * Returns the number of rows in that meet the given condition
	 *
	 * @return int
	 */
	function count($conditions = null, $version_conditions = null, $lang = 'en') {
		$table_name = $this->model->table_name();
		$version_table_name = $this->model->version_table_name();
		$id_field = $this->model->id_field;
		$foreign_key = $this->model->foreign_key();

		/*$version_field = $this->model->version_field;
		$language_field = $this->model->language_field;
		$default_language = $this->model->default_language;*/


		$sql = "SELECT COUNT(*) FROM ".$this->db->escape_identifier($this->table_name())." LEFT JOIN $version_table_name ON $table_name.$id_field = $version_table_name.$foreign_key WHERE ".$this->add_conditions($conditions, $version_conditions, $lang);

		return $this->count_by_sql($sql);
	}

	/**
	 * Returns a string of conditions to add for a query
	 *
	 * @param string $conditions
	 * @return string
	 */
	function add_conditions($conditions = null, $version_conditions = null, $lang = 'en') {
		$version_table_name = $this->model->version_table_name();
		$latest_field = $this->model->latest_field;

		if (is_null($conditions)) {
			$conditions = "1 = 1";
		}

		if (!isset($version_conditions)) {
			$version_conditions = "$version_table_name.$latest_field = 1";
		}

		$conditions .= " AND lang = '$lang' AND ".$version_conditions;

		return $conditions;

	}

}

/**
 * Second attempt at a versioned model, with support for multiple languages
 */

class MultilangModel extends Model {

	/**
	 * Reference to the current version of content
	 *
	 * @var MultilangVersionModel
	 */
	var $version;

	/**
	 * The name of the table used for versioning. If this isn't explicitely set,
	 * it will be deduced from the name of the of this table
	 *
	 * @var string
	 */
	var $version_table = null;

	/**
	 * The name of the field that contains the revision numbers
	 *
	 * @var string
	 */
	var $version_field = 'revision';

	/**
	 * The name of the field that indicates the latest version of the content
	 *
	 * @var string
	 */
	var $latest_field = 'latest';

	/**
	 * The name of the field that contains the language
	 *
	 * @var string
	 */
	var $language_field = 'lang';

	/**
	 * The default language
	 *
	 * @var string
	 */
	var $default_language = 'en';

	/**
	 * The foreign key
	 *
	 * @var string
	 */
	var $foreign_key;

	/**
	 * All languages
	 *
	 * @var array
	 */
	var $languages = array('en', 'fr');

	/**
	 * The field used for soft deletes
	 *
	 * @var string
	 */
	var $deleted_field = 'deleted';

	/**
	 * The field use for flags
	 *
	 * @var string
	 */
	var $flag_field = 'flag';

	/**
	 * Constructor
	 *
	 * @param DB $db
	 * @return MultilangModel
	 */
	function MultilangModel($db = null) {
		$this->_default_data = array($this->deleted_field => '0');

		parent::Model($db);

		$this->version = Model::factory($this->type.'_version');

		$this->has_many('versions', array('class_name' => $this->type.'_version', 'order'=>'revision'));

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
		$table = $this->version_table;

		if ($table) {
			return $table;
		} else {
			return $this->table_name().'_versions';
		}

	}

	/**
	 * Returns the name of foreign key on the table used for keeping versions of this class
	 *
	 * @static
	 * @return string
	 */
	function foreign_key() {
		$foreign_key = $this->foreign_key;

		if ($foreign_key) {
			return $foreign_key;
		} else {
			return $this->table_name().'_id';
		}

	}


	/**
	 * Assign an array of fields and values to the object
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
					if (is_array($value) && isset($this->columns[$field])) {
						$value = $this->columns[$field]->array_to_type(array_values($value));
					}

					if (method_exists($this, 'set_'.$field)) {
						call_user_func(array(& $this, 'set_'.$field), $value);
					} else if (!$this->set_associated($field, $value)) {
						$this->data[$field] = $value;
					}

				} elseif ($this->version->field_exists($field)) {
					if (is_array($value) && isset($this->version->columns[$field])) {
						$value = $this->version->columns[$field]->array_to_type(array_values($value));
					}

					if (method_exists($this->version, 'set_'.$field)) {
						call_user_func(array(& $this->version, 'set_'.$field), $value);
					} else if (!$this->version->set_associated($field, $value)) {
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

	function association($field) {
		if (isset($this->associations[$field])) {
			return $this->associations[$field];
		}

		return $this->version->association($field);
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
			$association = $this->associations[$field];

			if (is_a($association->model, 'Model') && !isset($params['language'])) {
				$params['language'] = $this->get('lang');
			}

			return $this->associations[$field]->get($this, $params);

		} else {
			return $this->version->find_associated($field, $params);

		}

		//return null;
	}

	/**
	 * Updates the model by inserting a new versions
	 */
	function update() {
		// set the language field to the default if it doesn't have a value
		if (!$this->version->get($this->language_field)) {
			$this->version->set($this->language_field, $this->default_language);
		}

		$lang = $this->version->get('lang');
		$finder = $this->version->finder();
		// set latest_field to 0 for other versions
		$finder->update_all(
			"$this->latest_field = 0", $this->foreign_key()." = ".$this->get_id()." AND ".$this->language_field." = '".$lang."'"
		);
		$this->version->set($this->latest_field, 1);
		$this->version->insert();

		return parent::update();
	}

	function update_over() {
		// set the language field to the default if it doesn't have a value
		if (!$this->version->get($this->language_field)) {
			$this->version->set($this->language_field, $this->default_language);
		}

		$this->version->update();

		return parent::update();
	}

	function insert() {

		parent::insert();

		// set the language field to the default if it doesn't have a value
		if ($this->version->get($this->language_field)) {
			$current_language = $this->version->get($this->language_field);
		} else {
			$current_language = $this->default_language;
		}

		$this->version->set($this->foreign_key(), $this->get_id());
		$this->version->set($this->latest_field, 1);


		// insert drafts for the other languages
		foreach($this->languages as $language) {
			// skip the current language
			$this->version->set($this->language_field, $language);

			$this->version->insert();

		}

		$this->version->set($this->language_field, $current_language);

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

	/**
	 * Changes the flag of all versions in the current language from one value to another
	 */

	function change_flag($from, $to) {
		if (!$this->get_id()) {
			return false;
		}

		$lang = $this->version->get($this->language_field);
		$finder = $this->version->finder();

		// set any previous prending version to draft
		$finder->update_all(
			'flag = '.$to, 'flag = '.$from.' AND '.$this->foreign_key().' = '.$this->get_id().' AND '.$this->language_field." = '".$lang."'"
		);

	}

	/**
	 * Returns a merged data dump of the record and the version record
	 *
	 * @return array
	 */
	function to_array() {
		return array_merge(parent::to_array(), $this->version->to_array(), array($this->id_field=>$this->id));

	}

	/**
	 * Returns a merged array of the columns for both the record and the version record
	 *
	 * @return array
	 */
	function columns() {
		return array_merge($this->columns, $this->version->columns);

	}

}

class MultilangVersionFinder extends Finder {

}

class MultilangVersionModel extends Model {
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