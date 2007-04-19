<?php


/**
 * Generates RSS 1.0 feeds
 *
 * @package view
 * @subpackage feed 
 */
class RSS100Format extends FeedFormat {
	var $protocol = 'RSS';
	var $version = '1.00';
	
	function detect($data) {
		if (!$data = FeedFormat::prepare_data($data)) {
			return false;		
		}		

		if (isset($data->xmlns) && $data->xmlns == "http://purl.org/rss/1.0/") {
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


?>