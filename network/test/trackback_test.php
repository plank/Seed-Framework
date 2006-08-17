<?php

Mock::generate('SimpleSocket');

/**
 * Base for trackback testing, setup some xml responses
 */
class BaseTrackbackTester extends UnitTestCase {
	var $normal_response;
	var $error_response;
		
	function BaseTrackbackTester() {
		parent::UnitTestCase();
		
		$this->normal_response = <<<EOL
<?xml version="1.0" encoding="iso-8859-1"?>
  <response>
  <error>0</error>
</response>
EOL;
		
		$this->error_response = <<<EOL
<?xml version="1.0" encoding="iso-8859-1"?>
  <response>
  <error>1</error>
  <message>You must include a URL</message>
</response>
EOL;
		
	}
	
}

class TrackbackTester extends BaseTrackbackTester {
	
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
		$this->assertEqual($trackback->error_code, TRACKBACK_NO_ERROR);
		$this->assertEqual($trackback->error_message, '');

		// most data is optional
		$trackback = new Trackback($url, $title);
		$this->assertEqual($trackback->error_code, TRACKBACK_NO_ERROR);
		$this->assertEqual($trackback->error_message, '');

		// but url isn't 
		$trackback = new Trackback();
		$this->assertEqual($trackback->error_code, TRACKBACK_CLIENT_VALIDATION_ERROR);
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
		$this->assertEqual($trackback->error_code, TRACKBACK_NO_ERROR);
		$this->assertEqual($trackback->error_message, '');

		$data = array('url'=>'URL', 'title'=>'Title');
		
		// most data is optional
		$trackback = new Trackback($data);
		$this->assertEqual($trackback->error_code, TRACKBACK_NO_ERROR);
		$this->assertEqual($trackback->error_message, '');

		$data = array();
		
		// but url isn't 
		$trackback = new Trackback($data);
		
		$this->assertEqual($trackback->error_code, TRACKBACK_CLIENT_VALIDATION_ERROR);
		$this->assertEqual($trackback->error_message, 'The following fields were missing: URL');		
		
	}
	
	function test_set_and_get_data() {
		
		$trackback = new Trackback();
		
		$data = array('url'=>'URL', 'title'=>'Title', 'excerpt'=>'Excerpt', 'blog_name'=>'Blog Name');
		
		$trackback->set_data($data);

		$this->assertEqual($trackback->url, $data['url']);
		$this->assertEqual($trackback->title, $data['title']);
		$this->assertEqual($trackback->excerpt, $data['excerpt']);
		$this->assertEqual($trackback->blog_name, $data['blog_name']);			
		
		$trackback->blog_name = "New blog name";
		
		$data['blog_name'] = "New blog name";
		
		$this->assertEqual($data, $trackback->get_data());
		
	}
	
	function test_response_parser() {
		$trackback = new Trackback();		
		$response = new TrackbackResponse($trackback);

		$response->parse($this->normal_response);
		
		$this->assertEqual($trackback->error_code, TRACKBACK_NO_ERROR);
		$this->assertEqual($trackback->error_message, '');

		$response->parse($this->error_response);
		
		$this->assertEqual($trackback->error_code, TRACKBACK_SERVER_VALIDATION_ERROR);
		$this->assertEqual($trackback->error_message, 'You must include a URL');		

	}
	
}

/**
 * Test sending with a mock socket
 */
class TrackbackSendTester extends BaseTrackbackTester {

	/**
	 * @var HTTP
	 */
	var $http;
	
	/**
	 * @var TestSocket
	 */
	var $socket;
	
	function setup() {
		$this->socket = & new TestSocket();
		$this->http = & new HTTP($this->socket);
		
	}

	function test_good_send() {
		$url = 'http://www.company.com/';
		$data = array('url'=>'URL', 'title'=>'Title', 'excerpt'=>'Excerpt', 'blog_name'=>'Blog Name');
		
		$this->socket->get_data = $this->normal_response;
		
		$trackback = new Trackback($data);
		$trackback->send($this->http, $url);
		
		$this->assertEqual($trackback->error_code, TRACKBACK_NO_ERROR);
		$this->assertEqual($trackback->error_message, '');
		
	}

	function test_bad_send() {
		$data = array();
		$url = 'http://www.company.com/';
		
		$this->socket->get_data = $this->error_response;
		
		$trackback = new Trackback($data);
		$trackback->send($this->http, $url);
		
		$this->assertEqual($trackback->error_code, TRACKBACK_CLIENT_VALIDATION_ERROR);
		$this->assertEqual($trackback->error_message, 'The following fields were missing: URL');
		
	}	

	function test_cant_connect() {
		$url = 'http://www.company.com/';
		$data = array('url'=>'URL', 'title'=>'Title', 'excerpt'=>'Excerpt', 'blog_name'=>'Blog Name');
		
		$this->socket->allow_connect = false;
		
		$trackback = new Trackback($data);
		$trackback->send($this->http, $url);
		
		$this->assertEqual($trackback->error_code, TRACKBACK_CONNECTION_ERROR);
		$this->assertEqual($trackback->error_message, "Couldn't connect to 'http://www.company.com/'");
		
	}

	function test_validated_send() {
		$url = 'http://www.company.com/';
		
		$good_data = array('url'=>'URL', 'title'=>'Title', 'excerpt'=>'Excerpt', 'blog_name'=>'Blog Name');

		$bad_data = array('title'=>'Title', 'excerpt'=>'Excerpt', 'blog_name'=>'Blog Name');

		$this->socket->get_data = $this->normal_response;		
		
		$trackback = new Trackback($good_data);
		$trackback->send($this->http, $url);
		
		$this->assertEqual($trackback->error_code, TRACKBACK_NO_ERROR);
		$this->assertEqual($trackback->error_message, '');
		
		$trackback = new Trackback($bad_data);
		$trackback->send($this->http, $url);
		
		$this->assertEqual($trackback->error_code, TRACKBACK_CLIENT_VALIDATION_ERROR);
		$this->assertEqual($trackback->error_message, 'The following fields were missing: URL');
	}

}


?>