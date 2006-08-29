<?php

class IteratorTester extends UnitTestCase  {
	
	/**
	 * Test the iterator with an array of strings
	 */
	function test_strings() {
		$array = array('One', 'Two', 'Three');
		
		$iterator = new SeedIterator($array);
		
		// iterator should return each item in the array in turn
		foreach ($array as $value) {
			$this->assertEqual($iterator->has_next(), true);
			$this->assertEqual($value, $iterator->next());	
			
		}
		
		// and shouldn't have a next afterwards
		$this->assertEqual($iterator->has_next(), false);
		
	}
	
	
}

?>