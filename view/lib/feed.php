<?php

/**
 * part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package view
 * @subpackage feed 
 */

/**
 * Format date as per RFC 3339
 */
define('RFC3339_DATE_FORMAT', 'Y-m-d\TH:i:s\Z'); // e.g. 2003-12-13T18:30:02Z


/**
 * A feed generation class
 *
 * @package view
 * @subpackage feed 
 */
class Feed {
	/**
	 * The title of the feed
	 * @var string
	 */
	var $title;
	
	/**
	 * The description of the feed
	 * @var string
	 */
	var $description;
	
	/**
	 * The link to the feed source
	 * @var string
	 */
	var $link;
	
	/**
	 * The date the feed was last updated
	 * @var string
	 */
	var $updated;
	
	/**
	 * A unique id for the feed
	 * @var string
	 */
	var $id;
	
	/**
	 * The name of the author of the feed
	 * @var string
	 */
	var $author_name;
	
	/**
	 * An array conataining the feed's entried
	 * @var array
	 */
	var $entries;
	
	/**
	 * The feed generator to use to generate the feed in the desired format
	 * @var FeedGenerator
	 */
	var $generator;
	
	function Feed($generator) {
		$this->generator = $generator;	
	
	}
	
	/**
	 * Sets the generator to use for the feed
	 *
	 * @param FeedGenerator $generator
	 */
	function setGenerator($generator) {
		$this->generator = $generator;
		
	}
	
	/**
	 * Adds an entry to the feed
	 *
	 * @param string $link
	 * @param string $title
	 * @param string $summary;
	 * @param string $author_name;
	 */
	function addEntry($link, $title = '', $summary = '', $updated = '', $author_name = '') {
		$entry = new FeedEntry();
		$entry->id = $link;
		$entry->link = $link;
		$entry->title = $title;
		$entry->summary = $summary;
		$entry->updated = $updated;		
		$entry->author_name = $author_name;	

		$this->appendEntry($entry);
	}
	
	/**
	 * Appends an entry to the list of entries. The data passed can either
	 * be an existing FeedEntry object, or it can be an array containing
	 * keys and values
	 *
	 * @param mixed $data
	 */
	function appendEntry($data) {
		if (is_object($data) && is_a($data, 'FeedEntry')) {
			$this->entries[] = $data;
			return true;
			
		} elseif (is_array($data)) {
			$entry = new FeedEntry();
			
			foreach($data as $key => $value) {
				$entry->$key = $value;
				
			}
			
			$this->entries[] = $entry;			
			return true;
		}
		
		trigger_error('Data passed to Feed::addEntry is in unexpected format', E_USER_WARNING);
		return false;
	
	}

	/**
	 * Sort all the entries in the feed
	 *
	 */
	function sortEntries() {
		usort($this->entries, array('FeedEntry', 'compare'));	
		
	}
	
	/**
	 * Generates the rss feed
	 *
	 * @return bool
	 */
	function generate() {
		if (is_null($this->generator)) {
			trigger_error('No generator set in Feed');
			return false;	
			
		}

		$this->sortEntries();
		
		if ($this->setUp()) {
			return $this->generator->generate($this);
			
		} else {
			return false;
			
		}
		
	}

	
	/**
	 * Feed setup code goes here
	 */
	function setUp() {
		return true;
	}
	
	/**
	 * Sends the appropriate header for the feed
	 */
	function sendHeader() {
		header('Content-type:'.$this->generator->content_type);		
	}
	
	
}

/**
 * Feed entry class
 *
 * @todo this should be refactored to validate its data somehow
 * @package view
 * @subpackage feed 
 */
class FeedEntry {
	/**
	 * The title of the entry
	 * @var string
	 */
	var $title;
	
	/**
	 * The link of the entry
	 * @var string
	 */
	var $link;
	
	/**
	 * The unique id of the entry
	 * @var string
	*/
	var $id;
	
	/**
	 * The date the entry was last updated
	 * @var string
	 */
	var $updated;
	
	/**
	 * A summary of the entry
	 * @var string
	 */
	var $summary;
	
	/**
	 * The name of the author of the entry
	 * @var string
	 */
	var $author_name;
	
	/**
	 * Compares two feed entries, returning their position relative to each other.
	 * Useful as a call back for usort.
	 *
	 * @param FeedEntry $feed_entry_a
	 * @param FeedEntry $feed_entry_b
	 * @return int
	 */
	function compare($feed_entry_a, $feed_entry_b) {
		if ($feed_entry_a->updated == $feed_entry_b->updated) {
			if ($feed_entry_a->title == $feed_entry_b->title) {
				return 0;	
			}
			
			// title, ascending
			return strcasecmp($feed_entry_a->title, $feed_entry_b->title);
				
		}
		
		// date, descending
		return  ($feed_entry_a->updated < $feed_entry_b->updated) ? 1 : -1;
		
	}
}

/**
 * Base feed generator class
 *
 * @package view
 * @subpackage feed 
 */
class FeedGenerator {
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

	/**
	 * Generates a feed using the passed feed object
	 *
	 * @param Feed $feed
	 * @return bool	 
	 */	
	function generate($feed) {
		trigger_error('FeedGenerator::generate needs to be implemented');
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


	
}

/**
 * Generates atom 1.0 feeds
 *
 * @package view
 * @subpackage feed 
 */
class Atom100Generator extends FeedGenerator {
	var $protocol = 'atom';
	var $version = '1.00';
	
	/**
	 * Generates a feed using the passed feed object
	 *
	 * @param Feed $feed
	 * @return bool	 
	 */
	function generate($feed) {
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

/**
 * Generates RSS 0.91 feeds.
 *
 * This format is deprecated, and has been replaced by RSS 2.00, so consider using that instead.
 *
 * @package view
 * @subpackage feed 
 */
class RSS091Generator extends FeedGenerator {
	var $protocol = 'RSS';
	var $version = '0.91';
	
	/**
	 * Generates a feed using the passed feed object
	 *
	 * @param Feed $feed
	 * @return bool	 
	 */
	function generate($feed) {
		$result = "<rss version='0.91'>\n";
		$result .= "  <channel>\n";
		$result .= "    <title>".$this->escape($feed->title)."</title>\n";
		$result .= "    <link>".$feed->link."</link>\n";
		$result .= "    <description>".$this->escape(strip_tags($feed->description))."</description>\n";
		$result .= "    <language>en-us</language>\n";

		foreach ($feed->entries as $entry) {		
			$result .= "    <item>\n";
			$result .= "      <title>".$this->escape($entry->title)."</title>\n";
			$result .= "      <link>".$entry->link ."</link>\n";
			$result .= "      <description>".$this->escape(strip_tags($entry->summary))."</description>\n";
			$result .= "    </item>\n";
		}
		
		$result .= "  </channel>\n";
		$result .= "</rss>\n";

		return $result;
		
	}

}

/**
 * Generates RSS 1.0 feeds
 *
 * @package view
 * @subpackage feed 
 */
class RSS100Generator extends FeedGenerator {
	var $protocol = 'RSS';
	var $version = '1.00';
	
	/**
	 * Generates a feed using the passed feed object
	 *
	 * @param Feed $feed
	 * @return bool
	 */
	function generate($feed) {
		$result = "<rdf:RDF\n";
		$result .= "  xmlns:rdf='http://www.w3.org/1999/02/22-rdf-syntax-ns#'\n";
		$result .= "  xmlns='http://purl.org/rss/1.0/'\n";
		$result .= "  xmlns:dc='http://purl.org/dc/elements/1.1/'\n";
		$result .= ">\n";
		$result .= "  <channel rdf:about='http://www.xml.com/cs/xml/query/q/19'>\n";
		$result .= "    <title>".$this->escape($feed->title)."</title>\n";
		$result .= "    <link>".$feed->link."</link>\n";
		$result .= "    <description>".$this->escape($feed->description)."</description>\n";
		$result .= "    <items>\n";
		$result .= "      <rdf:Seq>\n";
		
		foreach ($feed->entries as $entry) {		
			$result .= "        <rdf:li rdf:resource='".$entry->link."'/>\n";
		}
		
		$result .= "      </rdf:Seq>\n";
		$result .= "    </items>\n";
		$result .= "  </channel>\n";

		reset($feed->entries);
		
		foreach ($feed->entries as $entry) {
			$result .= "  <item rdf:about='".$entry->link."'>\n";
			$result .= "    <title>".$this->escape($entry->title)."</title>\n";
			$result .= "    <link>".$entry->link."</link>\n";
			$result .= "    <description>".$this->escape($entry->summary)."</description>\n";
			$result .= "    <dc:creator>".$this->escape($entry->author_name)."</dc:creator>\n";
			$result .= "    <dc:date>".$this->date($entry->updated)."</dc:date>\n";
			$result .= "  </item>\n";
		}
		
		$result .= "</rdf:RDF>\n";

		return $result;
	}

}

/**
 * Generates RSS 2.0 feeds
 *
 * @package view
 * @subpackage feed 
 */
class RSS200Generator extends FeedGenerator {
	var $protocol = 'RSS';
	var $version = '2.00';
	
	/**
	 * Generates a feed using the passed feed object
	 *
	 * @param Feed $feed
	 * @return bool
	 */
	function generate($feed) {
		$result = "<rss version='2.0' xmlns:dc='http://purl.org/dc/elements/1.1/'>\n";
		$result .= "  <channel>\n";
		$result .= "    <title>".$this->escape($feed->title)."</title>\n";
		$result .= "    <link>".$feed->link."</link>\n";
		$result .= "    <description>".$this->escape($feed->description)."</description>\n";
		$result .= "    <language>en-us</language>\n";
		
		foreach ($feed->entries as $entry) {
			$result .= "    <item>\n";
			$result .= "      <title>".$this->escape($entry->title)."</title>\n";
			$result .= "      <link>".$entry->link."</link>\n";
			$result .= "      <guid>".$entry->id."</guid>\n";
			$result .= "      <description>".$this->escape($entry->summary)."</description>\n";
			$result .= "      <dc:creator>".$this->escape($entry->author_name)."</dc:creator>\n";
			$result .= "      <dc:date>".$this->date($entry->updated)."</dc:date>\n";
			$result .= "    </item>\n";
		}
		
		$result .= "  </channel>\n";
		$result .= "</rss>\n";
		
		return $result;
	}

}


?>