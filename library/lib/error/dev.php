<?php

/**
 * Error handler for development
 */
function error_handler($errno, $errstr, $errfile, $errline, $errcontext)
{
	// ignore suppressed errors
	if (error_reporting() == 0) {
		return;	
	}
	
	// ignore e_strict errors
	if ($errno == E_STRICT) {
		return;	
	}
	
	// clean all buffers
	ob_end_clean_all();
	print "<html><head><title>Seed - ".error_string($errno)."</title>";
	print "<script type='text/javascript' src='/system/tabber.js'></script>";
	print "<link href='/system/error.css' rel='Stylesheet' type='text/css' /></head>";
	print "<link href='/system/tabber.css' rel='Stylesheet' type='text/css' /></head>";
	print "<body>";
	print "<h1>".ucfirst(error_string($errno)).": $errstr</h1>";
	
	print "<p>$errfile - <a href='#error_line'>line $errline</a></p>";
	
	print "<div class='tabber'>";
	print "<div class='tabbertab'>";
	print "<h2>Backtrace</h2>";
	print(nl2br(backtrace(3)));
	print "</div>";
	
	print "<div class='tabbertab'>";
	print "<h2>Source</h2>";
	
	$source = explode("\n", file_get_contents($errfile));
	
	print "<ol>\n";
	
	foreach ($source as $number => $line) {
		$number ++;
		
		if (!$line) {
			$line = ' ';	
		}
		
		if ($number == $errline) {
			print "<li id='error_line'>".highlight_string($line, true)."</li>\n";	
		} else {
			print "<li>".highlight_string($line, true)."</li>\n";
		}
	}
	
	print "</ol>\n";
	print "</div>";
	
	foreach(array('Get'=>$_GET, 'Post'=>$_POST, 'Cookies'=>$_COOKIE, 'Session'=>$_SESSION, 'Server'=>$_SERVER, 'Env'=>$_ENV, 'Context'=>$errcontext) as $title => $vars) {
		print "<div class='tabbertab'>";
		print "<h2>$title</h2>";
		print "<pre>".print_r($vars, true)."</pre>";
		print "</div>";
	}
	
	print "</div>";
	print "</body></html>";
	//message(error_string($errno), $errstr, "occured in $errfile in line $errline\n");
	
	die();
}


?>