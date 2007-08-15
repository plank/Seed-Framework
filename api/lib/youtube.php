<?php

require_once('api.php');

class YoutubeApi {
	/**
	 * HTTP Object for requesting data
	 *
	 * @var HTTP
	 */
	var $_connection;

	/**
	 * The developer id require for making requests
	 *
	 * @var string
	 */
	var $_dev_key;

	/**
	 * Constructor
	 *
	 * @return YoutubeApi
	 */
	function YoutubeApi($_dev_key) {
		$this->_dev_key = $_dev_key;
		$this->_connection = new HTTP(new SimpleSocket());
	}

	/**
	 * Returns the user with the given name
	 *
	 * @param string $name
	 * @return YoutubeUser
	 */
	function get_user($name) {
		$user = new YoutubeUser($name, $this->_connection, $this->_dev_key);

		if ($user->is_valid()) {
			return $user;
		}

		return false;

	}




}

class YoutubeObject extends ApiObject  {

	function _request_url($method, $params = null, $format = 'xml') {
		$url = sprintf("http://www.youtube.com/api2_rest?method=%s&dev_id=%s", $method, $this->_dev_key);

		foreach ($params as $key => $value) {
			$url .= "&".$key."=".$value;

		}

		return $url;

	}
}

class YoutubeUser extends YoutubeObject {

	function get_videos() {
		$data = $this->_request_data('youtube.videos.list_by_user', array('user'=>$this->name));

		if (!$data) return false;

		foreach ($data->video_list->video as $video) {
			$video = new YoutubeVideo($video, $this->_connection, $this->_dev_key);
			//$album->artist = $this->name;
			$result[] = $video;
		}

		return $result;
	}

}

class YoutubeVideo extends YoutubeObject {

	function _fix_data() {
		// split tags into an array
		$this->tags = explode(' ', $this->tags);

	}
}