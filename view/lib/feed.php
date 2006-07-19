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
define('RFC3339_DATE_FORMAT', 'Y-m-d\TH:i:s\Z'); // 2003-12-13T18:30:02Z


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
	
	
	var $escape_data = false;
	
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
	 * Generates the rss feed
	 *
	 * @return bool
	 */
	function generate() {
		if (is_null($this->generator)) {
			trigger_error('No generator set in Feed');
			return false;	
			
		}

		if ($this->setUp()) {
			$this->generator->escape_data = $this->escape_data;
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
	 * Set to true to escape entries
	 */
	var $escape_data = false;
	
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
		if ($this->escape_data) {
			return htmlentities($value);	
		} else {
			return $value;
		}
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
		$this->sendHeader();
		
		print "<?xml version='1.0' encoding='utf-8'?>";
		print "<feed xmlns='http://www.w3.org/2005/Atom'>";
		
		print "  <title>".$this->escape($feed->title)."</title>";
		print "  <link href='".$feed->link."'/>";
		print "  <updated>".$this->date($feed->updated)."</updated>";
		print "  <author>";
		print "    <name>".$this->escape($feed->author_name)."</name>";
		print "  </author>";
		print "  <id>".$feed->id."</id>";
		
		foreach ($feed->entries as $entry) {
		
			print "  <entry>";
			print "    <title>".$this->escape($entry->title)."</title>";
			print "    <link href='".$entry->link."'/>";
			print "    <id>".$entry->id."</id>";
			print "    <updated>".$this->date($entry->updated)."</updated>";
			print "    <summary>".$this->escape($entry->summary)."</summary>";
			print "  </entry>";
		
		}
		
		print "</feed>";
		
		return true;
	}
	
	function escape($value) {
		
		if ($this->escape_data) {
			return utf8_encode(htmlentities($value, ENT_QUOTES, 'UTF-8'));				
		} else {
			return utf8_encode($value);
		}
		
		
	}
	
}

/**
 * Generates RSS 0.91 feeds
 *
 * @package view
 * @subpackage feed 
 */
class RSS091Generator extends FeedGenerator {
	var $protocol = 'RSS';
	var $version = '0.91';
	var $date_format = RFC3339_DATE_FORMAT;
	
	/**
	 * Generates a feed using the passed feed object
	 *
	 * @param Feed $feed
	 * @return bool	 
	 */
	function generate($feed) {
		$this->sendHeader();
		
		print "<rss version='0.91'>";
		print "  <channel>";
		print "    <title>".$this->escape($feed->title)."</title>";
		print "    <link>".$feed->link."</link>";
		print "    <description>".$this->escape($feed->description)."</description>";
		print "    <language>en-us</language>";

		foreach ($feed->entries as $entry) {		
			print "    <item>";
			print "      <title>".$this->escape($entry->title)."</title>";
			print "      <link>".$entry->link ."</link>";
			print "      <description>".$this->escape($entry->summary)."</description>";
			print "    </item>";
		}
		
		print "  </channel>";
		print "</rss>";

		return true;
		
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
		$this->sendHeader();
				
		print "<rdf:RDF";
		print "  xmlns:rdf='http://www.w3.org/1999/02/22-rdf-syntax-ns#'";
		print "  xmlns='http://purl.org/rss/1.0/'";
		print "  xmlns:dc='http://purl.org/dc/elements/1.1/'";
		print ">";
		print "  <channel rdf:about='http://www.xml.com/cs/xml/query/q/19'>";
		print "    <title>".$this->escape($feed->title)."</title>";
		print "    <link>".$feed->link."</link>";
		print "    <description>".$this->escape($feed->description)."</description>";
		print "    <language>en-us</language>";
		print "    <items>";
		print "      <rdf:Seq>";
		
		foreach ($feed->entries as $entry) {		
			print "        <rdf:li rdf:resource='".$entry->link."'/>";
		}
		
		print "      </rdf:Seq>";		
		print "    </items>";
		print "  </channel>";

		reset($feed->entries);
		
		foreach ($feed->entries as $entry) {
			print "  <item rdf:about='".$entry->link."'>";
			print "    <title>".$this->escape($entry->title)."</title>";
			print "    <link>".$entry->link."</link>";
			print "    <description>".$this->escape($entry->description)."</description>";
			print "    <dc:creator>".$this->escape($entry->author_name)."</dc:creator>";
			print "    <dc:date>".$this->date($entry->updated)."</dc:date>";
			print "  </item>";
		}
		
		print "</rdf:RDF>";

		return true;
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
		$this->sendHeader();

		print "<rss version='2.0' xmlns:dc='http://purl.org/dc/elements/1.1/'>";
		print "  <channel>";
		print "    <title>".$this->escape($feed->title)."</title>";
		print "    <link>".$feed->link."</link>";
		print "    <description>".$this->escape($feed->description)."</description>";
		print "    <language>en-us</language>";
		
		foreach ($feed->entries as $entry) {
			print "    <item>";
			print "      <title>".$this->escape($entry->title)."</title>";
			print "      <link>".$entry->link."</link>";
			print "      <description>".$this->escape($entry->summary)."</description>";
			print "      <dc:creator>".$this->escape($entry->author)."</dc:creator>";
			print "      <dc:date>".$this->date($entry->updated)."</dc:date>";
			print "    </item>";
		}
		
		print "  </channel>";
		print "</rss>";
		
		return true;
	}

}


?>