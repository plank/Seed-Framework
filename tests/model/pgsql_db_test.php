<?php



class PgsqlDBTester extends UnitTestCase {
	/**
	 * @var PgsqlDB
	 */
	var $db;
	
	function setup() {
		$this->db = DB::factory('pgsql', 'localhost', '', '', 'unit_tests');
		
	}
	
	function test_creation() {
		$this->assertIsA($this->db, 'PgsqlDB');
		
	}
	
	function test_simple_query() {
		$result = $this->db->query('select * from test');	
		$this->assertTrue($result);
		
	}
	
	function test_query_value() {
		$this->assertEqual($this->db->query_value('select id from test'), array(1, 2));
		
	}
	
	function test_query_array() {
		$this->assertEqual($this->db->query_array('select * from test limit 1'), array(array('id'=>1, 'title'=>'One', 'text'=>'Text one')));
	}
	
	function test_query_single() {
		//$this->dump($this->db->query_single('select * from test'));		
	}

	function test_describe() {
		$this->dump($this->db->describe('test'));	
		
	}
	
}

?>