<?php
/**
 * routes.php, part of the seed framework
 *
 * The Routes system is designed to translate nice urls to actions and vice versa
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package controller
 */

/**
 * The Router takes care of analyising the request and returning the appropriate controller
 * it also dumps the nice url parameters into the request object
 *
 * @package controller
 * @subpackage route
 */
class Router {
	
	/**
	 * The array of connected routes
	 *
	 * @var array
	 */
	var $routes;
	
	/**
	 * Maps a request to a specific controller and takes care of loading it
	 * 
	 * @param Request $request The object representing the incoming request
	 * @return Controller The controller that will handle the request
	 */
	function map(& $request) {
		
		// strip out the query string if the url contains it
		
		$path_params = $this->parse($request->url->directory.$request->url->base_name);
		
		if (!isset($path_params['controller'])) {
			trigger_error("The mapper couldn't find a controller for the request '$url', please check the routings file", E_USER_ERROR);	
			return false;
		} 

		$request->set_path_parameters($path_params);
		
		if (isset($path_params['module'])) {
			$controller_path = $path_params['module'].'/';	
		} else {
			$controller_path = '';
		}
		
		$controller_path .= $path_params['controller'];
		
		return Controller::factory($controller_path, $this);
		
	}

	function load_config() {
		// register routes
		require_once(CONFIG_PATH.'routes.php');
		
	}
	
	/**
	 * Adds a route to the route set
	 *
	 * @param string $name			This argument can be omitted by making the route the first argument
	 * @param string $route
	 * @param array $defaults
	 * @param array $requirements
	 */
	function connect($name = null, $route = null, $defaults = null, $requirements = null) {
		if (is_null($name)) {
			trigger_error('No route passed to connect', E_USER_WARNING);
			return false;
		}
		
		// get the arguments from the function and pad the rest of the array so that the list call works
		$args = func_get_args();
		$args = array_merge($args, array_fill(0, 4, null));
		
		// was a name given?
		if (is_null($route) || is_array($route)) {
			$name = null;
		} else {
			$name = array_shift($args);	
		}
		
		list ($route, $defaults, $requirements) = $args;
		
		if (isset($name)) {
			$this->routes[$name] = new Route($route, $defaults, $requirements);
		} else {
			$this->routes[] = new Route($route, $defaults, $requirements);			
		}

		return true;
	}
	
	/**
	 * Generates a url
	 *
	 * @param array $request_values The path values from the incoming request
	 * @param array $new_values The new values to place into the url
	 * @param array $overwrite_values Values that will overwrite the request values
	 * @return string
	 */
	function url_for($request_values = null, $new_values = null, $overwrite_values = null) {
		
		// hack for requesting controllers in absolute mode
		if (isset($new_values['controller']) && substr($new_values['controller'], 0, 1) == '/') {
			$new_values['controller'] = substr($new_values['controller'], 1);
			unset($request_values['module']);
		}
		// end hack		
		
		foreach($this->routes as $route) {
			if($result = $route->generate_url($request_values, $new_values, $overwrite_values)) {
				return $result;	
				
			}
			
		}
		
		return false;
		
	}	
	
	function url_for_name($name, $request_values = null, $new_values = null, $overwrite_values = null) {
		
		
		if (!key_exists($name, $this->routes)) {
			trigger_error("Route '$name' not found", E_USER_WARNING);
			return false;	
			
		}
		
		$route = $this->routes[$name];
		
		return $route->generate_url($request_values, $new_values, $overwrite_values);
		
	}
	
	/**
	 * Parses a url
	 *
	 * @param string $url
	 * @return array
	 */
	function parse($url) {
		
		
		foreach($this->routes as $route) {
			if($result = $route->parse_url($url)) {
				return $result;	
				
			}
			
		}
		
		return false;
		
	}
	

	
		
	
}

/**
 * The Route class represents a url to parameter mapping
 *
 * @package controller
 * @subpackage route
 */
class Route {
	/**
	 * @var string
	 */
	var $route;
	
	/**
	 * @var array
	 */
	var $defaults;
	
	/**
	 * @var array
	 */
	var $requirements;
	
	/**
	 * An array of messages that log the last attempt at generation or parsing
	 *
	 * @var array
	 */
	var $log;
	
	/**
	 * @var array
	 */
	var $default_route = array('action'=>'index', 'id'=>null);
	
	/**
	 * @var array
	 */
	var $default_requirements = array();
	
	/**
	 * Constructor
	 *
	 * @param string $route The url of the route
	 * @param array $defaults The default values of the route
	 * @param array $requirements An array of regexs that values need to match for the route to match
	 */
	function Route($route, $defaults = null, $requirements = null) {
		$this->route = $route;
		
		if (isset($defaults)) {
			$this->defaults = $defaults;
		} else {
			$this->defaults = $this->default_route;	
		}
		
		if (isset($requirements)) {
			$this->requirements = $requirements;
		} else {
			$this->requirements = $this->default_requirements;	
		}
	}
	
	/**
	 * Attempts to generate a url with the given values. Returns a string,
	 * or false if it didn't work
	 *
	 * @param array $request_values The path values from the incoming request
	 * @param array $new_values The new values to place into the url
	 * @param array $overwrite_values Values that will overwrite the request values
	 * @return string
	 */
	function generate_url($request_values = null, $new_values = null, $overwrite_values = null) {
		
		$this->log = array();
		
		if (is_null($request_values)) {
			$request_values = array();	
		}
		
		if (is_null($new_values)) {
			$new_values = array();	
		}
		
		if (!is_null($overwrite_values)) {
			$request_values = array_merge($request_values, $overwrite_values);
			$new_values = array_merge($new_values, $overwrite_values);
		}
		
		$values = array_merge($request_values, $new_values);
		
		$route_parts = array_diff(explode('/', $this->route), array(''));
		$route_parts = array_reverse($route_parts);
		
		$defaults = $this->defaults;
		
		// make sure passed values pass requirements
		foreach($this->requirements as $key => $regex) {
			// if the value exists, see if it matches
			if (isset($values[$key])) {
				if (preg_match($regex, $values[$key]) == 0) {
					$this->log[] = "Passed values don't match requirement $key";
					return false;
				}	
			}	
		}
		

		// if the new values are the same as the old ones, we need to include them 
		// all to get an identical request
		$include = ($values === $request_values);
		
		if ($include) {
			$this->log[] = "New request was the same as the current one";
				
		}
		
		$return = array();
		
		// build the result array backwards so we can ignore defaults without multiple passes
		foreach($route_parts as $route_part) {
			$this->log[] = "Parsing token '$route_part'";
			if ($token = $this->get_token($route_part, '*')) {
				// token is a catch all, merge the values into the array
				if (isset($values[$token]) && is_array($values[$token])) {
					$return = array_merge(array_reverse($values[$token]), $return);	
				} else {
					$this->log[] = "Catch all var not found in values";
					return false;
				}
				
			} elseif ($token = $this->get_token($route_part)) {
				// token is a param
				if (key_exists($token, $values)) {
					// we have a value for it
					
					$default = assign($defaults[$token], '');
					$request_value = assign($request_values[$token], '');
					
					// if the value has changed here or it was explicitely set,
					// we need to include start including params
					if ($values[$token] != $request_value || isset($new_values[$token])) {
						$include = true;
					} else {
						
					}					
					
					// we only add it if it's required
					if ((count($return) || ($values[$token] !== $default)) && $include) {
						$this->log[] = "Added token '$token' = ".$values[$token]." from params";
						
						$return[] = $values[$token];	
					} else {
						$this->log[] = "Token '$token' not required";	
						
					}
					
					unset($new_values[$token]);
					unset($defaults[$token]);
					
				} elseif (key_exists($token, $defaults)) {
					// no value, but there is a default for it
					
					// we only add it if it's required
					if (count($return) && isset($defaults[$token])) {
						$this->log[] = "Added token '$token' = ".$values[$token]." from defaults";
						
						$return[] = $defaults[$token];	
					} 
					
					unset($defaults[$token]);
										
				} else {
					$this->log[] = "Token '$token' not found in values nor default";
					return false;
					
				}
					
			} else {
				// token is static
				$this->log[] = "Added static '$route_part'";
				$return[] = $route_part;	
				
			}

		}

		// leftover defaults must match values for the route to match
		foreach($defaults as $key => $default) {
			if (key_exists($key, $values) && $default == $values[$key]) {
				unset($new_values[$key]);
			} else {
				$this->log[] = "default key '$key' not found in values or not equal";
				return false;
			}	
			
		}
		
		$return = implode('/', array_reverse($return));
		
		// leftover values are added as querystring
		$query_string = array();
		
		foreach($new_values as $key => $value) {
			if ($value !== '') {
				$query_string[] = "$key=$value";
			}
		}
		
		if (count($query_string)) {
			$return .= '?'.implode('&', $query_string);
			
		}
		
		return $return;
		
	}
	
	/**
	 * Parses the current url against the route. Returns an array
	 * of values extracted from the url, or false if the url
	 * doesn't match.
	 * 
	 * @param string $url
	 * @return array
	 */
	
	function parse_url($url) {
		
		$this->log = array();
		
		// explode the route and the url, getting rid of extraneous slashes
		$route_parts = array_values(array_diff(explode('/', $this->route), array('')));
		$url_parts = array_values(array_diff(explode('/', $url), array('')));
		
		$return = $this->defaults;
	
		$catch_all = false;
		
		// use foreach because we need to ignore the keys
		foreach($route_parts as $route_part) {

			if ($catch_all) {
				trigger_error("The route '".$this->route."' is invalid", E_USER_WARNING);	
				return false;
			}
			
			// if the component is a catch-all
			if ($token = $this->get_token($route_part, '*')) {
				
				if (count($url_parts)) {
					$return[$token] = $url_parts;	
				} elseif (key_exists($token, $this->defaults)) {
					$return[$token] = $this->defaults[$token];
				} else {
					$return[$token] = array();
				}
				
				$url_parts = array();
				$catch_all = true;
				continue;
			}
			
			$url_part = array_shift($url_parts);
			
			if ($token = $this->get_token($route_part)) {
				if ($url_part) {
					// we have a requirement to match
					if (isset($this->requirements[$token])) {
						// if it doesn't, the route doesn't match
						if (preg_match($this->requirements[$token], $url_part) == 0) {
							$this->log[] = "requirement not met for $token";
							return false;
						}
						
					}
				
					$return[$token] = $url_part;
					
				} else {
					if (!key_exists($token, $this->defaults)) {
						$this->log[] = "no default for $token";
						return false;
					}
				}
				
			} else {
				if ($route_part != $url_part) {
					$this->log[] = "$route_part doesn't match $url_part";
					return false;
					
				}
			}
		}
		
		if (count($url_parts)) {
			$this->log[] = "URL had too many parameters";
			return false;	
		}
		
		// return the resulting params, removing null values
		$return = array_diff($return, array(null));
		
		return $return;
		
	
	}
	
	function get_token($string, $symbol = '$') {
		if (substr($string, 0, 1) == $symbol){
			return substr($string, 1);
			
		} else {
			return false;
			
		}
		
	}
	
	
	
}

?>