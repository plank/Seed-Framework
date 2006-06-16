<?php

class LiquidDrop {
	
	/**
	 * @var LiquidContext
	 */
	var $context;
	
	function before_method($method) {
		return null;
		
	}
	
	function invoke_drop($method) {
		
		$result = $this->before_method($method);
		
		if (is_null($result) && method_exists($this, $method)) {
			$result = $this->$method();
		}
		
		return $result;
	}
	
	function has_key($name) {
		return true;
		
	}
	
	function to_liquid() {
		return $this;
		
	}
	
	
}

?>