<?php


/**
 * Helper functions for generating url
 *
 * @package library
 * @deprecated 
 */

/**
 * Creates an escaped base link for the current page
 *
 * @param array An array of key value pairs to add to the query string
 * @param file The file to link to, defaults to the current file
 * @return string
 */
function make_link($added_vars = null, $file = null) {
	return htmlentities(make_uri($added_vars, $file));
}

/**
 * Creates a base link for the current page
 *
 * @param array An array of key value pairs to add to the query string
 * @param file The file to link to, defaults to the current file
 * @return string
 */
function make_uri($added_vars = null, $file = null) {
	if (is_null($file)) {
		$file = APP_ROOT;
	}

	$required_vars = array('lang', 'type', 'section', 'subsection', 'subsubsection');
	$query_parts = array();

	// add the required vars from the request
	foreach($required_vars as $required_var) {
		if (isset($_REQUEST[$required_var]) && $_REQUEST[$required_var]) {
			$query_parts[$required_var] = $required_var."=".$_REQUEST[$required_var];
		}
	}

	if (!is_null($added_vars)) {
		// add the user defined vars
		foreach($added_vars as $added_key => $added_value) {
			if ($added_value) {
				$query_parts[$added_key] = $added_key."=".$added_value;
			} else if(isset($query_parts[$added_key])) {
				unset($query_parts[$added_key]);				
			}
		}
	}

	// add the querystring parts to the
	if (count($query_parts)) {
		$file .= "?".implode('&', $query_parts);
	}

	return $file;

}

/**
 * Redirects the browser to a given page
 *
 * @param array An array of key value pairs to add to the query string
 * @param file The file to link to, defaults to the current file
 * @return string
 */
function redirect($added_vars = null, $file = null) {
	$url = make_uri($added_vars, $file);

	if (headers_sent() || REDIRECT_VIA_LINKS === true) {
		$url = htmlentities($url);
		print "You are being redirected to <a href='$url'>$url</a>";
	} else {
		header('location:'.$url);
	}

	die();
}

?>