<?php

class LastfmApi {

	/**
	 * HTTP Object for requesting data
	 *
	 * @var HTTP
	 */
	var $_connection;

	/**
	 * Constructor
	 *
	 * @return LastfmApi
	 */
	function LastfmApi() {
		$this->_connection = new HTTP(new SimpleSocket());
	}

	/**
	 * Gets an object for the given artist
	 *
	 * @param string $name
	 * @return LastfmArtist
	 */
	function get_artist($name) {
		$artist = new LastfmArtist($name, $this->_connection);

		if ($artist->is_valid()) {
			return $artist;
		}

		return false;

	}

}

/**
 * Base class for lastfm objects
 *
 */
class LastfmObject {

	/**
	 * The type of object
	 *
	 * @var string
	 */
	var $_type;

	/**
	 * HTTP Object for requesting data
	 *
	 * @var HTTP
	 */
	var $_connection;

	/**
	 * The name of an action that can be executed to load additional data
	 *
	 * @var string
	 */
	var $_load_action = '';

	/**
	 * True if additional data has already been loaded
	 *
	 * @var bool
	 */
	var $_loaded = false;

	/**
	 * The name of an action that we can use to check if a given action name exists
	 *
	 * @var string
	 */
	var $_existance_action = '';

	/**
	 * The name of the object
	 *
	 * @var string
	 */
	var $name;

	// Public

	/**
	 * Constructor
	 *
	 * @param unknown_type $data
	 * @return LastfmObject
	 */

	function LastfmObject($data, $connection) {

		$this->_connection = $connection;

		$result = $this->_parse_data($data);

	}

	/**
	 * Checks to see if the object is valid
	 *
	 * @return bool
	 */
	function is_valid() {
		return $this->_request_data($this->_existance_action, 'xml', true);

	}

	/**
	 * Loads additional data for the object
	 *
	 * @return bool
	 */
	function load() {
		if (!$this->_load_action || $this->_loaded) return false;

		$this->_loaded = true;

		$data = $this->_request_data($this->_load_action);

		if (!$data) return false;

		$this->_parse_data($data);

		return true;

	}

	// Private

	/**
	 * Parses a data source and assigns values
	 *
	 * @param mixed $data  Can either be a string, an array, or an SimpleXMLElement. If it's a string, it's assumed to be the name.
	 * @return bool
	 */
	function _parse_data($data) {
		if (is_string($data)) {
			$data = array('name'=>$data);
		}

		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$this->{$key} = $value;
			}
		}

		if (is_a($data, 'SimpleXMLElement')) {
			$this->_parse_xml($data);
		}

		$this->_fix_data();

		if (isset($this->url) && $this->url) $this->_parse_url($this->url);

		return true;
	}

	/**
	 * Massages the given data after parsing.
	 *
	 * @return bool
	 */
	function _fix_data() {
		return true;
	}

	/**
	 * Parses an SimpleXMLElement object.
	 *
	 * @param SimpleXMLElement $xml
	 */
	function _parse_xml($xml) {
		foreach ($xml as $key => $value) {
			if (!count($value)) {
				$this->{$key} = trim((string) $value);
			} else {
				$this->{$key} = (array) $value;
			}
		}
	}

	function _parse_url($url) {
		return true;
	}


	function _request_data($action, $format = 'xml', $check_existance = false) {
		$url = $this->_request_url($action, $format);

		//debug($url);

		$this->_connection->open($url);

		if ($check_existance) {
			$data = $this->_connection->head();
		} else {
			$data = $this->_connection->get();
		}

		$this->_connection->close(); // this is probably slowing everything down..

		if (!$data) return false;

		if ($data->response_code != 200) {
			return false;
		}

		if ($check_existance) return true;

		$data = $data->body;

		if (!$data) return false;

		if ($format == 'xml') {
			try {
				return new SimpleXMLElement($data);
			} catch(Exception $e) {
				die(debug($data));
				//echo 'Caught exception: ',  $e->getMessage(), "\n";
			}
		}

		return $data;

	}

	/**
	 * Gets a URL for a given action on the current object
	 *
	 * @param string $action
	 * @param string $format
	 * @return string
	 */
	function _request_url($action, $format = 'xml') {
		return 'http://ws.audioscrobbler.com/1.0/'.$this->_type.'/'.$this->_encode($this->name).'/'.$action.'.'.$format;

	}

	/**
	 * URL encode a string. Lastfm expects strings with certain charachters (slashes and ampersands) to be double encoded.
	 *
	 * @param string $string
	 * @return string
	 */
	function _encode($string) {
		if (preg_match('/(\/|&)/', $string)) {
			$string = urlencode($string);
		}

		return urlencode($string);
	}

	/**
	 * URL decode a string.
	 *
	 * @todo Add detection for double encoded strings
	 * @param string $string
	 * @return string
	 */
	function _decode($string) {
		$string = urldecode($string);

		if (preg_match('/(%[0-9A-F]{2})/', $string)) {
			$string = urldecode($string);
		}

		return $string;
	}
}

class LastfmArtist extends LastfmObject {

	var $_type = 'artist';

	var $_existance_action = 'similar';


	/**
	 * Retrieves the artist's album by popularity
	 *
	 * @return array
	 */
	function get_top_albums() {
		$data = $this->_request_data('topalbums');

		if (!$data) return false;

		foreach ($data->album as $album) {
			$album = new LastfmAlbum($album, $this->_connection);
			//$album->artist = $this->name;
			$result[] = $album;
		}

		return $result;

	}

	/**
	 * Retrieves the artist's tags by popularity
	 *
	 * @return array
	 */
	function get_top_tags() {
		$data = $this->_request_data('toptags');

		if (!$data) return false;

		foreach($data->tag as $tag) {
			$tag = new LastfmTag($tag, $this->_connection);

			$result[] = $tag;

		}

		return $result;

	}

}

class LastfmAlbum extends LastfmObject {

	var $_type = 'album';

	var $_load_action = 'info';

	function load() {
		if (!parent::load()) return false;

		if (!isset($this->tracks) || !$this->tracks) return false;
//		debug($this->tracks, is_array($this->tracks['track']));

		// convert any tracks into a collection of LastfmTrack objects
		if (is_array($this->tracks['track'])) {
			$tracks = (array) $this->tracks['track'];
		} else {
			$tracks = (array) $this->tracks;
		}

		$result = array();

		foreach($tracks as $track) {
			// Some additional info is available from the attributes, but we can just parse the url
//			$name = (string) $track['title'];
//			$artist = (string) $track->artist['name'];
			$track = new LastfmTrack($track, $this->_connection);
			$result[] = $track;

		}

		$this->tracks = $result;

		return true;

	}

	/**
	 * Returns all the tracks that belong to this artist
	 *
	 * @return array
	 */
	function get_tracks() {
		$this->load();

		return $this->tracks;
	}

	// albums have a different URL format
	function _request_url($action, $format = 'xml') {
		return 'http://ws.audioscrobbler.com/1.0/'.$this->_type.'/'.$this->_encode($this->artist).'/'.$this->_encode($this->name).'/'.$action.'.'.$format;

	}

	/**
	 * Extracts the artist name from the lastfm URL, since this information isn't actually given
	 *
	 * @param unknown_type $url
	 * @return unknown
	 */
	function _parse_url($url) {
		if (!preg_match('/http:\/\/www.last.fm\/music\/(.*?)\/(.*)/', $url, $matches)) return false;

		$this->artist = $this->_decode($matches[1]);

	}

}

class LastfmTrack extends LastfmObject {

	var $_type = 'track';


	/**
	 * Extracts the artist name from the lastfm URL, since this information isn't actually given
	 *
	 * @param unknown_type $url
	 * @return unknown
	 */
	function _parse_url($url) {
		if (!preg_match('/http:\/\/www.last.fm\/music\/(.*?)\/_\/(.*)/', $url, $matches)) return false;

		$this->artist = $this->_decode($matches[1]);
		$this->name = $this->_decode($matches[2]);
	}

}

class LastfmTag extends LastfmObject {

	var $_type = 'tag';

}

?>