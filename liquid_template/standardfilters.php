<?php

class SizeLiquidFilter extends LiquidFilter  {
	
	function filter($input) { 
		if (is_string($input) || is_numeric($input)) {
			return strlen($input);
			
		} elseif (is_array($input)) {
			return count($input);
			
		} elseif (is_a($input)) {
			
			if (method_exists($input, 'size')) {
				return $input->size();
			}
			
		}
		
		return $input;
		
	}
	
}

class DowncaseLiquidFilter extends LiquidFilter {
	function filter($input) {
		if (is_string($input)) {
			return strtolower($input);
		}
		
		return $input;
		
	}
	
}

class UpcaseLiquidFilter extends LiquidFilter {
	function filter($input) {
		if (is_string($input)) {
			return strtoupper($input);
		}
		
		return $input;		
	}
}

class TruncateLiquidFilter extends LiquidFilter {
	function filter($input, $characters = 100) {
		if (is_string($input) || is_numeric($input)) {
			if (strlen($input) > $characters) {
				return substr($input, 0, $characters).'&hellip;';
			}
		}
		
		return $input;
		
	}
}

class TruncatewordsLiquidFilter extends LiquidFilter {
	function filter($input, $words) {
		if (is_string($input)) {
			$wordlist = explode(" ", $input);
			
			if (size($wordlist) > $words) {
				return implode(" ", array_slice($wordlist, 0, $words)).'$hellip;';
				
			}
			
		}
		
		return $input;
		
	}
	
}

class JoinLiquidFilter extends LiquidFilter {
	
	function filter($input, $glue = ' ') {	
		
		if (is_array($input)) {
			return implode($glue, $input);
		}
		
		return $input;
	}
}


class DateLiquidFilter extends LiquidFilter {
	
	function filter($input, $format) {
		if (!is_numeric($input)) {
			$input = strtotime($input);
		}
		
		return strftime($format, $input);
		
	}
	
	
}

class FirstLiquidFilter extends LiquidFilter {
	
	function filter($input) {
		if (is_array($input)) {
			return array_shift($input);
		} 
		
		return $input;
		
	}
	
}

class LastLiquidFilter extends LiquidFilter {
	
	function filter($input) {
		if (is_array($input)) {
			return array_pop($input);
		} 
		
		return $input;		
		
	}
	
}

?>