<?php

class LiquidBlankFileSystem {

	
	function read_template_file($template_path) {
		trigger_error("This liquid context does not allow includes.");
		
	}
	

	
}

class LiquidLocalFileSystem extends LiquidBlankFileSystem {
	
	/**
	 * The root path
	 *
	 * @var string
	 */
	var $root;
	
	function LiquidLocalFileSystem($root) {
		$this->root = $root;
		
	}	
	
	function read_template_file($template_path) {
		$full_path = $this->full_path($template_path);
		
		if ($full_path) {
			file_get_contents($full_path);
		} else {
			trigger_error("No such template '$template_path'", E_USER_ERROR);
			return false;
		}
		
	}
	
	function full_path($template_path) {
		$name_regex = new Regexp('/^[^.\/][a-zA-Z0-9_\/]+$/');
		
		if (!$name_regex->match($template_path)) {
			trigger_error("Illegal template name '$template_path'", E_USER_ERROR);
			return false;
		}
		
		if (strpos($template_path, '/') !== false) {
			$full_path = $this->root.dirname($template_path).'/'."_".basename($template_path).".liquid";
			
		} else {
			$full_path = $this->root."_".$template_path.".liquid";
			
		}
		
		$root_regex = new Regexp(realpath($root));
		
		if (!$root_regex->match(realpath($full_path))) {
			trigger_error("Illegal template path '".realpath($full_path)."'", E_USER_ERROR);
		} else {
			return $full_path;
		}
		
	}
	
}