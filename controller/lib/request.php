<?php
/**
 * request.php, part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package controller
 */

seed_include('network/http');
seed_include('network/url');

/**
 * The following chunk of code strips slashes from the gpc array
 */
fix_magic_quotes();

/**
 * Simple request object
 *
 * @package controller
 */
class Request {
	/**
	 * The requested url
	 *
	 * @var URL
	 */
	var $url;
	
	/**
	 * The request GET variables
	 *
	 * @var array
	 */
	var $get;
	
	/**
	 * The request POST variable
	 *
	 * @var array
	 */
	var $post;
	
	/**
	 * The request cookies
	 *
	 * @var array
	 */
	var $cookies;
	
	/**
	 * The request parameters, which is the get merged with the post
	 *
	 * @var array
	 */
	var $parameters;
	
	/**
	 * The request FILE variables
	 *
	 * @var array
	 */
	var $files;
	
	/**
	 * The variables extracted from the requested path
	 *
	 * @var array
	 */
	var $path;
	
	/**
	 * The request session variables
	 *
	 * @var array
	 */
	var $session;
	
	/**
	 * Raw request input; this doesn't work on IIS!
	 *
	 * @var string
	 */
	var $body;
	
	/**
	 * The response method
	 *
	 * @var string
	 */
	var $method;

	/**
	 * Constructor
	 *
	 * @return Request
	 */
	function Request() {
		
		if (isset($_GET['url'])) {
			$this->url = new URL($_GET['url']); //, APP_ROOT);	
		} else {
			$this->url = new URL();
		}	
		
		$_GET = array_merge($_GET, $this->url->query_array);
		
/*		$this->get = & $_GET;
		$this->post = & $_POST;
		$this->cookies = & $_COOKIE;
		$this->files = & $_FILES;
		$this->session = & $_SESSION;*/

		$this->get = $_GET;
		$this->post = $_POST;
		$this->cookies = $_COOKIE;
		$this->files = $_FILES;
		$this->session = & $_SESSION;

		$this->parameters = array_merge($_GET, $_POST);	
		
		$this->method = $_SERVER['REQUEST_METHOD'];
		
		// allow emulation of DELETE and PUT via hidden field in POST
		if ($this->method == "POST" && isset($this->post['_method'])) {
			$this->method = $this->post['_method'];
			
		}
		
		// $this->body = file_get_contents('php://input');
		
	}
	
	/**
	 * Returns true if the request was a GET
	 *
	 * @return bool
	 */
	function is_get() {
		return $this->method == "GET";
	
	}
	
	/**
	 * Returns true if the request was a POST
	 *
	 * @return bool
	 */
	function is_post() {
		return $this->method == "POST";	
	
	}
	
	/**
	 * Returns true if the request was made via ajax
	 *
	 * @return bool
	 */
	function is_xml_http_request() {
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
			return $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
		} else {
			return false;	
		}
	}
	
	/**
	 * Alias for is_xml_http_request
	 *
	 * @return bool
	 */
	function is_ajax() {
		return $this->is_xml_http_request();	
	}
	
	/**
	 * Set the path parameters to the given array
	 *
	 * @param array $path
	 */
	function set_path_parameters($path = array()) {
		$this->path = $path;
		
		$this->parameters = array_merge($path, $_GET, $_POST);
	}
	
}