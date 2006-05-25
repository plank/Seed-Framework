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

	function test_columns() {
		$columns = $this->db->columns('test');
		
		$this->assertEqual($columns[0]->name, 'id');
		$this->assertEqual($columns[0]->default, '');
		$this->assertEqual($columns[0]->type, 'integer');
		$this->assertEqual($columns[0]->limit, '');
		$this->assertEqual($columns[0]->null, true);

		$this->assertEqual($columns[1]->name, 'title');
		$this->assertEqual($columns[1]->default, 'default title');
		$this->assertEqual($columns[1]->type, 'string');
		$this->assertEqual($columns[1]->limit, '255');
		$this->assertEqual($columns[1]->null, false);

		$this->assertEqual($columns[2]->name, 'text');
		$this->assertEqual($columns[2]->default, 'default text');
		$this->assertEqual($columns[2]->type, 'text');
		$this->assertEqual($columns[2]->limit, '');
		$this->assertEqual($columns[2]->null, false);

	}
}

?>