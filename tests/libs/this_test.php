<?php

class ThisTester extends UnitTestCase {

	var $test = 'hello';
	
	function test_all() {
		
		$this->assertEqual(this::class_name(), __CLASS__);	
		
		$this->assertEqual(this::get_var('test'), 'hello');		

		$this->assertTrue(this::call('call_me'));

		$this->assertEqual(this::call('call_me', 'test'), 'test');
		
		$this->assertEqual(this::call('math', 1, 10, 100), 111);
		
		$this->assertEqual(this::call_array('math', array(2, 20, 200)), 222);
		
		$this->assertTrue(this::method_exists('run'));
		
		$this->assertFalse(this::method_exists('foo'));
	}

	function call_me($param = true) {
		return $param;	
	}
	
	
	function math($one, $two, $three) {
		return $one + $two + $three;
		
	}
	
}


?>