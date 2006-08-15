<?php

require_once('support.php');

class Rss091Tester extends FeedTestCase {

	function test_dump() {
		$parser = FeedFormat::factory('rss091');
	
		$data = $this->read_feed('rss091.xml');
		$this->assertTrue($parser->detect($data));		
		
		$data = $this->read_feed('rss100.xml');
		$this->assertFalse($parser->detect($data));
		
	}
	
}


?>