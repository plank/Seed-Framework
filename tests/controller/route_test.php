<?php

class RouteTester extends UnitTestCase {

	function setup() {
		
		
	}
	
	function test_helpers() {
		$this->assertEqual(Route::get_token('$controller'), 'controller');
		$this->assertFalse(Route::get_token('controller'));
		
	}
	
	function test_default_route() {
		// this would be the default route for most apps
		$route = new Route('$controller/$action/$id', array('action'=>'index', 'id'=>null));
		
		// too many params shouldn't work
		$this->assertFalse($route->parse_url('url/is/too/long'));
		
		// no default for controller
		$this->assertFalse($route->parse_url(''));
		
		// these should work
		$this->assertEqual($route->parse_url('test'), array('controller'=>'test', 'action'=>'index'));
		$this->assertEqual($route->parse_url('test/this'), array('controller'=>'test', 'action'=>'this'));		
		$this->assertEqual($route->parse_url('test/this/url'), array('controller'=>'test', 'action'=>'this', 'id'=>'url'));		
		
		// extraneous slashes should be ignored
		$this->assertEqual($route->parse_url('/test'), array('controller'=>'test', 'action'=>'index'));
		$this->assertEqual($route->parse_url('test/this/'), array('controller'=>'test', 'action'=>'this'));		
		$this->assertEqual($route->parse_url('/test/this/url/'), array('controller'=>'test', 'action'=>'this', 'id'=>'url'));		
		

	}

	function test_route_with_requirements() {
		// a typical blog archive route
		$defaults = array('controller'=>'blog', 'action'=>'show_date', 'month'=>null, 'day'=>null);
		$requirements = array('year'=>'/^(19|20)\d\d$/', 'month'=>'/^[01]?\d$/', 'day'=>'/^[0-3]?\d$/');
		$route = new Route('blog/$year/$month/$day', $defaults, $requirements);
		
		// these should work
		$this->assertEqual($route->parse_url('blog/2004'), array('controller'=>'blog', 'action'=>'show_date', 'year'=>2004));
		$this->assertEqual($route->parse_url('blog/2004/12'), array('controller'=>'blog', 'action'=>'show_date', 'year'=>2004, 'month'=>12));
		$this->assertEqual($route->parse_url('blog/2004/12/25'), array('controller'=>'blog', 'action'=>'show_date', 'year'=>2004, 'month'=>12, 'day'=>25));
		
		// these shouldn't work, as the parameters don't match the requirements
		$this->assertFalse($route->parse_url('blosg/2004'));
		$this->assertFalse($route->parse_url('blog/2104/12'));
		$this->assertFalse($route->parse_url('blog/2004/200'));
		$this->assertFalse($route->parse_url('blog/20034'));
		$this->assertFalse($route->parse_url('blog/2004/12/41'));
		$this->assertFalse($route->parse_url('blog/2004/12/25/22'));

	}
	
	function test_catch_all() {
		
		// this route is good and should work
		$route = new Route('article/$id/*params', array());

		$this->assertEqual($route->parse_url('article/1/'), array('id'=>1, 'params'=>array()));
				
		$this->assertEqual($route->parse_url('article/1/1/2/3'), array('id'=>1, 'params'=>array('1', '2', '3')));
				
		// this one is bad and should raise an error
		$route = new Route('article/*params/no_good', array());
		$this->assertError($route->parse_url('article/1/1/2/3'), array('id'=>1, 'params'=>array('1', '2', '3')));		
	}
	
	function test_default_url_generation() {
		// this would be the default route for most apps
		$route = new Route('$controller/$action/$id', array('action'=>'index', 'id'=>null));		
		
		$this->assertEqual($route->generate_url(null, array('controller'=>'store')), 'store');
		$this->assertEqual($route->generate_url(null, array('controller'=>'store', 'id'=>'123')), 'store/index/123');
		$this->assertEqual($route->generate_url(null, array('controller'=>'store', 'action'=>'list')), 'store/list');
		$this->assertEqual($route->generate_url(null, array('controller'=>'store', 'action'=>'list', 'id'=>'123')), 'store/list/123');
		$this->assertEqual($route->generate_url(null, array('controller'=>'store', 'action'=>'list', 'id'=>'123', 'extra'=>'wibble')), 'store/list/123?extra=wibble');
		
	}
	
	function test_date_route_generation() {
		// our typical blog archive route
		$route = new Route('blog/$year/$month/$day', 
			array('controller'=>'blog', 'action'=>'show_date', 'month'=>null, 'day'=>null), 
			array('year'=>'/^(19|20)\d\d$/', 'month'=>'/^[01]?\d$/', 'day'=>'/^[0-3]?\d$/')
		);		

		// some request params
		$request = array('controller'=>'blog', 'action'=>'show_date', 'year'=>'2005', 'month'=>'4', 'day'=>'15');
		
		// urls represent hierarchies, so changes in the hierarchy means ignoring items further down
		$this->assertEqual($route->generate_url($request, array('day'=>'25')), 'blog/2005/4/25');
		$this->assertEqual($route->generate_url($request, array('month'=>'5')), 'blog/2005/5');
		$this->assertEqual($route->generate_url($request, array('year'=>'2004')), 'blog/2004');

		// although, we have to option of overwriting part of the request to do the opposite
		$this->assertEqual($route->generate_url($request, null, array('year'=>'2004')), 'blog/2004/4/15');		
		
		// if the new values are identical to the request, we need to return a url for the complete request
		$this->assertEqual($route->generate_url($request, array('year'=>'2005')), 'blog/2005/4/15');
		
		// params need to match the requirements as well, so this shouldn't work
		$this->assertFalse($route->generate_url($request, array('year'=>'2100')));
		
	}
	
	function test_something() {
		// regular controller
		$route = new Route('$controller/$action/$id', array('action'=>'index', 'id'=>null));

		$request = array('controller' => 'overview', 'action' => 'index');
		
		$this->assertEqual($route->generate_url($request, array('controller'=>'overview')), 'overview');
		
		$request = array('controller'=>'page', 'action'=>'edit', 'id'=>1);
		
		$this->assertEqual($route->generate_url($request, array('action'=>'delete', 'id'=>1)), 'page/delete/1');
		$this->assertEqual($route->generate_url($request, array('controller'=>'blog')), 'blog');
		
	
		
	}
	
	
}

?>