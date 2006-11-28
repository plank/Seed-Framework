<?php


seed_include('library/iterator');
seed_include('library/inflector');
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


if (SEED_PHP_VERSION == 4 && (defined('SEED_MODEL_VERSION') && SEED_MODEL_VERSION == 1)) {
	require_once('model_versions/version1.php');
	
} else {
	require_once('model_versions/version2.php');

}


?>