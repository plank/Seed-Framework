<?php

/**
 * http.php, part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package Network
 */

/**
 * Class for making HTTP requests
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package Network
 */
class HTTP {
	
	/**
	 * The currently connected URL
	 *
	 * @var URL
	 */
	var $url;
	
	/**
	 * The socket object being used to make requests
	 *
	 * @var SimpleSocket
	 */
	var $socket;
	
	/**
	 * Constructor
	 *
	 * @param SimpleSocket $socket
	 */
	function HTTP(& $socket) {
		$this->socket = & $socket;
	}
	
	/**
	 * Opens a connection to the given url
	 *
	 * @param mixed $url
	 * @return bool
	 */
	function open($url) {
		if (is_string($url)) {
			$url = new URL($url);	
			
		}		
		
		$this->url = $url;
		
		return $this->socket->open($url);
		
	}
	
	/**
	 * Closes the currently open connection
	 *
	 * @return bool
	 */
	function close() {
		return $this->socket->close();
		
	}

	/**
	 * Posts the given data to the currently open URL
	 *
	 * @param mixed $data
	 * @return HTTPResponse
	 */
	function post($data) {
		
		if (!$this->socket->connected) {
			trigger_error("Socket not connected", E_USER_WARNING);
			return false;	
		}
		
		if (is_array($data)) {
			$data = $this->build_data_string($data);	
		}
	
		if (!is_string($data)) {
			trigger_error("Posted data must either be a string or an array", E_USER_WARNING);
			return false;
		}
		
		$headers = array(
			'Host'=>$this->url->host,
			'Content-type'=>'application/x-www-form-urlencoded; charset=utf-8',
			'Content-length'=>strlen($data),
			'Connection'=>'close'
		);
		
		$this->_write_headers("POST", $headers);
		
		$this->socket->put($data."\r\n\r\n");
		
		$response = new HTTPResponse();
		
		$response->parse_response($this->socket->get_all());
		
		return $response;
		
	}
	
	/**
	 * Returns the http response code for the current url, if it's valid
	 *
	 * @return HTTPResponse
	 */
	function head() {
		if (!$this->socket->connected) {
			trigger_error("Socket not connected", E_USER_WARNING);
			return false;	
		}
		
		$headers = array(
			'Host'=>$this->url->host,
			'Connection'=>'close'
		);		
		
		$this->_write_headers('HEAD', $headers);
	
		$response = new HTTPResponse();
		
		$response->parse_response($this->socket->get_all());
		
		return $response;
		
	}

	/**
	 * Returns the complete code for the current url, if it's valid
	 *
	 * @return HTTPResponse
	 */
	function get() {
		if (!$this->socket->connected) {
			trigger_error("Socket not connected", E_USER_WARNING);
			return false;	
		}
		
		$headers = array(
			'Host'=>$this->url->host,
			'Connection'=>'close'
		);			
	
		$this->_write_headers('GET', $headers);
	
		$response = new HTTPResponse();
		
		$response->parse_response($this->socket->get_all());
		
		return $response;
		
	}	
		
	
	/**
	 * Write the headers for the given method to the socket
	 *
	 * @param string $method
	 * @param array $headers
	 */
	function _write_headers($method, $headers) {
		$this->socket->put($method." ".$this->url->path." HTTP/1.1\r\n"); // :".$this->url->port."
		
		foreach ($headers as $key => $value) {
			$this->socket->put($key.": ".$value."\r\n");	
			
		}
		
		$this->socket->put("\r\n");
	}
	
	/**
	 * Builds a post string using an array
	 *
	 * @param array @data
	 * @return string
	 */	
	function build_data_string($data) {
		
		$result = array();
		
		foreach($data as $field => $value) {
			if ($value) {
				$result[] = $field."=".urlencode($value);
			}

		}
		
		if (count($result)) {
			return implode('&', $result);	
			
		}		
		
		return '';
		
	}
	
}


class HTTPResponse {
	
	/**
	 * The http version of the response
	 *
	 * @var string
	 */
	var $http_version;
	
	/**
	 * The response code
	 *
	 * @var int
	 */
	var $response_code;

	/**
	 * The response message
	 *
	 * @var string
	 */
	var $message;
	
	/**
	 * The headers
	 *
	 * @var array
	 */
	var $headers;

	/**
	 * The response body
	 *
	 * @var string
	 */
	var $body;
	
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
	 * Parses a given response string
	 *
	 * @param string $string
	 */
	function parse_response($string) {
		list($headers, $this->body) = explode("\r\n\r\n", $string, 2);
		
		$headers = explode("\r\n", $headers);
		
		list($this->http_version, $this->response_code, $this->message) = $this->parse_status_line(array_shift($headers));
		
		$this->headers = $this->parse_headers($headers);
		
	}
	
	/**
	 * Parses an http status line
	 *
	 * @param string $string
	 * @return array
	 */
	function parse_status_line($string) {
		$pattern = preg_match('/\AHTTP(?:\/(\d+\.\d+))?\s+(\d\d\d)\s*(.*)\z/', $string, $matches);
		
		if (count($matches) == 4) {
			array_shift($matches);	
			return $matches;
		} else {
			return false;
		}
		
	}
	
	/**
	 * Parses an array of headers
	 *
	 * @param array $headers
	 * @param array
	 */
	function parse_headers($headers) {
		$result = array();
		
		foreach ($headers as $header) {
			preg_match('/\A([^:]+):\s*(.*)/', $header, $matches);
			if (count($matches) == 3) {
				$result[$matches[1]] = $matches[2];	
			}
			
		}
		
		return $result;
		
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

?>