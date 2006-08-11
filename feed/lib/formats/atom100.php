<?php

/**
 * Generates atom 1.0 feeds
 *
 * @package view
 * @subpackage feed 
 */
class Atom100Format extends FeedFormat {
	var $protocol = 'atom';
	var $version = '1.00';
	
	function character_data($content) {
		
		if ($this->depth() == 2) {
			if (in_array($this->current_element, array('title', 'updated', 'id'))) {
				debug($this->current_element);
				
				$this->feed->{$this->current_element} = $content;
			}
			
		}
		
		
	}
	 
	
	
	/**
	 * Generates a feed using the passed feed object
	 *
	 * @param Feed $feed
	 * @return bool	 
	 */
	function generate(& $feed) {
		$result = "<?xml version='1.0' encoding='utf-8'?>\n";
		$result .= "<feed xmlns='http://www.w3.org/2005/Atom'>\n";
		
		$result .= "  <title>".$this->escape($feed->title)."</title>\n";
		$result .= "  <link rel='self' href='".$feed->link."'/>\n";
		$result .= "  <updated>".$this->date($feed->updated)."</updated>\n";
		$result .= "  <author>\n";
		$result .= "    <name>".$this->escape($feed->author_name)."</name>\n";
		$result .= "  </author>\n";
		$result .= "  <id>".$feed->id."</id>\n";
		
		foreach ($feed->entries as $entry) {
		
			$result .= "  <entry>\n";
			$result .= "    <title>".$this->escape($entry->title)."</title>\n";
			$result .= "    <link rel='alternate' href='".$entry->link."'/>\n";
			$result .= "    <id>".$entry->id."</id>\n";
			$result .= "    <updated>".$this->date($entry->updated)."</updated>\n";
			
			if ($entry->summary) {
				$result .= "    <summary type='xhtml'><div xmlns='http://www.w3.org/1999/xhtml'>".$entry->summary."</div></summary>\n";
			}
			
			$result .= "  </entry>\n";
		
		}
		
		$result .= "</feed>\n";
		
		return $result;
	}
	
	function escape($value) {
		return utf8_encode(htmlentities($value, ENT_QUOTES, 'UTF-8'));				
		
	}
	
}

?>