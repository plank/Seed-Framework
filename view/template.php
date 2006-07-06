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
			if (file_exists(TEMPLATE_PATH.'layouts/'.$this->layout.'.php')) {
				ob_start();
				include(TEMPLATE_PATH.'layouts/'.$this->layout.'.php');
				$this->content_for_layout = ob_get_contents();
				ob_end_clean();
				
			} else {
				trigger_error('layouts/'.$this->layout.'.php not found');
				
			}
			
		}
		
		return $this->content_for_layout;
		
	}
	
	/**
	 * Renders a component
	 *
	 * @param array $options
	 * @return string
	 */
	function render_component($options = null) {
		if (!isset($options)) {
			$options = array();	
		}
		
		if (isset($options['controller'])) {
			$controller = $options['controller'];
		} else {
			$controller = $this->controller->get_type();	
		}
		
		$controller = Controller::factory($controller);
		
		if (!$controller) {
			return false;
		}
		
		$controller->layout = '';
		
		$request = $this->request;
		
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
		
		$partial = new Template();
		$partial->controller = $this->controller;
		
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
	 * @param Paginator $paginator
	 */
	function pagination_links($paginator, $link = null) {
		$current_page = $paginator->get_current_page();
		$num_pages = $paginator->page_count();
		$padding = 2;
		$link_array = null;
			
		if ($num_pages == 1) {
			return "";
		}
		
		if ($current_page->number == 1) {
			$return[] = "<span>&laquo;</span>";
			$return[] = "<span>1</span>";
		} else {
			$return[] = $this->link_to("&laquo;", $link, array('page'=>$current_page->number - 1));
			$return[] = $this->link_to('1', $link, array('page'=>1));
			
		}
	
		$low_page = $current_page->number - $padding;
		
		if ($low_page < 2) {
			$low_page = 2;	
		}
		
		$high_page = $current_page->number + $padding;
		
		if ($high_page > $num_pages - 1) {
			$high_page = $num_pages - 1;
		}

		if ($low_page > 2) {
			$return[] = '...';	
		}
		
		for($x = $low_page; $x <= $high_page; $x++) {
			if ($x == $current_page->number) {
				$return[] = $x;
			} else {
				$return[] = $this->link_to($x, $link, array('page'=>$x));	
			}
		}

		if ($high_page < $num_pages - 1) {
			$return[] = '...';	
		}
		
		if ($current_page->number == $num_pages) {
			$return[] = "<span>$num_pages</span>";
			$return[] = "<span>&raquo;</span>";
		} else {
			$return[] = $this->link_to($num_pages, $link, array('page'=>$num_pages));
			$return[] = $this->link_to("&raquo;", $link, array('page'=>$current_page->number + 1));
		}
	
		return implode(' ', $return);
	}
}

?>
