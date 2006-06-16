<?php


class AssignLiquidTag extends LiquidTag {
	
	function AssignLiquidTag($markup, $tokens) {
		$syntax = '/(\w+)\s*=\s*('.ALLOWED_VARIABLE_CHARS.'+)/';
		
		
		
		
	}
	
	
}

class CommentLiquidTag extends LiquidBlock {
	
	function render($context) {
		return '';
		
	}
	
}

class ForLiquidTag extends LiquidBlock {
	
	var $variable_name;
	var $collection_name;
	var $name;
	var $attributes;
	
	function ForLiquidTag($markup, & $tokens) {
		parent::LiquidTag($markup, $tokens);
		
		$syntax = '/(\w+)\s+in\s+('.ALLOWED_VARIABLE_CHARS.'+)/';
		
		if (preg_match($syntax, $markup, $matches)) {
			$this->variable_name = $matches[1];
			$this->collection_name = $matches[2];
			$this->name = $matches[1].'-'.$matches[2];
			$this->attributes = array();
		
			$attribute_regexp = new Regexp(TAG_ATTRIBUTES);
			
			$matches = $attribute_regexp->scan($markup);
			
			foreach ($matches as $match) {
				$this->attributes[$match[0]] = $match[1];
				
			}

			// test when we have matches
		} else {
			trigger_error("Syntax Error in 'for loop' - Valid syntax: for [item] in [collection]", E_USER_ERROR);
			
			
		}
		
	}
	
	/**
	 * Renders the tag
	 *
	 * @param LiquidContext $context
	 */
	
	function render($context) {
		if (!isset($context->registers['for'])) {
			$context->registers['for'] = array();
			
		}
		
		$collection = $context->get($this->collection_name);
		
		if (is_null($collection) || count($collection) == 0) {
			return '';
		}
		
		$range = array(0, count($collection));
		
		if (isset($this->attributes['limit']) || isset($this->attributes['offset'])) {
			
			$offset = 0;
			
			if (isset($this->attributes['offset'])) {
				if ($this->attributes['offset'] == 'continue') {
					$offset = $context->registers['for'][$this->name];
				} else {
					$offset = $context->get($this->attributes['offset']);
				}
			} 
			
			$limit = $context->get($this->attributes['limit']);
			
			$range_end = $limit ? $limit : count($collection) - $offset;
			
			$range = array($offset, $range_end);
			
			$context->registers['for'][$this->name] = $range_end;
		}
		
		$result = '';
		
		$segment = array_slice($collection, $range[0], $range[1]);
		
		if (!count($segment)) {
			return null;
		}
		
		$context->push();
		
		$length = count($segment);
		
		foreach($segment as $index => $item) {
			$context->set($this->variable_name, $item);
			$context->set('forloop', array(
				'name'		=> $this->name,
				'length' 	=> $length,
				'index' 	=> $index + 1,
				'index0' 	=> $index,
				'rindex'	=> $length - $index,
				'rindex0'	=> $length - $index - 1,
				'first'		=> (int)($index == 0),
				'last'		=> (int)($index == $length - 1)
			
			));
			
			$result .= $this->render_all($this->nodelist, $context);
			
		}
		
		$context->pop();
		
		return $result;
		
	}
	
}

?>