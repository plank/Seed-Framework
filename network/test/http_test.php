<?php

Mock::generate('SimpleSocket');

class HttpTester extends UnitTestCase {
	/**
	 * @var SimpleSocket
	 */
	var $socket;
	
	function setup() {
		$this->socket = new TestSocket();
		
	}
	
	function test_open_and_close() {
		$url = 'http://www.company.com/';
		
		$http = new HTTP($this->socket);
		
		$this->assertTrue($http->open($url));
		$this->assertTrue($http->close($url));
	}
	
	function test_build_data_string() {
		$data = array('foo'=>'bar', 'baz'=>'qux');
		
		$this->assertEqual(HTTP::build_data_string($data), 'foo=bar&baz=qux');
		
		
	}
	
	function test_post() {
		$url = 'http://www.company.com/hello';
		
		$data = array('foo'=>'bar', 'baz'=>'qux');

		$expected_data = "POST /hello:80 HTTP/1.1\r\n";
		$expected_data .= "Host: www.company.com\r\n";
		$expected_data .= "Content-type: application/x-www-form-urlencoded\r\n";
		$expected_data .= "Content-length: 15\r\n";
		$expected_data .= "Connection: close\r\n";
		$expected_data .= "\r\n";
		$expected_data .= "foo=bar&baz=qux\r\n";
		$expected_data .= "\r\n";

		$http = new HTTP($this->socket);
		$http->open($url);
		$http->post($data);
		
		$this->assertEqual($expected_data, $this->socket->put_data);
		
	}
	
	
}


?>