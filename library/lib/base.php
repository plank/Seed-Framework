<?php
/**
 * base.php, part of the seed framework
 *
 * A library of procedural functions
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package library
 */

/**
 * Returns the value of var, or the value of default if var isn' set.
 * Useful for assigning array elements that are not cartain to exist
 *
 * @param mixed $var
 * @param mixed $default
 * @return mixed
 */
function assign(& $var, $default = '') {
	
	if (isset($var) && $var) {
		return $var;
	} else {
		return $default;
	}
}


/**
 * Returns the name of the class that called the function
 *
 * @return string
 */
function class_name() {
	$trace = debug_backtrace();

	if (isset($trace[1]['class'])) {
		return $trace[1]['class'];
	} else {
		return false;
	}
}

/**
 * Flatten a multidimensional array into a single array. Does not maintain keys.
 *
 * @param array $array
 * @param bool unique
 * @return array 
 */
function array_flatten($array) {
	
	$return = array();
	
	foreach ($array as $element) {
		if (is_array($element)) {
			$return = array_merge($return, array_flatten($element));	
		} else {
			$return[] = $element;	
		}
	}
	
	return $return;	
	
}

/**
 * Returns all the key value pairs in the first array whose
 * keys appear in the second array
 *
 * @param array $array1
 * @param array $array2
 * @return array
 */
function array_intersect_by_key($array1, $array2) {
	$result = array();
	
	foreach($array1 as $key => $value) {
		if (key_exists($key, $array2)) {
			$result[$key] = $value;
		}
	}
	
	return $result;
}

/**
 * Returns all the key value pairs in the first array whose
 * keys don't appear in the second array
 *
 * @param array $array1
 * @param array $array2
 * @return array
 */
function array_diff_by_key($array1, $array2) {
	$result = array();
	
	if (!is_array($array1)) {
		trigger_error('Parameter 1 for array_diff_by_key is not an array', E_USER_WARNING);
		return false;
	}
	
	foreach($array1 as $key => $value) {
		if (!key_exists($key, $array2)) {
			$result[$key] = $value;
		} 
	}
	
	return $result;
}

/**
 * Returns the current time with microseconds as a float
 *
 * @return float
 */ 
function micro_time() {
	return array_sum(explode(' ', microtime()));
}

/**
 * Evaluates a file and returns the result as a string. Note that variables in the calling function's scope
 * won't be available in the included file.
 *
 * @param string $filename
 * @return string
 */
function include_into_string($filename) {

	if (!file_exists($filename)) {
		trigger_error("Couldn't include '$filename', file not found");	
		return false;
	}
	
	ob_start();

	require($filename);
	
	$result = ob_get_contents();
	
	ob_end_clean();
	
	return $result;
	
}

/**
 * Checks if a given email address is valid as per RFC822
 *
 * @copyright 2005 Cal Henderson <cal@iamcal.com> 
 * @see http://iamcal.com/publish/articles/php/parsing_email/
 */
function is_valid_email_address($email) {

	$qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';

	$dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';

	$atom = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c'.
		'\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';

	$quoted_pair = '\\x5c[\\x00-\\x7f]';

	$domain_literal = "\\x5b($dtext|$quoted_pair)*\\x5d";

	$quoted_string = "\\x22($qtext|$quoted_pair)*\\x22";

	$domain_ref = $atom;

	$sub_domain = "($domain_ref|$domain_literal)";

	$word = "($atom|$quoted_string)";

	$domain = "$sub_domain(\\x2e$sub_domain)*";

	$local_part = "$word(\\x2e$word)*";

	$addr_spec = "$local_part\\x40$domain";

	return preg_match("!^$addr_spec$!", $email) ? true : false;
}

/**
 * Adds a given path to any hrefs containing only anchors 
 *
 * @param string $text
 * @param string $document_url
 * @return string
 */
function absolute_anchors($text, $document_url) {
	$pattern = '/(href\s*=\s*)((")#([^"]*)"|(\')#([^\']*)\')/';
	
	return preg_replace($pattern, '\1\3\5'.$document_url.'#\4\6\3\5', $text);
	
}
?>