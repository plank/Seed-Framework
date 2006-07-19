<?php

function send_registered_email($from_name, $from_email, $to_name, $to_email, $subject, $message) {
	if (!is_valid_email_address($from_email) || !is_valid_email_address($to_email)) {
		return false;	
	}
	
	$headers = "MIME-Version: 1.0\n";
	$headers .= "Content-type: text/html; charset=iso-8859-1\n";
	$headers .= "X-Priority: 1\n";
	$headers .= "X-MSMail-Priority: High\n";
	$headers .= "X-Mailer: php\n";
	$headers .= "From: \"".$from_name."\" <".$from_email.">\n";
	
	return(mail("\"".$to_name."\" <".$to_email.">", $subject, $message, $headers));
}




?>