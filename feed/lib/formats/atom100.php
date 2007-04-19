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
	var $type = 'html';
	var $content_type = "application/atom+xml";
	
	function detect($data) {
		if (!$data = FeedFormat::prepare_data($data)) {
			return false;		
		}		
		
		if (isset($data->xmlns) && $data->xmlns == "http://www.w3.org/2005/Atom") {
			return true;	
		}

		return false;
	}
	
	/**
	 * Parses a feed and returns a Feed object
	 *
	 * @param mixed $data
	 * @return Feed
	 */
	function parse($data) {
		
		$this->feed = & new Feed();
		
		if (!$data = $this->prepare_data($data)) {
			return false;		
		}
			
		$feed_data = array('title', 'description', 'updated', 'id');
		$entry_data = array('title', 'summary', 'updated', 'id');
		
		foreach ($feed_data as $field) {
			if (isset($data->{$field}[0])) {
				$this->feed->{$field} = $data->{$field}[0]->get_data();
			}
		}
		
		if (isset($data->author[0]->name[0])) {
			$this->feed->author_name = $data->author[0]->name[0]->get_data();
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
		
		return $this->feed;
	}
	
	/**
	 * Generates a feed using the passed feed object
	 *
	 * @param Feed $feed
	 * @return bool	 
	 */
	function generate(& $feed) {
		
		parent::generate($feed);
		
		$result = "<?xml version='1.0' encoding='utf-8'?>\n";
		$result .= "<feed xmlns='http://www.w3.org/2005/Atom'>\n";
		
		$result .= "  ".$this->xhtml_element('title', $feed->title)."\n";
		$result .= "  <link rel='self' href='".$feed->link."'/>\n";
		$result .= "  <updated>".$this->date($feed->updated)."</updated>\n";
		$result .= "  <author>\n";
		$result .= "    <name>".$this->escape($feed->author_name)."</name>\n";
		$result .= "  </author>\n";
		$result .= "  <id>".$feed->id."</id>\n";
		
		foreach ($feed->entries as $entry) {
		
			$result .= "  <entry>\n";
			$result .= "    ".$this->xhtml_element('title', $entry->title)."\n";
			$result .= "    <link rel='alternate' href='".$entry->link."'/>\n";
			$result .= "    <id>".$entry->id."</id>\n";
			$result .= "    <updated>".$this->date($entry->updated)."</updated>\n";
			
			if ($entry->summary) {
				$result .= "    ".$this->xhtml_element('summary', $entry->summary)."\n";
			}
			
			$result .= "  </entry>\n";
		
		}
		
		$result .= "</feed>\n";
		
		return $result;
	}
	function xhtml_element($type, $string) {
		switch ($this->type) {
			case 'html':
				return 	"<$type type='html'>".$string."</$type>";
				break;
				
			case 'xhtml':
				return 	"<$type type='xhtml'><div xmlns='http://www.w3.org/1999/xhtml'>".$string."</div></$type>";			

			case 'text':				
			default:
				return 	"<$type type='text'>".$string."</$type>";
		}
		
		
		
	}
	function escape($value) {
		return htmlentities(utf8_encode($value), ENT_QUOTES, 'UTF-8');
		
	}
	
}

?>