<?php

/**
 * url.php, part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package library
 */

/**
 * Class representing urls
 *
 * @package library
 *
 */
class URL {
	var $input_url;
	var $input_base_url;

	var $scheme = 'http';
	var $ip;
	var $host;
	var $port;
	var $user;
	var $pass;
	var $path = '/';
	var $query;
	var $query_array;
	var $fragment;
	var $directory;
	var $file_name;
	var $base_name;
	var $extension;

	var $default_ports = array('http'=>'80', 'https'=>'443');

	/**
	 * Constructor
	 *
	 * @param string $url
	 * @return URL
	 */
	function URL($url = '', $relative_to = '') {

		$this->query_array = array();

		//debug($url, $relative_to);
		if (!$url) {
			return false;
		}

		$this->input_url = $url;
		$this->input_base_url = $relative_to;

		$parts = @parse_url($url);

		if (!is_array($parts)) {
			return false;
		}

		// if there's no scheme, treat the url a relative
		if (!isset($parts['scheme'])) {
			$new_parts = @parse_url($relative_to);

			$transfer = array('scheme', 'host', 'user', 'pass');

			foreach ($transfer as $key) {
				if (isset($new_parts[$key])) {
					$parts[$key] = $new_parts[$key];
				}
			}

			if (substr($url, 0, 1) != '/') {
				//debug($parts, $new_parts);

				if (!isset($parts['path'])) {
					$parts['path'] = '';
				}

				// if the relative path is a directory
				if (substr($new_parts['path'], -1) == '/') {
					$parts['path'] = $new_parts['path'].$parts['path'];

				} else {
					$parts['path'] = dirname($new_parts['path']).'/'.$parts['path'];

				}

			}

		}

		// assign the values to the object
		foreach($parts as $key => $value) {
			$this->$key = $value;

		}

		// split the query string into an array of values
		if ($this->query) {
			$query_parts = explode('&', $this->query);

			foreach ($query_parts as $query_part) {
				$key_values = explode('=', $query_part, 2);

				$key = $key_values[0];

				if (isset($key_values[1])) {
					$value = $key_values[1];

				} else {
					$value = '';

				}

				$this->query_array[$key] = $value;

			}

		}

		// clean the path, by removing /../ etc
		$this->path = $this->clean_path($this->path);

		// get the ip of the host, for future reference
		$ip = gethostbyname($this->host);

		if ($ip != $this->host) {
			$this->ip = $ip;
		}

		// get the port from the defaults
		if (!$this->port && key_exists($this->scheme, $this->default_ports)) {
			$this->port = $this->default_ports[$this->scheme];
		}

		// get filename
		$this->file_name = basename($this->path);

		// get directory name
		$this->directory = dirname($this->path);

		if ($this->directory != '/') {
			$this->directory .= '/';
		}

		// get extension and base name
		$file_name = explode('.', $this->file_name);

		if (count($file_name) == 1) {
			$this->base_name = $file_name[0];
			$this->extension = '';

		} else {
			$this->extension = array_pop($file_name);
			$this->base_name = implode('.', $file_name);

		}

	}

	/**
	 * Returns true if the url is validely formed
	 *
	 * @return bool
	 */
	function is_valid() {
		return ($this->scheme != '' && $this->host != '');

	}

	function to_path_string($include_query = true, $include_fragment = true) {
		$url = $this->path;

		if ($this->query && $include_query) {
			if ($include_query === true) {
				$url .= "?".$this->query;

			} else {

				if (!is_array($include_query)) {
					$include_query = array($include_query);

				}

				$query_parts = array();

				foreach($include_query as $key) {
					if (isset($this->query_array[$key])) {
						$query_parts[] = $key.'='.$this->query_array[$key];
					}
				}

				if (count($query_parts)) {
					$url .= "?".implode('&', $query_parts);
				}
			}
		}

		if ($this->fragment && $include_fragment) {
			$url .= "#".$this->fragment;
		}

		return $url;
	}

	/**
	 * Builds a url string with the various parts
	 *
	 * @return string
	 */
	function to_string($include_query = true, $include_fragment = true, $include_credentials = true) {
		if (!$this->is_valid()) {
			return false;
		}

		$url = $this->scheme."://";

		if ($this->user && $include_credentials) {
			if ($this->pass) {
				$url .= $this->user.":".$this->pass."@";
			} else {
				$url .= $this->user."@";
			}
		}

		$url .= $this->host.$this->to_path_string($include_query, $include_fragment);

		return $url;

	}

	/**
	 * Cleans a path by removing extraneous slashes, and resolving '.' and '..'s
	 *
	 * @param string $path
	 * @return string
	 */
	function clean_path($path) {
		$result = array();
		// $path_array = preg_split('/[\/\\\]/', $path);
		$path_array = explode('/', $path);

		// if the last element is empty, that means there's a trailing slash
		$has_trailing_slash = !end($path_array);

		// remove empty entries and single periods
		$path_array = array_diff($path_array, array('', '.'));

		// if we're left with nothing, return a single slash
		if (!count($path_array)) {
		   return '/';
		}

		// build the parts
		foreach ($path_array as $dir) {
			if ($dir == '..') {
				array_pop($result);

			} else {
				array_push($result, $dir);

			}
		}

		// make sure there's an ending slash, if needed
		if ($has_trailing_slash) {
			array_push($result, '');

		}

		return '/'.implode('/', $result);

	}

	/**
	 * Checks if the given string has a scheme component
	 *
	 * @param string $string
	 * @return bool
	 */
	function has_scheme($string) {
		return preg_match('@^[a-zA-Z]*://.*$@', $string);

	}

}



?>