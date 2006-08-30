<?php

class EnvironmentTester extends UnitTestCase {

	/**
	 * @var Environment
	 */
	var $env;
	
	function setup() {
		$server = array(
			'SCRIPT_FILENAME' => '/Library/WebServer/Documents/framework/test.php',
			'SERVER_NAME' => 'test.company.com'
		);
		
		$this->env = new Environment($server, dirname(__FILE__).'/environment/');	
		
	}
	
	function test_with_default_key() {
		$this->assertFalse($this->env->detect());
		
		$this->env->register('dev', '|Development|');
		$this->env->register('test', '|Library|');
		
		$this->assertEqual($this->env->detect(), 'test');
		
	}
	
	function test_with_custom_key() {
		$this->env->register('dev', '|dev|', 'SERVER_NAME');
		$this->env->register('test', '|test|', 'SERVER_NAME');
		
		$this->assertEqual($this->env->detect(), 'test');		
	}
	
	function test_loading() {
		// nothing registered, import should throw an error
		$this->assertFalse($this->env->import());
		$this->assertError();
		
		$this->env->register('dev', '|dev|', 'SERVER_NAME');
		$this->env->register('test', '|test|', 'SERVER_NAME');
		
		$this->assertTrue($this->env->import());
		
		$this->assertTrue(defined('SEED_TEST_ENVIRONMENT_TEST'));
		$this->assertFalse(defined('SEED_TEST_ENVIRONMENT_DEV'));		
	}
}


?>