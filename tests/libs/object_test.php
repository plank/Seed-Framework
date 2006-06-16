<?php


class MockObject extends Object {
	var $var1 = 'test';
	var $var2;
}

class ObjectTester extends UnitTestCase {
	
	function setup() {
		$this->obj = new MockObject();
		
		$this->obj->var2 = 'test';
		$this->obj->var3 = 'test';
		
	}
	
	
	function test_class_name() {
		
		
		$this->assertEqual($this->obj->get_class_name(), 'mockobject');
		
		
	}
	
	
	
	
}