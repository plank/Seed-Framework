<?php

class Rand {
	
	/**
	 * Generate a single digit random number 
	 *
	 * @return int
	 */
	function number() {
		return mt_rand(0, 9);
		
	} 
	
	/**
	 * Generate a string of random numbers $num_chars long
	 *
	 * @param int $size
	 * @return string
	 */
	function number_string($size) {
		$result = '';
		
		for ($x = 0; $x < $size; $x++) {
			$result .= Rand::number(0, 9);
			
		}
		
		return $result;
		
	}

	/**
	 * Generate an array of random numbers, each unique
	 *
	 * @param int $amount
	 * @param int $size
	 * @return array
	 */
	function number_strings($amount, $size) {
		$result = array();
		
		while (count($result) < $amount) {
			$num = Rand::number_string($size);	
			
			if (!in_array($num, $result)) {
				$result[] = $num;	
			}
			
		}
		
		return $result;
		
	}
	
}

?>