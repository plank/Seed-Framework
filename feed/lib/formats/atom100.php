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
	
	/**
	 * @param Feed $feed
	 * @param string $data
	 */
	function parse(& $feed, $data) {
		$data = parent::parse($feed, $data);
		
		$feed_data = array('title', 'description', 'updated', 'id');
		$entry_data = array('title', 'summary', 'updated', 'id');
		
		foreach ($feed_data as $field) {
			if (isset($data->{$field}[0])) {
				$this->feed->{$field} = $data->{$field}[0]->get_data();
			}
		}
		
		if (isset($data->author[0]->name[0])) {
			$this->feed->author_name = 	$data->author[0]->name[0]->get_data();
		}
		
		foreach ($data->entry as $entry) {
			$feed_entry = new FeedEntry();
			
			foreach ($entry_data as $field) {
				if (isset($entry->{$field}[0])) {
					$feed_entry->{$field} = $entry->{$field}[0]->get_data();
				}
			}			
			
			if (isset($entry->author[0]->name[0])) {
				$feed_entry->author_name = $entry->author[0]->name[0]->get_data();
			}
			
			$this->feed->appendEntry($feed_entry);
			
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