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

		$expected_data = "POST /hello HTTP/1.1\r\n";
		$expected_data .= "Host: www.company.com\r\n";
		$expected_data .= "Content-type: application/x-www-form-urlencoded; charset=utf-8\r\n";
		$expected_data .= "Content-length: 15\r\n";
		$expected_data .= "Connection: close\r\n";
		$expected_data .= "\r\n";
		$expected_data .= "foo=bar&baz=qux\r\n";
		$expected_data .= "\r\n";

		$http = new HTTP($this->socket);
		$http->open($url);
		$response = $http->post($data);
		
		$this->assertIsA($response, 'HTTPResponse');
		$this->assertEqual($expected_data, $this->socket->put_data);
		
		
	}
	
}

class HttpResponseTester extends UnitTestCase  {
	function test_status_parser() {
		$response = new HTTPResponse();
		
		$this->assertEqual($response->parse_status_line('HTTP/1.1 200 OK'), array('1.1', 200, 'OK'));
		
	}
	
	function test_header_parser() {
		$response = new HTTPResponse();
		
		$headers[] = "Host: www.company.com";
		$headers[] = "Content-type: application/x-www-form-urlencoded; charset=utf-8";
		$headers[] = "Content-length: 15";
		$headers[] = "Connection: close";
		
		$this->assertEqual(
			$response->parse_headers($headers), 
			array('Host' => 'www.company.com', 'Content-type' => 'application/x-www-form-urlencoded; charset=utf-8', 'Content-length' => '15', 'Connection' => 'close')
		);
		
	}
	
}


class RealSocketTester extends UnitTestCase {
/*
	function test_get() {
		$url = "http://workshed.plankdesign.com/test.html";
		
		$http = new HTTP(new SimpleSocket());
		$http->open($url);
		$this->dump($http->get());
		
	}
*/
	
}


?>