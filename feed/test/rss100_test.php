<?php

require_once('support.php');

class Rss100Tester extends FeedTestCase {

	function test_dump() {
		$parser = FeedFormat::factory('rss100');
	
		$data = $this->read_feed('rss100.xml');
		$this->assertTrue($parser->detect($data));		
		
		$data = $this->read_feed('atom100.xml');
		$this->assertFalse($parser->detect($data));
		
	}
	
}


?>