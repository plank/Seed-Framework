<?php

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
	
	/**
	 * Opens the page at the given url
	 *
	 * @param string $url The page to open
	 * @param bool $headers_only If true, get the headers only and ignore the content
	 * @return bool
	 */
	function open($url, $headers_only = false) {
		
		if (is_string($url)) {
			$url = new URL($url);	
		}

		assert($url);
		
		$this->url = $url;
		
		if ($headers_only) {
			$this->raw = $url->head();	
		} else {
			$this->raw = $url->get();
		}

		if (!$this->raw) {
			return false;	
		}
		
		list($raw_headers, $this->content) = explode("\r\n\r\n", $this->raw, 2);
		
		$this->parse_headers($raw_headers);
		
		return true;
		
	}
	
	/**
	 * Parse http headers
	 *
	 * @param string $raw_headers
	 */
	function parse_headers($raw_headers) {
		$headers = explode("\r\n", $raw_headers);
		
		$this->response_code = array_shift($headers);
		
		foreach ($headers as $header) {
			list($key, $value) = explode(': ', $header);
			
			$this->headers[$key] = $value;
			
		}		
		
	}
	
	/**
	 * Returns the title of the document
	 *
	 * @return string
	 */
	function get_title() {
		preg_match('@<title>(.*)</title>@is', $this->content, $matches);	
		
		if (isset($matches[1])) {
			return $matches[1];
		} else {
			return '';	
		}
		
	}
	
	/**
	 * Returns the html contents of the document
	 *
	 * @return string
	 */
	function get_html_contents() {
		preg_match('@<html>(.*)</html>@is', $this->content, $matches);	
		
		if (isset($matches[1])) {
			return $matches[1];
		} else {
			return '';	
		}
		
		
	}

	/**
	 * Returns the base href of the document, if it has one
	 *
	 * @return string
	 */
	function get_base_href() {
		preg_match("/base[\\s]*href[\\s]*=[\\s]*('|\")([^\"']*)('|\")/is", $this->content, $matches);	
		
		if (isset($matches[2])) {
			return $matches[2];
		} else {
			return '';	
		}		
		
	}
	
	/**
	 * Returns a plain text version of the html contents
	 *
	 * @return string
	 */
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
		
		$base_href = $this->get_base_href();
		
		if (!$base_href) {
			$base_href = $this->url->to_string();
		}
		
		foreach($matches[2] as $result) {
			if (!$must_contain || substr_count($result, $must_contain)) {
				$url = new URL($result, $base_href);
				$results[$url->to_string()] = $url;
			}
		}
	/*
		if (count($results) == 0 ) {
			return false;		
		} 
	*/
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