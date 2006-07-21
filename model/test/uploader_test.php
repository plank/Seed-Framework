<?php


class AbstractUploaderTester extends UnitTestCase {
	
	/**
	 * @var AbstractUploader
	 */
	var $uploader;
	
	var $source_path;

	var $destination_path;
	
	function setup() {
		
		$this->source_path = dirname(__FILE__).'/uploader/source/';
		$this->destination_path = dirname(__FILE__).'/uploader/destination/';	
		
		$this->uploader = new AbstractUploader();
		$this->uploader->upload_path = $this->destination_path;			
		
	}

	function test_setup() {
		$this->assertTrue(file_exists($this->source_path) && is_dir($this->source_path));
		
		$this->assertTrue(file_exists($this->destination_path) && is_dir($this->destination_path));
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