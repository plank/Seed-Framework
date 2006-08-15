<?php


class FeedTestCase extends UnitTestCase {

	function read_feed($feed_filename) {
		return file_get_contents(dirname(__FILE__).'/feeds/'.$feed_filename);
		
	}
	
}

?>