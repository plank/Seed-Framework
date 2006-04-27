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
	var $fragment;

	var $default_ports = array('http'=>'80', 'https'=>'443');
	
	/**
	 * Constructor
	 *
	 * @param string $url
	 * @return URL
	 */
	function URL($url = '', $relative_to = '') {

		if ($url) {
			$this->input_url = $url;
			$this->input_base_url = $relative_to;
			
			$parts = @parse_url($url);

			if (!is_array($parts)) {
				return false;	
			}
			
			// if there's no scheme, treat the url a relative
			if (!isset($parts['scheme'])) {
				$parts = @parse_url($relative_to);
				
				if (substr($url, 0, 1) == '/') {
					$parts['path'] = $url;	
					
				} else {
					$parts['path'] = dirname($parts['path']).'/'.$url;	
					
				}
				
			}

			// assign the values to the object
			foreach($parts as $key => $value) {
				$this->$key = $value;	
			
			}

			$this->path = $this->clean_path($this->path);
			
			// get the ip of the host, for future reference
			$ip = gethostbyname($this->host);
			
			if ($ip != $this->host) {
				$this->ip = $ip;
			}
			
			if (!$this->port && key_exists($this->scheme, $this->default_ports)) {
				$this->port = $this->default_ports[$this->scheme];
			}
			
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

	/**
	 * Builds a url string with the various parts
	 *
	 * @return string
	 */
	function to_string() {
		if (!$this->is_valid()) {
			return false;	
		}
		
		$url = $this->scheme."://";
		
		if ($this->user) {
			if ($this->pass) {
				$url .= $this->user.":".$this->pass."@";
			} else {
				$url .= $this->user."@";	
			}
		}
		
		$url .= $this->host.$this->path;
		
		if ($this->query) {
			$url .= "?".$this->query;	
		}
		
		if ($this->fragment) {
			$url .= "#".$this->fragment;	
		}
		
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
	 * Returns the http response code for the current url, if it's valid
	 *
	 * @return string
	 */
	function head() {
		if (!$this->is_valid() || !$this->ip) {
			return false;	
		}
		
		$sock = new Socket();

		if (!$sock->open($this)) {
			return false;	
		}
	
		$sock->put("HEAD {$this->path} HTTP/1.0\r\n"); 
		$sock->put("Host: {$this->host}\r\n");
		$sock->put("Connection: close\r\n\r\n");
	
		return $sock->get_all(); 
		
	}

	/**
	 * Returns the complete code for the current url, if it's valid
	 *
	 * @return string
	 */
	function get() {
		if (!$this->is_valid() || !$this->ip) {
			return false;	
		}
		
		$sock = new Socket();

		if (!$sock->open($this)) {
			return false;	
		}
	
		$sock->put("GET {$this->path} HTTP/1.0\r\n"); 
		$sock->put("Host: {$this->host}\r\n");
		$sock->put("Connection: close\r\n\r\n");
	
		return $sock->get_all(); 		
		
	}	
	
}


class WebPage {
	var $raw;

	/**
	 * @var URL
	 */
	var $url;
	
	/**
	 * @var string
	 */
	var $response_code;
	/**
	 * @var array
	 */
	var $headers;
	/**
	 * @var string
	 */
	var $content;
	
	function open($url) {
		
		if (is_string($url)) {
			$url = new URL($url);	
		}
		
		$this->url = $url;
		
		$this->raw = $url->get();

		if (!$this->raw) {
			return false;	
		}
		
		list($raw_headers, $this->content) = explode("\r\n\r\n", $this->raw, 2);
		
		$headers = explode("\r\n", $raw_headers);
		
		$this->response_code = array_shift($headers);
		
		foreach ($headers as $header) {
			list($key, $value) = explode(': ', $header);
			
			$this->headers[$key] = $value;
			
		}
		
		return true;
		
	}
	
	function get_title() {
		preg_match('@<title>(.*)</title>@is', $this->content, $matches);	
		
		return $matches[1];
		
	}
	
	function get_html_contents() {
		preg_match('@<html>(.*)</html>@is', $this->content, $matches);	
		
		return $matches[1];
		
		
	}

	function get_plain_text() {
		$text = $this->remove_tag_and_contents('script', $this->get_html_contents());
		$text = $this->remove_tag_and_contents('style', $text);
		$text = $this->strip_extra_whitespace($text);
		// remove extraneous whitespace
		return strip_tags($text);	
		
	}
	
	function get_links($url) {
	   
	   //Pattern building across multiple lines to avoid page distortion.
	   $pattern  = "/((@import\s+[\"'`]([\w:?=@&\/#._;-]+)[\"'`];)|";
	   $pattern .= "(:\s*url\s*\([\s\"'`]*([\w:?=@&\/#._;-]+)";
	   $pattern .= "([\s\"'`]*\))|<[^>]*\s+(src|href|url)\=[\s\"'`]*";
	   $pattern .= "([\w:?=@&\/#._;-]+)[\s\"'`]*[^>]*>))/i";
	   
	   preg_match_all ($pattern, $this->content, $matches);
	   return (is_array($matches)) ? $matches : false;
	   
	}
	
	/**
	 * Returns all the link sources in a piece of text as a string
	 *
	 * @param string $text the text to search in
	 * @param string $must_contain If this is set, only 
	 * @return array
	 */
	function extract_links($must_contain = '', $mode = 'href') {
		if (is_array($mode)) {
			$mode = "(".implode('|', $mode).")";	
		}
		
		preg_match_all("/".$mode."[\\s]*=[\\s]*('|\")([^\"']*)('|\")/", $this->content, $matches);
		
		$results = array();
		
		foreach($matches[2] as $result) {
			if (!$must_contain || substr_count($result, $must_contain)) {
				$results[] = new URL($result, $this->url->to_string());
			}
			
		}
	
		if (count($results) == 0 ) {
			return false;		
		} 
	
		return $results;
		
	}
	
	function remove_tag_and_contents($tag, $string) {
		return preg_replace("'<{$tag}[^>]*>.*</{$tag}>'siU", '', $string);
	}
	
	function strip_extra_whitespace($string) {
		// $string = preg_replace("'\s{1,}'m", ' ', $string);
		$string = preg_replace("'&nbsp;'m", ' ', $string);
		return trim(preg_replace("'(\s){1,}'m", ' ', $string));
		
		
	}
	
}

?>