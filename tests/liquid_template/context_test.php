<?php

class HundredCentes {
	function to_liquid() {
		return 100;
	}
	
}

class CentsDrop extends LiquidDrop {
	function amount() {
		return new HundredCentes();
	}
	
}

class LiquidContextTester extends UnitTestCase {
	
	/**
	 * Enter description here...
	 *
	 * @var LiquidContext
	 */
	var $context;
	
	function setup() {
		$this->context = new LiquidContext();
		
	}

	function test_variables() {
		$this->context->set('test', 'test');
		$this->assertEqual('test', $this->context->get('test'));
		
	}
	
	function test_variables_not_existing() {
		$this->assertNull($this->context->get('test'));
		
	}

	function test_scoping() {
		$this->context->push();
		$this->assertNoErrors($this->context->pop());
		$this->assertError($this->context->pop());
		
	}
	
	function test_length_query() {
		$this->context->set('numbers', array(1, 2, 3, 4));
		$this->assertEqual(4, $this->context->get('numbers.size'));		
	}
	
	function test_hierchal_data() {
		$this->context->set('hash', array('name' => 'tobi'));
		$this->assertEqual('tobi', $this->context->get('hash.name'));
		
		
	}
	
	function test_keywords() {
		$this->assertEqual(true, $this->context->get('true'));
		$this->assertEqual(false, $this->context->get('false'));
	}
	
}


?>