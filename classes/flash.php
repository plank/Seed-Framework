<?php
/**
 * flash.php, part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package library
 */

/**
 * Flash permits passing of data between requests, or within the current request.
 *
 * @package library
 */

class Flash {
	
	/**
	 * The data from the previous request
	 *
	 * @var array
	 */
	var $data;
	
	/**
	 * Constructor
	 *
	 * @return Flash
	 */
	function Flash() {
		if (isset($_SESSION['flash'])) {
			$this->data = $_SESSION['flash'];
		} else {
			$this->data = array();
		}
		
		$_SESSION['flash'] = array();
	}
	
	/**
	 * Singleton function
	 *
	 * @return Flash
	 */
	function & get_flash() {
		static $flash;
		
		if (is_null($flash)) {
			$flash[0] = new Flash();
		}
		
		return $flash[0];
	}
	
	/**
	 * Stores a value to be used in the next request. The value will not be available in the current one.
	 *
	 * @param string $key
	 * @param mixed $data
	 */
	function next($key, $data) {
		$_SESSION['flash'][$key] = $data;
	}
	
	/**
	 * Stores a value to be used in the current request. The value will not be available in the next one
	 *
	 * @param string $key
	 * @param mixed $data
	 */
	function now($key, $data) {
		$this->data[$key] = $data;
	}
	
	/**
	 * Keeps a specific value so that it's available in the next request. If the key passed is null, all
	 * flash entries will be kept
	 *
	 * @param string $key
	 */
	function keep($key = null) {
		if (is_null(key)) {
			$_SESSION['flash'] = $this->data;
		} else if (isset($this->data[$key])) {
			$_SESSION['flash'][$key] = $this->data[$key];
		}
	}
	
	/**
	 * Returns a specific flash entry
	 *
	 * @param string $key
	 * @return mixed
	 */
	function get($key) {
		if (isset($this->data[$key])) {
			return $this->data[$key];
		} else {
			return null;
		}
	}
	
}

?>