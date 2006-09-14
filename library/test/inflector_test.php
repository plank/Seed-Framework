<?php

class InflectorTester extends UnitTestCase {
	
	function test_underscore() {
		$this->assertEqual('hello', Inflector::underscore('hello'));
		$this->assertEqual('hello', Inflector::underscore('Hello'));
		$this->assertEqual('hello_goodbye', Inflector::underscore('HelloGoodbye'));
		$this->assertEqual('hello_goodbye', Inflector::underscore('helloGoodbye'));
	}
	
	function test_linkify() {
		$this->assertEqual('hello', Inflector::linkify('hello'));
		$this->assertEqual('hello', Inflector::linkify('Hello'));
		$this->assertEqual('hello_goodbye', Inflector::linkify('Hello goodbye'));
		$this->assertEqual('hello_goodbye', Inflector::linkify('hello Goodbye'));
		
		// consecutive non word characters should return as a single underscore
		$this->assertEqual('hello_goodbye', Inflector::linkify('Hello, Goodbye!'));
		$this->assertEqual('hello_goodbye', Inflector::linkify('Hello , & Goodbye!'));
		
		// periods in abbreviations should simply be removed
		$this->assertEqual('faq', Inflector::linkify('F.A.Q.'));
		
		// but normal periods should be fine
		$this->assertEqual('hello_goodbye', Inflector::linkify('Hello. Goodbye.'));		
		
	}
	
}




?>