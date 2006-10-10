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
	
	function test_explode_quoted() {
		
		// some simple strings
		$this->assertEqual(explode_quoted(" ", "piece1 piece2 piece3 piece4 piece5 piece6"), array("piece1", "piece2", "piece3", "piece4", "piece5", "piece6"));
		$this->assertEqual(explode_quoted(" ", '"piece1 piece2" "piece3 piece4" "piece5 piece6"'), array("piece1 piece2", "piece3 piece4", "piece5 piece6"));
		$this->assertEqual(explode_quoted(" ", "'piece1 piece2' 'piece3 piece4' 'piece5 piece6'", "'"), array("piece1 piece2", "piece3 piece4", "piece5 piece6"));
		$this->assertEqual(
			explode_quoted(" ", "'piece1 piece2' 'piece3 piece4' 'piece5 piece6'", "'", null, false), 
			array("'piece1 piece2'", "'piece3 piece4'", "'piece5 piece6'")
		);
		
		// with escaped quote
		$this->assertEqual(explode_quoted(",", '"piece \" 1","test this","hi"'), array('piece " 1', 'test this', 'hi'));		
		
		// csv test
		$this->assertEqual(explode_quoted(",", '"testing, stuff, here","is testing ok",200,456'), array("testing, stuff, here", "is testing ok", 200, 456));
		
		// sql test
		$this->assertEqual(
			explode_quoted(";", "SELECT * FROM test WHERE data = 'hello; \'goodbye;\'';SELECT * FROM test", "'", null, false),
			array("SELECT * FROM test WHERE data = 'hello; \'goodbye;\''", "SELECT * FROM test")
		);

		// multiple quotes
		$this->assertEqual(
			explode_quoted(";", "SELECT * FROM test WHERE data = 'hello \'\'\' ; data; \'\'; '", "'", null, false),
			array("SELECT * FROM test WHERE data = 'hello \'\'\' ; data; \'\'; '")
		);
		
		// postgress/sybase style quoting 
		$this->assertEqual(
			explode_quoted(";", "SELECT * FROM test WHERE data = 'hello; ''goodbye;''';SELECT * FROM test", "'", "'", false),
			array("SELECT * FROM test WHERE data = 'hello; ''goodbye;'''", "SELECT * FROM test")
		);

		
	}
	
}


?>