<?php

class Atom100Tester extends UnitTestCase {
	function test_parsing() {

		$feed = new Feed('atom100');
		
		$feed->parse(file_get_contents(dirname(__FILE__).'/feeds/atom100.xml'));
		
		$this->dump($feed);
	}
	
}

?>