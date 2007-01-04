<?php


class CLIArgsTester extends UnitTestCase {

	
	function test_parse_args() {
		
		$this->assertEqual(CLIArgs::parse_args('-abc', 'abc'), array('a'=>true, 'b'=>true, 'c'=>true));
		
	}
	
	
	
}

?>