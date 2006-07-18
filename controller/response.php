<?php

/**
 * response.php, part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package controller
 */

/**
 * Represents a response to an HTTP Request
 *
 * @package controller
 */
class Response {
	/**
	 * The default headers to send on all requests
	 *
	 * @var array
	 */
	var $DEFAULT_HEADERS = array('Cache-Control' => 'no-cache', 'Content-Type' => 'text/html; charset=UTF-8');

	/**
	 * The response code to send, defaults to 200 Found
	 *
	 * @var int
	 */
	var $response_code = 200;
	
	/**
	 * The headers to send
	 *
	 * @var array
	 */
	var $headers;

	/**
	 * The response body to send
	 *
	 * @var string
	 */
	var $body;
	
	/**
	 * Constructor
	 *
	 * @return Response
	 */
	function Response() {
		$this->body = '';
		$this->headers = $this->DEFAULT_HEADERS;
	}
	
	/**
	 * Adds a given header with the given value to the response
	 *
	 * @param string $header
	 * @param string $value
	 */
	function add_header($header, $value) {
		$this->headers[$header] = $value;
	}
	
	/**
	 * Performs a redirect to the given url
	 *
	 * @param string $to_url The url to redirect to.
	 * @param bool $permanently Set to true to return a 301 response, false for a 302
	 */
	function redirect($to_url, $permanently = false) {
		
		$this->response_code = $permanently ? 301 : 302;

		$this->headers = array(
			'location' => $to_url
		);
		
		$this->body = "<html><head><title>".$this->response_code_string($this->response_code)."</title></head>";
		$this->body .= "<body>You are being <a href=\"$to_url\">redirected</a>.</body></html>";

	}

	/**
	 * Returns a generic response with a given code
	 *
	 * @param int $code The response code to return
	 * @param string $message An optional message to add to the response
	 */
	function status($code, $message = '') {
		$this->response_code = $code;
		
		$this->body = "<html><head><title>".$this->response_code_string($code)."</title></head><body><h1>".$this->response_code_string($code)."</h1>";
		
		if ($message) {
			$this->body .= "<p>".$message."</p>";	
		}
		
		$this->body .= "</body></html>";
	}
	
	
	/**
	 * Outputs all the headers, followed by the body if the request method requires it
	 *
	 * @param string $method
	 */
	function out($method = 'GET') {
		if (!headers_sent($file, $line)) {
			header('HTTP/1.1 '.$this->response_code_string($this->response_code));

			foreach ($this->headers as $header => $value) {
				header("$header: $value");
			}
		} else {
			// print "Couldn't send headers, output already started at $file, line $line";	
		}
		
		if($method == 'HEAD' || $this->body == '') {
			return;
		} else {
			print $this->body;
		}
		
	}
	
	/**
	 * Returns the proper string for a given response code
	 *
	 * @param int $code
	 * @return string
	 */
	function response_code_string($code = 200) {
		$codes = array (
		    100 => "Continue",
		    101 => "Switching Protocols",
		    200 => "OK",
		    201 => "Created",
		    202 => "Accepted",
		    203 => "Non-Authoritative Information",
		    204 => "No Content",
		    205 => "Reset Content",
		    206 => "Partial Content",
		    300 => "Multiple Choices",
		    301 => "Moved Permanently",
		    302 => "Found",
		    303 => "See Other",
		    304 => "Not Modified",
		    305 => "Use Proxy",
		    307 => "Temporary Redirect",
		    400 => "Bad Request",
		    401 => "Unauthorized",
		    402 => "Payment Required",
		    403 => "Forbidden",
		    404 => "Not Found",
		    405 => "Method Not Allowed",
		    406 => "Not Acceptable",
		    407 => "Proxy Authentication Required",
		    408 => "Request Time-out",
		    409 => "Conflict",
		    410 => "Gone",
		    411 => "Length Required",
		    412 => "Precondition Failed",
		    413 => "Request Entity Too Large",
		    414 => "Request-URI Too Large",
		    415 => "Unsupported Media Type",
		    416 => "Requested range not satisfiable",
		    417 => "Expectation Failed",
		    500 => "Internal Server Error",
		    501 => "Not Implemented",
		    502 => "Bad Gateway",
		    503 => "Service Unavailable",
		    504 => "Gateway Time-out"
		);	
		
        if (isset($codes[$code])) {
        	return $code.' '.$codes[$code];
        	
        } else {
        	return false;
        	
        }
        
	}
	
}