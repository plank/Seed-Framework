<?php

class AssignLiquidTag extends LiquidTag {
	
	function AssignLiquidTag($markup, & $tokens, & $file_system) {
		$syntax = '/(\w+)\s*=\s*('.ALLOWED_VARIABLE_CHARS.'+)/';
		
		if (preg_match($syntax, $markup, $matches)) {
			$this->to = $matches[1];
			$this->from = $matches[2];
			
		} else {
			trigger_error("Syntax Error in 'assign' - Valid syntax: assign [var] = [source]");
			
		}

	}

	function render(& $context) {
		$context->set($this->to, $context->get($this->from));
		
	}
	
}

class CaptureLiquidTag extends LiquidBlock {
	
	/**
	 * The variable to assign to
	 *
	 * @var string
	 */
	var $to;
	
	function CaptureLiquidTag($markup, & $tokens, & $file_system) {
		$syntax = '/(\w+)/';
		
		if (preg_match($syntax, $markup, $matches)) {
			$this->to = $matches[1];
			parent::LiquidTag($markup, $tokens, $file_system);
			
		} else {
			trigger_error("Syntax Error in 'capture' - Valid syntax: assign [var] = [source]");
			
		}

	}

	function render(& $context) {
		$output = parent::render($context);
		
		$context->set($this->to, $output);
		
	}	
	
}

class CommentLiquidTag extends LiquidBlock {
	
	function render($context) {
		return '';
		
	}
	
}

class CycleLiquidTag extends LiquidTag {
	
	/**
	 * Constructor
	 *
	 * @param string $markup
	 * @param array $tokens
	 * @return CycleLiquidTag
	 */
	function CycleLiquidTag($markup, & $tokens, & $file_system) {
		$simple_syntax = new Regexp("/".QUOTED_FRAGMENT."/");
		$named_syntax = new Regexp("/(".QUOTED_FRAGMENT.")\s*\:\s*(.*)/");
		
		if ($named_syntax->match($markup)) {
			$this->variables = $this->variables_from_string($named_syntax->matches[2]);
			$this->name = $named_syntax->matches[1];
			
		} elseif ($simple_syntax->match($markup)) {
			$this->variables = $this->variables_from_string($markup);
			$this->name = "'".implode($this->variables)."'";
			
		} else {
			trigger_error("Syntax Error in 'cycle' - Valid syntax: cycle [name :] var [, var2, var3 ...]");
			
		}
		

	}

	/**
	 * 
	 * @var LiquidContext $context
	 * @return string
	 */
	function render(& $context) {
		
		$context->push();
		
		$key = $context->get($this->name);
		
		if (isset($context->registers['cycle'][$key])) {
			$iteration = $context->registers['cycle'][$key];
		} else {
			$iteration = 0;
		}
		
		$result = $context->get($this->variables[$iteration]);
		
		$iteration += 1;

		if ($iteration >= count($this->variables)) {
			$iteration = 0;
		}
		
		$context->registers['cycle'][$key] = $iteration;
		
		$context->pop();
		
		return $result;
	}
	
	
	function variables_from_string($markup) {
		$regexp = new Regexp('/\s*('.QUOTED_FRAGMENT.')\s*/');
		$parts = explode(',', $markup);
		$result = array();
		
		foreach($parts as $part) {
			$regexp->match($part);
			
			if ($regexp->matches[1]) {
				$result[] = $regexp->matches[1];
			}
			
		}
		
		return $result;
		
	}
	
}

class ForLiquidTag extends LiquidBlock {
	
	var $variable_name;
	var $collection_name;
	var $name;
	var $attributes;
	
	function ForLiquidTag($markup, & $tokens, & $file_system) {
		parent::LiquidTag($markup, $tokens, $file_system);
		
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
	
	function render(& $context) {
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
			
			$context->registers['for'][$this->name] = $range_end + $offset;
			
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

class LiquidDecisionBlock extends LiquidBlock {
	
	function string_value($value) {
				
		if (is_object($value)) {
			if (method_exists($value, 'to_string')) {
				$value = $value->to_string();
			} else {
				trigger_error("Cannot convert $value to string", E_USER_WARNING);
				return false;
			}
			
		}		

		// a value of 'array' should always return true
		if (is_array($value)) {
			return true;
			
		}
		
		return $value;
	}
	
	function equal_variables($left, $right, & $context) {
		$left = $this->string_value($context->get($left));
		$right = $this->string_value($context->get($right));

		return ($left == $right);	
		
	}
	
	function interpret_condition($left, $right, $op = null, & $context) {
		
		if (is_null($op)) {
			$value = $this->string_value($context->get($left));
			return $value;
			
		}

		// values of 'empty' have a special meaning in array comparisons
		if ($right == 'empty' && is_array($context->get($left))) {
			$left = count($context->get($left));
			$right = 0;
			
		} elseif ($left == 'empty' && is_array($context->get($right))) {
			$right = count($context->get($right));
			$left = 0;
			
		} else {
			$left = $context->get($left);
			$right = $context->get($right);

			$left = $this->string_value($left);
			$right = $this->string_value($right);
		}
		
		// special rules for null values
		if (is_null($left) || is_null($right)) {
			// null == null returns true
			if ($op == '==') {
				return true;
			}
			
			// null != anything other than null return true
			if ($op == '!=' && (!is_null($left) || !is_null($right))) {
				return true;
			}
			
			// everything else, return false;
			return false;
		}
		
		// regular rules
		switch ($op) {
			case '==':
				return ($left == $right);
			
			case '!=':
				return ($left != $right);
				
			case '>':
				return ($left > $right);

			case '<':
				return ($left < $right);

			case '>=':
				return ($left >= $right);

			case '<=':
				return ($left <= $right);

			default:
				trigger_error("Error in tag '".$this->name()."' - Unknown operator $op");
				return null;
							
		}
		
	}
	
}

class IfLiquidTag extends LiquidDecisionBlock {
	
	var $nodelist_true;
	var $nodelist_false;
	
	function IfLiquidTag($markup, & $tokens, & $file_system) {
		$regex = new Regexp('/('.QUOTED_FRAGMENT.')\s*([=!<>]+)?\s*('.QUOTED_FRAGMENT.')?/');
		
		$this->nodelist_true = & $this->nodelist;
		$this->nodelist = array();
		
		$this->nodelist_false = array();
		
		parent::LiquidTag($markup, $tokens, $file_system);
		
		if ($regex->match($markup)) {
			$this->left = $regex->matches[1];
			$this->operator = $regex->matches[2];
			$this->right = $regex->matches[3];
			
		} else {
			trigger_error("Syntax Error in tag 'if' - Valid syntax: if [condition]", E_USER_ERROR);
			
		}
		
	}
	
	function unknown_tag($tag, $params, $tokens) {
		if ($tag == 'else') {
			$this->nodelist = & $this->nodelist_false;
			$this->nodelist_false = array();
		} else {
			parent::unknown_tag($tag, $params, $tokens);
			
		}
		
	}
	
	/**
	 * Render the tag
	 *
	 * @param LiquidContext $context
	 */
	function render(& $context) {
		$context->push();
		
		if ($this->interpret_condition($this->left, $this->right, $this->operator, $context)) {
			$result = $this->render_all($this->nodelist_true, $context);
		} else {
			$result = $this->render_all($this->nodelist_false, $context);
			
		}
		
		$context->pop();
		
		return $result;
	}
	
}

class CaseLiquidTag extends LiquidDecisionBlock {
	
	function CaseLiquidTag($markup, & $tokens, & $file_system) {
		$this->nodelists = array();
		$this->else_nodelist = array();
		
		parent::LiquidTag($markup, $tokens, $file_system);
		
		$syntax = '/'.QUOTED_FRAGMENT.'/';
		
		if (preg_match($syntax, $markup, $matches)) {
			$this->left = $matches[0];
			
		} else {
			trigger_error("Syntax Error in tag 'case' - Valid syntax: case [condition]", E_USER_ERROR);
			
		}

		
	}
	
	function end_tag() {
		$this->push_nodelist();
		
	}
	
	function unknown_tag($tag, $params, & $tokens) {
		$when_syntax = '/'.QUOTED_FRAGMENT.'/';
		
		switch ($tag) {
		case 'when':
			if (preg_match($when_syntax, $params, $matches)) {
				
				$this->push_nodelist();
				$this->right = $matches[0];
				$this->nodelist = array();
				
			} else {
				trigger_error("Syntax Error in tag 'case' - Valid when condition: when [condition]", E_USER_ERROR);
				
			}
			break;
			
		case 'else':
			$this->push_nodelist();
			$this->right = null;
			$this->else_nodelist = & $this->nodelist;
			$this->nodelist = array();
			break;
		
		default:
			parent::unknown_tag($tag, $params, $tokens);
			
			
		}
		
	}
	
	function push_nodelist() {
		
		if (!is_null($this->right)) {
			$this->nodelists[] = array($this->right, $this->nodelist);
			
		} 
		
	}
	
	/**
	 * Enter description here...
	 *
	 * @param LiquidContext $context
	 */
	
	function render(& $context) {
		
		$output = ''; // array();
		$run_else_block = true;
		
		foreach($this->nodelists as $data) {
			list($right, $nodelist) = $data;
			
			if ($this->equal_variables($this->left, $right, $context)) {
				$run_else_block = false;
				
				$context->push();
				$output .= $this->render_all($nodelist, $context);
				$context->pop();
				
			}
		}

		if ($run_else_block) {

			
			$context->push();
			$output .= $this->render_all($this->else_nodelist, $context);
			$context->pop();			
			
		}
	
		return $output; // implode('', $output);
		
	}
	
}

class IncludeLiquidTag extends LiquidTag {
	
	var $template_name;
	
	var $attributes;
	
	var $collection;
	
	var $variable;
	
	/**
	 * Enter description here...
	 *
	 * @var LiquidDocument
	 */
	var $document;
	
	function IncludeLiquidTag($markup, & $tokens, & $file_system) {
		$regex = new Regexp('/("[^"]+"|\'[^\']+\')(\s+(with|for)\s+('.QUOTED_FRAGMENT.'+))?/');
							
		$attributes_regex = new Regexp(TAG_ATTRIBUTES);
		
		if ($regex->match($markup)) {
			
			$this->template_name = substr($regex->matches[1], 1, strlen($regex->matches[1]) - 2);
			
			if (isset($regex->matches[1])) {
				$this->collection = ($regex->matches[3] == "for");
				
				
				$this->variable = $regex->matches[4];

			}
			
			$this->attributes = array();
			
			$matches = $attributes_regex->scan($markup);
				
			foreach ($matches as $match) {
				$this->attributes[$match[0]] = $match[1];
				
			}
		} else {
			trigger_error("Error in tag 'include' - Valid syntax: include '[template]' (with|for) [object|collection]", E_USER_ERROR);
			
		}
		
		parent::LiquidTag($markup, $tokens, $file_system);
		
	}
	
	function parse($tokens) {
		if (!isset($this->file_system)) {
			trigger_error("No file system", E_USER_ERROR);
		} 
		
		$source = $this->file_system->read_template_file($this->template_name);
		$tokens = LiquidTemplate::tokenize($source);
		$this->document = new LiquidDocument($tokens, $this->file_system);
		
	}
	
	/**
	 * Renders the node
	 *
	 * @param LiquidContext $context
	 */
	function render(& $context) {
		$result = '';
		$variable = $context->get($this->variable);
		
		$context->push();
		
		foreach($this->attributes as $key => $value) {
			$context->set($key, $context->get($value));
			
		}
		
		if ($this->collection) {
			
			foreach($variable as $item) {
				$context->set($this->template_name, $item);
				$result .= $this->document->render($context);
			}
			
		} else {
			if (!is_null($this->variable)) {
				$context->set($this->template_name, $variable);
				
			}
			
			$result .= $this->document->render($context);
			
		}
		
		
		$context->pop();
		
		return $result;
		
		
	}
	
}
?>