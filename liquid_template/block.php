<?php

require_once('tag.php');

class LiquidBlock extends LiquidTag {
	
	/**
	 * @var array
	 */
	var $nodelist;
	
	function parse(& $tokens) {
		
		$this->nodelist = array();
		
		if (!is_array($tokens)) {
			return;
		}
		
		while($token = array_shift($tokens)) {
			
			if (preg_match('/^'.TAG_START.'/', $token)) {
				
				if (preg_match('/^'.TAG_START.'\s*(\w+)\s*(.*)?'.TAG_END.'$/', $token, $matches)) {
					
					// if we found the proper block delimitor just end parsing here and let the outer block proceed 
					if ($matches[1] == $this->block_delimiter()) {
						$this->end_tag();
						return;
						
					}

					// search for a defined class of the right name, instead of searching in an array				
					$tag_name = $matches[1].'LiquidTag';
					
					// fetch the tag from registered blocks
					if (class_exists($tag_name)) {
						$this->nodelist[] = new $tag_name($matches[2], $tokens, $this->file_system);
						
					} else {
						$this->unknown_tag($matches[1], $matches[2], $tokens);	
						
					}
					

				} else {
					trigger_error("Tag $token was not properly terminated", E_USER_ERROR);
					
				}
								
			} elseif (preg_match('/^'.VARIABLE_START.'/', $token, $matches)) {
				$this->nodelist[] = $this->create_variable($token);
				
				
			} elseif ($token != '') {
				$this->nodelist[] = $token;
					
				
			}
			
			
			
		}
		
		
	
	}
	
	function end_tag() {
		
		
		
	}
	
	function unknown_tag($tag, $params, & $tokens) {
//		switch ($tag) {
			trigger_error("Unkown tag $tag", E_USER_ERROR);
			
			
			
		//}
		
	}
	
	/**
	 * Returns the string that delimits the end of the block
	 *
	 * @return string
	 */
	
	function block_delimiter() {
		return "end".$this->block_name();
		
		
	}
	
	/**
	 * Returns the name of the block
	 *
	 * @return string
	 */
	function block_name() {
		return str_replace('liquidtag', '', strtolower(class_name($this)));
		
	}
	
	function create_variable($token) {
		
		if (preg_match('/^'.VARIABLE_START.'(.*)'.VARIABLE_END.'$/', $token, $matches)) {
			return new LiquidVariable($matches[1]);			
		} else {
			
			trigger_error("Variable $token was not properly terminated");
		}
		
	}
	
	/**
	 * Enter description here...
	 *
	 * @param LiquiContext $context
	 * @return unknown
	 */
	
	function render(& $context) {
		
		return $this->render_all($this->nodelist, $context);
		
	}
	
	function assert_missing_delimitation() {
		trigger_error($this->block_name()." tag was never closed", E_USER_ERROR);
		
	}
	
	function render_all($list, & $context) {
		$result = '';
		
		if (!is_array($list)) {
			trigger_error('Parameter $list is not an array', E_USER_ERROR);
			return;
		}
		
		foreach($list as $token) {
			if (is_object($token) && method_exists($token, 'render')) {

				$result .= $token->render($context);
				
			} else {
				$result .= $token;
				
				
			}
			
		}
		
		return $result;
	}
	
}


?>