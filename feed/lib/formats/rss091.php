<?php



/**
 * Generates RSS 0.91 feeds.
 *
 * This format is deprecated, and has been replaced by RSS 2.00, so consider using that instead.
 *
 * @package view
 * @subpackage feed 
 */
class RSS091Format extends FeedFormat {
	var $protocol = 'RSS';
	var $version = '0.91';
	
	function detect() {
		
	}
	
	/**
	 * Generates a feed using the passed feed object
	 *
	 * @param Feed $feed
	 * @return bool	 
	 */
	function generate(& $feed) {
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

?>