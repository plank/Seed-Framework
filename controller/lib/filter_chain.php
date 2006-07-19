<?php
/**
 * filter_chain.php, part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package controller
 * @subpackage filter 
 */


/**
 * Implements a filter chain for calling filters before and after actions
 *
 * @package controller
 * @subpackage filter
 */

class FilterChain {
	
	/**
	 * A reference to the controller
	 *
	 * @var Controller
	 */
	var $controller;
	
	/**
	 * The chain of pre filters
	 *
	 * @var array
	 */
	var $pre_chain;
	
	/**
	 * The chain of post filters
	 *
	 * @var array
	 */
	var $post_chain;
	
	/**
	 * Constructor
	 *
	 * @param Controller $controller
	 * @return FilterChain	 
	 */
	function FilterChain(&$controller) {
		$this->controller = &$controller;
		$this->pre_chain = array();
		$this->post_chain = array();
	}
	
	/**
	 * Appends a given filter to the pre filter chain
	 *
	 * @param mixed $filter The filter to add; can either be the name of a method on the controller,
	 * or a SimpleFilter object
	 * @param array $only An array of action names. If this is given, the filter will only run on these actions
	 * @param array $except An array of action names. If this is given, the filter will not run on these actions
	 */
	function before_filter($filter, $only = null, $except = null) {
		return $this->append_before_filter($filter, $only, $except);
			
	}

	/**
	 * Appends a given filter to the post filter chain
	 *
	 * @param mixed $filter The filter to add; can either be the name of a method on the controller,
	 * or a SimpleFilter object
	 * @param array $only An array of action names. If this is given, the filter will only run on these actions
	 * @param array $except An array of action names. If this is given, the filter will not run on these actions
	 */
	function after_filter($filter, $only = null, $except = null) {
		return $this->append_after_filter($filter, $only, $except);		
	}
	
	/**
	 * Appends a given filter to the pre filter chain
	 *
	 * @param mixed $filter The filter to add; can either be the name of a method on the controller,
	 * or a SimpleFilter object
	 * @param array $only An array of action names. If this is given, the filter will only run on these actions
	 * @param array $except An array of action names. If this is given, the filter will not run on these actions
	 */
	function append_before_filter($filter, $only = null, $except = null) {
		if (!$this->is_valid_filter($filter)) {
			return false;	
		}
		
		array_push($this->pre_chain, array($filter, $only, $except));
		
		return true;
	}
	
	/**
	 * Prepends a given filter to the pre filter chain
	 *
	 * @param mixed $filter The filter to add; can either be the name of a method on the controller,
	 * or a SimpleFilter object
	 * @param array $only An array of action names. If this is given, the filter will only run on these actions
	 * @param array $except An array of action names. If this is given, the filter will not run on these actions
	 */
	function prepend_before_filter($filter, $only = null, $except = null) {
		if (!$this->is_valid_filter($filter)) {
			return false;	
		}
		
		array_unshift($this->pre_chain, array($filter, $only, $except));
		
		return true;		
	}
	
	/**
	 * Appends a given filter to the post filter chain
	 *
	 * @param mixed $filter The filter to add; can either be the name of a method on the controller,
	 * or a SimpleFilter object
	 * @param array $only An array of action names. If this is given, the filter will only run on these actions
	 * @param array $except An array of action names. If this is given, the filter will not run on these actions
	 */
	function append_after_filter($filter, $only = null, $except = null) {
		if (!$this->is_valid_filter($filter)) {
			return false;	
		}
		
		array_push($this->post_chain, array($filter, $only, $except));		
		
		return true;		
	}
	
	/**
	 * Prepends a given filter to the post filter chain
	 *
	 * @param mixed $filter The filter to add; can either be the name of a method on the controller,
	 * or a SimpleFilter object
	 * @param array $only An array of action names. If this is given, the filter will only run on these actions
	 * @param array $except An array of action names. If this is given, the filter will not run on these actions
	 */
	function prepend_after_filter($filter, $only = null, $except = null) {
		if (!$this->is_valid_filter($filter)) {
			return false;	
		}
		
		array_unshift($this->post_chain, array($filter, $only, $except));
		
		return true;		
	}

	/**
	 * Appends a given filter around the filter chains. In other words, it appends to the pre chain,
	 * but prepends to the post chain. This allows correct nesting of around filters
	 *
	 * @param AroundFilter $filter The filter to add.
	 * @param array $only An array of action names. If this is given, the filter will only run on these actions
	 * @param array $except An array of action names. If this is given, the filter will not run on these actions
	 */
	function append_around_filter(&$filter, $only = null, $except = null) {
		if (!$this->is_valid_filter($filter, true)) {
			return false;
		}
		
		array_push($this->pre_chain, array(&$filter, $only, $except));
		array_unshift($this->post_chain, array(&$filter, $only, $except));
		
		return true;
	}
	
	/**
	 * Prepends a given filter around the filter chains. In other words, it prepends to the pre chain,
	 * but appends to the post chain. This allows correct nesting of around filters
	 *
	 * @param AroundFilter $filter The filter to add.
	 * @param array $only An array of action names. If this is given, the filter will only run on these actions
	 * @param array $except An array of action names. If this is given, the filter will not run on these actions
	 */
	function prepend_around_filter(&$filter, $only = null, $except = null) {
		if (!$this->is_valid_filter($filter, true)) {
			return false;
		}
		
		array_unshift($this->pre_chain, array(&$filter, $only, $except));
		array_push($this->post_chain, array(&$filter, $only, $except));
		
		return true;
	}
	
	/**
	 * Appends a given filter around the filter chains. In other words, it appends to the pre chain,
	 * but prepends to the post chain. This allows correct nesting of arund filters
	 *
	 * @param AroundFilter $filter The filter to add.
	 * @param array $only An array of action names. If this is given, the filter will only run on these actions
	 * @param array $except An array of action names. If this is given, the filter will not run on these actions
	 */
	function around_filter(&$filter, $only = null, $except = null) {
		return $this->append_around_filter($filter, $only, $except);	
		
	}
	
	/**
	 * Calls the pre filter chain for a given action
	 *
	 * @param string $action The name of the action being executed
	 * @return bool True if all filters were called, false if any of the filters interupted the chain
	 */
	function call_before($action) {
		return $this->call_filters(true, $action);	
	}
	
	/**
	 * Calls the post filter chain for the given action
	 *
	 * @param string $action The name of the action being executed
	 * @return bool True if all filters were called, false if any of the filters interupted the chain
	 */
	function call_after($action) {
		return $this->call_filters(false, $action);			
	}
	
	
	/**
	 * Checks to see if a given filter is valid (i.e. is a filter sub class
	 * or a valid method on the current controller
	 *
	 * @param mixed $filter
	 * @return bool
	 */
	function is_valid_filter($filter, $around = false) {
		
		if ($around) {
			return is_a($filter, 'AroundFilter');
		}
		
		if (is_a($filter, 'SimpleFilter')) {
			return true;	
		}
		
		if (is_string($filter) && method_exists($this->controller, $filter)) {
			return true;	
		}
		
		return false;
	}

	/**
	 * Calls the given filter chain
	 *
	 * @param bool $before Set to true to call the before chain, false to set to the after chain
	 * @param string $action The name of the action being executed
	 * @return bool True if all filters were called, false if any of the filters interupted the chain
	 */
	function call_filters($before = true, $action) {
		if ($before) {
			$filters = &$this->pre_chain;	
		} else {
			$filters = &$this->post_chain;
		}
		
		// we need to use a regular for loop instead of foreach to maintain references
		for($x = 0, $length = count($filters); $x< $length; $x++) {
			$filter = & $filters[$x][0];
			$included = $filters[$x][1];
			$excluded = $filters[$x][2];
			
			if (is_array($included) && !in_array($action, $included)) {
				continue;
			}
			
			if (is_array($excluded) && in_array($action, $excluded)) {
				continue;
			}
			
			if (!$this->call_filter($before, $filter)) {
				return false;
			}
			
		}
		
		return true;
	}
	
	/**
	 * Calls a given filter
	 *
	 * @param bool $before Determines which method is called on around filters
	 * @param mixed $filter The filter to call
	 * @return bool Returns the return value of the filter
	 */
	function call_filter($before = true, &$filter) {
		if (is_a($filter, 'AroundFilter')) {
			if ($before) {
				return $filter->before($this->controller); 
			} else {
				return $filter->after($this->controller);
			}
		} 
		
		if (is_a($filter, 'SimpleFilter')) {
			return $filter->filter($this->controller);	
		}
		
		return call_user_func(array(&$this->controller, $filter), $this->controller);
		
	}
	
}



?>