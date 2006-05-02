<?php

class UrlTester extends UnitTestCase {
	
	function test_basic_urls() {
		
		$url = new URL('http://www.plankdesign.com/');
		
		$this->assertEqual($url->scheme, 'http');
		$this->assertEqual($url->host, 'www.plankdesign.com');
		$this->assertEqual($url->port, 80);
		$this->assertEqual($url->path, '/');
		$this->assertEqual($url->input_url, $url->to_string());
		
		$url = new URL('http://www.plankdesign.com/test/path/');
		
		$this->assertEqual($url->scheme, 'http');
		$this->assertEqual($url->host, 'www.plankdesign.com');
		$this->assertEqual($url->port, 80);
		$this->assertEqual($url->path, '/test/path/');

		$url = new URL('ftp://user:pass@plankdesign.com');
		
		$this->assertEqual($url->scheme, 'ftp');
		$this->assertEqual($url->user, 'user');
		$this->assertEqual($url->pass, 'pass');
		$this->assertEqual($url->host, 'plankdesign.com');
		
		$url = new URL('http://www.plankdesign.com/test/../path');
		
		$this->assertEqual($url->path, '/path');
		
	}
	
	function test_relative_urls() {
		$base_href = 'http://www.plankdesign.com/test/path/index.php';
		
		$url = new URL('/new/path', $base_href);
		$this->assertEqual($url->to_string(), 'http://www.plankdesign.com/new/path');
		
		$url = new URL('to/new/place', $base_href);
		$this->assertEqual($url->to_string(), 'http://www.plankdesign.com/test/path/to/new/place');
		
		$url = new URL('../directory', $base_href);
		$this->assertEqual($url->to_string(), 'http://www.plankdesign.com/test/directory');
		
		$url = new URL('../../../hello.php', $base_href);
		$this->assertEqual($url->to_string(), 'http://www.plankdesign.com/hello.php');
		
		// make sure we can properly ignore query strings
		$url = new URL('some_new_place?id=1', $base_href);
		$this->assertEqual($url->to_string(false), 'http://www.plankdesign.com/test/path/some_new_place');
		
	}
	
	function test_query_string() {
		$url = new URL('http://www.plankdesign.com/index.php?id=2&page=3')	;
		$this->assertEqual($url->query, 'id=2&page=3');
		$this->assertEqual($url->query_array, array('id'=>'2','page'=>'3'));
		$this->assertEqual($url->to_string(), 'http://www.plankdesign.com/index.php?id=2&page=3');
		
		// we can choose which parts of the query string are included in the string, as well as their order
		$this->assertEqual($url->to_string('id'), 'http://www.plankdesign.com/index.php?id=2');
		$this->assertEqual($url->to_string('page'), 'http://www.plankdesign.com/index.php?page=3');
		$this->assertEqual($url->to_string(array('page', 'id')), 'http://www.plankdesign.com/index.php?page=3&id=2');
		
		// unfound query string parts are ignored
		$this->assertEqual($url->to_string('dummy'), 'http://www.plankdesign.com/index.php');
		
	}
	
}

?>