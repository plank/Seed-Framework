<?php

/**
 * part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package view
 * @subpackage template
 */

/**
 * Template class
 *
 * @package view
 * @subpackage template
 */
class Template {
	
	/**
	 * The name of the layout to use
	 *
	 * @var string
	 */
	var $layout;
	
	/**
	 * @var array
	 */
	var $params;
	
	/**
	 * Reference to the parent controller
	 *
	 * @var Controller
	 */
	var $controller;
	
	/**
	 * 
	 */
	var $_path;
	
	/**
	 * Flash 
	 *
	 * @var Flash
	 */
	var $flash;
	
	/**
	 * Constructor
	 *
	 * @return Template
	 */
	function Template() {

	}
	
	/**
	 * Renders the given file
	 *
	 * @param string $template_path
	 * @return string
	 */
	function render_file($template_path) {
		/* any variables found here end up in the templates global scope */
		
		ob_start();
		include($template_path);		
		$this->content_for_layout = ob_get_contents();
		ob_end_clean();		
		
		if ($this->layout) {
			
			$layout_path = $this->get_layout_path();
			
			if (file_exists($layout_path)) {
				ob_start();
				include($layout_path);
				$this->content_for_layout = ob_get_contents();
				ob_end_clean();
				
			} else {
				trigger_error("layout '$layout_path' was not found", E_USER_ERROR);
				
			}
			
		}
		
		return $this->content_for_layout;
		
	}
	
	/**
	 * Returns the path to the current layout
	 *
	 * @return string
	 */
	function get_layout_path() {
		if (substr($this->layout, 0, 1) == '/') {
			return $this->layout;			
		} else {
			return TEMPLATE_PATH.'layouts/'.$this->layout.'.php';
		}
		
	}
	
	
	/**
	 * Renders a component
	 *
	 * @param array $options
	 * @return string
	 */
	function render_component($options = null) {
		
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
		
		$controller->layout = '';
		
		$request = clone($this->request);
		
		if (isset($options)) {
			$request->parameters = array_merge($request->parameters, $options);
			$request->get = array_merge($request->get, $options);
		}

		$response = $controller->process($request, new Response());
		return $response->body;
		
	}
	
	/**
	 * Renders a partial. 
	 * 
	 * @param string $partial_name
	 * @param array $local_assigns
	 * @return string
	 */
	function render_partial($partial_name, $local_assigns = null) {
		
		$partial_path = dirname($partial_name);
		
		if ($partial_path == '.') {
			$partial_path = TEMPLATE_PATH.$this->controller->get_type();
		} else {
			$partial_path = TEMPLATE_PATH.dirname($partial_name);
		}
		
		$partial_path .= '/_'.basename($partial_name).".php";
		
		if (!file_exists($partial_path)) {
			trigger_error("partial '$partial_name' not found in '$partial_path'");
			return false;
		}
		
		$partial = & new Template();
		$partial->helper = & $this->helper;
		$partial->controller = & $this->controller;
		$partial->flash = & $this->flash;
		
		if (isset($local_assigns)) {
			foreach($local_assigns as $name => $value) {
				$partial->$name = $value;
			}
		}
		
		return $partial->render_file($partial_path);
		
	}
	
	/**
	 * Return an html link
	 *
	 * @param string $text The text to display in the link
	 * @param mixed $added_vars If added_vars is a string, it is treated as a URL. If it's an array, it's used as parameters
	 * for url_for.
	 * @return string
	 */
	function link_to($text = '', $options = null, $overwrite_options = null, $html_options = null) {
		// if $options is false, simply return the text
		if (!$options && !$overwrite_options) {
			return $text;
		}
	
		// if it's a string,
		if (!is_array($options) && !is_array($overwrite_options)) {
			// link directly to it
			$link = $options;			
			
		} else {
			// use $options as params for url_for
			$link = $this->controller->url_for($options, $overwrite_options);
			
		}
		
		if (isset($html_options)) {
			$attributes = array();
			
			foreach ($html_options as $key => $value) {
				$attributes[] = "$key='".htmlspecialchars($value, ENT_QUOTES)."'";
			}
			
			$attributes = implode(" ", $attributes);
			
			return "<a href='".$link."' $attributes>$text</a>";
		
		}
		
		return "<a href='".$link."'>$text</a>";		
		
		
	}

	/**
	 * Returns a button that posts to a given url
	 *
	 * @return string
	 */
	function button_to($text = '', $options = null, $overwrite_options = null) {
		// if $options is false, simply return the text
		if (!$options && !$overwrite_options) {
			return $text;
		}
	
		// if it's a string,
		if (!is_array($options) && !is_array($overwrite_options)) {
			// link directly to it
			$link = $options;			
			
		} else {
			// use $options as params for url_for
			$link = $this->controller->url_for($options, $overwrite_options);
			
		}
		
		$result = "<form method='post' action='$link' class='button-to'>";
		$result .= "<div><input type='submit' value='$text' /></div>";
		$result .= "</form>";
		
		return $result;
		
	}
	
	function url_for($options = null, $overwrite_options = null) {
		return $this->controller->url_for($options, $overwrite_options);	
		
	}
	
	function link_to_stylesheet($style_sheet) {
		return "<link rel='stylesheet' type='text/css' href='".APP_ROOT."_styles/$style_sheet' />";
	}
	
	function show_flash($name, $class_name = null) {
		
		$value = $this->flash->get($name);
		
		if (!$value) {
			return '';
		}
		
		if (!isset($class_name)) {
			$class_name = $name;
		}
		
		if (is_array($value)) {
			return "<ul class='$class_name'><li>".implode("</li><li>", $value)."</li></ul>";
			
		} else {
			return "<span class='$class_name'>$value</span>";
		}

	}
	
	/**
	 * @param Paginator $paginator
	 */	
	function results($paginator) {
		$page = $paginator->get_current_page();
		
		$return = "viewing results ".($page->first_item());
		$return .= " - ".($page->last_item());
		$return .= " of ".$paginator->item_count;
		
		return $return;
	}	
	
	/**
	 * Returns a string containing pagination links
	 *
	 * @param Paginator $paginator
	 */
	function pagination_links($paginator) {
		$links = new PaginationView($this, $paginator);
		
		return $links->generate();
	}
	
	function get_params() {
		$params = $this->controller->request->get;
		unset($params['url']);

		return $params;
		
	}
}

?>
