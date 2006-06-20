<?php


class LiquidTestCase extends UnitTestCase {

	function assert_template_result($expected, $template, $assigns = null, $message = "%s", $debug = false) {
	
		if (is_null($assigns)) {
			$assigns = array();
		}
		
		$result = new LiquidTemplate;
		$result->parse($template);

		if ($debug) {
			debug($result);
		}
		
		$this->assertEqual($expected, $result->render($assigns), $message);
	}

}