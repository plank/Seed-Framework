<?php
/**
 * controller.php, part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package controller
 */

/**
 * Controller
 *
 * @package controller
 */

class Controller {
	
	/**
	 * @var string
	 */
	var $type;
	
	/**
	 * @var string
	 */
	var $full_type;
	
	/**
	 * The type of model associated with the controller
	 *
	 * @var string
	 */
	var $model = '';
	
	/**
	 * DB object
	 * 
	 * @var db
	 */
	var $db;
	
	/**
	 * A scaffolding object
	 *
	 * @var Scaffolding
	 */
	var $scaffolding;
	
	/**
	 * The name of the scaffolding class to use
	 *
	 * @var string
	 */
	var $scaffolding_class = 'scaffolding';
	
	/**
	 * The router object
	 *
	 * @var Router
	 */
	var $router;
	
	/**
	 * The request object
	 *
	 * @var Request
	 */
	var $request;

	/**
	 * The response object
	 * 
	 * @var Response
	 */
	var $response;
		
	/**
	 * The template object
	 *
	 * @var Template
	 */
	var $template;
	
	/**
	 * The flash object
	 *
	 * @var Flash
	 */
	var $flash;	
	
	/**
	 * The filter chain object
	 *
	 * @var FilterChain
	 */
	var $filter_chain;
	
	/**
	 * The array of request parameters, which is the get and post variables merged 
	 *
	 * @var array
	 */
	var $params;
	
	/**
	 * The name of the action to execute
	 *
	 * @var string
	 */
	var $action_name;
	
	/**
	 * The default layout to use with this controller
	 *
	 * @var string
	 */
	var $layout;
	
	/**
	 * Set to true once a template has been rendered
	 *
	 * @var bool
	 */
	var $performed_render = false;
	
	/**
	 * Set to true once a redirect has been performed
	 *
	 * @var bool
	 */
	var $performed_redirect = false;
	
	/**
	 * An array of methods that are protected from being run
	 *
	 * @var array
	 */
	var $_hidden_methods = array(
		'Controller',
		'factory',
		'get_type',
		'has_performed',
		'process',
		'get_template_name',
		'render',
		'redirect'
	);
	
	/**
	 * Constructor
	 *
	 * @return Controller
	 */
	function Controller() {
		$this->db = & db::get_db();
		$this->flash = & Flash::get_flash();
		
		if (!isset($this->layout)) {
			$this->layout = $this->get_type();
		}
		
		if (!isset($this->model)) {
			$this->model = $this->get_type();	
		}
		
		$this->template = new Template();
		
		if (class_exists($this->scaffolding_class)) {
			$this->scaffolding = & new $this->scaffolding_class($this);
		}
		
		$this->filter_chain = & new FilterChain($this);
		$this->setup();
	}
	
	function setup() {
		
	}
	
	/**
	 * Requires the file for the given type
	 *
	 * @param string $type
	 * @return bool
	 */
	function import($type) {
		$type = strtolower($type);		
		
		$path = CONTROLLER_PATH.$type.".php";
		
		if (!file_exists($path)) {
			trigger_error("Controller file '$path' does not exist", E_USER_ERROR);	
			return false;
		}
		
		require_once($path);
		
		return true;
		
	}
	
	/**
	 * Factory method, returns a controller for the given type
	 *
	 * @param string $type
	 * @return Controller
	 */
	function factory($type, $router = null) {
		
		$type = strtolower($type);
		
		Controller::import($type);
		
		$class_name = Inflector::camelize(basename($type)).'Controller';
		
		if (!class_exists($class_name)) {
			trigger_error("Controller file for '$type' exists, but doesn't contain controller class '$class_name'", E_USER_ERROR);	
			return false;
		}
		
		$controller = new $class_name;
		
		if (!is_a($controller, 'Controller')) {
			trigger_error("Class '$class_name' doesn't extend Controller", E_USER_ERROR);
			return false;
		}
		
		$controller->full_type = $type;
		$controller->router = $router;
		return $controller;
	}
	
	/**
	 * Returns true if there's been a render or a redirect
	 *
	 * @return bool
	 */
	function has_performed() {
		return $this->performed_render || $this->performed_redirect;
	}
	
	/**
	 * Returns the type of the class. i.e. if the class is PageController, returns page
	 *
	 * @return string
	 */
	function get_type() {
		if (isset($this->type)) {
			return $this->type;
		}
		
		if (get_class($this) === __CLASS__) {
			trigger_error('Tried to get type for base controller', E_USER_WARNING);
			return 'base';
		} 
		
		return Inflector::underscore(str_replace('controller', '', strtolower(get_class($this))));
		
	}
	
	/**
	 * Processes the request and calls the appropriate method on the sub controller
	 *
	 * @param Request $request
	 * @return bool
	 */
	function process($request, $response) {

		$this->request = $request;
		$this->params = $request->parameters;
		$this->response = $response;

		if (isset($this->scaffolding)) {
			$this->scaffolding->controller = & $this;	
		}
		$this->filter_chain->controller = & $this;	
		$this->template->controller = & $this;
		$this->template->params = $this->params;
		$this->template->request = $request;
		$this->template->flash = $this->flash;
		
		// choose the action to perform
		if (isset($this->params['action'])) {
			$this->action_name = $this->params['action'];
		} else {
			$this->action_name = 'index';
		}

		Logger::log('dispatch', LOG_LEVEL_DEBUG, 'controller: '.$this->get_type().', action: '.$this->action_name);
		
		if (substr($this->action_name, 0, 1) == '_') {
			Logger::log('dispatch', LOG_LEVEL_WARNING, "Call to protected action '$this->action_name'");
			$this->response->status(404);
			return $this->response;
		}
		
		$filter_result = $this->filter_chain->call_before($this->action_name);
		
		if (!$filter_result || $this->has_performed()) {
			Logger::log('dispatch', LOG_LEVEL_WARNING, "Filter chain returned false");
			return $this->response;	
			
		}
		
		// import the model type
		if ($this->model) {
			Model::import($this->model);
		}
		
		// make sure the method exists and that it's not protected
		if (method_exists($this, $this->action_name) && !in_array($this->action_name, $this->_hidden_methods)) {
			call_user_func(array(&$this, $this->action_name));	
			
		} elseif (isset($this->scaffolding) && method_exists($this->scaffolding, $this->action_name)) {
			call_user_func(array(&$this->scaffolding, $this->action_name));
			
		} else {
			Logger::log('dispatch', LOG_LEVEL_WARNING, "Action '$this->action_name' not found in ".get_class($this));
			$this->response->status(404);
			
		}

		if (!$this->has_performed()) {
			$this->render();
		}
		
		$this->filter_chain->call_after($this->action_name);
		
		return $this->response;
	}
	
	/**
	 * Includes a helper object into the template. It first looks for a helper containing the name
	 * of the class, and if that isn't found, it looks for the ApplicationHelper
	 *
	 * @return bool Returns true if a helper was found and loaded, false if not
	 */
	function include_helper() {

		$type = $this->get_type();

		$helper_file_name = HELPER_PATH.$type.'_helper.php';

		if (!file_exists($helper_file_name)) {
			$type = 'application';
			$helper_file_name = HELPER_PATH.$type.'_helper.php';

		}

		if (!file_exists($helper_file_name)) {
			return false;	
		}		

		require_once($helper_file_name);	

		$helper_class_name = Inflector::camelize($type).'Helper';

		if (!class_exists($helper_class_name)) {
			return false;	
		}

		$this->template->helper = new $helper_class_name($this->template);

		$this->template->helper->template = & $this->template;

		return true;
	}
	
	/**
	 * Add all the user assigned object variables to the template
	 */
	function add_variables_to_assigns() {
		$class_vars = get_class_vars(__CLASS__);
		
		$object_vars = get_object_vars($this);
		
		$vars = array_diff_by_key($object_vars, $class_vars);
		
		foreach($vars as $key => $value) {
			$this->template->$key = $value;	
		}
		
	}
	
	/**
	 * Renders the template for the current actions
	 *
	 * @param string $template_path
	 */
	function render($template_path = null) {
		if (!is_a($this->template, 'Template')) {
			trigger_error("The template variable was overwritten", E_USER_ERROR);
			return false;
		}
		
		if ($this->has_performed()) {
			trigger_error("Double render error", E_USER_WARNING);
			return false;
		}
		
		if (!isset($template_path)) {
			$template_path = $this->get_template_name();
		}
		
		if (file_exists($template_path)) {	
			$this->add_variables_to_assigns();
			$this->include_helper();
					
			// render template
			$this->template->layout = $this->layout;
			$this->render_text($this->template->render_file($template_path));
			
		} else {
			trigger_error("No template found in '$template_path'", E_USER_ERROR);
		}
	}
	
	function render_text($text = '', $status = null) {
		$this->performed_render = true;
		
		if (isset($status)) {
			$this->response->response_code = $status;	
		}
		
		if ($text) {
			$this->response->body = $text;
		}
		
	}
	
	function render_nothing() {
		$this->render_text();	
	}
	
	function render_component($controller, $options = null) {
		$controller = Controller::factory($controller);
		
		if (!$controller) {
			return false;
		}
		
//		$controller->layout = '';
		
		$request = $this->request;
		
		if (isset($options)) {
			$request->parameters = array_merge($request->parameters, $options);
			$request->get = array_merge($request->get, $options);
		}

		$this->response = $controller->process($request, $this->response);
		$this->performed_render = true;
	}
	
	/**
	 * Returns the name of the template for the current action
	 *
	 * @param string $action_name
	 * @return string
	 */
	function get_template_name($action_name = null) {
		if (is_null($action_name)) {
			$action_name = $this->action_name;
		}
		
		return TEMPLATE_PATH.$this->full_type.'/'.$action_name.'.php';
		
	}

	
	/**
	 * Sets the response to redirect to the passed action or url
	 *
	 * @param mixed $options
	 * @return bool
	 */
	function redirect($options = null, $overwrite_options = null) {
		$request = & $this->request;
				
		if ($this->has_performed()) {
			trigger_error("Double render error", E_USER_WARNING);
			return false;
		}
		
		if (is_array($options) || is_null($options)) {
			$options = APP_ROOT.$this->router->url_for($request->path, $options, $overwrite_options);
		}
		
		$this->response->redirect($options);
		$this->performed_redirect = true;
		
		return true;
	}	
	
	function url_for($options = null, $overwrite_options = null) {
		$request = $this->request;

		if (is_array($options) || is_null($options)) {
			return APP_ROOT.$this->router->url_for($request->path, $options, $overwrite_options);
		} else {
			$options = APP_ROOT.$options;	
			
			foreach($overwrite_options as $key => $value) {
				if ($value) {
					$query_string[$key] = "$key=$value";	
				}
				
			}	
			
			if (isset($query_string)) {
				$options .= "?".implode('&amp;', $query_string);				
				
			}
			
			return $options;
			
		}
	}
}


?>
