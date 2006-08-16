<?php

/**
 * simple_socket.php, part of the seed framework
 *
 * Class wrapping fsock functions.
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package Network
 */

class SimpleSocket {
	var $handle;
	
	var $open = false;
	
	var $error_number;
	
	var $error_string;
	
	/**
	 * Opens a socket to a given url
	 *
	 * @param mixed $url  A string or an url object
	 * @return bool
	 */
	function open($url) {
		
		if (is_string($url)) {
			$url = new URL($url);	
			
		}
		
		//Set the port number
		if($url->scheme == "https") {
			$ssl = "ssl://";
			
		} else {
			$ssl = '';
		}  
		
		$target_url = $ssl.$url->host;

		//Connect
		$this->handle = fsockopen($target_url, $url->port, $this->error_number, $this->error_string, 1); 	

		//Error checking
		$this->open = ($this->handle && true);

		return $this->open;
		
	}
	
	/**
	 * Writes a string to the stream
	 *
	 * @param string $string
	 * @return int The number of bytes written
	 */
	function put($string) {
		if (!$this->open) {
			return false;	
		}
		
		return fputs($this->handle, $string, strlen($string));
		
	}
	
	/**
	 * Gets a string from the stream
	 *
	 * @param int $length The number of bytes to read
	 * @return string
	 */
	function get($length = null) {
		if (!$this->open) {
			return false;	
		}
		
		return fgets($this->handle, $length);
		
	}
	
	/**
	 * Gets the entire stream in one read and closes the socket
	 *
	 * @return string
	 */
	function get_all() {
		if (!$this->open) {
			return false;	
		}		
		
		$response = '';
		
		//loop through the response from the server 
		while(!$this->eof()) {
			$response .= @fgets($this->handle, 1024);
		} 
	
		$this->close();
		
		return $response;		
		
	}
	
	/**
	 * Tests for the end of the stream
	 *
	 * @return bool
	 */
	function eof() {
		if (!$this->open) {
			return false;	
		}
		
		return feof($this->handle);	
		
	}

	/**
	 * Closes the stream
	 *
	 * @return bool
	 */
	function close() {
		$this->open = false;
		
		return fclose($this->handle);	
		
	}
	
}

?>