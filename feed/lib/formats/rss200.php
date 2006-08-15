<?php



/**
 * Generates RSS 2.0 feeds
 *
 * @package view
 * @subpackage feed 
 */
class RSS200Format extends FeedFormat {
	var $protocol = 'RSS';
	var $version = '2.00';
	
	function detect() {
		
	}
	
	/**
	 * Generates a feed using the passed feed object
	 *
	 * @param Feed $feed
	 * @return bool
	 */
	function generate(& $feed) {
		
		parent::generate($feed);
		
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