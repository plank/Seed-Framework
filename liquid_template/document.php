<?php


class LiquidDocument extends LiquidBlock {
	
	function LiquidDocument($tokens, $tags) {
		$this->tags = $tags;
		$this->parse($tokens);
		
	}
	
	
	function block_delimiter() {
		return '';
		
	}
	
	function assert_missing_delimitation() {
		
		
	}
	
}


?>