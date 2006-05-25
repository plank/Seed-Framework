<?php

class ColumnTester extends UnitTestCase {
	
	function test_extract_limit() {
		$this->assertEqual(Column::extract_limit('varchar(255)'), '255');
		$this->assertEqual(Column::extract_limit('int(11)'), '11');
		$this->assertFalse(Column::extract_limit('datetime'));
		
	}
	
	function test_extract_type() {
		$this->assertEqual(Column::extract_type('varchar(255)'), 'varchar');
		$this->assertEqual(Column::extract_type('int(11)'), 'int');
		$this->assertEqual(Column::extract_type('datetime'), 'datetime');
		
	}	
	
	function test_creation() {
		$column = new Column('title', 'new', 'varchar(255)', false);	
		
		$this->assertEqual($column->name, 'title');
		$this->assertEqual($column->default, 'new');
		$this->assertEqual($column->type, 'string');
		$this->assertEqual($column->limit, 255);
		$this->assertEqual($column->null, false);
		
	}
	
}


?>