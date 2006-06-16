<?php

class RegexpTester extends UnitTestCase {
	
	function test_scan() {
		$regexp = new Regexp('/\w+/');
		$this->assertEqual($regexp->scan("cruel world"), array("cruel", "world"));
		
		$regexp = new Regexp('/.../');
		$this->assertEqual($regexp->scan("cruel world"), array("cru", "el ", "wor"));

		$regexp = new Regexp('/(...)/');
		$this->assertEqual($regexp->scan("cruel world"), array(array("cru"), array("el "), array("wor")));

		$regexp = new Regexp('/(..)(..)/');
		$this->assertEqual($regexp->scan("cruel world"), array(array("cr", "ue"), array("l ", "wo")));
	
		
	}
	
	
}


?>