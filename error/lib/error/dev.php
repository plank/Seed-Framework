<?php



function display_source($source, $errline = 0, $extracted_lines = 0) {
	
	if ($extracted_lines) {
		$number = $errline > $extracted_lines ? $errline - $extracted_lines : 1;
		
		$source = array_splice($source, $number, $extracted_lines * 2 - 1);
	}
	
	print "<ol>\n";
	
	foreach ($source as $line) {
		$number ++;
		
		$line = str_replace("\r", '', $line);
		$line = str_replace("\n", '', $line);
		
		if (!trim($line)) {
			print "<li value='$number'>&nbsp;</li>\n";
			
		} else if ($number == $errline) {
			print "<li value='$number' id='error_line'><pre style='display: inline'>".htmlentities($line)."</pre></li>\n";
			
		} else {
			print "<li value='$number'><pre style='display: inline'>".htmlentities($line)."</pre></li>\n";
			
		}
	}	
	
	print "</ol>\n";	
}

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
	
	print "<p>in <i>$errfile</i> - line <b>$errline</b></p>";

	$source = explode("\n", file_get_contents($errfile));
	
	display_source($source, $errline, 4);
	
	print "<div class='tabber'>";

	
	print "<div class='tabbertab'>";
	print "<h2>Backtrace</h2>";
	print(nl2br(backtrace(3)));
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