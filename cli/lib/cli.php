<?php

class CLI {
	
	/**
	 * @var CLIArgs
	 */
	var $args;
	
	/**
	 * Constructor
	 *
	 * @param CLIArgs
	 * @return CLI
	 */
	function CLI($args) {
		$this->args = $args;
		
		set_error_handler(array('CLI', '_error_handler'));
	}
	

	/**
	 * Runs the command
	 *
	 * @return int
	 */
	function run() {
		$args = $this->parse_args($args);
		
		$this->out('cli test');
		$this->out(print_r($this->args, true));
		
		return 0;
	}
	
	/**
	 * Retrieves a line from the standard input
	 */
	function in() {
		return fgets(STDIN);	
	}
	
	/**
	 * Writes a line to the standard output
	 */
	function out($string) {
		return fwrite(STDOUT, $string);		
	}

	/**
	 * Writes a line to the error output
	 */
	function err($string) {
		return fwrite(STDERR, $string);	
	}
	
	/**
	 * Error handler for CLI
	 *
	 * @param int $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param int $errline
	 */
	function _error_handler($errno, $errstr, $errfile, $errline) {
		CLI::err("$errstr in $errfile on $errline\n");	
	}
}


?>