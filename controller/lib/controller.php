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
 * Factory for controller objects.
 *
 * Implemented as a singleton, the controller factory is responsible for creating Controller objects, as well as including
 * the files containing. By default, the factory looks in app/controller, but it's possible to register other locations 
 * (for plug-ins, etc)
 *
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package controller 
 */
class ControllerFactory {
	
	/**
	 * An array of key-value pairs, where the key is a type, and the value is the file for that type
	 *
	 * @var array
	 */
	var $mappings;
	
	/**
	 * Constructor
	 *
	 * @return ControllerFactory
	 */
	function ControllerFactory() {
		$this->mappings = array();	
	}
	
	/**
	 * Register a controller type to path mapping
	 *
	 * @param string $type
	 * @param string $path
	 */
	function register($type, $path) {
		$this->mappings[$type] = $path;	
	}
	
	/**
	 * Singleton method
	 * 
	 * @static 
	 * @return ControllerFactory
	 */
	function & get_instance() {
		static $instances;
		
		if (!isset($instances[0])) {
			$instances[0] = new ControllerFactory();	
		}
		
		return $instances[0];
		
	}
	
	/**
	 * Requires the file for the given type. This will first look for a mapping with that key, and failing that
	 * will look in app/controllers. Triggers an error if the file isn't found.
	 *
	 * @param string $type  The type of the controller i.e. news for NewsController
	 * @return bool
	 */
	function import($type) {
		$type = strtolower($type);		
		
		if (isset($this->mappings[$type])) {
			$path = $this->mappings[$type];
		} else {
			$path = CONTROLLER_PATH.$type.".php";
			// allow client applications to overide Seed and SeedCMS controllers
			if(defined('CLIENT_APP_DIR') && file_exists(CLIENT_APP_DIR.'/controllers/'.$type.".php")) {
			    $path = CLIENT_APP_DIR.'/controllers/'.$type.".php";
			}
		}
		
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
		
		ControllerFactory::import($type);
		
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
	
}

/**
 * Controller
 *
 * Controllers are the heart of seed requests. They are made up of one or more actions that are executed on requests, and then either
 * render a template or redirect to another action. Actions are defined as methods on the controller, and will be made accessible to the
 * web server via the routes.
 *
 * Action, by default, render a template in the app/views directory corresponding to the name of the controller and the action after
 * executing the code of the action.
 *
 * Actions can also redirect after performing their code, by returning a 302 Moved HTTP Response.
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
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
	 * The scaffolding object
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
	 * The class to use for the template object
	 *
	 * @var string
	 */
	var $template_type = '';
	
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
		$factory = ControllerFactory::get_instance();
		
		return $factory->import($type);
		
	}
	
	/**
	 * Factory method, returns a controller for the given type
	 *
	 * @param string $type
	 * @return Controller
	 */
	function factory($type, $router = null) {
		$factory = ControllerFactory::get_instance();

		return $factory->factory($type, $router);
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
	
	function _assign_shortcuts($request, $response) {
		$this->request = $request;
		$this->params = $request->parameters;
		$this->response = $response;

		if ($this->template_type) {
			$this->template = Template::factory($this->template_type);
		} else {
			$this->template = Template::factory($this->get_type());
		}

		if (!$this->template) {
			$this->template = & new ApplicationTemplate($this);
		}
		
		$this->template->controller = & $this;
		$this->template->params = $this->params;
		$this->template->request = $request;
		$this->template->flash = $this->flash;		
		
	}
	
	/**
	 * Processes the request and calls the appropriate method on the sub controller
	 *
	 * @param Request $request
	 * @return bool
	 */
	function process($request, $response) {
		
		$this->_assign_shortcuts($request, $response);

		if (isset($this->scaffolding)) {
			$this->scaffolding->controller = & $this;	
		}
		
		$this->filter_chain->controller = & $this;	
		
		// choose the action to perform
		$this->action_name = isset($this->params['action']) ? $this->params['action'] : 'index';

		Logger::log('dispatch', LOG_LEVEL_DEBUG, 'controller: '.$this->get_type().', action: '.$this->action_name);
		
		if (substr($this->action_name, 0, 1) == '_') {
			Logger::log('dispatch', LOG_LEVEL_WARNING, "Call to protected action '$this->action_name'");
			$this->response->status(404);
			return $this->response;
		}
		
		// run before filters
		$filter_result = $this->filter_chain->call_before($this->action_name);
		
		if (!$filter_result || $this->has_performed()) {
			Logger::log('dispatch', LOG_LEVEL_WARNING, "Filter chain returned false");
			return $this->response;	
			
		}
		
		// import the model type
		if ($this->model) {
			Model::import($this->model);
		}
		
		if (in_array($this->action_name, $this->_hidden_methods) || substr($this->action_name, 0, 1) == '_') {
			// protected action, raise error
			trigger_error("Call to protected method", E_USER_ERROR);
			
		} else if (method_exists($this, $this->action_name)) {
			// normal action
			call_user_func(array(&$this, $this->action_name));
			
		} elseif (isset($this->scaffolding) && method_exists($this->scaffolding, $this->action_name)) {
			// scaffold action
			call_user_func(array(&$this->scaffolding, $this->action_name));
			
		} else {
			// action not found
			$this->action_not_found();
			
		}

		// render if it wasn't a manual render or redirect hasn't already happened
		if (!$this->has_performed()) {
			$this->render();
		}
		
		// call the after filter chain
		$this->filter_chain->call_after($this->action_name);
		
		return $this->response;
	}

	/**
	 * This action is called when no other action matches the request
	 *
	 */
	function action_not_found() {
		trigger_error("Action '$this->action_name' not found in ".get_class($this), E_USER_ERROR);		
		
	}
	
	/**
	 * Includes a helper object into the template. It first looks for a helper containing the name
	 * of the class, and if that isn't found, it looks for the ApplicationHelper
	 *
	 * @deprecated   Helpers will be removed completely in the near future
	 * @return bool  Returns true if a helper was found and loaded, false if not
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
	
	function render_to_string($template_path = null) {
		$this->render($template_path);	
		$result = $this->response->body;
		$this->erase_render_results();
		return $result;
	}
	
	/**
	 * Renders a given string, with the given status code
	 *
	 * @param string $text
	 * @param int $status
	 */
	function render_text($text = '', $status = null) {
		$this->performed_render = true;
		
		if (isset($status)) {
			$this->response->response_code = $status;	
		}
		
		if ($text) {
			$this->response->body = $text;
		}
		
	}
	
	function erase_render_results() {
		$this->performed_render = false;
		$this->response->response_code = 200;
		$this->response->body = '';
			
	}
	
	/**
	 * Renders an empty string, with the given status code
	 *
	 * @param int $status
	 */
	function render_nothing($status = null) {
		$this->render_text(' ', $status);	
	}
	
	/**
	 * Renders a component as a string
	 *
	 * @param array $options
	 * @return string
	 */
	function render_component_as_string($options = null) {
		
		static $stack = array();

		if (in_array($options, $stack)) {
			trigger_error('Recursion in render_component', E_USER_ERROR);	
			return false;
		} else {
			$stack[] = $options;
		}

		if (!isset($options)) {
			$options = array();	
		}
		
		if (isset($options['controller'])) {
			$controller = $options['controller'];
		} else {
			$controller = $this->controller->get_type();	
		}
		
		$controller = Controller::factory($controller, $this->controller->router);
		
		if (!$controller) {
			return false;
		}
		
//		$controller->layout = '';
		
		$request = clone($this->request);
		
		if (isset($options)) {
			$request->parameters = array_merge($request->parameters, $options);
			$request->get = array_merge($request->get, $options);
		}

		return $controller->process($request, $this->response);

	}
	
	/**
	 * Renders a component
	 *
	 * @param array $options
	 */	
	function render_component($options = null) {
		$this->render_text($this->render_component_as_string($options));
	}
	
	/**
	 * Renders a partial. Useful when making ajax requests
	 *
	 * @param string $partial_name
	 */
	function render_partial($partial_name) {
		$layout = $this->layout;
		$this->layout = '';
		
		$result = $this->render($this->get_template_name($partial_name));
		
		$this->layout = $layout;
		
		return $result;
			
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
		if(defined('CLIENT_APP_DIR') && file_exists($template_name = CLIENT_APP_DIR.'views/'.$this->full_type.'/'.$action_name.'.php')) {
		    $template_name = CLIENT_APP_DIR.'views/'.$this->full_type.'/'.$action_name.'.php';
		}
		else {
		    $template_name = TEMPLATE_PATH.$this->full_type.'/'.$action_name.'.php';
		}
		return $template_name;
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
		
		$options = $this->url_for($options, $overwrite_options);
		
		$this->response->redirect($options);
		$this->performed_redirect = true;
		
		return true;
	}	
	
	/**
	 * Returns a URL for a given set of options
	 *
	 * @param mixed $options
	 * @param array $overwrite_options
	 * @return string
	 */
	function url_for($options = null, $overwrite_options = null) {
		$request = $this->request;

		if (is_array($options) || is_null($options)) {
			
			if (is_null($options)) {
				$current_options = $this->request->get;	
				unset($current_options['url']);

				$overwrite_options = array_merge($current_options, $overwrite_options);
			}
			
			return APP_ROOT.$this->router->url_for($request->path, $options, $overwrite_options);
			
		} else {
			$options = $options.Route::build_query_string($overwrite_options);	
			
			return $options;
			
		}
	}
	
}


?>
