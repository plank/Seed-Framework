<?php


class Regexp {
	
	/**
	 * The regexp pattern
	 *
	 * @var string
	 */
	
	var $pattern;
	
	var $result;
	
	/**
	 * The matches from the last method called
	 *
	 * @var array;
	 */
	
	var $matches;
	
	/**
	 * Constructor
	 *
	 * @param string $pattern
	 * @return Regexp
	 */
	
	function Regexp($pattern) {
		
		if (substr($pattern, '0', 1) != '/') {
			$pattern = '/'.$this->quote($pattern).'/';
			
		}
		
		$this->pattern = $pattern;	
		
	}
	
	/**
	 * Quotes regular expression characters
	 *
	 * @param string $string
	 * @return string
	 */
	function quote($string) {
		return preg_quote($string, '/');
		
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
	
	function match($string) {
		$this->result = preg_match($this->pattern, $string, $this->matches);		
		return $this->result;
	}
	
	function match_all($string) {
		$this->result = preg_match_all($this->pattern, $string, $this->matches);
		return $this->result;
		
	}
	
}


?>