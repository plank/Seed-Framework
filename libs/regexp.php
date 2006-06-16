<?php


class Regexp {
	
	/**
	 * The regexp pattern
	 *
	 * @var string
	 */
	
	var $pattern;
	
	/**
	 * Constructor
	 *
	 * @param string $pattern
	 * @return Regexp
	 */
	
	function Regexp($pattern) {
		
		if (substr($pattern, '0', 1) != '/') {
			$pattern = '/'.$pattern.'/';
			
		}
		
		$this->pattern = $pattern;	
		
	}
	
	function scan($string) {
		
		$result = preg_match_all($this->pattern, $string, $matches);
		
		if (count($matches) == 1) {
			return $matches[0];
		}
		
		array_shift($matches);
		
		$result = array();
		
		foreach($matches as $match_key => $sub_matches) {
			foreach($sub_matches as $sub_match_key => $sub_match) {
				$result[$sub_match_key][$match_key] = $sub_match;
				
			}
		}
		
		return $result;
		
	}
	
}


?>