<?php


class LiquidTestCase extends UnitTestCase {

	function assert_template_result($expected, $template, $assigns = null, $message = null) {
	
		if (is_null($assigns)) {
			$assigns = array();
		}
		
		$result = LiquidTemplate::parse($template);
		
		$this->assertEqual($expected, $result->render($assigns));
	}

}