<?php

/**
 * response.php, part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package controller
 */

seed_include('network/http');

/**
 * Represents a response to an HTTP Request
 *
 * @package controller
 */
class Response extends HTTPResponse {
	/**
	 * The default headers to send on all requests
	 *
	 * @var array
	 */
	var $DEFAULT_HEADERS = array('Cache-Control' => 'no-cache', 'Content-Type' => 'text/html; charset=UTF-8');

	/**
	 * Constructor
	 *
	 * @return Response
	 */
	function Response() {
		$this->response_code = 200;
		$this->message = $this->response_code_string();
		$this->body = '';
		$this->headers = $this->DEFAULT_HEADERS;
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
			'Location' => $to_url
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
			print "<!-- Couldn't send headers, output already started at $file, line $line -->";
		}

		if($method == 'HEAD' || $this->body == '') {
			return;
		} else {
			print $this->body;
		}

	}



}