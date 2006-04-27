<?php
/**
 * inflector.php, part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package library
 */

/**
 * Class for manipulating text strings 
 *
 * @package library
 */
class Inflector {
	function camelize($string) {
		$strings = explode('_', str_replace('-', '_', $string));
		$result = '';
		
		foreach($strings as $string) {
			$result .= ucfirst($string);
		}
		
		return $result;
	}
	
	/**
	 * Converts a camelized string to underscore form
	 *
	 * @param string $string
	 * @return string
	 */
	function underscore($string) {

		$string = preg_replace('/([A-Z]+)([A-Z][a-z])/','\1_\2', $string);
		$string = preg_replace('/([a-z\d])([A-Z])/','\1_\2', $string);
		$string = strtolower(str_replace('-', '_', $string));

		return $string;	
		
	}
	
	/**
	 * Returns a human friendly version of a string
	 *
	 * @param string $string The string to humanize
	 * @return string The humanized string
	 */
	function humanize($string) {
		return ucfirst(str_replace('_', ' ', $string));
		
	}
	
	/**
	 * Returns a lower case and underscored version of a string,
	 * removing any non word character.
	 * suitable for urls etc.	
	 */
	function linkify($string) {
		$string = preg_replace('/\./', '', $string);
		$string = preg_replace('/\W+/', '_', $string);
		$string = strtolower(trim($string, '_'));	
		 
		return $string;
		 
	}
	
}

?>