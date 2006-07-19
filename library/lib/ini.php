<?php
/**
 * ini.php, part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package library
 */


/**
 * Class for parsing ini files
 *
 * @package library
 */

class Ini {
	
	function Ini($file_path) {

		if (!file_exists($file_path)) {
			trigger_error("Ini file not found in '$file_path'", E_USER_WARNING);
			return false;
		
		}

		$data = parse_ini_file($file_path, true);
		
		foreach($data as $key => $value) {
			$this->$key = $value;
		}
		
	}
}


?>