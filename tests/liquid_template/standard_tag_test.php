<?php

require_once('test_helper.php');


class LiquidStandardTagTester extends LiquidTestCase {
	
	
	function test_no_transform() {
		
		$this->assert_template_result('this text should come out of the template without change...',
			'this text should come out of the template without change...');
			
 	    $this->assert_template_result('blah','blah');
 	    $this->assert_template_result('<blah>','<blah>');
 	    $this->assert_template_result('|,.:','|,.:');
 	    $this->assert_template_result('','');

 	    $text = "this shouldnt see any transformation either but has multiple lines
 	     	              as you can clearly see here ...";
 	    
 	    $this->assert_template_result($text, $text);
 	    
	}
	
	function test_has_a_block_which_does_nothing() {
	    $this->assert_template_result("the comment block should be removed  .. right?",
 	                           "the comment block should be removed {%comment%} be gone.. {%endcomment%} .. right?");
 	   
 	    $this->assert_template_result('','{%comment%}{%endcomment%}');
 	    $this->assert_template_result('','{%comment%}{% endcomment %}');
	    $this->assert_template_result('','{% comment %}{%endcomment%}');
 	    $this->assert_template_result('','{% comment %}{% endcomment %}');
 	    $this->assert_template_result('','{%comment%}comment{%endcomment%}');
 	    $this->assert_template_result('','{% comment %}comment{% endcomment %}');
 	   
 	    $this->assert_template_result('foobar','foo{%comment%}comment{%endcomment%}bar');
 	    $this->assert_template_result('foobar','foo{% comment %}comment{% endcomment %}bar');
 	    $this->assert_template_result('foobar','foo{%comment%} comment {%endcomment%}bar');
 	    $this->assert_template_result('foobar','foo{% comment %} comment {% endcomment %}bar');
 	   
 	    $this->assert_template_result('foo  bar','foo {%comment%} {%endcomment%} bar');
 	    $this->assert_template_result('foo  bar','foo {%comment%}comment{%endcomment%} bar');
 	    $this->assert_template_result('foo  bar','foo {%comment%} comment {%endcomment%} bar');
 	   
 	    $this->assert_template_result('foobar','foo{%comment%}
 	                                     {%endcomment%}bar');
		
		
	}
	
	function test_for() {
		$this->assert_template_result(' yo  yo  yo  yo ','{%for item in array%} yo {%endfor%}',array('array' =>array(1,2,3,4)));
		$this->assert_template_result('yoyo','{%for item in array%}yo{%endfor%}',array('array' =>array(1,2)));
		$this->assert_template_result(' yo ','{%for item in array%} yo {%endfor%}',array('array' =>array(1)));
		$this->assert_template_result('','{%for item in array%}{%endfor%}',array('array' =>array(1,2)));

		$expected = <<<HERE

  yo

  yo

  yo

HERE;
		$template = <<<HERE
{%for item in array%}
  yo
{%endfor%}
HERE;
		$this->assert_template_result($expected, $template, array('array' => array(1,2,3)));
		
	}
	
	function test_for_with_variable() {
		$this->assert_template_result(' 1  2  3 ', '{%for item in array%} {{item}} {%endfor%}',array('array' => array(1,2,3)));
		$this->assert_template_result('123', '{%for item in array%}{{item}}{%endfor%}',array('array' => array(1,2,3)));
		$this->assert_template_result('123', '{% for item in array %}{{item}}{% endfor %}',array('array' => array(1,2,3)));
		$this->assert_template_result('abcd', '{%for item in array%}{{item}}{%endfor%}',array('array' => array('a','b','c','d')));
		$this->assert_template_result('a b c', '{%for item in array%}{{item}}{%endfor%}',array('array' => array('a',' ','b',' ','c')));
		$this->assert_template_result('abc', '{%for item in array%}{{item}}{%endfor%}',array('array' => array('a','','b','','c')));
	}
	
	function test_for_helpers() {
		$assigns = array('array'=>array(1,2,3));
		
		$this->assert_template_result(' 1/3  2/3  3/3 ', '{%for item in array%} {{forloop.index}}/{{forloop.length}} {%endfor%}',$assigns);
		$this->assert_template_result(' 1  2  3 ', '{%for item in array%} {{forloop.index}} {%endfor%}',$assigns);
		$this->assert_template_result(' 0  1  2 ', '{%for item in array%} {{forloop.index0}} {%endfor%}',$assigns);
		$this->assert_template_result(' 2  1  0 ', '{%for item in array%} {{forloop.rindex0}} {%endfor%}',$assigns);
		$this->assert_template_result(' 3  2  1 ', '{%for item in array%} {{forloop.rindex}} {%endfor%}',$assigns);
		$this->assert_template_result(' 1  0  0 ', '{%for item in array%} {{forloop.first}} {%endfor%}',$assigns);
		$this->assert_template_result(' 0  0  1 ', '{%for item in array%} {{forloop.last}} {%endfor%}',$assigns);

		
	}
	
	function test_limiting() {
	    $assigns = array('array' => array(1,2,3,4,5,6,7,8,9,0));
		$this->assert_template_result('12','{%for i in array limit:2 %}{{ i }}{%endfor%}',$assigns);
		$this->assert_template_result('1234','{%for i in array limit:4 %}{{ i }}{%endfor%}',$assigns);
		$this->assert_template_result('3456','{%for i in array limit:4 offset:2 %}{{ i }}{%endfor%}',$assigns);
		$this->assert_template_result('3456','{%for i in array limit: 4  offset: 2 %}{{ i }}{%endfor%}',$assigns);
		
	}
	
	
}

?>