<?php

/**
 * Class for parsing XML Files
 */
class XmlParser {

	/**
	 * @var XmlNode
	 */
	var $document;

	/**
	 * @var array
	 */
	var $_stack;
	
	/**
	 * Parses an xml data string and returns the resulting document
	 *
	 * @param string $xml_data
	 * @return XmlNode
	 */
	function parse($xml_data) {
		// reset parser
		$this->_stack = array();
		$this->document = null;
		
		$parser = & new ExpatStreamParser($this);
		
		$parser->parse($xml_data);
		
		return $this->document;
		
	}
	
	/**
	 * Handler for element starts
	 *
	 * @param string $name
	 * @param array $attributes
	 */
	function start_element($name, $attributes) {
		
		$node = & new XmlNode($name, $attributes);	
		
		if (!$this->document) {
			// if we don't yet have a document, this is the root node
			$this->document = & $node;
			
		} else {
			// add it to the parent
			$this->_stack[0]->add_child($node);	
			
		}
		
		@array_unshift($this->_stack, & $node);
		
	}
	
	/**
	 * Handler for element ends
	 *
	 * @param string $name
	 */
	function end_element($name) {
		// pop it off the stack
		array_shift($this->_stack);
	}
	
	/**
	 * Handler for character data
	 *
	 * @param string $content
	 */
	function character_data($content) {
		$node = & $this->_stack[0];
		
		//$node->set_data($node->get_data($content).$content);
		$node->set_data($content);
	}
	
}

/**
 * Class for XmlNodes
 *
 * @ Attributes begin with xml to avoid name conflicts, but should be considered private
 */
class XmlNode {
	
	/**
	 * @var array
	 */
	var $xml_attributes;
	
	/**
	 * @var string
	 */
	var $xml_name;
	
	/**
	 * @var string
	 */
	var $xml_data;
	
	/**
	 * @var array
	 */
	var $xml_children;
	
	/**
	 * Constructor
	 *
	 * @param string $name
	 * @param array $attributes
	 * @return XmlNode
	 */
	function XmlNode($name, $attributes = null) {
		$this->xml_name = $name;
		
		if (isset($attributes)) {
			$this->xml_attributes = $attributes;
			
			foreach($attributes as $key => $value) {
				$this->$key = $value;	
			}
			
		} else {
			$this->xml_attributes = array();	
		}
		
		$this->xml_children = array();
	}

	/**
	 * Returns the name of the node
	 *
	 * @return string
	 */
	function get_name() {
		return $this->xml_name;	
		
	}
	
	/**
	 * Sets the data for the node
	 *
	 * @param string $data
	 */
	function set_data($data = '') {
		$this->xml_data = $data;
	}
	
	/**
	 * Returns the data for the node
	 *
	 * @return string
	 */
	function get_data() {
		return $this->xml_data;	
	}
	
	/**
	 * Returns the array of attributes for the node
	 *
	 * @return array
	 */
	function get_attributes() {
		return $this->xml_attributes;	
	}
	
	/**
	 * Returns a given attribute
	 *
	 * @param string $key
	 * @return string
	 */
	function get_attribute($key) {
		if (isset($this->xml_attributes[$key])) {
			return $this->xml_attributes[$key];
		} else {
			return null;	
		}
	}
	
	/**
	 * Sets an attributes
	 *
	 * @param string $key
	 * @param string $value
	 */
	function set_attribute($key, $value) {
		$this->xml_attributes[$key] = $value;	
	}
	
	/**
	 * Returns an array containing all the children
	 *
	 * @return array
	 */
	function get_children() {
		return $this->xml_children;	
	}
	
	/**
	 * Returns the node at a given offset
	 *
	 * @param int $offset
	 * @return XmlNode
	 */
	function get_child($offset) {
		if (isset($this->xml_children[$offset])) {
			return $this->xml_children[$offset];
		} else {
			return null;	
		}
	}
	
	/**
	 * Adds a given xml_node as a child to the current node
	 *
	 * @param XmlNode $node
	 */
	function add_child(& $node) {
		
		if (!isset($this->{$node->get_name()})) {
			$this->{$node->get_name()} = array();	
		}
		
		$this->{$node->get_name()}[] = & $node;
		
		$this->xml_children[] = & $node;
	}	
	
}

?>