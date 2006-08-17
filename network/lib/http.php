<?php


class HTTP {
	
	/**
	 * @var URL
	 */
	var $url;
	
	/**
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
			'Content-type'=>'application/x-www-form-urlencoded',
			'Content-length'=>strlen($data),
			'Connection'=>'close'
		);
		
		$this->_write_headers("POST", $headers);
		
		$this->socket->put($data."\r\n\r\n");
		
		return $this->socket->get_all();
		
	}
	
	/**
	 * Write the headers for the given method to the socket
	 *
	 * @param string $method
	 * @param array $headers
	 */
	function _write_headers($method, $headers) {
		$this->socket->put($method." ".$this->url->path.":".$this->url->port." HTTP/1.1\r\n");
		
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

?>