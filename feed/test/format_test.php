<?php

class FormatTester extends UnitTestCase {
	

	function test_factory() {
		$this->assertIsA(FeedFormat::factory('atom100'), 'Atom100Format');
		$this->assertIsA(FeedFormat::factory('rss091'), 'Rss091Format');
		$this->assertIsA(FeedFormat::factory('rss100'), 'Rss100Format');
		$this->assertIsA(FeedFormat::factory('rss200'), 'Rss200Format');						
	}

	
}

?>