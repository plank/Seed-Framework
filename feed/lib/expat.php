<?php

class ExpatParser {
	
	/**
	 * @var resource
	 */
	var $parser;
	
	/**
	 * @var array
	 */
	var $stack;
	
	/**
	 * The name of the current element
	 *
	 * @var string
	 */
	var $current_element;
	
	/**
	 * When this is set to true, character data that is pure whitespace is ignored
	 *
	 * @var bool
	 */
	var $ignore_whitespace = true;
	
	/**
	 * Constructor
	 */
	function ExpatParser() {

	}

	
	function depth() {
		return count($this->stack);	
	}
	
	function parse($xml_data) {
		$this->parser = xml_parser_create();
		$this->stack = array();
		
		xml_set_object($this->parser, $this);
		xml_set_element_handler($this->parser, '_start_element', '_end_element');
		xml_set_character_data_handler($this->parser, '_character_data');
		
		xml_parse($this->parser, $xml_data);	
		
		xml_parser_free($this->parser);
		
	}
	
	// public
	
	function start_element($name, $attributes) {
		
	}
	
	function end_element($name) {
		
	}

	function character_data($content) {
		
	}	
	
	// private
	
	function _start_element($parser, $name, $attributes) {
		$name = strtolower($name);
		$this->current_element = $name;
		
		array_push($this->stack, $name);
		$attributes = array_change_key_case($attributes);
		$this->start_element($name, $attributes);
	}
	
	function _end_element($parser, $name) {
		array_pop($this->stack);
		$this->end_element(strtolower($name));	
	}
	
	function _character_data($parser, $content) {
		if ($this->ignore_whitespace && trim($content) == '') {
			return;	
		}
		
		$this->character_data($content);	
	}
	

}

?>