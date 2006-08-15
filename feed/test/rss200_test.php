<?php

require_once('support.php');

class Rss200Tester extends FeedTestCase {

	function test_dump() {
		$parser = FeedFormat::factory('rss200');
	
		$data = $this->read_feed('rss200.xml');
		$this->assertTrue($parser->detect($data));		
		
		$data = $this->read_feed('rss100.xml');
		$this->assertFalse($parser->detect($data));
		
	}
	
}


?>