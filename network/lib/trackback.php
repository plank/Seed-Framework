<?php

/**
 * trackback.php, part of the seed framework
 *
 * Library for handling trackbacks
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package Network
 */

/**
 * Class for sending and receiving trackbacks
 */
class Trackback {

	/**
	 * The url of the blog entry
	 *
	 * @var string
	 */
	var $url;
	
	/**
	 * The title of the blog entry
	 *
	 * @var string
	 */
	var $title;
	
	/**
	 * An excerpt of the blog entry
	 *
	 * @var string
	 */
	var $excerpt;
	
	/**
	 * The name of the blog
	 *
	 * @var string
	 */
	var $blog_name;
	
	/**
	 * The last error message
	 *
	 * @var string
	 */
	var $error_message = '';
	
	/**
	 * The last error code
	 *
	 * @var int
	 */
	var $error_code = 0;
	
	/**
	 * A list of object properties with their associated properties
	 *
	 * @var array
	 */
	var $meta_data = array(
		'url' => 		array('name' => 'URL', 			'required'=>true),
		'title' => 		array('name' => 'Title', 		'required'=>false),
		'excerpt' => 	array('name' => 'Excerpt', 		'required'=>false),
		'blog_name' => 	array('name' => 'Blog Name', 	'required'=>false)
	);
	
	/**
	 * Constructor
	 *
	 * @param mixed $url         Either a string containing the url, or an array containing all the trackback data (like $_POST)
	 * @param string $title
	 * @param string $excerpt
	 * @param string $blog_name
	 * @return Trackback
	 */
	function Trackback($url = null, $title = null, $excerpt = null, $blog_name = null) {
		
		if (is_array($url)) {
			
			foreach($this->meta_data as $field => $field_data) {
				if (isset($url[$field])) {
					
					$this->$field = $url[$field];
				}			
			}
			
		} else {
			$this->url = $url;
			$this->title = $title;
			$this->excerpt = $excerpt;
			$this->blog_name = $blog_name;
		}
		
		$this->validate();
	}
	

	
	/**
	 * Send a ping to the given trackback url
	 *
	 * @param string $trackback_url
	 * @return bool
	 */
	function send($trackback_url) {
		if (!$this->validate()) {
			return false;	
		}
		
		foreach($this->meta_data as $field => $field_data) {
			$querystring[] = $field."=".urlencode($this->$field);
			
		}
		
		$string = $trackback_url."?".implode('&', $querystring);
		
		return true;
	}
	

	
	/**
	 * Checks to see if the current trackback object is valid.
	 *
	 * @return bool
	 */
	function validate() {
		
		$errors = array();
		
		foreach($this->meta_data as $field => $field_data) {
			
			if ($field_data['required'] && !isset($this->$field)) {
				$errors[] = $field_data['name'];
			}
			
		}
		
		if (count($errors)) {
			$this->error_code = 1;
			$this->error_message = "The following fields were missing: ".implode(', ', $errors);
			
			return false;	
		} else {
			
			$this->error_code = 0;
			$this->error_message = '';
	
			return true;
		}
		
	}

}

/**
 * Class for sending responses to trackbacks
 */
class TrackbackResponse {

	/**
	 * The content type of the response
	 *
	 * @var string
	 */
	var $content_type = "text/xml";
	
	/**
	 * The encoding/character set to use for the response
	 *
	 * @var string
	 */
	var $encoding = "utf-8";
	
	/**
	 * The trackback object we're responding to
	 *
	 * @var Trackback
	 */
	var $trackback;
	
	/**
	 * Constructor
	 *
	 * @param Trackback $trackback
	 * @return TrackbackResponse
	 */
	function TrackbackResponse(& $trackback) {
		$this->trackback = & $trackback;	
	}
	
	/**
	 * Sends a response
	 *
	 */
	function send() {
		header('Content-type: '.$this->content_type.'; charset='.$this->encoding);
		
		print $this->get_xml();	
		
	}
	
	/**
	 * Parses a response received from the server
	 */
	function parse($response) {
		preg_match('/<error>(\d)<\/error>/', $response, $matches);
		
		$this->trackback->error_code = $matches[1];
		
		if ($this->trackback->error_code) {
			preg_match('/<message>([^<]*)<\/message>/', $response, $matches);
			
			$this->trackback->error_message = $matches[1];
			
		} else {
			$this->trackback->error_message = '';
			
		}
		
		return !($this->trackback->error_code);
	}	
	
	/**
	 * Returns an xml string containing error info for the trackback object
	 *
	 * @return string
	 */
	function get_xml() {
		if ($this->trackback->error_code) {
			return $this->error_xml($this->trackback->error_message);
		} else {
			return $this->success_xml();	
		}
		
	}
	
	/**
	 * Returns an error string 
	 *
	 * @param string $message
	 * @return string
	 */
	function error_xml($message) {
$data = <<<EOL
<?xml version="1.0" encoding="{$this->encoding}"?>
  <response>
  <error>1</error>
  <message>$message</message>
</response>
EOL;

		return $data;		
		
	}
	
	/**
	 * Returns a success string
	 *
	 * @return string
	 */
	function success_xml() {
$data = <<<EOL
<?xml version="1.0" encoding="{$this->encoding}"?>
  <response>
  <error>0</error>
</response>
EOL;

		return $data;
	}

}

?>