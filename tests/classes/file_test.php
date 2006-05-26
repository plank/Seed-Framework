<?php

class FileTester extends UnitTestCase {
	/**
	 * a test path, for this to work the path shouldn't exist
	 * 
	 * @var string
	 */
	var $test_path = '/path/to/a/file.php';
		
	function test_get_path() {
		$file = new File($this->test_path);
		$this->assertEqual($file->get_path(), $this->test_path);
	
	}
	
	function test_get_name_and_extension() {
		$file = new File($this->test_path);
		$this->assertEqual($file->get_name(), 'file.php');
		$this->assertEqual($file->get_name(true), 'file');
		$this->assertEqual($file->get_extension(), 'php');
	}
	
	function test_get_parent() {
		$file = new File($this->test_path);
		$this->assertEqual($file->get_parent(), '/path/to/a');
		
		
		
	}
	
	function test_is_file_and_directory() {
		$file = new File(__FILE__);	
		$this->assertTrue($file->is_file());

		$file = $file->get_parent_file();
		$this->assertTrue($file->is_directory());
		
	}
	
	function test_existance() {
		$file = new File(__FILE__);
		$this->assertTrue($file->exists());

		$file = new File($this->test_path);
		$this->assertFalse($file->exists());
	}
	
	function test_list_names() {
		$file = new File(dirname(__FILE__).'/file');
		
		$this->assertTrue($file->is_directory());
		$file_names = $file->list_names();
		
		for($x = 1; $x < 3; $x++) {
			$this->assertEqual($file_names[$x - 1], $file->get_path().'/0'.$x.'.txt');
			
		}
		
	}
	
	function test_list_files() {
		$file = new File(dirname(__FILE__).'/file');
		
		$this->assertTrue($file->is_directory());
		$files = $file->list_files();
		
		for($x = 1; $x < 3; $x++) {
			$this->assertEqual($files[$x - 1]->get_path(), $file->get_path().'/0'.$x.'.txt');
			
		}
		
	}


		
	
}

?>