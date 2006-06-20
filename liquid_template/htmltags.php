<?php

class TableRowLiquidTag extends LiquidBlock  {
	
	var $variable_name;
	
	var $collection_name;
	
	var $attributes;
	
	function TableRowLiquidTag($markup, & $tokens, & $file_system) {
		parent::LiquidTag($markup, $tokens, $file_system);
		
		$syntax = new Regexp("/(\w+)\s+in\s+(".ALLOWED_VARIABLE_CHARS."+)/");
		$tag_attributes = new Regexp(TAG_ATTRIBUTES);
		
		if ($syntax->match($markup)) {
			$this->variable_name = $syntax->matches[1];
			$this->collection_name = $syntax->matches[2];		
			$this->attributes = array();
			
			$attributes = $tag_attributes->scan($markup);
			
			foreach($attributes as $attribute) {
				$this->attributes[$attribute[0]] = $attribute[1];
				
			}
			
		} else {
			trigger_error("Syntax Error in 'table_row loop' - Valid syntax: table_row [item] in [collection] cols=3", E_USER_ERROR);
			
		}
		
	}
	
	/**
	 * Renders the current node
	 *
	 * @param LiquidContext $context
	 * @return string
	 */
	function render(& $context) {
		$collection = $context->get($this->collection_name);
		
		if (!is_array($collection)) {
			die(debug('not array', $collection));
			
		}
		
		// discard keys
		$collection = array_values($collection);
		
		if (isset($this->attributes['limit']) || isset($this->attributes['offset'])) {
			$limit = $context->get($this->attributes['limit']);
			$offset = $context->get($this->attributes['offset']);
			$collection = array_slice($collection, $offset, $limit);
			
		}
		
		$length = count($collection);
		
		$cols = $context->get($this->attributes['cols']);
		
		$row = 1;
		$col = 0;
		
		$result = "<tr class=\"row1\">\n";
		
		$context->push();
		
		foreach($collection as $index => $item) {
			$context->set($this->variable_name, $item);			
			$context->set('tablerowloop', array(
				'length' 	=> $length,
				'index' 	=> $index + 1,
				'index0' 	=> $index,
				'rindex'	=> $length - $index,
				'rindex0'	=> $length - $index - 1,
				'first'		=> (int)($index == 0),
				'last'		=> (int)($index == $length - 1)
			
			));
			
			$result .= "<td class=\"col".(++ $col)."\">" . $this->render_all($this->nodelist, $context) . "</td>";
			
			if ($col == $cols && ! ($index == $length - 1)) {
				$col = 0;
				$result .= "</tr>\n<tr class=\"row".(++ $row)."\">";
			}
			
		}
		
		$context->pop();
		
		$result .= "</tr>\n";
		
		return $result;
	}
	
	
}


?>