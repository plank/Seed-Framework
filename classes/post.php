<?php

/**
 * post.php, part of the seed framework
 *
 * Helper classes for posting data using a variety of methods 
 *
 * @author mateo murphy, based on code from http://sourceforge.net/projects/paypal
 * @copyright mateo murphy
 * @license The MIT License
 * @package library
 */

/**
 * The location of the curl.exec
 */
define('CURL_LOCATION', '/usr/local/bin/curl');

/**
 * Post base class
 *
 * Use the factory method to create objects
 */
class Post {
	
	/**
	 * Builds a post string using an array
	 *
	 * @param array @data
	 * @return string
	 */
	function build_post_string($data) {
		$return = '';
		
		foreach($data as $key=>$value) { 
			$return .= $key."=".urlencode($value)."&"; 
		}
	
		$return = substr($return, 0, strlen($return) - 1);
		
		return $return;
	}

	/**
	 * Factory method to create post objects of the given type
	 *
	 * @param string $type
	 * @return Post
	 */
	function factory($type = 'Fsock') {
		$class_name = $type.'Post';
		
		if (class_exists($class_name)) {
			$object = new $class_name;
			return $object;	
			
		} else {
			trigger_error("postFactory was unable to create class of type $type");	
			return false;
			
		}
	}
}

class CurlPost extends Post {

	/**
	 * Post using curl
	 *
	 * @param string $url The url to post to
	 * @param array $data The data to post
	 * @return string	 
	 */
	function post_data($url, $data)  {
	
		$postdata = Post::build_post_string($data);
	
		//execute curl on the command line
		exec(CURL_LOCATION." -d \"$postdata\" $url", $info);
	
		$info = implode(",",$info); 
	
		return $info; 
	}
}


class LibCurlPost extends Post {
	
	/**
	 * Post using libCurl
	 *
	 * @param string $url The url to post to
	 * @param array $data The data to post
	 * @return string
	 */
	function post_data($url, $data)  {
	
		$postdata = Post::build_post_string($data);
	
		$ch = curl_init(); 
	
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_URL, $url); 
		curl_setopt($ch, CURLOPT_POST, 1); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		
		//Start ob to prevent curl_exec from displaying stuff. 
//		ob_start(); 
		$info = curl_exec($ch);
	
		//Get contents of output buffer 
//		$info = ob_get_contents(); 
		curl_close($ch);
	
		//End ob and erase contents.  
//		ob_end_clean(); 
	
		return $info; 
	}
}

class FsockPost extends Post {
	
	/**
	 * Post using fsock
	 *
	 * @param string $url The url to post to
	 * @param array $data The data to post
	 * @return string
	 */
	function post_data($url, $data) { 
	
		//Parse url 
		$web = parse_url($url); 
	
		$postdata = Post::build_post_string($data);
	
	//	print wordwrap("Postdata = $postdata \n", 79, "\n");
		
		//Set the port number
		if($web['scheme'] == "https") {
			$web['port'] = "443";
			$ssl = "ssl://";
			
		} else {
			$web['port'] = "80";
			$ssl = '';
		}  
		
		$target_url = $ssl.$web['host'];
		
/*		if ($web['query']) {
			// $target_url .= "?".$web['query'];	
		}*/
		
	
	//	print "Posting to ".$target_url." on port ".$web[port]."\n";
		
		//Connect
		$fp = fsockopen($target_url, $web['port'], $errnum, $errstr, 30); 
	
		//Error checking
		if(!$fp) {
			trigger_error("$errnum: $errstr", E_USER_WARNING); 
			return false;
		}

		fputs($fp, "POST {$web['path']} HTTP/1.1\r\n"); 
		fputs($fp, "Host: {$web['host']}\r\n"); 
		fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n"); 
		fputs($fp, "Content-length: ".strlen($postdata)."\r\n"); 
		fputs($fp, "Connection: close\r\n\r\n"); 
		fputs($fp, $postdata . "\r\n\r\n"); 
	
		//loop through the response from the server 
		while(!feof($fp)) {
			$info[] = @fgets($fp, 1024);
		} 
	
		//close fp - we are done with it 
		fclose($fp); 
	
		//merge results
		$info = implode("", $info); 
	
		return $info; 
	} 
} 
?>