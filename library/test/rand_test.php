<?php

class RandTester extends UnitTestCase {
	
	function test_number() {
		
		for($x = 0; $x < 20; $x ++) {
			$number = Rand::number();
			
			$this->assertTrue($number >= 0 && $number <= 9);
		}	
	}
	
	function test_number_string() {
		for($x = 0; $x < 20; $x ++) {
			$number = Rand::number_string(5);
			
			$this->_test_number($number, 5);
		}
	}

	function _test_number($number, $desired_size) {
		$this->assertTrue(intval($number) == $number);
		$this->assertTrue(strlen($number) == $desired_size);			
	}
	
	function test_number_strings() {
		$numbers = Rand::number_strings(35, 5);
		
		foreach ($numbers as $number) {
			$this->_test_number($number, 5);	
			
		}
		
	}
}


?>