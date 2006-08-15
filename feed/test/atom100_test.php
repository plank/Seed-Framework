<?php

require_once('support.php');

class Atom100Tester extends FeedTestCase {
	
	function test_detection() {
		$parser = FeedFormat::factory('atom100');
	
		$data = $this->read_feed('atom100.xml');
		$this->assertTrue($parser->detect($data));
		
		$data = $this->read_feed('rss100.xml');
		$this->assertFalse($parser->detect($data));

		
	}
	
	function test_parsing() {

	}

}

?>