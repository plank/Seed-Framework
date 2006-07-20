<?php

class TestDB extends AbstractDB {
	
	var $test_result = array(
		array('id'=>1, 'name'=>'foo'),
		array('id'=>2, 'name'=>'bar'),
		array('id'=>3, 'name'=>'baz')
	);
	
	// copied from mysql adapter
	var $native_database_types = array(
		'primary_key' => "int(11) DEFAULT NULL auto_increment PRIMARY KEY",
		'string'      => array('name' => "varchar", 'limit' => 255 ),
		'text'        => array('name' => "text" ),
		'integer'     => array('name' => "int", 'limit' => 11 ),
		'float'       => array('name' => "float" ),
		'datetime'    => array('name' => "datetime" ),
		'timestamp'   => array('name' => "datetime" ),
		'time'        => array('name' => "time" ),
		'date'        => array('name' => "date" ),
		'binary'      => array('name' => "blob" ),
		'boolean'     => array('name' => "tinyint", 'limit' => 1)
	
	);
	
	
	function select_all($sql) {
		return $this->test_result;
	}
	
	function execute($sql, $name = null) {
		return $sql;
		
	}
}


class AbstractDBTester extends UnitTestCase {
	
	/**
	 * @var TestDB
	 */
	var $db;
	
	function setup() {
		$this->db = new TestDB();
	}
	
	function test_adapter_name() {
		$this->assertEqual($this->db->adapter_name(), 'test');
		
	}
	
	/**
	 * Test the various quoting methods
	 */
	function test_quoting() {
		$this->assertEqual($this->db->quote_string('foo'), "'foo'");	
		$this->assertEqual($this->db->quote_column_name('bar'), '"bar"');
		$this->assertEqual($this->db->quote_table_name('baz'), '"baz"');
		$this->assertEqual($this->db->quoted_true(), "'t'");
		$this->assertEqual($this->db->quoted_false(), "'f'");
		$this->assertEqual($this->db->quoted_date('06/30/1980'), '1980-06-30 12:00:00');
	}
	
	function test_selects() {
		$this->assertEqual($this->db->select_all('dummy'), $this->db->test_result);
		$this->assertEqual($this->db->select_one('dummy'), array('id'=>1, 'name'=>'foo'));
		$this->assertEqual($this->db->select_value('dummy'), '1');
		$this->assertEqual($this->db->select_values('dummy'), array('1', '2', '3'));

	}
	
	function test_add_limit_offset() {
		$sql = "SELECT * FROM news";
		
		$this->assertEqual($this->db->add_limit_offset($sql), $sql);
		$this->assertEqual($this->db->add_limit_offset($sql, array('limit'=>10)), 'SELECT * FROM news LIMIT 10');
		$this->assertEqual($this->db->add_limit_offset($sql, array('offset'=>10)), 'SELECT * FROM news');
		$this->assertEqual($this->db->add_limit_offset($sql, array('limit'=>10, 'offset'=>20)), 'SELECT * FROM news LIMIT 10 OFFSET 20');
	}

	function test_drop_table() {
		$this->assertEqual($this->db->drop_table('foo'), 
						   'DROP TABLE "foo"');	
	}
	
	function test_add_column() {
		$this->assertEqual($this->db->add_column('foo', 'bar', 'string'), 
						   'ALTER TABLE "foo" ADD "bar" varchar(255)');
		$this->assertEqual($this->db->add_column('foo', 'bar', 'string', array('limit'=>127)), 
						   'ALTER TABLE "foo" ADD "bar" varchar(127)');
		$this->assertEqual($this->db->add_column('foo', 'bar', 'text'), 
						   'ALTER TABLE "foo" ADD "bar" text');
		$this->assertEqual($this->db->add_column('foo', 'bar', 'string', array('limit'=>127, 'default'=>'hello')),
						   'ALTER TABLE "foo" ADD "bar" varchar(127) DEFAULT \'hello\'');
		$this->assertEqual($this->db->add_column('foo', 'bar', 'string', array('limit'=>127, 'null'=>false)), 
						   'ALTER TABLE "foo" ADD "bar" varchar(127) NOT NULL');
	}
	
	function test_alter_table() {
		$this->assertEqual($this->db->remove_column('foo', 'bar'), 'ALTER TABLE "foo" DROP "bar"');
						   
	}
	
	function test_add_index() {
		$this->assertEqual($this->db->add_index('foo', 'bar'), 
						   'CREATE INDEX foo_bar_index ON "foo" ("bar")');
		
		$this->assertEqual($this->db->add_index('foo', array('bar', 'baz'), array('unique'=>true)), 
						   'CREATE UNIQUE INDEX foo_bar_index ON "foo" ("bar", "baz")');
						   
		$this->assertEqual($this->db->add_index('foo', array('bar', 'baz'), array('name'=>'my_index')),
						   'CREATE INDEX my_index ON "foo" ("bar", "baz")');
	}
	
	function test_remove_index() {
		$this->assertEqual($this->db->remove_index('foo', 'bar'), 'DROP INDEX foo_bar_index ON "foo"');
		$this->assertEqual($this->db->remove_index('foo', array('column'=>'bar')), 'DROP INDEX foo_bar_index ON "foo"');
		$this->assertEqual($this->db->remove_index('foo', array('name'=>'bar')), 'DROP INDEX bar ON "foo"');
	}
	
}
	
?>