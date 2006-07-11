<?php
/**
 * request.php, part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package controller
 */


/**
 * The following chunk of code strips slashes from the gpc array
 */

// clean gpc of slashes
if (get_magic_quotes_gpc()) {
	$_GET = transcribe($_GET);
	$_POST = transcribe($_POST);
	$_COOKIE = transcribe($_COOKIE);	
	$_REQUEST = transcribe($_REQUEST);

	$HTTP_GET_VARS = $_GET;
	$HTTP_POST_VARS = $_GET;
	$HTTP_COOKIE_VARS = $_COOKIE;
}

/**
 * Recursively strips slashes from an array
 */
function transcribe($aList, $aIsTopLevel = true) {
   $gpcList = array();
   $isMagic = get_magic_quotes_gpc();
  
   foreach ($aList as $key => $value) {
       $decodedKey = ($isMagic && !$aIsTopLevel) ? stripslashes($key) : $key;
       
       if (is_array($value)) {
           $decodedValue = transcribe($value, false);
       } else {
           $decodedValue = ($isMagic) ? stripslashes($value):$value;
       }
       
       $gpcList[$decodedKey] = $decodedValue;
   }
   
   return $gpcList;
}

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
	var $input;
	

	/**
	 * Constructor
	 *
	 * @return Request
	 */
	function Request() {
		$this->url = new URL($_REQUEST['url']); //, APP_ROOT);
		$this->get = & $_GET;
		$this->post = & $_POST;
		$this->cookies = & $_COOKIE;
		$this->files = & $_FILES;
		$this->session = & $_SESSION;
		
		$this->parameters = array_merge($_GET, $_POST);	
		
		$this->method = $_SERVER['REQUEST_METHOD'];
		
		// allow emulation of DELETE and PUT via hidden field in POST
		if ($this->method == "POST" && isset($this->post['_method'])) {
			$this->method = $this->post['_method'];
			
		}
		
		$this->input = file_get_contents('php://input');
		
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
	 * Set the path parameters to the given array
	 *
	 * @param array $path
	 */
	function set_path_parameters($path = array()) {
		$this->path = $path;
		
		$this->parameters = array_merge($path, $_GET, $_POST);
	}
	
}