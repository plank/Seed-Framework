<?php

class MockFilterController {

	function MockFilterController() {
		
	}

	function filter_1() {
		return true;
	}
	
	function filter_2() {
		return false;	
	}

	
}

class TestSimpleFilter1 extends SimpleFilter  {
	function filter() {
		return true;
	}	
}

class TestSimpleFilter2 extends SimpleFilter {
	function filter() {
		return true;
	}	
}

class TestAroundFilter1 extends AroundFilter {
	
	var $counter = 0;
	var $controller;
	
	function before(&$controller) {
		$this->controller = &$controller;
		
		$this->counter ++; 
		return true;
	}
	
	function after(&$controller) {
		
		$this->counter ++;
		return true;
	}
	
}

class TestAroundFilter2 extends AroundFilter {
	
	function before() {
		$this->counter ++; 		
		return true;
	}
	
	function after() {
		$this->counter ++; 		
		return false;
	}
	
}

class TestAroundFilter3 extends AroundFilter {

	function before() {
		$this->counter ++; 		
		return true;
	}
	
	function after() {
		$this->counter ++; 		
		return true;
	}

}

class FilterTester extends UnitTestCase {
	
	/**
	 * @var FilterChain
	 */
	var $filter_chain;
	
	function setUp() {
		$this->filter_chain = new FilterChain(new MockFilterController());
		
	}
	
	function test_adding_method_filters() {

		// these methods exist
		$this->assertTrue($this->filter_chain->before_filter('filter_1'));
		$this->assertTrue($this->filter_chain->after_filter('filter_2'));
		
		// this one doesn't
		$this->assertFalse($this->filter_chain->before_filter('filter_0'));
		
//		$this->dump($this->filter_chain->pre_chain);
//		$this->dump($this->filter_chain->post_chain);
	}
	
	function test_adding_simple_filters() {
		$pre_filter_1 = new TestSimpleFilter1();
		$pre_filter_2 = new TestSimpleFilter2();
		
		$post_filter_1 = new TestSimpleFilter1();
		$post_filter_2 = new TestSimpleFilter2();
		
		$bad_filter = new Directory();
		
		$this->assertTrue($this->filter_chain->before_filter($pre_filter_1));
		$this->assertTrue($this->filter_chain->before_filter($pre_filter_2));
		
		$this->assertTrue($this->filter_chain->after_filter($post_filter_1));
		
		// make sure we refuse the bad filter
		$this->assertFalse($this->filter_chain->after_filter($bad_filter));
		

		
		$this->assertIsA($this->filter_chain->pre_chain[0][0], 'TestSimpleFilter1');
		$this->assertIsA($this->filter_chain->pre_chain[1][0], 'TestSimpleFilter2');

		$this->assertIsA($this->filter_chain->post_chain[0][0], 'TestSimpleFilter1');
				
		$this->assertTrue($this->filter_chain->prepend_after_filter($post_filter_2));
		$this->assertIsA($this->filter_chain->post_chain[0][0], 'TestSimpleFilter2');
		
		//$this->dump($this->filter_chain->pre_chain);		
		//$this->dump($this->filter_chain->post_chain);
		
	}
	
	function test_adding_around_filter() {
		$around_filter_1 = new TestAroundFilter1();
		$around_filter_2 = new TestAroundFilter2();
		$around_filter_3 = new TestAroundFilter3();
		
		$bad_filter = new TestSimpleFilter1();
		
		$this->assertTrue($this->filter_chain->append_around_filter($around_filter_1));
		$this->assertTrue($this->filter_chain->append_around_filter($around_filter_2));
		$this->assertFalse($this->filter_chain->append_around_filter($bad_filter));
		$this->assertTrue($this->filter_chain->prepend_around_filter($around_filter_3));
		
		$this->assertIsA($this->filter_chain->pre_chain[0][0], 'TestAroundFilter3');
		$this->assertIsA($this->filter_chain->pre_chain[1][0], 'TestAroundFilter1');		
		$this->assertIsA($this->filter_chain->pre_chain[2][0], 'TestAroundFilter2');		
		
		$this->assertIsA($this->filter_chain->post_chain[0][0], 'TestAroundFilter2');
		$this->assertIsA($this->filter_chain->post_chain[1][0], 'TestAroundFilter1');
		$this->assertIsA($this->filter_chain->post_chain[2][0], 'TestAroundFilter3');		
						
	}
	
	function test_adding_different_filters() {
		$around_filter = new TestAroundFilter1();
		$simple_filter_1 = new TestSimpleFilter1();
		$simple_filter_2 = new TestSimpleFilter2();
		
		$this->assertTrue($this->filter_chain->before_filter($simple_filter_1));
		$this->assertTrue($this->filter_chain->after_filter($simple_filter_2));
		$this->assertTrue($this->filter_chain->around_filter($around_filter));

		$this->assertIsA($this->filter_chain->pre_chain[0][0], 'TestSimpleFilter1');		
		$this->assertIsA($this->filter_chain->pre_chain[1][0], 'TestAroundFilter1');				
		
		$this->assertIsA($this->filter_chain->post_chain[0][0], 'TestAroundFilter1');
		$this->assertIsA($this->filter_chain->post_chain[1][0], 'TestSimpleFilter2');
		
		
	}
	
	function test_calling_filter() {
		$action_name = 'test';
		$around_filter = new TestAroundFilter1();
		
		// make sure empty filter chain returns true
		$this->assertTrue($this->filter_chain->call_before($action_name));
		$this->assertTrue($this->filter_chain->call_after($action_name));
		
		// add a filter
		$this->assertTrue($this->filter_chain->append_around_filter($around_filter));
		
		// run the filters
		$this->assertTrue($this->filter_chain->call_before($action_name));
		$this->assertTrue($this->filter_chain->call_after($action_name));		
		
		// make sure the reference to the filter was correctly maintained
		$this->assertEqual($around_filter->counter, 2);
		
	}

	function test_interupting_filter() {
		$action_name = 'test';
		
		$around_filter_1 = new TestAroundFilter1();
		$around_filter_2 = new TestAroundFilter2();
		$around_filter_3 = new TestAroundFilter3();
		
		$this->assertTrue($this->filter_chain->around_filter($around_filter_1));
		$this->assertTrue($this->filter_chain->around_filter($around_filter_2));
		$this->assertTrue($this->filter_chain->around_filter($around_filter_3));		
		
		// run the filters
		$this->assertTrue($this->filter_chain->call_before($action_name));
		$this->assertFalse($this->filter_chain->call_after($action_name));			
		
		// filter 1 should not execute it's after method, because it's at the end
		// of the post chain, which should be interupted by filter two
		$this->assertEqual($around_filter_1->counter, 1);
		$this->assertEqual($around_filter_2->counter, 2);
		$this->assertEqual($around_filter_3->counter, 2);
		
	}

	function test_only() {
		$action_name = 'test';
		
		$around_filter_1 = new TestAroundFilter1();
		$around_filter_2 = new TestAroundFilter2();		
		$around_filter_3 = new TestAroundFilter3();
		
		$this->assertTrue($this->filter_chain->around_filter($around_filter_1, array('test')));
		$this->assertTrue($this->filter_chain->around_filter($around_filter_2, array('no_test')));
		$this->assertTrue($this->filter_chain->around_filter($around_filter_3));
		
		$this->assertTrue($this->filter_chain->call_before($action_name));
		$this->assertTrue($this->filter_chain->call_after($action_name));			
		
		// filter 1 should run
		$this->assertEqual($around_filter_1->counter, 2);
		
		// filter 2 shouldn't 
		$this->assertEqual($around_filter_2->counter, 0);

		// filter 3 should run
		$this->assertEqual($around_filter_3->counter, 2);
		
	}

	function test_except() {
		$action_name = 'test';
		
		$around_filter_1 = new TestAroundFilter1();
		$around_filter_2 = new TestAroundFilter2();		
		$around_filter_3 = new TestAroundFilter3();
		
		$this->assertTrue($this->filter_chain->around_filter($around_filter_1, null, array('no_test')));
		$this->assertTrue($this->filter_chain->around_filter($around_filter_2, null, array('test')));
		$this->assertTrue($this->filter_chain->around_filter($around_filter_3));
		
		$this->assertTrue($this->filter_chain->call_before($action_name));
		$this->assertTrue($this->filter_chain->call_after($action_name));			
		
		// filter 1 should run
		$this->assertEqual($around_filter_1->counter, 2);
		
		// filter 2 shouldn't 
		$this->assertEqual($around_filter_2->counter, 0);

		// filter 3 should run
		$this->assertEqual($around_filter_3->counter, 2);		
		
	}
	
	
}


?>