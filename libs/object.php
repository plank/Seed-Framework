<?php

/**
 * Generic object base class
 *
 */
class Object {
	
	/**
	 * Returns all the methods defined in the object
	 *
	 * @return array
	 */
	function get_methods() {
		return get_class_methods($this);
		
	}
	
	/**
	 * Returns all the class vars for the object
	 *
	 * @return array
	 */
	function get_class_vars() {
		return get_class_vars($this->get_class_name());
		
	}
	
	/**
	 * Returns all the object vars for the object
	 *
	 * @return array
	 */
	function get_object_vars() {
		return get_object_vars($this);
		
	}
	
	/**
	 * Returns all the object vars that are *not* class vars. These will be all
	 * the vars that were added to the object dynamically
	 *
	 * @return array
	 */
	function get_non_class_vars() {
		return array_diff_by_key($this->get_object_vars(), $this->get_class_vars());
		
	}
	
	/**
	 * Returns the name of the class
	 *
	 * @return string
	 */
	function get_class_name() {
		return get_class($this);
		
	}
	
	/**
	 * Returns the name of the parent class
	 *
	 * @return string
	 */
	function get_parent_class_name() {
		return get_parent_class($this);
		
	}
	
	/**
	 * Returns true if the current object is or is a subclass of the given class name
	 *
	 * @param string $class_name
	 * @return bool
	 */
	function is_a($class_name) {
		return is_a($this, $class_name);
		
	}
	
	/**
	 * Returns true if the current object is a subclass of the given class name
	 *
	 * @param string $class_name
	 * @return bool
	 */
	function is_subclass_of($class_name) {
		return is_subclass_of($this, $class_name);
		
	}
	
	/**
	 * Returns true if the current object has the given method
	 *
	 * @param string $method_name
	 * @return bool
	 */
	function has_method($method_name) {
		return method_exists($this, $method_name);
		
	}

	/**
	 * Returns true if the current object has the given property
	 *
	 * @param string $property_name
	 * @return bool
	 */
	function has_property($property_name) {
		return array_key_exists($property_name, $this->get_object_vars());
		
	}
	
	/**
	 * Returns a string representation of the current object
	 *
	 * @return string
	 */
	function to_string() {
		return $this->get_class_name();
		
	}
	
	/**
	 * Returns a storable string representation of the object
	 *
	 * @return string
	 */
	function serialize() {
		return serialize($this);
		
	}
	
	
	
}


?>