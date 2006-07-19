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
		
		$this->assertEqual($columns['id']->name, 'id');
		$this->assertEqual($columns['id']->default, '');
		$this->assertEqual($columns['id']->type, 'integer');
		$this->assertEqual($columns['id']->limit, '');
		$this->assertEqual($columns['id']->null, true);

		$this->assertEqual($columns['title']->name, 'title');
		$this->assertEqual($columns['title']->default, 'default title');
		$this->assertEqual($columns['title']->type, 'string');
		$this->assertEqual($columns['title']->limit, '255');
		$this->assertEqual($columns['title']->null, false);

		$this->assertEqual($columns['text']->name, 'text');
		$this->assertEqual($columns['text']->default, 'default text');
		$this->assertEqual($columns['text']->type, 'text');
		$this->assertEqual($columns['text']->limit, '');
		$this->assertEqual($columns['text']->null, false);

	}
}

?>