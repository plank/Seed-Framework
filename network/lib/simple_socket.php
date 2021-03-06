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
	var $_handle;

	var $connected = false;

	var $error_number;

	var $error_string;

	var $connect_timeout = 30;

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

		// Set the port number
		if($url->scheme == "https") {
			$ssl = "ssl://";
		} else {
			$ssl = '';
		}

		$target_url = $ssl.$url->host;

		// Connect
		$this->_handle = @fsockopen($target_url, $url->port, $this->error_number, $this->error_string, $this->connect_timeout);

		// Error checking
		$this->connected = is_resource($this->_handle);

		return $this->connected;

	}

	/**
	 * Writes a string to the stream
	 *
	 * @param string $string
	 * @return int The number of bytes written
	 */
	function put($string) {
		if (!$this->connected) {
			return false;
		}

		return fputs($this->_handle, $string, strlen($string));

	}

	/**
	 * Gets a string from the stream
	 *
	 * @param int $length The number of bytes to read
	 * @return string
	 */
	function get($length = null) {
		if (!$this->connected) {
			return false;
		}

		return fgets($this->_handle, $length);

	}

	/**
	 * Gets the entire stream in one read and closes the socket
	 *
	 * @return string
	 */
	function get_all() {
		if (!$this->connected) {
			return false;
		}

		$response = '';

		//loop through the response from the server
		while(!$this->eof()) {
			$response .= @fgets($this->_handle, 1024);
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
		if (!$this->connected) {
			return false;
		}

		return feof($this->_handle);

	}

	/**
	 * Closes the stream
	 *
	 * @return bool
	 */
	function close() {
		$this->connected = false;

		if (is_resource($this->_handle)) return fclose($this->_handle);

		return false;
	}

}

?>