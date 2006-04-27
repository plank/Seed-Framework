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
		
	function Response() {
		$this->body = '';
		$this->headers = $this->DEFAULT_HEADERS;
	}
	
	function add_header($header, $value) {
		$this->headers[$header] = $value;
	}
	
	function redirect($to_url, $permanently = false) {
		$this->headers = array(
			'Status' => $permanently ? "301 Moved Permanently" : "302 Found",
			'location' => $to_url
		);
		
		$this->body = "<html><body>You are being <a href=\"$to_url\">redirected</a>.</body></html>";

	}

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
	
}