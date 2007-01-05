<?php


class CLIArgsTester extends UnitTestCase {

	function test_short_options_parser() {
		$this->assertEqual(CLIArgs::parse_short_options('abc'), array('a'=>0, 'b'=>0, 'c'=>0));
		$this->assertEqual(CLIArgs::parse_short_options('ab:c::'), array('a'=>0, 'b'=>1, 'c'=>2));
		
	}
	
	function test_long_options_parser() {
		$this->assertEqual(CLIArgs::parse_long_options(array('stringa', 'stringb=', 'stringc==')), array('stringa'=>0, 'stringb'=>1, 'stringc'=>2));
		
	}

	
	/**
	 * Copy of the the tests from PEAR::getopt
	 */
	function test_pear_tests() {
		
		$args = new CLIArgs('-abc', 'abc');
		$this->assertIdentical($args->options, array('a'=>true, 'b'=>true, 'c'=>true));
		$this->assertIdentical($args->params, array());
		$this->assertTrue($args->valid);		
		
		$args = new CLIArgs('-abc foo', 'abc');
		$this->assertIdentical($args->options, array('a'=>true, 'b'=>true, 'c'=>true));
		$this->assertIdentical($args->params, array('foo'));
		$this->assertTrue($args->valid);		
		
		$args = new CLIArgs('-abc foo', 'abc:');
		$this->assertIdentical($args->options, array('a'=>true, 'b'=>true, 'c'=>'foo'));
		$this->assertIdentical($args->params, array());		
		$this->assertTrue($args->valid);		
		
		$args = new CLIArgs("-abc foo bar gazonk", "abc");
		$this->assertIdentical($args->options, array('a'=>true, 'b'=>true, 'c'=>true));
		$this->assertIdentical($args->params, array('foo', 'bar', 'gazonk'));		
		$this->assertTrue($args->valid);		
		
		$args = new CLIArgs("-abc foo bar gazonk", "abc:");
		$this->assertIdentical($args->options, array('a'=>true, 'b'=>true, 'c'=>'foo'));
		$this->assertIdentical($args->params, array('bar', 'gazonk'));		
		$this->assertTrue($args->valid);		
		
		$args = new CLIArgs("-a -b -c", "abc");
		$this->assertIdentical($args->options, array('a'=>true, 'b'=>true, 'c'=>true));
		$this->assertIdentical($args->params, array());	
		$this->assertTrue($args->valid);			
		
		$args = new CLIArgs("-a -b -c", "abc:");
		$this->assertFalse($args->valid);		
		
		$args = new CLIArgs("-abc", "ab:c");
		$this->assertIdentical($args->options, array('a'=>true, 'b'=>'c'));
		$this->assertIdentical($args->params, array());			
		$this->assertTrue($args->valid);
		
		$args = new CLIArgs("-abc foo -bar gazonk", "abc");
		$this->assertIdentical($args->options, array('a'=>true, 'b'=>true, 'c'=>true));
		$this->assertIdentical($args->params, array('foo', '-bar', 'gazonk'));			
		$this->assertTrue($args->valid);		
		
	}
	
	
	
}

?>