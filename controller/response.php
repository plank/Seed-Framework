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
	var $DEFAULT_HEADERS = array('Cache-Control' => 'no-cache', 'Content-Type' => 'text/html; charset=UTF-8');
	
	var $body;
	var $headers;
	
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
		$this->headers = array(
			'Status' => $this->response_code($permanently ? 301 : 302),
			'location' => $to_url
		);
		
		$this->body = "<html><body>You are being <a href=\"$to_url\">redirected</a>.</body></html>";

	}

	/**
	 * Outputs all the headers, followed by the body if the request method requires it
	 *
	 */
	function out() {
		if (!headers_sent($file, $line)) {
			foreach ($this->headers as $header => $value) {
				header("$header: $value");
			}
		} else {
			// print "Couldn't send headers, output already started at $file, line $line";	
		}
		
		if($_SERVER['REQUEST_METHOD'] == 'HEAD') {
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
	function response_code($code = 200) {
        $codes = array (
            100 => "HTTP/1.1 100 Continue",
            101 => "HTTP/1.1 101 Switching Protocols",
            200 => "HTTP/1.1 200 OK",
            201 => "HTTP/1.1 201 Created",
            202 => "HTTP/1.1 202 Accepted",
            203 => "HTTP/1.1 203 Non-Authoritative Information",
            204 => "HTTP/1.1 204 No Content",
            205 => "HTTP/1.1 205 Reset Content",
            206 => "HTTP/1.1 206 Partial Content",
            300 => "HTTP/1.1 300 Multiple Choices",
            301 => "HTTP/1.1 301 Moved Permanently",
            302 => "HTTP/1.1 302 Found",
            303 => "HTTP/1.1 303 See Other",
            304 => "HTTP/1.1 304 Not Modified",
            305 => "HTTP/1.1 305 Use Proxy",
            307 => "HTTP/1.1 307 Temporary Redirect",
            400 => "HTTP/1.1 400 Bad Request",
            401 => "HTTP/1.1 401 Unauthorized",
            402 => "HTTP/1.1 402 Payment Required",
            403 => "HTTP/1.1 403 Forbidden",
            404 => "HTTP/1.1 404 Not Found",
            405 => "HTTP/1.1 405 Method Not Allowed",
            406 => "HTTP/1.1 406 Not Acceptable",
            407 => "HTTP/1.1 407 Proxy Authentication Required",
            408 => "HTTP/1.1 408 Request Time-out",
            409 => "HTTP/1.1 409 Conflict",
            410 => "HTTP/1.1 410 Gone",
            411 => "HTTP/1.1 411 Length Required",
            412 => "HTTP/1.1 412 Precondition Failed",
            413 => "HTTP/1.1 413 Request Entity Too Large",
            414 => "HTTP/1.1 414 Request-URI Too Large",
            415 => "HTTP/1.1 415 Unsupported Media Type",
            416 => "HTTP/1.1 416 Requested range not satisfiable",
            417 => "HTTP/1.1 417 Expectation Failed",
            500 => "HTTP/1.1 500 Internal Server Error",
            501 => "HTTP/1.1 501 Not Implemented",
            502 => "HTTP/1.1 502 Bad Gateway",
            503 => "HTTP/1.1 503 Service Unavailable",
            504 => "HTTP/1.1 504 Gateway Time-out"
        );		
		
        if (isset($codes[$code])) {
        	return $codes[$code];
        	
        } else {
        	return false;
        	
        }
        
	}
	
}