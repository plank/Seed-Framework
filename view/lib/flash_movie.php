<?php 

/**
 * Class for generating flash movies
 * 
 * @package view
 * @subpackage flash_movie
 */
class FlashMovie {
	// this parameters usually won't need to be changed
	var $classid = "clsid:d27cdb6e-ae6d-11cf-96b8-444553540000";
	var $codebase = "http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0";
	var $mime_type = "application/x-shockwave-flash";
	var $plugin_page = "http://www.macromedia.com/go/getflashplayer";
	
	var $source;
	var $allow_script_access = "sameDomain";
	var $quality = 'high';
	var $bg_color = '#333333';
	
	var $flash_vars;
	var $html_vars;
	
	/**
	 * Constructor
	 *
	 * @param string $source     The path to the flash movie to show
	 * @param array $flash_vars  An array of key value pairs to pass to the flash movie
	 * @param array $html_vars   An array of key value pairs containing attributes for the tags
	 * @return FlashMovie
	 */
	function FlashMovie($source, $flash_vars = null, $html_vars) {
		$this->source = $source;
		
		$this->flash_vars = assign($flash_vars, array());
		
		$this->html_vars = assign($html_vars, array());
		
	}
	
	/**
	 * We should check if values need to be urlencoded?
	 *
	 * @return string
	 */
	function get_flash_vars() {
		$result = array();
		
		foreach($this->flash_vars as $key => $value) {
			$result[] = "$key=".htmlentities($value, ENT_QUOTES, 'utf-8');	
			
		}
		
		return implode('&', $result);
		
	}
	
	/**
	 * @return string
	 */
	function get_html_vars() {
		$result = array();
		
		foreach($this->html_vars as $key => $value) {
			$result[] = "$key='".htmlentities($value, ENT_QUOTES, 'utf-8')."'";
		}	
		
		return implode(' ', $result);
		
	}
	
	/**
	 * @return string
	 */
	function generate() {

		$return  = "<object classid='$this->classid' codebase='$this->codebase' ".$this->get_html_vars().">";
		$return .= "	<param name='allowScriptAccess' value='$this->allow_script_access' />";
		$return .= "	<param name='movie' value='$this->source' />";
		$return .= "	<param name='quality' value='$this->quality' />";
		$return .= "	<param name='bgcolor' value='$this->bg_color' />";
		$return .= "	<param name='FlashVars' value='".$this->get_flash_vars()."' />";
		$return .= "	<embed src='$this->source' ".$this->get_html_vars()." quality='$this->quality' bgcolor='$this->bg_color' ";
		$return .= "allowScriptAccess='$this->allow_script_access' type='$this->mime_type' pluginspage='$this->plugin_page' ";
		$return .= "FlashVars='".$this->get_flash_vars()."' />";
		$return .= "</object>";

		return $return;

	}
	
}

?>