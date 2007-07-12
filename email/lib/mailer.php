<?php

seed_vendor_include('phpmailer');

class Mailer {
	
	var $bcc;
	var $cc;
	var $charset;
	var $from;
	var $headers;
	var $recipients;
	var $sent_on;
	var $subject;
	
	/** 
	 * @var PHPMailer
	 */
	var $_mailer;
	
	function Mailer() {
		$_mailer = new PHPMailer();	
	}
	
	function create($method) {
		$args = func_get_args();
		$method = array_shift($args);
		
		if (!method_exists($this, $method)) {
			// raise an error?
			return false;	
		}
		
		// call the method to setup the message
		call_user_func_array(array(&$this, $method), $args);
		

		
	}
	
	function deliver($method) {
		$args = func_get_args();
		$method = array_shift($args);
		
		if (!method_exists($this, $method)) {
			// raise an error?
			return false;	
		}
		
		// call the method to setup the message
		call_user_func_array(array(&$this, $method), $args);
		
		
		$this->_mailer->Send();
	}
	
	
}



?>