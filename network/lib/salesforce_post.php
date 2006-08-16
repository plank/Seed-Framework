<?php

require_once('post.php');

/**
 * The type of post to use, can be one of Fsock, Curl, or LibCurl
 */
define('POST_TYPE', 'Fsock');

/**
 * The url to post to
 */
define('POST_URL', 'http://www.salesforce.com/servlet/servlet.WebToLead');

/**
 * Set to true for test mode, which send additional data to indicate that the post is
 * a test
 */
define('POST_TEST_MODE', true);

/**
 * The email address to send along with the post when in test mode, a message will 
 * be recieved at this address
 */
define('POST_TEST_EMAIL', "mateo@plankdesign.com");

/**
 * Class for making posts to salesforce with redknee register data
 */
class SalesforcePost {

	/**
	 * Posts $_POST data to salesforce
	 *
	 * @static 
	 */
	function post($source_data) {
		
		// get a post object of the right type
		$post = Post::factory(POST_TYPE);
		
		// populate default values
		$data = array(
			'encoding' => 'UTF-8',
			'oid'=>'00D00000000hhjt',			
/*			'00N00000006pSq2'=>'',
			'00N00000004mKq4'=>'',
			'00N00000006pSpi'=>'',
			'salutation'=>'',
			'00N00000006pSqC'=>'',
			'fax'=>'',
			'industry'=>'',
			'URL'=>'',
			'zip'=>'',
			'00N00000006pSpW'=>'',
			'00N00000006pSps'=>'',
			'00N00000006pSq8'=>'',
			'street'=>'',
			'00N00000006pSpe'=>'',
			'employees'=>'',
			'member_status'=>'',
			'00N00000006pSqD'=>'',
			'state'=>'',
			'00N00000006pSpn'=>'',
			'Campaign_ID'=>'',
			'mobile'=>'',
			'00N00000006pSpX'=>'',
			'00N00000006pE5l'=>'',
			'currency'=>'USD',
			'00N00000006pSqH'=>'',
			'rating'=>'',
			'00N00000006pSpY'=>'',
			'company'=>'',
			'submit'=>'',
			'00N00000006pSq3'=>'',
			'city'=>'',
			'00N00000006pSpd'=>'',
			'00N00000006pSpT'=>'',
			'last_name'=>'',
			'lead_source'=>'',
			'00N00000006pSpU'=>'',
			'country'=>'',
			'00N00000006pSpV'=>'',
			'00N00000006pSq7'=>'',
			'description'=>'',
			'00N00000006pSqI'=>''*/
		);
		
		$mapping = array(
			'job_title'=>'title',
			'region'=>'00N00000004mKq4'
		);

		// map the posted info to the data, where neccesary
		foreach($mapping as $source_field => $target_field) {
			if (isset($source_data[$source_field])) {
				$data[$target_field] = utf8_encode(trim(assign($source_data[$source_field])));
				unset($source_data[$source_field]);
			}
		}
		
		// merge the rest
		$data = array_merge($source_data, $data);
		
		// if we're in test mode, add the debug info
		if (POST_TEST_MODE == true) {
			$data['debug'] = 1;
			$data['debugEmail'] = POST_TEST_EMAIL;	
			
		}
		
		// do the post and store the result
		$result = $post->post_data(POST_URL, $data);
		
		// send the result to the test admin email
		if (POST_TEST_MODE == true) {
			if (!$result) {
				$result = 'post_data returned false';
			}
			
			mail(POST_TEST_EMAIL, 'salesforcetest', $result);
		}
		
		return true;
	}
}



?>