<?php

class LiquidContext {
	
	/**
	 * Local scopes
	 *
	 * @var array
	 */
	var $assigns;
	
	var $registers;
	
	var $filters;
	
	function LiquidContext($assigns = null, $registers = null) {
		if (isset($assigns)) {
			$this->assigns = array($assigns);
			
		} else {
			$this->assigns = array(array());
			
		}
		
		if (isset($registers)) {
			$this->registers = $registers;
			
		} else {
			$this->registers = array();
			
		}
		
		
	}
	
	function add_filters($filter) {
		$this->filters[$filter->method] = $filter;
		
		
	}
	
	function invoke($method, $value, $args) {
		if (isset($this->filters[$method])) {
			$filter = $this->filters[$method];
			return $filter->filter($value, $args);
			
		} else {
			return $args[0];
			
		}
		
	}
	
	function push() {
		array_unshift($this->assigns, array());
		
	}
	
	function merge($new_assigns) {
		$this->assigns[0] = array_merge($this->assigns[0], $new_assigns);
		
	}
	
	function pop() {
		if (count($this->assigns) == 1) {
			trigger_error('No elements to pop', E_USER_ERROR);
			return false;	
		}
		
		array_shift($this->assigns);
	}
	
	/**
	 * Replaces []
	 * 
	 * @param string
	 * @return mixed
	 */
	function get($key) {
		return $this->resolve($key);
		
	}
	
	/**
	 * Replaces []=
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	function set($key, $value) {
		$this->assigns[0][$key] = $value;
		
	}
	
	/**
	 * Resolve a key by either returning the appropriate literal or by looking up the appropriate variable
	 *
	 * @param string $key
	 * @return mixed
	 */
	
	function resolve($key) {
		if (is_null($key) || $key == 'null') {
			return null;
		}
	
		if ($key == 'true') {
			return true;
		}
		
		if ($key == 'false') {
			return false;
		}
		
		if (preg_match('/^\'(.*)\'$/', $key, $matches)) {
			return $matches[1];
		}

		if (preg_match('/^"(.*)"$/', $key, $matches)) {
			return $matches[1];
		}

		if (preg_match('/^(\d+)$/', $key, $matches)) {
			return $matches[1];
		}

		if (preg_match('/^(\d[\d\.]+)$/', $key, $matches)) {
			return $matches[1];
		}			
		
		
		return $this->variable($key);
		
	}
	
	function fetch($key) {
		foreach ($this->assigns as $scope) {
			if (array_key_exists($key, $scope)) {
				$obj = $scope[$key];
				
				if (is_a($obj, 'LiquidDrop')) {
					$obj->context = $this;
				}
				
				return $obj;
				
			}
			
		}
		
	}
	
	function variable($key) {
		$parts = explode(VARIABLE_ATTRIBUTE_SEPERATOR, $key);
		
		$object = $this->fetch(array_shift($parts));
		
		if (is_object($object)) {
			$object = $object->to_liquid();
		}
		
		if ($object) {
			if (is_a($object, 'LiquidDrop')) {
				$object->context = $this;
			}
			
			while (count($parts) > 0) {
				$next_part_name = array_shift($parts);


				if (is_array($object)) {
					
					// if the last part of the context variable is .size we just return the count
					if ($next_part_name == 'size' && count($parts) == 0 && !array_key_exists('size', $object)) {
						return count($object);	
						
					}					
					
					if (array_key_exists($next_part_name, $object)) {
						$object = $object[$next_part_name];
						
					}
					
				}
				
				if (is_object($object)) {
					
					if (!method_exists($object, 'has_key')) {
						return null;
						
					}
					
					if (!$object->has_key($next_part_name)) {
						return null;
					}
					
					// php4 doesn't support array access, so we have
					// to use the invoke method instead
					$object = $object->invoke_drop($next_part_name);
				
					if (is_object($object)) {
						$object = $object->to_liquid;
					}
					
					if (is_a($object, 'LiquidDrop')) {
						$object->context = $this;
					}					
					
				}

				

			}

			//debug($object);
			return $object;		
			
		} else {
			//die('null');
			return null;
			
		}
		
				
	}
	
}


?>