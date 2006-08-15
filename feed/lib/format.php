<?php

/**
 * Base feed Format class
 *
 * @package feed
 * @abstract 
 */
class FeedFormat {
	
	/**
	 * @var Feed
	 */
	var $feed;
	
	/**
	 * The content type header value to send
	 * @var string
	 */
	var $content_type = 'Text/XML';
	
	/**
	 * The protocol of the feed (atom or RSS)
	 * @var string
	 */
	var $protocol;
	
	/**
	 * The version of the protocol
	 * @var string
	 */
	var $version;

	/**
	 * The date format to use for dates
	 */
	var $date_format = RFC3339_DATE_FORMAT;

	
	function factory($format) {
		$format = ucfirst(strtolower($format));
		
		$class_name = $format.'Format';
		
		if (class_exists($class_name)) {
			return new $class_name;	
		}
		
		$file_name = dirname(__FILE__).'/formats/'.$format.'.php';
		
		if (!file_exists($file_name)) {
			trigger_error("Couldn't find an adapter for feed format $format");	
			return false;
		}
		
		require_once($file_name);
		
		if (!class_exists($class_name)) {
			trigger_error("Adapter file didn't contain adapter for feed format $format");
			return false;	
		}
		
		return new $class_name;
		
	}
	
	function detect($data) {
		
	}
	
	/**
	 * Generates a feed using the passed feed object
	 *
	 * @param Feed $feed
	 */	
	function generate(& $feed) {
		$feed->sortEntries();
		
	}

	
	/**
	 * Parses a feed, populating the passed feed object
	 *
	 * @param mixed $data
	 * @return bool	 
	 */		
	function parse($data) {


		
	}
	
	/**
	 * Parses a string of xml data into a Xml Tree
	 *
	 * @param string $data
	 * @return XmlNode
	 */
	function prepare_data($data) {
		if (is_string($data)) {
			$parser = new XmlParser();
		
			$data = $parser->parse($data); 
		}
		
		if (is_object($data) && is_a($data, 'XmlNode')) {
			return $data;
		}
		
		trigger_error("Parameter 1 of FeedFormat::prepare_data() must be a string or an XmlNode object");
		
		return false;
	}
	
	/**
	 * Escapes the passed value
	 *
	 * @param string $value
	 * @return string
	 */
	function escape($value) {
		return htmlentities($value);	
	}

	/**
	 * Formats the passed value in the correct format for the feed
	 *
	 * @param string $value The date as a string
	 * @return string
	 */
	function date($value) {
		
		if ($value) {
			$value = strtotime($value);
		} else {
			$value = time();	
		}
		
		return date($this->date_format, $value);
	}

	/**
	 * Sends the appropriate header for the feed
	 */
	function sendHeader() {
		header('Content-type:'.$this->content_type);		
	}
	

}

?>