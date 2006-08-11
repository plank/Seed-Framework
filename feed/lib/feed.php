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
	 * @var FeedFormat
	 */
	var $format;
	
	function Feed($format) {
		$this->setFormat($format);	
	
	}
	
	/**
	 * Sets the generator to use for the feed
	 *
	 * @param FeedFormat $format
	 */
	function setFormat($format) {
		if (is_string($format)) {
			$format = FeedFormat::factory($format);	
		}
		
		if (is_a($format, 'FeedFormat')) {
			$this->format = $format;
			return true;
		}
		
		trigger_error('Invalid feed format, must be a string or an object');
		return false;
		
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
	function appendEntry(& $data) {
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
		if (is_null($this->format)) {
			trigger_error('No format set in Feed');
			return false;	
			
		}

		$this->sortEntries();
		
		if ($this->setUp()) {
			return $this->format->generate($this);
			
		} else {
			return false;
			
		}
		
	}
	
	function parse($data) {
		if (is_null($this->format)) {
			trigger_error('No format set in Feed');
			return false;	
			
		}
		
		$this->format->parse($this, $data);
		
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
	function compare(& $feed_entry_a, & $feed_entry_b) {
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


?>