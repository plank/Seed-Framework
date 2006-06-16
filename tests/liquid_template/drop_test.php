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

	function text() {
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
		$tpl->render(array('product' => new ProductDrop));
		$this->assertNoErrors();
		
		
	    $tpl = LiquidTemplate::parse( ' {{ product.top_sales }} '  );
	    $tpl->render(array('product' => new ProductDrop));
	    $this->assertError('worked');

	}

	function test_text_drop() {
		
		$tpl = LiquidTemplate::parse(' {{ product.texts.text }} ');
		$output = $tpl->render(array('product' => new ProductDrop()));	
		$this->assertEqual(' text1 ', $output);

		$tpl = LiquidTemplate::parse(' {{ product.catchall.unknown }} ');
		$output = $tpl->render(array('product' => new ProductDrop()));	
		$this->assertEqual(' method: unknown ', $output);		
		
	}
	
	// this test needs standard tags to pass
	/*
	function test_text_array_drop() {
		$tpl = LiquidTemplate::parse('{% for text in product.texts.array %} {{text}} {% endfor %}');
		$output = $tpl->render(array('product' => new ProductDrop()));
		//$this->dump($output);
		
	}
	*/
	
	function test_context_drop() {
		$tpl = LiquidTemplate::parse(' {{ context.bar }} ');
		$output = $tpl->render(array('context' => new ContextDrop(), 'bar'=>'carrot'));	
		$this->assertEqual(' carrot ', $output);		
		
	}
	
	function test_nested_context_drop() {
		$tpl = LiquidTemplate::parse(' {{ product.context.foo }} ');
		$output = $tpl->render(array('product' => new ProductDrop(), 'foo'=>'monkey'));	
		$this->assertEqual(' monkey ', $output);		

	}
	
	// skip this test as php4 doesn't support protected vars
	/*
	function test_protected() {
		$tpl = LiquidTemplate::parse(' {{ product.callmenot }} ');
		$output = $tpl->render(array('product' => new ProductDrop()));	
		$this->assertEqual('  ', $output);			
		
	}
	
	*/
	
}

?>