<?php

/**
 * Abstract class for XMl Stream Parsers
 */
class AbstractStreamParser {
	
	/**
	 * The output encoding
	 *
	 * @var string
	 */
	var $output_encoding = "UTF-8";
	
	/**
	 * The XML data to be parsed
	 *
	 * @var string
	 */
	var $xml_data;
	
	/**
	 * When this is set to true, character data that is pure whitespace is ignored
	 *
	 * @var bool
	 */
	var $ignore_whitespace = true;
	
	/**
	 * The method on the handler to call on start_element events
	 *
	 * @var string
	 */
	var $start_element_handler = "start_element";
	
	/**
	 * The method on the handler to call on end_element events
	 *
	 * @var string
	 */	
	var $end_element_handler = "end_element";
	
	/**
	 * The method on the handler to call on character_data events
	 *
	 * @var string
	 */	
	var $character_data_handler = "character_data";
	
	/**
	 * The method to call on errors
	 *
	 * @var string
	 */
	var $error_handler = "error_handler";

	/**
	 * A reference to the object that will handle the different events
	 *
	 * @var object
	 */
	var $handler;
	
	/**
	 * The processing stack
	 *
	 * @var array
	 */
	var $stack;	
	
	/**
	 * Constructor
	 *
	 * @param object $handler
	 */
	function AbstractStreamParser(& $handler) {
		$this->handler = & $handler;
		
	}	
	
	/**
	 * Parse the given xml_data string
	 *
	 * @param string $xml_data
	 * @param string $input_encoding  Explicitely set the input encoding
	 * @return bool
	 */	
	function parse($xml_data, $input_encoding = '') {
		$this->stack = array();			
	}
	
	/**
	 * Detects and returns the input encoding
	 *
	 * @param string $xml_data
	 * @return string
	 */
	function detect_encoding($xml_data) {
		if (preg_match('/<?xml.*encoding=[\'"](.*?)[\'"].*?>/m', $xml_data, $matches)) {
		    return strtoupper($matches[1]);
		}

		return 'UTF-8';

	}

	/**
	 * Error handler
	 * 
	 * @param int $code
	 * @param string $message
	 */
	function handle_error($code, $message = '') {
		if (!$message) {
			$message = $this->error_string($code);
		}
		
		$line = $this->get_current_line_number();
		$col = $this->get_current_column_number();

		if (method_exists($this->handler, $this->error_handler)) {
			call_user_func(array(& $this->handler, $this->start_element_handler), $code, $line, $col, $message);
		} else {
			trigger_error('Error '.$code.' at '.$line.':'.$col.': '.$message, E_USER_WARNING);
		}
	}	
	
}

/**
 * Class wrapping the expat php extension
 */
class ExpatStreamParser extends AbstractStreamParser  {
	
	/**
	 * @var resource
	 */
	var $parser;
	
	/**
	 * @var string
	 */
	var $character_data;
	
	/**
	 * Returns true if the character encoding is supported
	 *
	 * @param string $encoding
	 * @return string
	 */
	function is_supported_encoding($encoding) {
		return in_array($encoding, array("UTF-8", "US-ASCII", "ISO-8859-1"));
	}
	
	/**
	 * Parse the given xml_data string
	 *
	 * @param string $xml_data
	 * @param string $input_encoding  Explicitely set the input encoding
	 * @return bool
	 */	
	function parse($xml_data, $input_encoding = '') {
		
		parent::parse($xml_data, $input_encoding);
		
		// if the encoding wasn't specified, try to detect it
		if (!$input_encoding) {
			$input_encoding = $this->detect_encoding($xml_data);	
		} else {
			$input_encoding = strtoupper($input_encoding);
		}
		
		if (!$this->is_supported_encoding($input_encoding)) {
			trigger_error("$input_encoding encoding is not currently handled");
			return false;
		}
		
		$this->parser = xml_parser_create($input_encoding);

		xml_set_object($this->parser, $this);
		
		// set options
		xml_parser_set_option($this->parser, XML_OPTION_TARGET_ENCODING, $this->output_encoding);
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, '0'); // case folding is evil
		
		// set handlers
		xml_set_element_handler($this->parser, 'start_element', 'end_element');
		xml_set_character_data_handler($this->parser, 'character_data');
		
		$this->xml_data = $xml_data;
		
		if (!xml_parse($this->parser, $xml_data)) {
			$this->handle_error(xml_get_error_code($this->parser));		
		}
		
		xml_parser_free($this->parser);
		
		return true;
		
	}
	
	/**
	 * Returns an error string for a given error code
	 *
	 * @param int $code
	 * @return string
	 */
	function error_string($code) {
		return ucfirst(xml_error_string($code));	
	}
	
	/**
	 * Returns the current line number of the parser
	 *
	 * @return int
	 */
	function get_current_line_number() {
		return 	xml_get_current_line_number($this->parser);
		
	}
	
	/**
	 * Returns the current column number of the parser
	 *
	 * @return int
	 */
	function get_current_column_number() {
		return xml_get_current_column_number($this->parser);
	}
	
	/**
	 * Start element handler
	 * 
	 * @param resource $parser
	 * @param string $name
	 * @param array $attributes
	 */
	function start_element($parser, $name, $attributes) {
		
		// throw an error is an element starts with xml, which is not allowed but
		// not handled by expat
		if (strtolower(substr($name, 0, 3)) == 'xml') {
			$this->handle_error(XML_ERROR_INVALID_TOKEN);
		}
		
		array_unshift($this->stack, $name);
		
		$this->flush_character_data_buffer();
		
		// validate that the passed handler can handle the events
		if (method_exists($this->handler, $this->start_element_handler)) {
			call_user_func(array(& $this->handler, $this->start_element_handler), $name, $attributes);
		}

	}
	
	/**
	 * End element handler
	 *
	 * @param resource $parser
	 * @param string $name
	 */
	function end_element($parser, $name) {
		$last_node = array_shift($this->stack);
		
		// this error should get caught by expat, but we'll add a check just in case...
		if ($name != $last_node) {
			$this->handle_error(XML_ERROR_INVALID_TOKEN);
		}
		
		$this->flush_character_data_buffer();
		
		if (method_exists($this->handler, $this->end_element_handler)) {
			call_user_func(array(& $this->handler, $this->end_element_handler), $name);
		}		

	}
	
	/**
	 * Character data handler
	 *
	 * @param resource $parser
	 * @param string $content
	 */
	function character_data($parser, $content) {
		if ($this->ignore_whitespace && trim($content) == '') {
			return;	
		}
		
		$this->character_data .= $content;
		

	}
	
	function flush_character_data_buffer() {
		if (!$this->character_data) {
			return;
		}
		
		if (method_exists($this->handler, $this->character_data_handler)) {
			call_user_func(array(& $this->handler, $this->character_data_handler), $this->character_data);	
		}			
		
		$this->character_data = '';
		
	}
	
}

?>