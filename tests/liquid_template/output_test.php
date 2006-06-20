<?php

require_once('test_helper.php');

class Make_FunnyLiquidFilter extends LiquidFilter {
	
	function filter($input) {
		return 'LOL';
		
	}
	
}

class Cite_FunnyLiquidFilter extends LiquidFilter {
	
	function filter($input) {
		return 'LOL: '.$input;
		
	}
	
}

class Add_SmileyLiquidFilter extends LiquidFilter {
	
	function filter($input, $smiley = ":-)") {
		return $input.' '.$smiley;
		
	}
	
}


class Add_TagLiquidFilter extends LiquidFilter {
	function filter($input, $tag = "p", $id = "foo") {
		return "<".$tag." id=\"".$id."\">".$input."</".$tag.">";
	}
}

class ParagraphLiquidFilter extends LiquidFilter {

	function filter($input) {
    	return "<p>".$input."</p>";
	}
}

class Link_toLiquidFilter extends LiquidFilter {
	function filter($name, $url) {
		return "<a href=\"".$url."\">".$name."</a>";
	}

}

class OutputTester extends LiquidTestCase {
	
	function setup() {
		$this->assigns = array(
			'best_cars' => 'bmw',
			'car' => array('bmw' => 'good', 'gm' => 'bad')
		);

	}

	function test_variable() {
		$text = " {{best_cars}} ";
		$expected = " bmw ";
		
		$this->assert_template_result($expected, $text, $this->assigns);
		
	}
	
	function test_variable_trasversing() {
		$text = " {{car.bmw}} {{car.gm}} {{car.bmw}} ";
		
		$expected = " good bad good ";
		$this->assert_template_result($expected, $text, $this->assigns);
	}
	
	function test_variable_piping() {
		$text = " {{ car.gm | make_funny }} ";
		$expectd = " LOL ";
		
		$this->assert_template_result($expectd, $text, $this->assigns);
	}
	
	function test_variable_piping_with_input() {
		$text = " {{ car.gm | cite_funny }} ";
		$expectd = " LOL: bad ";
		
		$this->assert_template_result($expectd, $text, $this->assigns);
	}

	function test_variable_piping_with_args() {
		$text = " {{ car.gm | add_smiley : ':-(' }} ";
		$expected = " bad :-( ";
		
		$this->assert_template_result($expected, $text, $this->assigns);
	}
	
	function text_variable_piping_with_no_args() {
		$text = " {{ car.gm | add_smile }} ";
		$expected = " bad :-( ";
		
		$this->assert_template_result($expected, $text, $this->assigns);
	}
	
	
	function test_multiple_variable_piping_with_args() {
		$text = " {{ car.gm | add_smiley : ':-(' | add_smiley : ':-('}} ";
		$expected = " bad :-( :-( ";

		$this->assert_template_result($expected, $text, $this->assigns);		

	}
		
	function test_variable_piping_with_two_args() {
		$text = " {{ car.gm | add_tag : 'span', 'bar'}} ";
		$expected = " <span id=\"bar\">bad</span> ";
		
		$this->assert_template_result($expected, $text, $this->assigns);				
	}
		
		
	function test_variable_piping_with_variable_args() {
		$text = " {{ car.gm | add_tag : 'span', car.bmw}} ";
		$expected = " <span id=\"good\">bad</span> ";
		
		$this->assert_template_result($expected, $text, $this->assigns);				
	}

	function test_multiple_pipings() {
		$text = " {{ best_cars | cite_funny | paragraph }} ";
		$expected = " <p>LOL: bmw</p> ";
		
		$this->assert_template_result($expected, $text, $this->assigns);				
	}		
		
	function test_link_to() {
		$text = " {{ 'Typo' | link_to: 'http://typo.leetsoft.com' }} ";
		$expected = " <a href=\"http://typo.leetsoft.com\">Typo</a> ";
		
		$this->assert_template_result($expected, $text, $this->assigns);				
	}		
	
}


?>