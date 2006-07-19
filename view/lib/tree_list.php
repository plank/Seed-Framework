<?php

/**
 * part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package view
 * @subpackage html
 */

/**
 * Class for displaying nested set models in a list
 *
 * @package view
 * @subpackage html
 */
class TreeList {
	
	/**
	 * @var Iterator
	 */
	var $result;
	
	/**
	 * @var Controller
	 */
	var $controller;
	
	/**
	 * @var string
	 */
	var $name_field = 'name';
	
	/**
	 * @var string
	 */
	var $level_field = 'level';
	
	/**
	 * @var string
	 */
	var $id_field = 'id';
	
	/**
	 * @var array
	 */
	var $link_options;
	
	/**
	 * @var string
	 */
	var $class_name = 'tree_list';
	
	/**
	 * @var string
	 */
	var $id = 'tree_list';
	
	/**
	 * Constuctor
	 *
	 * @param Iterator $iterator
	 * @param Controller $controller
	 * @return TreeList
	 */
	function TreeList($iterator, $controller = null) {
		$this->result = $iterator;
		$this->controller = $controller;
		$this->setup();
	}

	function factory($type, $iterator, $controller = null) {
		$className = Inflector::camelize($type).'TreeList';

		if (class_exists($className)) {
			return new $className($iterator, $controller);
		} else {
			return false;
		}		
	}	
	
	function setup() {
		
	}
	
	function generate() {
		
		$return = "";
		
		$this->root_level = false;
		$level = 0;
		
		while($node = $this->result->next()) {
			if (!$this->include_node($node)) {
				continue;	
			}
			
			$node_level = $node->get($this->level_field);
			
			if ($this->root_level === false) {
				$this->root_level = $level = $node_level - 1;	
				
			}
			
			if ($node_level == 0) {
				continue;
			}
			
			if ($node_level > $level) {
				while($node_level > $level) {
					if ($level) {
						$return .= "<ul>\n";	
					} else {
						$return .= "<ul id='$this->id' class='$this->class_name'>\n";							
					}
					$level ++;
				}
				
			} else {
				while ($node_level < $level) {
					$return .= "</li></ul>";	
					$level --;
				}
				
				$return .= "</li>\n";
					
			}
			
			$return .= str_repeat("\t", $level).$this->generate_node($node);
			
		}
		
		while ($level > $this->root_level) {
			$return .= "</li></ul>\n";	
			$level --;
		}
		
		return $return;
		
	}
	
	/**
	 * This method serves as a hook for subclasses to selectively skip certain nodes
	 *
	 * @param Model $node
	 * @return bool
	 */
	function include_node($node) {
		return true;	
		
	}
	
	function generate_node($node) {
		$options = $this->link_options;
		$options['id'] = $node->get_id();
	
		$return = "<li><a href='".$this->controller->url_for($options)."'>".$this->escape($node->get($this->name_field))."</a>";
		
		return $return;
	}
	
	function escape($string) {
		return htmlentities($string, ENT_QUOTES, 'UTF-8');
		
	}
}



?>