<?php

require_once('atom100.php');

/**
 * Generates and parses atom 0.30 feeds
 *
 * @package view
 * @subpackage feed 
 */
class Atom030Format extends Atom100Format {
	var $protocol = 'atom';
	var $version = '0.30';
	var $type = 'html';
	var $content_type = "application/atom+xml";
	
	function detect($data) {
		if (!$data = FeedFormat::prepare_data($data)) {
			return false;		
		}		
		
		if (isset($data->xmlns) && $data->xmlns == "http://purl.org/atom/ns#") {
			return true;	
		}

		return false;
	}
	
}

?>