<?php

class HTML {

	function text_field($values, $name, $options = null) {
		if (isset($values[$name])) {
			$options['value'] = $values[$name];	
		} 
		
		return "<input type='text' name='$name' ".HTML::attribute_string($options)." />";
		
	}

	function text_area($values, $name, $options = null) {
		if (isset($values[$name])) {
			$value = $values[$name];	
		} else {
			$value = '';	
		}
		
		return "<textarea name='$name' ".HTML::attribute_string($options)." >".htmlentities($value, ENT_QUOTES, 'utf-8')."</textarea>";

	}
	
	function select($values, $name, $choices, $options = null) {
		if (isset($values[$name])) {
			$value = $values[$name];	
		} else {
			$value = null;	
		}
		
		return "<select name='$name' ".HTML::attribute_string($options).">".make_options($choices, $value)."</select>";
		
	}
	
	/**
	 * Returns a string of attributes for the control
	 *
	 * @return string
	 */
	function attribute_string($options = null) {
		if (is_null($options)) {
			return false;
		}
		
		foreach($options as $name => $value) {
			$return[] = $name.'="'.htmlentities($value, ENT_QUOTES, 'utf-8').'"';
		}
		
		return implode(' ', $return);
	}
	
	
	function clean($text) {
	  // Tags which cannot be nested but are typically left unclosed.
	  $nonesting = array('li', 'p');
	  
	  // Single use tags in HTML4
	  $singleuse = array('base', 'meta', 'link', 'hr', 'br', 'param', 'img', 'area', 'input', 'col', 'frame');
	
	  // Properly entify angles
	  $text = preg_replace('!<([^a-zA-Z/])!', '&lt;\1', $text);
	
	  // Splits tags from text
	  $split = preg_split('/<([^>]+?)>/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
	  // Note: PHP ensures the array consists of alternating delimiters and literals
	  // and begins and ends with a literal (inserting $null as required).
	
	  $tag = false; // Odd/even counter. Tag or no tag.
	  $stack = array();
	  $output = '';
	  foreach ($split as $value) {
	    // HTML tag
	    if ($tag) {
	      list($tagname) = explode(' ', strtolower($value), 2);
	      // Closing tag
	      if ($tagname{0} == '/') {
	        $tagname = substr($tagname, 1);
	        if (!in_array($tagname, $singleuse)) {
	          // See if we have other tags lingering first, and close them
	          while (($stack[0] != $tagname) && count($stack)) {
	            $output .= '</'. array_shift($stack) .'>';
	          }
	          // If the tag was not found, just leave it out;
	          if (count($stack)) {
	            $output .= '</'. array_shift($stack) .'>';
	          }
	        }
	      }
	      // Opening tag
	      else {
	        // See if we have an identical tag already open and close it if desired.
	        if (count($stack) && ($stack[0] == $tagname) && in_array($stack[0], $nonesting)) {
	          $output .= '</'. array_shift($stack) .'>';
	        }
	        // Push non-single-use tags onto the stack
	        if (!in_array($tagname, $singleuse)) {
	          array_unshift($stack, $tagname);
	        }
	        // Add trailing slash to single-use tags as per X(HT)ML.
	        else {
	          $value = rtrim($value, ' /') . ' /';
	        }
	        $output .= '<'. $value .'>';
	      }
	    }
	    else {
	      // Passthrough
	      $output .= $value;
	    }
	    $tag = !$tag;
	  }
	  // Close remaining tags
	  while (count($stack) > 0) {
	    $output .= '</'. array_shift($stack) .'>';
	  }
	  return $output;
	}
	
}


?>