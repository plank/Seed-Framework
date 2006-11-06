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
 * Load PHP4 or 5 specific functions
 */
if (version_compare(phpversion(), '5.0') < 0) {
	require_once('base/php4.php');
	
} else {
	require_once('base/php5.php');
	
}

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
 * Works like array combine, but works when the array are of different sizes. If there are more keys than values,
 * values will be set to null; if there are more values, they will be discarded 
 */
function array_combine_resized($keys, $values) {
	foreach($keys as $key) {
		$result[$key] = array_shift($values);
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
 * Checks if a given url is valid
 * This check is not nearly as rigorous as the email one, this should be fixed in the future
 *
 * @param string $url  The url to check
 * @return bool
 */
function is_valid_url($url) {
	$regex = '/(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/';
	
	return preg_match($regex, $url) ? true : false;
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

/**
 * Returns the current date/time as an ISO 8601 formated date
 *
 * @return string
 */
function now() {
	return date('Y-m-d H:i:s');	
	
}

/**
 * Recursively strips slashes from an array
 *
 * @param array $array
 * @param bool $is_top_level
 * @return array
 */
function transcribe($array, $is_top_level = true) {
   $result = array();
   $is_magic = get_magic_quotes_gpc();
  
   foreach ($array as $key => $value) {
       $decoded_key = ($is_magic && !$is_top_level) ? stripslashes($key) : $key;
       
       if (is_array($value)) {
           $decoded_value = transcribe($value, false);
       } else {
           $decoded_value = ($is_magic) ? stripslashes($value) : $value;
       }
       
       $result[$decoded_key] = $decoded_value;
   }
   
   return $result;
}

/**
 * Strips slashes from GET, POST and COOKIES when magic_quote enabled
 *
 * @return bool  True if quotes needed to be removes, false if not
 */
function fix_magic_quotes() {
	global $HTTP_GET_VARS, $HTTP_POST_VARS, $HTTP_COOKIE_VARS;	
	
	// clean gpc of slashes
	if (!get_magic_quotes_gpc()) {
		return false;
	}
	
	$_GET = transcribe($_GET);
	$_POST = transcribe($_POST);
	$_COOKIE = transcribe($_COOKIE);	
	$_REQUEST = transcribe($_REQUEST);

	$HTTP_GET_VARS = $_GET;
	$HTTP_POST_VARS = $_GET;
	$HTTP_COOKIE_VARS = $_COOKIE;

	return true;

}

/**
 * Quote aware explode function
 *
 * @param string $seperator
 * @param string $str
 * @param string $quote_character
 * @param string $escape_character  Quote characters preceded by the escape character are ignored. Default value is a backslash
 * @param bool $unquote			    If set to true, quoted chunks are unquoted, and escaped quotes are unescaped
 * @return array
 */
function explode_quoted($seperator, $str, $quote_character = '"', $escape_character = null, $unquote = true){

	if (is_null($escape_character)) {
		$escape_character = '\\';	
	}
	
	if (!$seperator) {
		return false;	
	}
	
	$seperator = preg_quote($seperator, '/');
	$quote_character = preg_quote($quote_character, '/');
	$escape_character = preg_quote($escape_character, '/');
	
	if ($quote_character == $escape_character) {
		$qc = "(?<!".$quote_character.")".$quote_character."(?:".$quote_character.$quote_character.")*(?!".$quote_character.")";
		$nqc = "(?:[^".$quote_character."]|(?:".$quote_character.$quote_character.")+)";
		
	} else if ($escape_character) {
		$qc = "(?<!".$escape_character.")".$quote_character;
		$nqc = "(?:[^".$quote_character."]|(?<=".$escape_character.")".$quote_character.")";
	
	} else {
		$qc = $quote_character;
		$nqc = "[^".$qc."]";
	}
	
	$expr = "/".$seperator."(?=(?:".$nqc."*".$qc.$nqc."*".$qc.")*(?!".$nqc."*".$qc."))/";
	
	$results = preg_split($expr, trim($str));
	
	// unquote values
	if ($unquote) {
		$results = preg_replace("/^".$quote_character."(.*)".$quote_character."$/","$1", $results);
	
		// unescape quotes
		if ($escape_character) {
			$results = preg_replace("/".$escape_character.$quote_character."/", $quote_character, $results);
		}
	
	} 
	
	return $results;	
	
}

/**
 * Truncate text string function. Tag aware.
 * Compensates for img tag at the beginning of a paragraph.
 * XHTML only! so solo tags need closing />  ie: good: <br />, <img src="foo" /> bad: <br>, <img src="foo">
 * 
 * @param string $text Text to process
 * @param integer $minimum_length Approx length, in characters, you want text to be
 * @param integer $length_offset The variation in how long the text can be. Defaults will make length will be between 200 and 200-20=180 characters and the character where the last tag ends
 * @param bool $cut_words
 * @param mixed $dots Add the final text to the string - can be an image tag, or text, or ... - any string basically. default to FALSE.
 * @return string
 * @author http://ca.php.net/manual/en/function.substr.php#59719
 * @author mitchell amihod - modifications to make it wrapper tag aware.
 * 
 */
function html_substr($text, $minimum_length = 200, $length_offset = 20, $cut_words = FALSE, $dots = FALSE) {
   // Reset tag counter & quote checker
	$tag_counter = 0;
	$quotes_on = FALSE;
   
	$tag_open = "";
	$tag_close = "";
	
	if( substr($text,0,1) == "<" ) {
		//so we have a tag, lets find the closing >
		$close_index = strpos($text, '>' );
		$tag_open = substr($text, 0, $close_index+1);
		$text = substr($text, $close_index+1);
		
		//we have tag_open, so check if its an image tag.
		if( strstr($tag_open, 'img') ) {
			$tag_close = "";
		}
		else {
			$closing_tag_index = strrpos($text, '<');
			$tag_close = substr($text, $closing_tag_index);
			$text = substr($text, 0, -(strlen($tag_close)) );
		}
	}
   
   
   // Check if the text is too long
   if (strlen($text) > $minimum_length) {
       // Reset the tag_counter and pass through (part of) the entire text
       $c = 0;
       for ($i = 0; $i < strlen($text); $i++) {
           // Load the current character and the next one
           // if the string has not arrived at the last character
           $current_char = substr($text,$i,1);
           if ($i < strlen($text) - 1) {
               $next_char = substr($text,$i + 1,1);
           }
           else {
               $next_char = "";
           }
           // First check if quotes are on
           if (!$quotes_on) {
               // Check if it's a tag
               // On a "<" add 3 if it's an opening tag (like <a href...)
               // or add only 1 if it's an ending tag (like </a>)
               if ($current_char == '<') {
                   if ($next_char == '/') {
                       $tag_counter += 1;
                   }
                   else {
                       $tag_counter += 3;
                   }
               }
               // Slash signifies an ending (like </a> or ... />)
               // substract 2
               if ($current_char == '/' && $tag_counter <> 0) $tag_counter -= 2;
               // On a ">" substract 1
               if ($current_char == '>') $tag_counter -= 1;
               // If quotes are encountered, start ignoring the tags
               // (for directory slashes)
               if ($current_char == '"') $quotes_on = TRUE;
           }
           else {
               // IF quotes are encountered again, turn it back off
               if ($current_char == '"') $quotes_on = FALSE;
           }
          
           // Count only the chars outside html tags
           if($tag_counter == 2 || $tag_counter == 0){
               $c++;
           }         
                          
           // Check if the counter has reached the minimum length yet,
           // then wait for the tag_counter to become 0, and chop the string there
           if ($c > $minimum_length - $length_offset && $tag_counter == 0 && ($next_char == ' ' || $cut_words == TRUE)) {
               $text = substr($text,0,$i + 1);             
               if($dots){
                   $text .= $dots;
               }
               return $tag_open.$text.$tag_close;
           }
       }
   } 
   return $tag_open.$text.$tag_close;
}

/**
 * Formats a date
 *
 * @param string $format  The format of the date, same as date() function
 * @param mixed $date	  Can either be a string representation of a date, or a timestamp; null or false values will make the function return false
 * @return string		  The formated date, or false if the date value was null or false
 */
function format_date($format, $date = null) {
	
	if (is_null($date) || !$date || $date == '0000-00-00 00:00:00') {
		return false;	
	}
	
	if (!is_numeric($date)) {
		$date = strtotime($date);	
	}
	
	return date($format, $date);
	
}

/**
 * Erases and turns off all output buffers
 *
 */
function ob_end_clean_all() {
	while(ob_get_level()) {
		if (!ob_end_clean()) {
			return;	
		}	
	}	
	
}


?>