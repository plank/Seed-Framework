<?php

class Errors {
	
	/**
	 * The errors associated to the base object
	 *
	 * @var array
	 */
	var $base_errors;
	
	/**
	 * The errors associated to particular attributes
	 *
	 * @var array
	 */
	var $attribute_errors;
	
	/**
	 * The total number of errors
	 *
	 * @var int
	 */
	var $count;	
	
	/**
	 * Default error messages
	 *
	 * @var array
	 */
	var $default_messages = array(
		"inclusion" => "is not included in the list",
		"exclusion" => "is reserved",
		"invalid" => "is invalid",
		"confirmation" => "doesn't match confirmation",
		"accepted " => "must be accepted",
		"empty" => "can't be empty",
		"blank" => "can't be blank",
		"too_long" => "is too long (max is %d characters)",
		"too_short" => "is too short (min is %d characters)",
		"wrong_length" => "is the wrong length (should be %d characters)",
		"taken" => "has already been taken",
		"not_a_number" => "is not a number"
    );

		
    /**
     * Constructor
     *
     * @return Errors
     */
	function Errors() {
		$this->clear();
	}
	
	/**
	 * Adds an error message to a specific attribute
	 *
	 * @param string $attribute
	 * @param string $message
	 */
	function add($attribute, $message = null) {
		if (is_null($message)) {
			$message = $this->default_messages['invalid'];	
		}
		
		$this->attribute_errors[$attribute][] = $message;
		$this->count ++;
	}
	
	/**
	 * Add an generic error message
	 *
	 * @param string $message
	 */
	function add_to_base($message) {
		$this->base_errors[] = $message;
		$this->count ++;
	}	
	
	/**
	 * Resets all the errors
	 *
	 */
	function clear() {
		$this->base_errors = array();	
		$this->attribute_errors = array();		
		$this->count = 0;
	}
	
	/**
	 * Returns the number of errors. Multiple errors on the same attribute are counted towards this value
	 *
	 * @return int
	 */
	function count() {
		return $this->count;	
		
	}
	
	/**
	 * Returns true if the record is valid, and has no errors
	 *
	 * @return bool
	 */
	function is_empty() {
		return $this->count == 0;	
		
	}
	
	/**
	 * Return true if the record is invalid
	 *
	 * @return bool
	 */
	function is_invalid() {
		return $this->count != 0;	
		
	}
	
	/**
	 * Returns the errors associated with a particular attribute
	 *
	 * @param string $attribute
	 * @return mixed
	 */
	function on($attribute) {
		if (!isset($this->attribute_errors[$attribute])) {
			return false;
		}
		
		if (count($this->attribute_errors[$attribute]) == 1) {
			return $this->attribute_errors[$attribute][0];
		}
		
		return $this->attribute_errors[$attribute];
		
	}
	
	/**
	 * Returns the errors on the object in general
	 *
	 * @return mixed
	 */
	function on_base() {
		if (count($this->base_errors) == 0) {
			return false;
		}
		
		if (count($this->base_errors) == 1) {
			return $this->base_errors[0];
		}
		
		return $this->base_errors;
		
	}	
	
}


?>