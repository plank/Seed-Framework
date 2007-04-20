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
	
	function detect($data) {
		if (!$data = FeedFormat::prepare_data($data)) {
			return false;		
		}		

		if (isset($data->version) && $data->version == "2.0") {
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
		
		$data = $data->channel[0];
		
		// feed info
		$this->feed->title = $data->title[0]->get_data();
		$this->feed->link = $data->link[0]->get_data();
		$this->feed->description = $data->description[0]->get_data();
		$this->feed->updated = strtotime($data->lastBuildDate[0]->get_data());
		$this->feed->copyright = $data->copyright[0]->get_data();
		
		// entries
		foreach ($data->item as $entry) {
			$item = & $this->feed->addEntry(
				$entry->link[0]->get_data(), 
				$entry->title[0]->get_data(), 
				$entry->description[0]->get_data(), 
				strtotime($entry->pubDate[0]->get_data())
				
			);

			if (isset($entry->category[0])) {
				$item->category = $entry->category[0]->get_data();
			}
			
			if (isset($entry->author[0])) {
				$item->author_name = $entry->author[0]->get_data();
			}
			
			// use default id of link if the guid is missing or empty
			if (isset($entry->guid[0]) && $entry->guid[0]->get_data()) {
				$item->id = $entry->guid[0]->get_data();
			}
			
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