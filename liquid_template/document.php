<?php


class LiquidDocument extends LiquidBlock {
	
	function LiquidDocument(& $tokens, & $file_system) {
		$this->file_system = $file_system;
		$this->parse($tokens);
		
	}
	
	
	function block_delimiter() {
		return '';
		
	}
	
	function assert_missing_delimitation() {
		
		
	}
	
}


?>