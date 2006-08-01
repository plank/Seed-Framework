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
	
	
	
	function test_list_files() {
		$dir = new File(dirname(__FILE__).'/file');
		
		$this->assertTrue($dir->is_directory());
		$files = $dir->list_files();
		
		$x = 1;
		
		foreach($files as $file) {
			if (!$file->is_hidden() && $file->is_file()) {
				$this->assertEqual($file->path, $dir->get_path().'/0'.$x.'.txt');	
				$x ++;
			}
			
		}		
	}	
	
	
	
	function test_list_names() {
		$file = new File(dirname(__FILE__).'/file');
		
		$this->assertTrue($file->is_directory());
		$file_names = $file->list_names();
		
		$x = 1;
		
		foreach($file_names as $file_name) {
			if (substr(basename($file_name), 0, 1) != '.' && !is_dir($file_name)) {
				$this->assertEqual($file_name, $file->get_path().'/0'.$x.'.txt');	
				$x ++;
			}
			
		}
			
	}

	function test_list_names_recursive() {
		$dir = new File(dirname(__FILE__).'/file');

		$this->assertTrue($dir->is_directory());
		$files = $dir->list_names(true);

//		$this->dump($files);
		
		$expected = array(
			'/01.txt',
			'/02.txt',
			'/03.txt',
			'/dir',
			'/dir/11.txt',
			'/dir/12.txt'
		);		
	
		$x = 0;
		
		foreach($files as $file) {
			$this->assertEqual($dir->path.$expected[$x], $file);
			$x ++;
		}
		
	}
	
}

?>