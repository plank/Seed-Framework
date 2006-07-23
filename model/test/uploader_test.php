<?php
/*
Files format is as follows

$_FILES['userfile']['name']
$_FILES['userfile']['type']
$_FILES['userfile']['size']
$_FILES['userfile']['tmp_name']
$_FILES['userfile']['error']
*/

class AbstractUploaderTester extends UnitTestCase {
	
	/**
	 * @var AbstractUploader
	 */
	var $uploader;
	
	var $source_path;

	var $destination_path;
	
	var $destination_path2;
	
	function setup() {
		
		$this->source_path = dirname(__FILE__).'/uploader/source/';
		$this->destination_path = dirname(__FILE__).'/uploader/destination/';	
		$this->destination_path2 = dirname(__FILE__).'/uploader/destination2/';	
				
		$this->uploader = new AbstractUploader();
		$this->uploader->upload_path = $this->destination_path;			
		$this->uploader->rename_duplicates = true;
	}

	function test_setup() {
		$this->assertTrue(file_exists($this->source_path) && is_dir($this->source_path), "Source path '$this->source_path' does not exist");
		$this->assertTrue(file_exists($this->destination_path) && is_dir($this->destination_path), "Destination path '$this->destination_path' does not exist");
		$this->assertTrue(file_exists($this->destination_path2) && is_dir($this->destination_path2), "Destination path '$this->destination_path2' does not exist");
		
		$this->assertTrue(is_writable($this->source_path), "Source path '$this->source_path' is not writable");
		$this->assertTrue(is_writable($this->destination_path), "Destination path '$this->destination_path' is not writable");
		$this->assertTrue(is_writable($this->destination_path2), "Destination path '$this->destination_path2' is not writable");
	}

	function test_single_upload() {
		$file = array();
		
		$file['name'] = 'test1.txt';
		$file['type'] = 'text/html';
		$file['size'] = '1';
		$file['tmp_name'] = $this->source_path.'/test1.txt';
		$file['error'] = '';
		
		$destination = $this->destination_path.'/test1.txt';
		
		$this->assertFalse($this->uploader->handle_upload('test', $file));	
		
		$this->assertTrue(file_exists($destination));
		unlink($destination);
	}
	
	function test_multiple_uploads() {
		$file = array();
		
		for ($x = 1; $x < 3; $x++) {
			$file['field'.$x]['name'] = 'test'.$x.'.txt';
			$file['field'.$x]['type'] = 'text/html';
			$file['field'.$x]['size'] = '1';
			$file['field'.$x]['tmp_name'] = $this->source_path.'/test'.$x.'.txt';
			$file['field'.$x]['error'] = '';
		
			$destinations[$x] = $this->destination_path.'/test'.$x.'.txt';
		}
		
		$this->assertFalse($this->uploader->handle_uploads($file));	
		
		$this->assertTrue(file_exists($destinations[1]));
		$this->assertTrue(file_exists($destinations[2]));
		unlink($destinations[1]);
		unlink($destinations[2]);
		
	}
	
	function test_renaming_duplicates() {
		$file = array();
		
		$file['name'] = 'test1.txt';
		$file['type'] = 'text/html';
		$file['size'] = '1';
		$file['tmp_name'] = $this->source_path.'/test1.txt';
		$file['error'] = '';
		
		$destinations[1] = $this->destination_path.'/test1.txt';
		$destinations[2] = $this->destination_path.'/test1_1.txt';
		
		$this->assertFalse($this->uploader->handle_upload('test', $file));
		$this->assertFalse($this->uploader->handle_upload('test', $file));			
		
		$this->assertTrue(file_exists($destinations[1]));
		$this->assertTrue(file_exists($destinations[2]));
		unlink($destinations[1]);
		unlink($destinations[2]);		
		
	}
	
	function test_multiple_save_paths() {
		
		$this->uploader->upload_path = array($this->destination_path, $this->destination_path2);
		
		$file = array();
		
		$file['name'] = 'test1.txt';
		$file['type'] = 'text/html';
		$file['size'] = '1';
		$file['tmp_name'] = $this->source_path.'/test1.txt';
		$file['error'] = '';
		
		$destinations[1] = $this->destination_path.'/test1.txt';
		$destinations[2] = $this->destination_path2.'/test1.txt';
		
		$this->assertFalse($this->uploader->handle_upload('test', $file));
		
		$this->assert_and_delete_file($destinations[1]);
		$this->assert_and_delete_file($destinations[2]);
	}
	
	function assert_and_delete_file($file) {
		$this->assertTrue(file_exists($file));
		unlink($file);
		
	}
}



/*
class UploaderTestModel {
	
	function upload_path($field) {
		
	}
	
	function upload_file_name($filename, $field) {
		
	}
	
	
}
*/


?>