<?php

class LiquidTag {
	
	var $markup;
	
	var $file_system;
	
	function LiquidTag($markup, & $tokens, & $file_system) {
		$this->markup = $markup;
		$this->file_system = $file_system;
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