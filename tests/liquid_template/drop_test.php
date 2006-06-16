<?php

class ContextDrop extends LiquidDrop {
	function before_method($method) {
		return $this->context->get($method);
		
	}
	
}

class TextDrop extends LiquidDrop {
	function get_array() {
		return array('text1', 'text2');
		
	}

	function get_text() {
		return 'text1';
		
	}
}

class CatchallDrop extends LiquidDrop {
	function before_method($method) {
		return 'method: '.$method;
		
	}
	
}

class ProductDrop extends LiquidDrop {
	function top_sales() {
		trigger_error('worked', E_USER_ERROR);
		
	}
	
	function texts() {
		return new TextDrop();
		
	}
	
	function catchall() {
		return new CatchallDrop();
		
	}
	
	function context() {
		return new ContextDrop();
	}
	
	function callmenot() {
		return "protected";
		
	}
	
}

class LiquidDropTester extends UnitTestCase {
	
	function test_product_drop() {
		
		$tpl = LiquidTemplate::parse('  ');
		$this->assertNoErrors($tpl->render(array('product' => new ProductDrop)));
		
		
	    $tpl = LiquidTemplate::parse( ' {{ product.top_sales }} '  );
	    $this->assertError(($tpl->render(array('product' => new ProductDrop))));

	}
	
}

?>