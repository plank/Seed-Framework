<?php 
define('FILTER_SEPERATOR', '\|');
define('ARGUMENT_SPERATOR', ',');
define('FILTER_ARGUMENT_SEPERATOR', ':');
define('VARIABLE_ATTRIBUTE_SEPERATOR', '.');
define('TAG_START', '{%');
define('TAG_END', '%}');
define('VARIABLE_START', '{{');
define('VARIABLE_END', '}}');
define('ALLOWED_VARIABLE_CHARS', '[a-zA-Z_.-]');
define('QUOTED_FRAGMENT', '"[^"]+"|\'[^\']+\'|[^\s,|]+');
define('TAG_ATTRIBUTES', '/(\w+)\s*\:\s*('.QUOTED_FRAGMENT.')/');
define('TOKENIZATION_REGEXP', '/('.TAG_START.'.*?'.TAG_END.'|'.VARIABLE_START.'.*?'.VARIABLE_END.')/');



class LiquidTemplate {
	
	/**
	 * The root of the node tree
	 *
	 * @var LiquidDocument
	 */
	var $root;
	
	/**
	 * The file system to use for includes
	 *
	 * @var LiquidBlankFileSystem
	 */
	var $file_system;
	
	/**
	 * Constructor
	 *
	 * @return LiquidTemplate
	 */
	function LiquidTemplate() {
		$this->file_system = new LiquidBlankFileSystem();
	}
	
	function register_tag($name) {
		$this->tags[$name] = $name;
		
	}
	
	function tokenize($source) {
		if (!$source) {
			return array();
			
		}
		
		$tokens = preg_split(TOKENIZATION_REGEXP, $source, null, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

		return $tokens;
		
	}
	
	function parse($source) {
		$this->root = new LiquidDocument(LiquidTemplate::tokenize($source), $this->file_system);	
		
	}
	
	function render($assigns = null, $filters = null, $registers = null) {
		if (is_null($assigns)) {
			$assigns = array();
		}

		$context = new LiquidContext($assigns, $options['registers']);
		
		if (is_a($filters, 'LiquidFilter')) {
			$filters = array($filters);
			
		}
		
		if (is_array($filters)) {
			foreach ($filters as $filter) {
				$context->add_filters($filter);
				
			}
		
		}
		
		return $this->root->render($context);
		
	}
	
}

?>