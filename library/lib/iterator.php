<?php
/**
 * iterator.php, part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package library
 */


/**
 * Base iterator class, handles arrays
 *
 * @package library
 */
class SeedIterator {
	/**
	 * Variable to hold the data being iterated over
	 *
	 * @var mixed
	 */
	var $data;
	
	/**
	 * The position in the list
	 *
	 * @var int
	 */
	var $position;
	
	/**
	 * Constructor
	 *
	 * @param array $data
	 * @return SeedIterator
	 */
	function SeedIterator($data) {

		if (!$this->_validate_data($data)) {
			return false;
		}
		
		$this->data = $data;

		$this->position = 0;
		
	}
	
	/**
	 * Returns true if the data passed is valid for the type of iterator
	 *
	 * Subclass this method for the type of data to be handled
	 *
	 * @param mixed $data
	 * @return bool
	 */
	function _validate_data($data) {
		if (!is_array($data)) {
			trigger_error("Iterator expects an array as data parameter in constructor", E_USER_WARNING);
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * Returns true if the iterator has more elements
	 *
	 * @return bool
	 */
	function has_next() {
		return $this->position < count($this->data);
		
	}

	/**
	 * Returns the next item in the iterator
	 *
	 * @return mixed
	 */
	function next() {
		$this->position ++;
		$return = current($this->data);
		next($this->data);
		
		return $return;

	}

	/**
	 * Returns an array containing the iterator data
	 *
	 * @return array
	 */
	function to_array() {
		$result = array();	
		
		$this->reset();
		
		while ($item = $this->next()) {
			$result[] = $item;	
		}
		
		return $result;
		
	}
	
}


?>