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
	var $get;
	var $post;
	var $cookies;
	var $parameters;
	var $files;
	var $path;
	var $session;
	
	function & get_request() {
		static $request;
		
		if (!isset($request)) {
			$request[0] = new Request();
		}
		
		return $request[0];
		
	}
		
	function Request() {
		$this->get = & $_GET;
		$this->post = & $_POST;
		$this->cookies = & $_COOKIE;
		$this->files = & $_FILES;
		$this->session = & $_SESSION;
		
		$this->parameters = array_merge($_GET, $_POST);		
	}
	
	function set_path_parameters($path = array()) {
		$this->path = $path;
		
		$this->parameters = array_merge($path, $_GET, $_POST);
	}
	
}