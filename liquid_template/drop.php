<?php

/** 
 * A drop in liquid is a class which allows you to to export DOM like things to liquid
 * Methods of drops are callable. 
 * The main use for liquid drops is the implement lazy loaded objects. 
 * If you would like to make data available to the web designers which you don't want loaded unless needed then 
 * a drop is a great way to do that
 *
 * Example:
 *
 * class ProductDrop extends LiquidDrop {
 *   function top_sales() {
 *      Products::find('all', array('order' => 'sales', 'limit' => 10 ));
 *   }
 * }
 *  
 * tmpl = Liquid::Template.parse( ' {% for product in product.top_sales %} {{ product.name }} {%endfor%} '  )
 * tmpl.render('product' => ProductDrop.new ) * will invoke top_sales query. 
 *
 * Your drop can either implement the methods sans any parameters or implement the before_method(name) method which is a 
 * catch all
 * 
 */

class LiquidDrop {
	
	/**
	 * @var LiquidContext
	 */
	var $context;
	
	function before_method($method) {
		return null;
		
	}
	
	function invoke_drop($method) {
		
		$result = $this->before_method($method);
		
		if (is_null($result) && method_exists($this, $method)) {
			$result = $this->$method();
		}
		
		return $result;
	}
	
	function has_key($name) {
		return true;
		
	}
	
	function to_liquid() {
		return $this;
		
	}
	
	
}

?>