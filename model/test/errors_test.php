<?php


class ErrorsTester extends UnitTestCase {
	
	/**
	 * @var Errors
	 */
	var $errors;
	
	function setup() {
		$this->errors = new Errors();	
		
	}
	
	function test_on() {
		// by default, there are no errors so any call should return false
		$this->assertfalse($this->errors->on('name'));
		
	}

	function test_add() {
		
		$this->assertEqual($this->errors->count(), 0);
		$this->assertTrue($this->errors->is_empty());
		
		// add a first item
		$this->errors->add('name', 'a message');
		$this->assertequal($this->errors->on('name'), 'a message');
		$this->assertequal($this->errors->count(), 1);	
		
		$this->errors->add('text');
		$this->assertequal($this->errors->on('text'), $this->errors->default_messages['invalid']);
		$this->assertequal($this->errors->count(), 2);
		
		$this->errors->add('name', 'a second message');
		$this->assertequal($this->errors->on('name'), array('a message', 'a second message'));
		$this->assertequal($this->errors->count(), 3);		

		$this->assertFalse($this->errors->is_empty());
		$this->assertTrue($this->errors->is_invalid());
		
		$this->errors->clear();
		$this->assertequal($this->errors->count(), 0);
		
		$this->assertTrue($this->errors->is_empty());
		$this->assertFalse($this->errors->is_invalid());		
	}
	

	function test_add_to_base() {
		
		$this->errors->add_to_base('a message');
		$this->assertequal($this->errors->on_base(), 'a message');
		$this->assertequal($this->errors->count(), 1);	
		
		$this->errors->add_to_base('a second message');
		$this->assertequal($this->errors->on_base(), array('a message', 'a second message'));
		$this->assertequal($this->errors->count(), 2);

		$this->assertFalse($this->errors->is_empty());
		$this->assertTrue($this->errors->is_invalid());
		
		$this->errors->clear();
		$this->assertequal($this->errors->count(), 0);
		
		$this->assertTrue($this->errors->is_empty());
		$this->assertFalse($this->errors->is_invalid());			
		
	}
	
	
	
}

	
?>