<?php

class LiquidContext {
	
	/**
	 * Local scopes
	 *
	 * @var array
	 */
	var $assigns;

	/**
	 * Enter description here...
	 *
	 * @var array
	 */
	var $registers;
	
	
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
	
	function add_filters($filter, $name = null) {
		$filter->context = $this;
		
		if (is_null($name)) {
			$name = $filter->name;
		}
		
		$this->filters[$name] = $filter;
		
		
	}
	
	function invoke($method, $value, $args = null) {
		
		$filter_class = ucfirst($method).'LiquidFilter';
		//debug($filter_class, $method, $value, $args);
		if (class_exists($filter_class)) {
			$filter = new $filter_class();
			
			if (!is_array($args)) {
				$args = array();
			}
			
			if (is_a($filter, 'LiquidFilter')) {
				array_unshift($args, $value);
				return call_user_method_array('filter', $filter, $args);
				
			}
			
		} 
			
		return $value;
			
	}
	
	/**
	 * Push new local scope on the stack.
	 *
	 * @return bool
	 */
	function push() {
		array_unshift($this->assigns, array());
		return true;
		
	}
	
	function merge($new_assigns) {
		$this->assigns[0] = array_merge($this->assigns[0], $new_assigns);
		
	}
	
	/**
	 * Pop from the stack.
	 *
	 * @return bool
	 */
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
	 * Test for empty has been moved to interpret condition, in LiquidDecisionBlock
	 *
	 * @param string $key
	 * @return mixed
	 */
	function resolve($key) {
		// this shouldn't happen
		if (is_array($key)) {
			trigger_error("Cannot resolve arrays as key", E_USER_ERROR);
			return null;
		}
		
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
		
		
		if (!is_null($object)) {
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
					} else {
						return null;
						
					}
					
				} else if (is_object($object)) {
					
					if (!method_exists($object, 'has_key')) {
						return null;
						
					}
					
					if (!$object->has_key($next_part_name)) {
						return null;
					}
					
					// php4 doesn't support array access, so we have
					// to use the invoke method instead
					$object = $object->invoke_drop($next_part_name);
					
				}

				if (is_object($object) && method_exists($object, 'to_liquid')) {
					$object = $object->to_liquid();
				}
				
				if (is_a($object, 'LiquidDrop')) {
					$object->context = $this;
				}	

			}

			return $object;		
			
		} else {
			return null;
			
		}
		
				
	}
	
}


?>