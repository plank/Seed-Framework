<?php

class LiquidTag {
	
	var $markup;
	
	function LiquidTag($markup, & $tokens) {
		$this->markup = $markup;
		return $this->parse($tokens);
	}
	
	function parse($tokens) {
		
		
	}
	
	function name() {
		return strtolower(this::class_name());
		
	}
	
	function render($context) {
		return '';
		
	}
	
}

?>