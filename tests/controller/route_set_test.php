<?php

class RouteSetTester extends UnitTestCase {

	function setup() {
		Route::reset_routes();
		
	}
	
	function test_connecting() {
		$this->assertTrue(Route::connect('', '$controller/$action/$id'));
		$this->assertTrue(Route::connect('admin', '$controller/$action/$id'));

		$routes = Route::get_routes();
		$this->assertIsA($routes[0], 'Route');		
		$this->assertIsA($routes['admin'], 'Route');
	}
	
	/**
	 * Test a complete set of routes, taken from Agile Web Development with Rails, to make sure
	 * the routes parse the same way as rails
	 */
	function test_complete_parse() {
		
		Route::connect('', 'blog/',
			array('controller'=>'blog', 'action'=>'index')
		);
		
		Route::connect('', 'blog/$year/$month/$day', 
			array('controller'=>'blog', 'action'=>'show_date', 'month'=>null, 'day'=>null), 
			array('year'=>'/^(19|20)\d\d$/', 'month'=>'/^[01]?\d$/', 'day'=>'/^[0-3]?\d$/')
		);
		
		Route::connect('', 'blog/show/$id',
			array('controller'=>'blog', 'action'=>'show'),
			array('id'=>'/\d+/')
		);
		
		Route::connect('', 'blog/$controller/$action/$id');
		
		Route::connect('', '*anything',
			array('controller'=>'blog', 'action'=>'unkown_request')
		);
		
		$this->assertEqual(Route::parse('blog'), array('controller'=>'blog', 'action'=>'index'));
		$this->assertEqual(Route::parse('blog/show/123'), array('controller'=>'blog', 'action'=>'show', 'id'=>'123'));
		$this->assertEqual(Route::parse('blog/2004'), array('controller'=>'blog', 'action'=>'show_date', 'year'=>'2004'));
		$this->assertEqual(Route::parse('blog/2004/12'), array('controller'=>'blog', 'action'=>'show_date', 'year'=>'2004', 'month'=>'12'));
		$this->assertEqual(Route::parse('blog/2004/12/25'), array('controller'=>'blog', 'action'=>'show_date', 'year'=>'2004', 'month'=>'12', 'day'=>'25'));
		$this->assertEqual(Route::parse('blog/article/edit/123'), array('controller'=>'article', 'action'=>'edit', 'id'=>'123'));
		$this->assertEqual(Route::parse('blog/article/show_stats'), array('controller'=>'article', 'action'=>'show_stats'));
		$this->assertEqual(Route::parse('blog/wibble'), array('controller'=>'wibble', 'action'=>'index'));
		$this->assertEqual(Route::parse('junk'), array('anything'=>array('junk'), 'controller'=>'blog', 'action'=>'unkown_request'));
		
	}
	
	/**
	 * Do the same for generating
	 */
	function test_complete_generation() {
		Route::connect('', 'blog/$year/$month/$day', 
			array('controller'=>'blog', 'action'=>'show_date', 'month'=>null, 'day'=>null), 
			array('year'=>'/^(19|20)\d\d$/', 'month'=>'/^[01]?\d$/', 'day'=>'/^[0-3]?\d$/')
		);
		
		Route::connect('', 'blog/show/$id',
			array('controller'=>'blog', 'action'=>'show'),
			array('id'=>'/\d+/')
		);
		
		Route::connect('', 'blog/$controller/$action/$id');

		$request = array('controller'=>'blog', 'action'=>'show_date', 'year'=>'2005', 'month'=>'4', 'day'=>'15');
		
		// should match the first route
		$this->assertEqual(Route::url_for($request, array('day'=>'25')), 'blog/2005/4/25');
				
		// as should this
		$this->assertEqual(Route::url_for($request, array('year'=>'2004')), 'blog/2004');
		
		// but this would match the 3rd
		$this->assertEqual(Route::url_for($request, array('action'=>'edit', 'id'=>'123')), 'blog/blog/edit/123');
		

	}


}


?>