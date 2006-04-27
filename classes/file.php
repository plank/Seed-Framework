<?php
/**
 * file.php, part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package library
 */

/**
 * A class for handling files and directories. 
 * Based on java.io.File
 *
 * @package library
 *
 */
class File {
	var $path;
	
	/**
	 * Constructor
	 *
	 * @param unknown_type $path
	 * @return File
	 */
	function File($path) {
		$this->path = $path;
	}

	/**
	 * Returns the complete path represented by this file
	 *
	 * @return string
	 */
	function get_path() {
		return $this->path;	
		
	}
	
	/**
	 * Returns the base name of the file
	 *
	 * @return string
	 */
	function get_name($without_extension = false) {
		if ($without_extension) {
			return basename($this->path, '.'.$this->get_extension());
		} else {
			return basename($this->path);
		}
	}
	
	/**
	 * Return the extension of the file, not including the dot
	 *
	 * @return string
	 */
	function get_extension() {
		return array_pop(explode('.', basename($this->path)));
	}
	
	/**
	 * Returns the parent path of the file
	 *
	 * @return string
	 */
	function get_parent() {
		return dirname($this->path);
	}
	
	/**
	 * Returns the parent path of the file as a file object
	 *
	 * @return File
	 */
	function get_parent_file() {
		$parent = $this->get_parent();
		
		if (file_exists($parent)) {
			return new File($parent);
		} else {
			return false;
		}
		
	}
	
	/**
	 * Returns true if this file object represents a directory
	 *
	 * @return bool
	 */
	function is_directory() {
		return is_dir($this->path);
		
	}
	
	/**
	 * Returns true if the file object represents a file
	 *
	 * @return bool
	 */
	function is_file() {
		return is_file($this->path);
		
	}
	
	/**
	 * Returns true if the file is hidden. Currently only works on unix based systems.
	 *
	 * @return bool
	 */
	function is_hidden() {
		return substr($this->get_name(), 0, 1) == '.';
		
	}
	
	/**
	 * Returns true if the file exists
	 *
	 * @return bool
	 */
	function exists() {
		return file_exists($this->path);
	}
	
	/**
	 * Returns a list of filenames in an array
	 * 
	 * Name changed from the original java "list" due to the word being reserved in php
	 * 
	 * @return array
	 */
	function list_names() {
		if (!$this->is_directory()) {
			return false;
		}
		
		$return = array();
		
		$handle = opendir($this->path); 
		
		while (false !== ($file = readdir($handle))) {
			if ($file == '.' || $file == '..') {
				continue;
			}
			
			$return[] = $this->path.'/'.$file;
			
		}

		return $return;		
		
	}
	
	/**
	 * Returns a list of file objects in an array
	 *
	 * @return array
	 */
	function list_files() {
		if (!$this->is_directory()) {
			return false;
		}
		
		$return = array();
		
		$handle = opendir($this->path); 
		
		while (false !== ($file = readdir($handle))) {
			if ($file == '.' || $file == '..') {
				continue;
			}
			
			$return[] = new File($this->path.'/'.$file);
			
		}

		return $return;
	}

	function mkdirs() {
		$path_parts = explode('/', $this->path);
		
		
		$make_path = '/';
		
		foreach ($path_parts as $path_part) {
			if ($path_part == '') {
				continue;
			}
			
			$make_path .= $path_part.'/';
			
			if (!file_exists($make_path)) {
				mkdir($make_path);
				
				chmod($make_path, 0777);
			}

		}
		
		return true;
		
	}
	
}

?>