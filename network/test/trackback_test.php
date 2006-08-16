<?php

Mock::generate('SimpleSocket');

class TrackbackTester extends UnitTestCase {
	
	function test_mock() {
		$socket = new MockSimpleSocket();
		
		$this->dump($socket);	
	}
	
	function test_create_and_validate() {
		$url = 'URL';
		$title = 'Title';
		$excerpt = 'Excerpt';
		$blog_name = 'Blog Name';
		
		$trackback = new Trackback($url, $title, $excerpt, $blog_name);
		
		$this->assertEqual($url, $trackback->url);
		$this->assertEqual($title, $trackback->title);
		$this->assertEqual($excerpt, $trackback->excerpt);
		$this->assertEqual($blog_name, $trackback->blog_name);
		
		// track back should be valid
		$this->assertEqual($trackback->error_code, 0);
		$this->assertEqual($trackback->error_message, '');

		// most data is optional
		$trackback = new Trackback($url, $title);
		$this->assertEqual($trackback->error_code, 0);
		$this->assertEqual($trackback->error_message, '');

		// but url isn't 
		$trackback = new Trackback();
		$this->assertEqual($trackback->error_code, 1);
		$this->assertEqual($trackback->error_message, 'The following fields were missing: URL');
		
		
	}
	
	function test_array_create_and_validate() {
		$data = array('url'=>'URL', 'title'=>'Title', 'excerpt'=>'Excerpt', 'blog_name'=>'Blog Name');
		
		$trackback = new Trackback($data);

		$this->assertEqual($trackback->url, $data['url']);
		$this->assertEqual($trackback->title, $data['title']);
		$this->assertEqual($trackback->excerpt, $data['excerpt']);
		$this->assertEqual($trackback->blog_name, $data['blog_name']);
		
		// track back should be valid
		$this->assertEqual($trackback->error_code, 0);
		$this->assertEqual($trackback->error_message, '');

		$data = array('url'=>'URL', 'title'=>'Title');
		
		// most data is optional
		$trackback = new Trackback($data);
		$this->assertEqual($trackback->error_code, 0);
		$this->assertEqual($trackback->error_message, '');

		$data = array();
		
		// but url isn't 
		$trackback = new Trackback($data);
		
		$this->assertEqual($trackback->error_code, 1);
		$this->assertEqual($trackback->error_message, 'The following fields were missing: URL');		
		
	}
	
}

class TrackbackResponseTester {
	function test_parser() {
		$trackback = new Trackback();		
		$response = new TrackbackResponse($trackback);
		
		
$data = <<<EOL
<?xml version="1.0" encoding="iso-8859-1"?>
  <response>
  <error>0</error>
</response>
EOL;

		$response->parse($data);
		
		$this->assertEqual($trackback->error_code, 0);
		$this->assertEqual($trackback->error_message, '');
		


$data = <<<EOL
<?xml version="1.0" encoding="iso-8859-1"?>
  <response>
  <error>1</error>
  <message>You must include a URL and ID</message>
</response>
EOL;

		$response->parse($data);
		
		$this->assertEqual($trackback->error_code, 1);
		$this->assertEqual($trackback->error_message, 'You must include a URL and ID');		
		


	}
	
}

?>