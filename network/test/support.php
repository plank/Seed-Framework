<?php

class TestSocket {
	
	var $connected = false;
	
	var $allow_connect = true;
	
	var $put_data;
	
	var $get_data;
	
	function open() {
		if (!$this->allow_connect) {
			return false;	
		}
		
		$this->connected = true;
		
		return true;
	}
	
	function put($data) {
		$this->put_data .= $data;
	}
	
	function get() {
		return $this->get_data;		
	}
	
	function get_all() {
		return $this->get_data;
	}
	
	function eof() {
		return false;
	}
	
	function close() {
		$this->connected = false;
		
		return true;
	}
	
}

?>