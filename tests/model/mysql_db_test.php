<?php

class MysqlDBTester extends UnitTestCase {
	
	function setup() {
		$this->db = DB::factory('mysql', DB_HOST, DB_USER, DB_PASS, DB_NAME);		
		
	}
	
	function test_describe() {
		//$this->dump($this->db->describe('test'));	
		
	}	
	
}

?>