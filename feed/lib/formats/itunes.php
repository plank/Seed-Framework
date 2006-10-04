<?php



/**
 * Generates RSS 2.0 feeds
 *
 * @package view
 * @subpackage feed 
 */
class ItunesFormat extends FeedFormat {
	var $protocol = 'RSS (itunes)';
	var $version = '2.00';
	var $date_format = RFC2822_DATE_FORMAT;
	
	function detect($data) {
		if (!$data = $this->prepare_data($data)) {
			return false;		
		}		

		if (isset($data->version) && $data->version == "2.0") {
			return true;	
		}

		return false;
	}	
	/**
	 * Generates a feed using the passed feed object
	 *
	 * @param Feed $feed
	 * @return bool
	 */
	function generate(& $feed) {
		
		parent::generate($feed);
		
		$result = "<rss version='2.0' xmlns:itunes='http://www.itunes.com/dtds/podcast-1.0.dtd'>\n";
		$result .= "  <channel>\n";
		$result .= "    <title>".$this->escape($feed->title)."</title>\n";
		$result .= "    <link>".$feed->link."</link>\n";
		$result .= "    <language>en-us</language>\n";
		$result .= "    <copyright>".$this->escape($feed->copyright)."</copyright>\n";
		$result .= "    <itunes:subtitle>".$this->escape($feed->subtitle)."</itunes:subtitle>\n";
		$result .= "    <itunes:author>".$this->escape($feed->author_name)."</itunes:author>\n";
		$result .= "    <itunes:summary>".$this->escape($feed->description)."</itunes:summary>\n";
		$result .= "    <description>".$this->escape($feed->description)."</description>\n";
		$result .= "    <itunes:owner>\n";
		$result .= "      <itunes:name>".$this->escape($feed->author_name)."</itunes:name>";
		$result .= "    </itunes:owner>\n";
		if (isset($feed->image)) {
			$result .= "    <itunes:image>".$this->escape($feed->image)."</itunes:image>\n";
		}
//		$result .= "    <itunes:category>".$this->escape($feed->category)."</itunes:category>\n";
		
		
		foreach ($feed->entries as $entry) {
			$result .= "    <item>\n";
			$result .= "      <title>".$this->escape($entry->title)."</title>\n";
			$result .= "      <itunes:author>".$this->escape($entry->author_name)."</itunes:author>\n";
			$result .= "      <itunes:subtitle>".$this->escape($entry->subtitle)."</itunes:subtitle>>\n";
			$result .= "      <itunes:summary>".$this->escape($entry->summary)."</itunes:summary>\n";
			$result .= "      <enclosure url='".$entry->link."' length='".$entry->link_length."' type='".$entry->link_type."' />\n";
			$result .= "      <guid>".$entry->id."</guid>\n";
			$result .= "      <pubDate>".$this->date($entry->updated)."</pubDate>\n";
			$result .= "    </item>\n";
		}
		
		$result .= "  </channel>\n";
		$result .= "</rss>\n";
		
		return $result;
	}

}

?>