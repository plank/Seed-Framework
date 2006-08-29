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
 * Class for displaying nested set models using the treeview.net dhtml control
 *
 * @package view
 * @subpackage html
 */
class TreeView {
	
	/**
	 * @var SeedIterator
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
	var $class_name = 'tree_view';
	
	/**
	 * @var string
	 */
	var $id = 'tree_view';
	
	/**
	 * @var string
	 */
	var $root_label = 'Root';
	
	/**
	 * Constuctor
	 *
	 * @param SeedIterator $iterator
	 * @param Controller $controller
	 * @return TreeList
	 */
	function TreeView($iterator, $controller = null) {
		$this->result = $iterator;
		$this->controller = $controller;
		$this->setup();
	}

	function factory($type, $iterator, $controller = null) {
		$className = Inflector::camelize($type).'TreeView';

		if (class_exists($className)) {
			return new $className($iterator, $controller);
		} else {
			return false;
		}		
	}	
	
	function setup() {
		
	}
	
	function generate() {
		$return  = "<div id='".$this->id."' class='".$this->class_name."'>\n";
		$return .= "<script type='text/javascript'>\n";
		
		$return .= "HIGHLIGHT = 1\n";
		$return .= "USETEXTLINKS = 1\n";
		$return .= "STARTALLOPEN = 0\n";
		$return .= "USEFRAMES = 0\n";
		$return .= "USEICONS = 1\n";
		$return .= "WRAPTEXT = 1\n";
		$return .= "PRESERVESTATE = 1\n";
		$return .= "HIGHLIGHT_BG = '#c9131c'\n";
		$return .= "ICONPATH = '_images/tree/'\n\n";
		
		$node = $this->result->next();

		$options = $this->link_options;
		$options['id'] = $node->get_id();
		$link = $this->controller->url_for($options);		
		
		$return .= "\taux0 = gFld(\"<a href='$link' class='root'>".$this->root_label."<a>\", \"\")\n";
		$return .= "\taux0.xID = \"0\"\n";
		
		while($node = $this->result->next()) {
			$level = $node->get($this->level_field);
			$name = $node->get($this->name_field);
			$options = $this->link_options;
			$options['id'] = $node->get_id();
			$link = $this->controller->url_for($options);
			
			if ($node->has_children()) {
				$return .= str_repeat("\t", $level + 1)."aux$level = insFld(aux".($level - 1).", gFld(\"".$name."\", \"".$link."\"))\n";
				
			} else {
				$return .= str_repeat("\t", $level + 1)."aux$level = insDoc(aux".($level - 1).", gLnk(\"S\", \"".$name."\", \"".$link."\"))\n";
			}
			
			$return .= str_repeat("\t", $level + 1)."aux$level.xID = \"".$node->get_id()."\"\n";
			
		}
		
		$return .= "\nfoldersTree = aux0\n\n";
		
		$return .= "initializeDocument()</script>\n";
		$return .= "<noscript>A tree for site navigation will open here if you enable JavaScript in your browser.</noscript>\n";
		$return .= "</div>\n";
		return $return;
		
	}
	
	function escape($string) {
		return htmlentities($string, ENT_QUOTES, 'UTF-8');
		
	}
}



?>