<?php 

class MoneyLiquidFilter extends LiquidFilter {
	
	function filter($value) {
		return sprintf(' %d$ ', $value);
		
	}
	
}

class Money_with_underscoreLiquidFilter extends LiquidFilter {
	
	function filter($value) {
		return sprintf(' %d$ ', $value);
		
	}
	
}

class Canadian_MoneyLiquidFilter extends LiquidFilter {
	
	function filter($value) {
		return sprintf(' %d$ CAD ', $value);
		
	}
	
}


class LiquidFiltersTester extends UnitTestCase {
	
	/**
	 * The current context
	 *
	 * @var LiquidContext
	 */
	
	var $context;
	
	function setup() {
		$this->context = new LiquidContext();
		
	}
	
	function test_local_filter() {
		$var = new LiquidVariable('var | money');
		$this->context->set('var', 1000);
		$this->context->add_filters(new MoneyLiquidFilter());
		$this->assertIdentical(' 1000$ ', $var->render($this->context));
		
	}
	
	function test_underscore_in_filter_name() {
		$var = new LiquidVariable('var | money_with_underscore ');
		$this->context->set('var', 1000);
		$this->context->add_filters(new Money_with_underscoreLiquidFilter());
		$this->assertIdentical(' 1000$ ', $var->render($this->context));		
		
	}
	/*
	function test_second_filter_overwrites_first() {
		$var = new LiquidVariable('var | money ');
		$this->context->set('var', 1000);
		$this->context->add_filters(new MoneyLiquidFilter(), 'money');
		$this->context->add_filters(new Canadian_MoneyLiquidFilter(), 'money');
		$this->assertIdentical(' 1000$ CAD ', $var->render($this->context));		
		
	}
	*/
	
	function test_size() {
		$var = new LiquidVariable("var | size");
		$this->context->set('var', 1000);
		//context.add_filters(MoneyFilter)
		$this->assertEqual(4, $var->render($this->context));		
	}
	
	function test_join() {
		$var = new LiquidVariable("var | join");
	
		$this->context->set('var', array(1, 2, 3, 4));
		$this->assertEqual("1 2 3 4", $var->render($this->context));		
		
	}
	
}


class LiquidFiltersInTemplate extends UnitTestCase {
	
	// the rest of this test needs to be implemented when/if we get global filters working
	function test_local_global() {
		$template = new LiquidTemplate;
		$template->parse('{{1000 | money}}');
		$output = $template->render(null, new MoneyLiquidFilter());
		$this->assertIdentical(' 1000$ ', $output);	
	}
	
	
}

?>