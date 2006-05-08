<?php

/**
 * Class for creating import scripts
 *
 */
class Importer {

	/**
	 * The path to import
	 *
	 * @var string
	 */
	var $path;
	
	/**
	 * Connection to the db
	 *
	 * @var DB
	 */
	var $db;
	
	/**
	 * An array of succesfully imported files
	 *
	 * @var array
	 */
	var $good_files;
	
	/**
	 * An array of unsuccesfully imported files
	 *
	 * @var array
	 */
	var $bad_files;
	
	/**
	 * Constructor
	 *
	 * @param string $path The path containing the files to import
	 */
	function Importer($path = null) {
		$this->path = $path;
		$this->db = DB::get_db();	
		
		$this->good_files = array();
		$this->bad_files = array();
	}
	
	/**
	 * Hook for setting up imports in subclasses
	 */
	function setUp() {
		
	}
	
	/**
	 * Starts the import
	 *
	 * @param string $path The path containing the files to import
	 */
	function import($path = null) {
		
		$this->setUp();
		
		if (!isset($path)) {
			$path = $this->path;
		}
		
		if (!file_exists($path)) {
			trigger_error("The given path '$path' doesn't exist", E_USER_WARNING);
			return false;
		}
		
		$files = dir($path);

		// loop throught the directory and attempt to import each file contained
		while(($file = $files->read())!== false) {
			if (substr($file, 0, 1) == '.') {
				continue;
			}

			$contents = file($path.'/'.$file);
			$result = $this->import_file($contents, $file);
			
			if ($result) {
				$this->good_files[] = $file;
			} else {
				$this->bad_files[] = $file;
			}
		}
	}
	
	/**
	 * Hook for importing files
	 *
	 * @param string $contents The contents of the file
	 * @return bool Return true if the file was succesfully imported, so we can count them later
	 */
	function import_file($contents) {
		trigger_error('Importer::import_file needs to be implemented', E_USER_ERROR);
		
	}
}

?>