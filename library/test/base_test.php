<?php

class BaseTester extends UnitTestCase {

	/**
	 * Test the array_flatten function
	 */
	function test_array_flatten() {
		$array1 = array("one", "two", "three", "four");
		
		$this->assertEqual($array1, array_flatten($array1));
		
		$array2 = array("one", array("two", "three"), "four");
		$this->assertEqual($array1, array_flatten($array2));
		
		$array2 = array(array(array("one"), "two"), array("three", array(array("four"))));		
		$this->assertEqual($array1, array_flatten($array2));		
		
		$array2 = array(array(array("1"=>"one"), "2"=>"two"), array("5"=>"three", array(array("4"=>"four"))));		
		$this->assertEqual($array1, array_flatten($array2));				

	}
	
}


?>