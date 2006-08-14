<?php

class Atom100Tester extends UnitTestCase {

	function test_parsing() {
		$data = file_get_contents(dirname(__FILE__).'/feeds/atom100.xml');
		$feed = new Feed('atom100');
		
		$feed->parse($data);
		$this->dump($feed);
	}

}

?>