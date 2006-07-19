<?php

/**
 * timer.php, part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package library
 */

/**
 * Class for timing program execution.
 *
 * @package library
 *
 */
class Timer {
	var $start_time;
	
	function Timer() {
		$this->start();
	
	}
	
	function start() {
		$this->start_time = $this->get_microtime();	
		
	}
	
	function get() {
		return $this->get_microtime() - $this->start_time;	
		
	}
	
	function get_microtime() {
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
	
}

?>